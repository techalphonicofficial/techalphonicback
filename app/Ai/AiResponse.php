<?php

namespace App\Ai;

final class AiResponse
{
    /** @param  array<string, mixed>  $data  decoded JSON output */
    public function __construct(
        public readonly array $data,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly string $model,
    ) {}

    /** Decodes a model's JSON text, tolerating a ```json fence. */
    public static function decode(string $text): array
    {
        $text = trim($text);
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
        }
        $data = json_decode($text, true);
        if (! is_array($data)) {
            throw new AiException('The AI response was not valid JSON.');
        }

        return $data;
    }
}
