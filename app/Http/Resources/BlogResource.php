<?php

namespace App\Http\Resources;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Blog */
class BlogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => $this->url,
            'excerpt' => $this->excerpt,
            'content' => $this->when($request->routeIs('api.blogs.show'), $this->content),
            'category' => $this->category?->only(['name', 'slug']),
            'author' => $this->author?->name,
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->pluck('name')),
            'image' => $this->featuredImage?->url,
            'reading_time' => $this->reading_time,
            'published_at' => $this->published_at?->toIso8601String(),
        ];
    }
}
