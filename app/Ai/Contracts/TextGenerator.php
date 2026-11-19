<?php

namespace App\Ai\Contracts;

use App\Ai\AiRequest;
use App\Ai\AiResponse;

/** A provider that returns JSON matching a schema. Drivers throw App\Ai\AiException with an admin-readable message. */
interface TextGenerator
{
    public function generate(AiRequest $request): AiResponse;

    /** Checks the key and model with a free metadata call; returns the model's display name. */
    public function test(): string;

    /** @return array<string, string> model id => label, as reported by the provider */
    public function models(): array;
}
