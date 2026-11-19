<?php

namespace App\Ai\Drivers;

use Anthropic\Client;
use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\NotFoundException;
use Anthropic\Core\Exceptions\RateLimitException;
use Anthropic\Models\ModelInfo;
use App\Ai\AiException;
use App\Ai\AiRequest;
use App\Ai\AiResponse;
use App\Ai\Contracts\TextGenerator;
use Illuminate\Support\Facades\Cache;
use Throwable;

/** Claude via the official Anthropic PHP SDK, using structured outputs (JSON schema) and prompt caching on the system prompt. */
class AnthropicDriver implements TextGenerator
{
    public function __construct(private string $apiKey, private string $model) {}

    public function generate(AiRequest $request): AiResponse
    {
        $outputConfig = ['format' => ['type' => 'json_schema', 'schema' => $request->schema]];
        if ($this->supportsEffort($request->effort)) {
            $outputConfig['effort'] = $request->effort;
        }

        $message = $this->call(fn () => $this->client()->messages->create(
            model: $this->model,
            maxTokens: $request->maxTokens,
            // Thinking is adaptive by default on current models; depth is steered with effort, so no temperature here.
            system: [['type' => 'text', 'text' => $request->system, 'cacheControl' => ['type' => 'ephemeral']]],
            messages: [['role' => 'user', 'content' => $request->prompt]],
            outputConfig: $outputConfig,
        ));

        if ($message->stopReason === 'refusal') {
            $why = $message->stopDetails?->explanation;
            throw new AiException('Claude declined to write this page'.($why ? ': '.$why : '.'));
        }
        if ($message->stopReason === 'max_tokens') {
            throw new AiException('The response hit the max-token limit before finishing. Raise "Max output tokens" in AI settings.');
        }

        $text = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $text .= $block->text;
            }
        }

        $usage = $message->usage;

        return new AiResponse(
            AiResponse::decode($text),
            $usage->inputTokens + (int) $usage->cacheCreationInputTokens + (int) $usage->cacheReadInputTokens,
            $usage->outputTokens,
            $message->model,
        );
    }

    public function test(): string
    {
        return $this->info()->displayName;
    }

    public function models(): array
    {
        $out = [];
        $page = $this->call(fn () => $this->client()->models->list(limit: 100));
        foreach ($page->getItems() as $m) {
            $out[$m->id] = $m->displayName;
        }

        return $out;
    }

    private function info(): ModelInfo
    {
        return $this->call(fn () => $this->client()->models->retrieve($this->model));
    }

    /** Asks the Models API whether this model accepts the effort level (cached for a day). */
    private function supportsEffort(string $level): bool
    {
        $caps = Cache::remember('ai.anthropic.effort.'.$this->model, 86400, function () {
            try {
                $effort = $this->info()->capabilities?->effort;
                if (! $effort?->supported) {
                    return [];
                }

                return array_values(array_filter(['low', 'medium', 'high', 'xhigh', 'max'], fn ($l) => $effort->{$l}?->supported ?? false));
            } catch (Throwable) {
                return null;
            }
        });

        // Capabilities unavailable: only the Opus and Sonnet lines are known to take effort.
        return $caps === null ? (bool) preg_match('/opus|sonnet/', $this->model) : in_array($level, $caps, true);
    }

    private function client(): Client
    {
        return new Client(apiKey: $this->apiKey, requestOptions: ['timeout' => 600.0, 'maxRetries' => 2]);
    }

    private function call(callable $fn): mixed
    {
        try {
            return $fn();
        } catch (AuthenticationException) {
            throw new AiException('Anthropic rejected the API key. Check it in AI settings.');
        } catch (NotFoundException) {
            throw new AiException("Anthropic doesn't recognise the model \"{$this->model}\".");
        } catch (RateLimitException) {
            throw new AiException('Anthropic rate limit reached. Try again in a minute or lower the batch size.');
        } catch (APIStatusException $e) {
            throw new AiException('Anthropic API error ('.($e->status ?? '?').'): '.$e->getMessage());
        } catch (APIConnectionException $e) {
            throw new AiException('Could not reach the Anthropic API: '.$e->getMessage());
        }
    }
}
