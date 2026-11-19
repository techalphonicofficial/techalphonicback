<?php

namespace App\Jobs;

use App\Ai\Pseo\PseoContentWriter;
use App\Models\AiGeneration;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/** Writes AI content for one programmatic page. Provider retries happen inside the drivers, so the job itself runs once. */
class GenerateLandingContent implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 900;

    public function __construct(public int $generationId) {}

    public function handle(PseoContentWriter $writer): void
    {
        $generation = AiGeneration::find($this->generationId);
        if ($generation && $generation->status === 'queued') {
            $writer->run($generation);
        }
    }

    /** Worker-level failure (timeout, crash): make sure the row doesn't stay "running" forever. */
    public function failed(?Throwable $e): void
    {
        $generation = AiGeneration::find($this->generationId);
        if ($generation?->isPending()) {
            app(PseoContentWriter::class)->fail($generation, 'The background job stopped: '.($e?->getMessage() ?: 'unknown error'));
        }
    }
}
