<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** Audit + usage record for one AI content request (see App\Ai\Pseo\PseoContentWriter). */
class AiGeneration extends Model
{
    public const STATUSES = ['queued' => 'Queued', 'running' => 'Writing', 'succeeded' => 'Applied', 'failed' => 'Failed'];

    protected $fillable = [
        'subject_type', 'subject_id', 'user_id', 'batch_id', 'provider', 'model', 'prompt_version', 'status', 'fields',
        'input_tokens', 'output_tokens', 'cost_usd', 'similarity', 'output', 'error', 'duration_ms', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'output' => 'array',
        'cost_usd' => 'float',
        'similarity' => 'float',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(Builder $q): Builder
    {
        return $q->whereIn('status', ['queued', 'running']);
    }

    public function scopeThisMonth(Builder $q): Builder
    {
        return $q->where('created_at', '>=', now()->startOfMonth());
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['queued', 'running'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    /** Badge classes shared by the admin views. */
    public function statusClasses(): string
    {
        return match ($this->status) {
            'succeeded' => 'bg-emerald-50 text-emerald-700',
            'failed' => 'bg-red-50 text-red-700',
            'running' => 'bg-brand-50 text-brand-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    /** 1234567 → "1.2M" (no intl extension needed). */
    public static function compact(int $n): string
    {
        foreach (['B' => 1e9, 'M' => 1e6, 'K' => 1e3] as $suffix => $size) {
            if ($n >= $size) {
                return rtrim(rtrim(number_format($n / $size, 1), '0'), '.').$suffix;
            }
        }

        return (string) $n;
    }

    public function totalTokens(): int
    {
        return $this->input_tokens + $this->output_tokens;
    }
}
