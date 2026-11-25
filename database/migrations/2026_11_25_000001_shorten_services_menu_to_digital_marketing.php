<?php

use App\Support\ContentCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Header menu: "Services" becomes "Digital Marketing" with a short list of services. Nothing is deleted: the old
 * columns stay in Admin → Menus as inactive items. Does nothing when the menu has already been changed by hand.
 */
return new class extends Migration
{
    private const SERVICES = ['search-engine-optimization', 'ai-seo-services', 'social-media', 'pay-per-click', 'content-marketing', 'branding'];

    private const ALL_LABEL = 'All digital marketing services';

    public function up(): void
    {
        $category = DB::table('service_categories')->where('slug', 'digital-marketing')->value('id');
        $services = DB::table('services')->whereIn('slug', self::SERVICES)->pluck('slug', 'id');
        if (! $category || $services->isEmpty()) {
            return;
        }

        foreach (DB::table('menus')->whereIn('location', ['main', 'mobile'])->pluck('id') as $menuId) {
            $items = fn () => DB::table('menu_items')->where('menu_id', $menuId);
            $top = $items()->whereNull('parent_id')->where('label', 'Services')->first();
            $column = $top ? $items()->where('parent_id', $top->id)->where('linkable_type', 'service_category')->where('linkable_id', $category)->first() : null;
            $links = $column ? $items()->where('parent_id', $column->id)->where('linkable_type', 'service')->whereIn('linkable_id', $services->keys())->get() : collect();
            if ($links->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($items, $menuId, $top, $category, $services, $links) {
                $items()->where('parent_id', $top->id)->update(['is_active' => false, 'updated_at' => now()]);
                foreach ($links as $link) {
                    $items()->where('id', $link->id)->update([
                        'parent_id' => $top->id,
                        'sort_order' => array_search($services[$link->linkable_id], self::SERVICES, true),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('menu_items')->insert([
                    'menu_id' => $menuId, 'parent_id' => $top->id, 'label' => self::ALL_LABEL, 'type' => 'service_category',
                    'linkable_type' => 'service_category', 'linkable_id' => $category, 'is_active' => true,
                    'sort_order' => 50, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $items()->where('id', $top->id)->update([
                    'label' => 'Digital Marketing', 'type' => 'service_category', 'linkable_type' => 'service_category',
                    'linkable_id' => $category, 'url' => null, 'is_mega' => false, 'updated_at' => now(),
                ]);
            });
            ContentCache::flush();
        }
    }

    public function down(): void
    {
        $category = DB::table('service_categories')->where('slug', 'digital-marketing')->value('id');

        foreach (DB::table('menus')->whereIn('location', ['main', 'mobile'])->pluck('id') as $menuId) {
            $items = fn () => DB::table('menu_items')->where('menu_id', $menuId);
            $top = $items()->whereNull('parent_id')->where('label', 'Digital Marketing')->first();
            $column = $top ? $items()->where('parent_id', $top->id)->where('is_active', false)->where('linkable_type', 'service_category')->where('linkable_id', $category)->first() : null;
            if (! $column) {
                continue;
            }

            DB::transaction(function () use ($items, $top, $column) {
                $items()->where('parent_id', $top->id)->where('label', self::ALL_LABEL)->delete();
                $items()->where('parent_id', $top->id)->where('linkable_type', 'service')->update(['parent_id' => $column->id]);
                $items()->where('parent_id', $top->id)->update(['is_active' => true]);
                $items()->where('id', $top->id)->update(['label' => 'Services', 'type' => 'custom', 'linkable_type' => null, 'linkable_id' => null, 'is_mega' => true]);
            });
            ContentCache::flush();
        }
    }
};
