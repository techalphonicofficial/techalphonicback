<?php

namespace App\Models\Concerns;

use App\Models\PageBlock;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasBlocks
{
    public static function bootHasBlocks(): void
    {
        static::forceDeleted(fn ($model) => $model->blocks()->delete());
    }

    public function blocks(): MorphMany
    {
        return $this->morphMany(PageBlock::class, 'blockable')->orderBy('sort_order');
    }

    public function visibleBlocks(): MorphMany
    {
        return $this->blocks()->where('is_visible', true);
    }

    /**
     * Replace all blocks with the submitted list (admin page builder posts the full ordered list).
     *
     * @param  array<int, array{type: string, data?: array, is_visible?: bool}>  $blocks
     */
    public function syncBlocks(array $blocks): void
    {
        $this->blocks()->delete();
        foreach (array_values($blocks) as $i => $block) {
            if (empty($block['type'])) {
                continue;
            }
            $this->blocks()->create([
                'type' => $block['type'],
                'data' => $block['data'] ?? [],
                'is_visible' => (bool) ($block['is_visible'] ?? true),
                'sort_order' => $i,
            ]);
        }
    }
}
