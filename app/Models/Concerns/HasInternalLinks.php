<?php

namespace App\Models\Concerns;

use App\Models\InternalLink;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasInternalLinks
{
    public function internalLinks(): MorphMany
    {
        return $this->morphMany(InternalLink::class, 'source')->orderBy('sort_order');
    }

    /** @param  array<int, array{anchor: string, url?: string}>  $links */
    public function syncInternalLinks(array $links): void
    {
        $this->internalLinks()->delete();
        foreach (array_values($links) as $i => $link) {
            if (empty($link['anchor']) || empty($link['url'])) {
                continue;
            }
            $this->internalLinks()->create([
                'anchor' => $link['anchor'],
                'url' => $link['url'],
                'group' => $link['group'] ?? 'related',
                'sort_order' => $i,
            ]);
        }
    }
}
