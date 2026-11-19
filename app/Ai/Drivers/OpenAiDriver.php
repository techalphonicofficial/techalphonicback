<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\AiRequest;
use App\Ai\AiResponse;
use App\Ai\Contracts\TextGenerator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/** OpenAI Responses API with strict JSON-schema structured output. */
class OpenAiDriver implements TextGenerator
{
    private const BASE = 'https://api.openai.com/v1';

    public function __construct(private string $apiKey, private string $model) {}

    public function generate(AiRequest $request): AiResponse
    {
        $body = [
            'model' => $this->model,
            'instructions' => $request->system,
            'input' => $request->prompt,
            'max_output_tokens' => $request->maxTokens,
            'store' => false,
            'text' => ['format' => [
                'type' => 'json_schema',
                'name' => $request->schemaName,
                'schema' => $request->schema,
                'strict' => true,
            ]],
        ];
        // Reasoning models take an effort level and reject temperature; the others are the reverse.
        if ($this->isReasoningModel()) {
            $body['reasoning'] = ['effort' => $request->effort === 'low' ? 'low' : ($request->effort === 'medium' ? 'medium' : 'high')];
        } else {
            $body['temperature'] = $request->temperature;
        }

        $json = $this->check($this->send(fn (PendingRequest $http) => $http->post(self::BASE.'/responses', $body)))->json();

        if (($json['status'] ?? null) === 'incomplete') {
            $reason = $json['incomplete_details']['reason'] ?? 'unknown';
            throw new AiException($reason === 'max_output_tokens'
                ? 'The response hit the max-token limit before finishing. Raise "Max output tokens" in AI settings.'
                : "OpenAI stopped early ({$reason}).");
        }

        $text = '';
        foreach ($json['output'] ?? [] as $item) {
            foreach (($item['type'] ?? null) === 'message' ? ($item['content'] ?? []) : [] as $part) {
                if (($part['type'] ?? null) === 'refusal') {
                    throw new AiException('OpenAI declined to write this page: '.($part['refusal'] ?? ''));
                }
                if (($part['type'] ?? null) === 'output_text') {
                    $text .= $part['text'];
                }
            }
        }

        return new AiResponse(
            AiResponse::decode($text),
            (int) ($json['usage']['input_tokens'] ?? 0),
            (int) ($json['usage']['output_tokens'] ?? 0),
            (string) ($json['model'] ?? $this->model),
        );
    }

    public function test(): string
    {
        return (string) $this->check($this->send(fn (PendingRequest $http) => $http->get(self::BASE.'/models/'.rawurlencode($this->model))))->json('id');
    }

    public function models(): array
    {
        $ids = collect($this->check($this->send(fn (PendingRequest $http) => $http->get(self::BASE.'/models')))->json('data', []))
            ->pluck('id')
            ->filter(fn ($id) => preg_match('/^(gpt-|o\d|chatgpt-)/', $id) && ! preg_match('/(audio|realtime|transcribe|tts|image|search|embedding)/', $id))
            ->sort()->values();

        return $ids->combine($ids)->all();
    }

    private function isReasoningModel(): bool
    {
        return (bool) preg_match('/^(o\d|gpt-5)/', $this->model);
    }

    private function send(callable $fn): Response
    {
        try {
            return $fn(Http::withToken($this->apiKey)->acceptJson()->timeout(600)->connectTimeout(15)
                ->retry(2, 2000, fn ($e) => $e instanceof ConnectionException || in_array($e->response?->status(), [429, 500, 502, 503], true), throw: false));
        } catch (ConnectionException $e) {
            throw new AiException('Could not reach the OpenAI API: '.$e->getMessage());
        }
    }

    private function check(Response $response): Response
    {
        if ($response->successful()) {
            return $response;
        }
        $message = $response->json('error.message') ?: $response->reason();
        throw new AiException(match ($response->status()) {
            401 => 'OpenAI rejected the API key. Check it in AI settings.',
            404 => "OpenAI doesn't recognise the model \"{$this->model}\".",
            429 => 'OpenAI rate limit or quota reached: '.$message,
            default => 'OpenAI API error ('.$response->status().'): '.$message,
        });
    }
}
