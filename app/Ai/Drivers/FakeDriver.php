<?php

namespace App\Ai\Drivers;

use App\Ai\AiException;
use App\Ai\AiRequest;
use App\Ai\AiResponse;
use App\Ai\Contracts\TextGenerator;

/** Test double: returns canned data (or throws) and records requests. Enabled with AiManager::fake(). */
class FakeDriver implements TextGenerator
{
    /** @var AiRequest[] */
    public array $requests = [];

    public function __construct(private array|AiException|null $data = null) {}

    public function generate(AiRequest $request): AiResponse
    {
        $this->requests[] = $request;
        if ($this->data instanceof AiException) {
            throw $this->data;
        }

        return new AiResponse($this->data ?? [], 1200, 3400, 'fake-model');
    }

    public function test(): string
    {
        return 'Fake model';
    }

    public function models(): array
    {
        return ['fake-model' => 'Fake model'];
    }
}
