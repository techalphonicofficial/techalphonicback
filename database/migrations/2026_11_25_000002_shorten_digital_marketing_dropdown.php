<?php

use App\Support\ContentCache;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Header menu that already has "Digital Marketing" with a long flat list: hide the detailed SEO links and add a link to
 * the category page, which lists every service. Nothing is deleted: the hidden links stay in Admin → Menus as inactive
 * items. Does nothing unless the menu has exactly this shape, so hand-made changes are left alone.
 */
return new class extends Migration
{
    private const HIDE = ['Local SEO', 'Ecommerce SEO', 'Technical SEO', 'On-Page SEO', 'Off-Page SEO', 'App Store SEO'];

    private const ALL_LABEL = 'All digital marketing services';

    public function up(): void
    {
        $this->eachMenu(isMega: true, linksActive: true, change: function (object $top, array $links) {
            DB::table('menu_items')->whereIn('id', $links)->update(['is_active' => false, 'updated_at' => now()]);
            DB::table('menu_items')->insert([
                'menu_id' => $top->menu_id, 'parent_id' => $top->id, 'label' => self::ALL_LABEL, 'type' => $top->type,
                'linkable_type' => $top->linkable_type, 'linkable_id' => $top->linkable_id, 'url' => $top->url,
                'is_active' => true, 'sort_order' => 50, 'created_at' => now(), 'updated_at' => now(),
            ]);
            // A short list is a plain dropdown, not the full-width panel.
            DB::table('menu_items')->where('id', $top->id)->update(['is_mega' => false, 'updated_at' => now()]);
        });
    }

    public function down(): void
    {
        $this->eachMenu(isMega: false, linksActive: false, change: function (object $top, array $links) {
            DB::table('menu_items')->whereIn('id', $links)->update(['is_active' => true, 'updated_at' => now()]);
            DB::table('menu_items')->where('parent_id', $top->id)->where('label', self::ALL_LABEL)->delete();
            DB::table('menu_items')->where('id', $top->id)->update(['is_mega' => true, 'updated_at' => now()]);
        });
    }

    /** Runs $change for every header menu whose "Digital Marketing" item has all the links above in the given state. */
    private function eachMenu(bool $isMega, bool $linksActive, Closure $change): void
    {
        foreach (DB::table('menus')->whereIn('location', ['main', 'mobile'])->pluck('id') as $menuId) {
            $top = DB::table('menu_items')->where('menu_id', $menuId)->whereNull('parent_id')
                ->where('label', 'Digital Marketing')->where('is_mega', $isMega)->first();
            if (! $top) {
                continue;
            }

            $children = DB::table('menu_items')->where('parent_id', $top->id);
            $links = (clone $children)->whereIn('label', self::HIDE)->where('is_active', $linksActive)->pluck('label', 'id');
            $hasAllLink = (clone $children)->where('label', self::ALL_LABEL)->exists();
            if ($links->unique()->count() !== count(self::HIDE) || $hasAllLink === $linksActive) {
                continue;
            }

            DB::transaction(fn () => $change($top, $links->keys()->all()));
            ContentCache::flush();
        }
    }
};
