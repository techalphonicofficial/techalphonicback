<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getMorphClass(),
            'title' => $this->title ?? $this->name,
            'heading' => $this->heading,
            'slug' => $this->slug,
            'url' => $this->url,
            'excerpt' => $this->excerpt ?? $this->introduction ?? $this->short_description,
            'content' => $this->content,
            'blocks' => $this->whenLoaded('visibleBlocks', fn () => $this->visibleBlocks->map->only(['type', 'data'])),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
