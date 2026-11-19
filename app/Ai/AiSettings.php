<?php

namespace App\Ai;

use App\Models\Setting;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * AI configuration, stored in the settings table under group "ai".
 *
 * API keys are encrypted with APP_KEY before they are written, are never rendered back into a form, and are
 * kept out of SettingsService's cache (that service skips this group), so they never reach the frontend API.
 */
class AiSettings
{
    public const GROUP = 'ai';

    public const PROVIDERS = [
        'anthropic' => [
            'label' => 'Anthropic (Claude)',
            'default_model' => 'claude-opus-5-5',
            'models' => [
                'claude-opus-5-5' => 'Claude Opus 5.5 · best writing quality',
                'claude-sonnet-5-5' => 'Claude Sonnet 5.5 · balanced speed and cost',
                'claude-haiku-5-5' => 'Claude Haiku 5.5 · fastest, lowest cost',
            ],
            'keys_url' => 'https://platform.claude.com/settings/keys',
        ],
        'openai' => [
            'label' => 'OpenAI',
            'default_model' => 'gpt-5',
            'models' => [
                'gpt-5' => 'GPT-5',
                'gpt-5-mini' => 'GPT-5 mini',
                'gpt-4.1' => 'GPT-4.1',
            ],
            'keys_url' => 'https://platform.openai.com/api-keys',
        ],
        'gemini' => [
            'label' => 'Google Gemini',
            'default_model' => 'gemini-2.5-pro',
            'models' => [
                'gemini-2.5-pro' => 'Gemini 2.5 Pro',
                'gemini-2.5-flash' => 'Gemini 2.5 Flash',
            ],
            'keys_url' => 'https://aistudio.google.com/apikey',
        ],
    ];

    /** Parts of a programmatic page the AI may write. */
    public const FIELDS = [
        'hero' => 'H1, hero subheading & introduction',
        'content' => 'Main content',
        'sections' => 'Custom sections',
        'benefits' => 'Benefits',
        'process' => 'Process steps',
        'why_choose_us' => 'Why choose us',
        'faqs' => 'FAQs',
        'seo' => 'Meta title, description & focus keyword',
    ];

    public const EFFORTS = ['low' => 'Low · fastest', 'medium' => 'Medium · recommended', 'high' => 'High · most thorough'];

    public const DEFAULTS = [
        'ai_enabled' => '0',
        'ai_provider' => 'anthropic',
        'ai_effort' => 'medium',
        'ai_temperature' => '0.7',
        'ai_max_tokens' => '16000',
        'ai_fields' => '["hero","content","sections","benefits","process","why_choose_us","faqs","seo"]',
        'ai_monthly_budget' => '',
        'ai_price_input' => '',
        'ai_price_output' => '',
        'ai_brand_voice' => 'Confident, clear and helpful. Plain English for business owners, no hype or jargon.',
        'ai_instructions' => '',
    ];

    private ?array $values = null;

    public function all(): array
    {
        if ($this->values === null) {
            $stored = Schema::hasTable('settings')
                ? Setting::where('group', self::GROUP)->pluck('value', 'key')->all()
                : [];
            $this->values = array_merge(self::DEFAULTS, array_filter($stored, fn ($v) => $v !== null && $v !== ''));
        }

        return $this->values;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function enabled(): bool
    {
        return filter_var($this->get('ai_enabled'), FILTER_VALIDATE_BOOLEAN);
    }

    public function provider(): string
    {
        $p = (string) $this->get('ai_provider');

        return isset(self::PROVIDERS[$p]) ? $p : 'anthropic';
    }

    public function model(?string $provider = null): string
    {
        $provider ??= $this->provider();

        return (string) ($this->get("ai_{$provider}_model") ?: self::PROVIDERS[$provider]['default_model']);
    }

    public function apiKey(?string $provider = null): ?string
    {
        $cipher = $this->get('ai_'.($provider ?? $this->provider()).'_key');
        if (! $cipher) {
            return null;
        }
        try {
            return Crypt::decryptString($cipher);
        } catch (DecryptException) {
            return null; // APP_KEY changed since the key was saved: treat as missing.
        }
    }

    public function hasKey(?string $provider = null): bool
    {
        return $this->apiKey($provider) !== null;
    }

    /** "••••Ab12" so admins can tell which key is saved without exposing it. */
    public function maskedKey(string $provider): ?string
    {
        $key = $this->apiKey($provider);

        return $key ? '••••'.substr($key, -4) : null;
    }

    public function ready(): bool
    {
        return $this->enabled() && $this->hasKey();
    }

    /** @return string[] */
    public function fields(): array
    {
        $fields = json_decode((string) $this->get('ai_fields'), true);

        return array_values(array_intersect(array_keys(self::FIELDS), is_array($fields) ? $fields : []));
    }

    public function effort(): string
    {
        return array_key_exists($this->get('ai_effort'), self::EFFORTS) ? $this->get('ai_effort') : 'medium';
    }

    public function temperature(): float
    {
        return max(0, min(1.5, (float) $this->get('ai_temperature')));
    }

    public function maxTokens(): int
    {
        return max(2000, min(64000, (int) $this->get('ai_max_tokens')));
    }

    public function monthlyBudget(): ?float
    {
        $v = $this->get('ai_monthly_budget');

        return is_numeric($v) && (float) $v > 0 ? (float) $v : null;
    }

    /** Estimated cost in USD from the configured per-million-token prices (null when prices are not set). */
    public function cost(int $inputTokens, int $outputTokens): ?float
    {
        $in = $this->get('ai_price_input');
        $out = $this->get('ai_price_output');
        if (! is_numeric($in) && ! is_numeric($out)) {
            return null;
        }

        return round(($inputTokens * (float) $in + $outputTokens * (float) $out) / 1_000_000, 5);
    }

    public function set(string $key, ?string $value): void
    {
        Setting::updateOrCreate(['group' => self::GROUP, 'key' => $key], ['value' => $value]);
        $this->values = null;
    }

    public function setApiKey(string $provider, ?string $plain): void
    {
        $this->set("ai_{$provider}_key", $plain ? Crypt::encryptString($plain) : null);
    }
}
