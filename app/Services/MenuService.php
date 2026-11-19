<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Support\ContentCache;
use Illuminate\Support\Facades\Schema;

/**
 * Returns menus as plain arrays (cache-friendly), resolving linked models to URLs once.
 */
class MenuService
{
    /** @return array<int, array{label: string, url: ?string, icon: ?string, description: ?string, mega: bool, new_tab: bool, children: array}> */
    public function tree(string $location): array
    {
        return ContentCache::remember("menu.$location", function () use ($location) {
            if (! Schema::hasTable('menus')) {
                return [];
            }
            $menu = Menu::where('location', $location)->where('is_active', true)->first();
            if (! $menu) {
                return $location === 'mobile' ? $this->tree('main') : [];
            }

            $items = MenuItem::where('menu_id', $menu->id)->where('is_active', true)
                ->orderBy('sort_order')->with('linkable')->get();

            // Hide items that point to unpublished content.
            $items = $items->reject(fn (MenuItem $i) => $i->linkable_type && (! $i->linkable
                || (method_exists($i->linkable, 'isPublished') && ! $i->linkable->isPublished())));

            return $this->build($items->groupBy(fn ($i) => $i->parent_id ?? 0), 0);
        });
    }

    private function build($grouped, int $parentId, int $depth = 0): array
    {
        if ($depth > 5) {
            return [];
        }

        return ($grouped[$parentId] ?? collect())->map(fn (MenuItem $item) => [
            'label' => $item->label,
            'url' => $item->href(),
            'icon' => $item->icon,
            'description' => $item->description,
            'mega' => $item->is_mega,
            'new_tab' => $item->open_new_tab,
            'is_heading' => $item->type === 'heading',
            'children' => $this->build($grouped, $item->id, $depth + 1),
        ])->values()->all();
    }
}
