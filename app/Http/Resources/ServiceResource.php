<?php

namespace App\Http\Resources;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Service */
class ServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'url' => $this->url,
            'category' => $this->whenLoaded('category', fn () => $this->category?->only(['id', 'name', 'slug'])),
            'short_description' => $this->short_description,
            'description' => $this->when($request->routeIs('api.services.show'), $this->description),
            'content' => $this->when($request->routeIs('api.services.show'), $this->content),
            'icon' => $this->icon,
            'image' => $this->featuredImage?->url,
            'benefits' => $this->when($request->routeIs('api.services.show'), $this->benefits),
            'process' => $this->when($request->routeIs('api.services.show'), $this->process),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
