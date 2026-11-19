<?php

namespace App\Models\Concerns;

use App\Enums\ContentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Draft / Published / Scheduled / Unpublished / Archived workflow plus secure preview tokens.
 */
trait HasPublishing
{
    public function initializeHasPublishing(): void
    {
        $this->mergeCasts(['status' => ContentStatus::class, 'published_at' => 'datetime', 'preview_expires_at' => 'datetime']);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('status'), ContentStatus::Published->value)
            ->where(fn (Builder $q) => $q->whereNull($this->qualifyColumn('published_at'))
                ->orWhere($this->qualifyColumn('published_at'), '<=', now()));
    }

    public function isPublished(): bool
    {
        return $this->status === ContentStatus::Published
            && ($this->published_at === null || $this->published_at->lte(now()));
    }

    public function publish(): void
    {
        $this->status = ContentStatus::Published;
        $this->published_at ??= now();
        $this->save();
    }

    public function unpublish(): void
    {
        $this->status = ContentStatus::Unpublished;
        $this->save();
    }

    public function generatePreviewToken(int $hours = 72): string
    {
        $this->preview_token = Str::random(48);
        $this->preview_expires_at = now()->addHours($hours);
        $this->saveQuietly();

        return $this->preview_token;
    }

    public function previewTokenIsValid(string $token): bool
    {
        return $this->preview_token !== null
            && hash_equals($this->preview_token, $token)
            && ($this->preview_expires_at === null || $this->preview_expires_at->isFuture());
    }
}
