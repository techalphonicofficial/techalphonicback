<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Blog;
use App\Models\LandingPage;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\Icons;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Menu builder. The editor posts the whole tree as JSON (drag-and-drop order + nesting) in one save.
 */
class MenuController extends Controller
{
    public function index(Request $request)
    {
        $this->guard($request);
        $menus = Menu::withCount('items')->get()->keyBy('location');

        return view('admin.menus.index', ['menus' => $menus, 'locations' => Menu::LOCATIONS]);
    }

    public function edit(Request $request, string $location)
    {
        $this->guard($request);
        abort_unless(array_key_exists($location, Menu::LOCATIONS), 404);
        $menu = Menu::firstOrCreate(['location' => $location], ['name' => Menu::LOCATIONS[$location]]);
        $items = $menu->items()->get();

        return view('admin.menus.edit', [
            'menu' => $menu,
            'tree' => $this->toTree($items->groupBy(fn ($i) => $i->parent_id ?? 0), 0),
            'linkables' => [
                'page' => Page::orderBy('title')->get(['id', 'title'])->map(fn ($p) => ['id' => $p->id, 'label' => $p->title]),
                'service_category' => ServiceCategory::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'label' => $p->name]),
                'service' => Service::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['id' => $p->id, 'label' => $p->name]),
                'landing_page' => LandingPage::orderBy('title')->limit(2000)->get(['id', 'title'])->map(fn ($p) => ['id' => $p->id, 'label' => $p->title]),
                'blog' => Blog::orderByDesc('published_at')->limit(500)->get(['id', 'title'])->map(fn ($p) => ['id' => $p->id, 'label' => $p->title]),
            ],
            'icons' => Icons::names(),
        ]);
    }

    public function update(Request $request, string $location)
    {
        $this->guard($request);
        $menu = Menu::where('location', $location)->firstOrFail();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
            'tree' => ['required', 'json'],
        ]);
        $tree = json_decode($data['tree'], true) ?: [];

        DB::transaction(function () use ($menu, $tree, $data, $request) {
            $menu->update(['name' => $data['name'], 'is_active' => $request->boolean('is_active')]);
            $menu->items()->delete();
            $this->saveTree($menu, $tree, null);
        });
        ActivityLog::record('menu_updated', null, 'Menu "'.$menu->name.'" saved');

        return back()->with('success', 'Menu saved.');
    }

    private function saveTree(Menu $menu, array $nodes, ?int $parentId, int $depth = 0): void
    {
        if ($depth > 5) {
            return;
        }
        foreach (array_values($nodes) as $i => $node) {
            validator($node, [
                'label' => ['required', 'string', 'max:100'],
                'type' => ['required', Rule::in(array_keys(MenuItem::TYPES))],
                'linkable_id' => ['nullable', 'integer'],
                'url' => ['nullable', 'string', 'max:500', 'regex:#^(/|https?://|mailto:|tel:|\#)#'],
                'icon' => ['nullable', 'string', 'max:50'],
                'description' => ['nullable', 'string', 'max:255'],
            ])->validate();

            $morph = MenuItem::TYPES[$node['type']];
            $linkableId = $morph && ! empty($node['linkable_id']) && Relation::getMorphedModel($morph)::whereKey($node['linkable_id'])->exists()
                ? (int) $node['linkable_id'] : null;

            $item = $menu->items()->create([
                'parent_id' => $parentId,
                'label' => $node['label'],
                'type' => $node['type'],
                'linkable_type' => $linkableId ? $morph : null,
                'linkable_id' => $linkableId,
                'url' => $morph ? null : ($node['url'] ?? null),
                'icon' => $node['icon'] ?? null,
                'description' => $node['description'] ?? null,
                'is_mega' => (bool) ($node['is_mega'] ?? false),
                'open_new_tab' => (bool) ($node['open_new_tab'] ?? false),
                'is_active' => (bool) ($node['is_active'] ?? true),
                'sort_order' => $i,
            ]);
            $this->saveTree($menu, $node['children'] ?? [], $item->id, $depth + 1);
        }
    }

    private function toTree($grouped, int $parent): array
    {
        return ($grouped[$parent] ?? collect())->map(fn (MenuItem $i) => [
            'label' => $i->label, 'type' => $i->type, 'linkable_id' => $i->linkable_id, 'url' => $i->url, 'icon' => $i->icon,
            'description' => $i->description, 'is_mega' => $i->is_mega, 'open_new_tab' => $i->open_new_tab, 'is_active' => $i->is_active,
            'children' => $this->toTree($grouped, $i->id),
        ])->values()->all();
    }

    private function guard(Request $request): void
    {
        abort_unless($request->user()->can('menus.manage'), 403);
    }
}
