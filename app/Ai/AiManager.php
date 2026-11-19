<?php

namespace App\Ai;

use App\Ai\Contracts\TextGenerator;
use App\Ai\Drivers\AnthropicDriver;
use App\Ai\Drivers\FakeDriver;
use App\Ai\Drivers\GeminiDriver;
use App\Ai\Drivers\OpenAiDriver;

/**
 * Resolves the configured provider. Settings are re-read on every call, so a long-running queue worker
 * picks up provider/model/key changes without a restart.
 */
class AiManager
{
    private ?FakeDriver $fake = null;

    public function fake(array|AiException|null $data = null): FakeDriver
    {
        return $this->fake = new FakeDriver($data);
    }

    public function settings(): AiSettings
    {
        return new AiSettings;
    }

    /** Overrides let the settings screen test a key/model before it is saved. */
    public function driver(?string $provider = null, ?string $model = null, ?string $apiKey = null): TextGenerator
    {
        if ($this->fake) {
            return $this->fake;
        }

        $settings = $this->settings();
        $provider ??= $settings->provider();
        $apiKey ??= $settings->apiKey($provider);
        $model = $model ?: $settings->model($provider);
        if (! $apiKey) {
            throw new AiException('No API key saved for '.(AiSettings::PROVIDERS[$provider]['label'] ?? $provider).'.');
        }

        return match ($provider) {
            'anthropic' => new AnthropicDriver($apiKey, $model),
            'openai' => new OpenAiDriver($apiKey, $model),
            'gemini' => new GeminiDriver($apiKey, $model),
            default => throw new AiException("Unknown AI provider \"{$provider}\"."),
        };
    }
}
