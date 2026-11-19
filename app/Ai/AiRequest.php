<?php

namespace App\Ai;

final class AiRequest
{
    /**
     * @param  array<string, mixed>  $schema  JSON Schema (all properties required, additionalProperties false)
     */
    public function __construct(
        public readonly string $system,
        public readonly string $prompt,
        public readonly array $schema,
        public readonly string $schemaName = 'content',
        public readonly int $maxTokens = 16000,
        public readonly string $effort = 'medium',
        public readonly float $temperature = 0.7,
    ) {}
}
