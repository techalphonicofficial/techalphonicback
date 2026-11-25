<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\MenuItem;
use App\Services\MenuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** The migration that shortens a "Digital Marketing" dropdown with a long flat list, as on the live site. */
class ShortenDigitalMarketingMenuTest extends TestCase
{
    use RefreshDatabase;

    private const LINKS = ['SEO Services', 'Local SEO', 'Ecommerce SEO', 'Technical SEO', 'AI SEO', 'On-Page SEO', 'Off-Page SEO',
        'App Store SEO', 'Content Marketing', 'Social Media Marketing', 'PPC Advertising', 'Branding'];

    private const SHORT = ['SEO Services', 'AI SEO', 'Content Marketing', 'Social Media Marketing', 'PPC Advertising', 'Branding', 'All digital marketing services'];

    private function migration(): object
    {
        return require database_path('migrations/2026_11_25_000002_shorten_digital_marketing_dropdown.php');
    }

    private function menu(): MenuItem
    {
        $menu = Menu::create(['location' => 'main', 'name' => 'Main', 'is_active' => true]);
        $seo = MenuItem::create(['menu_id' => $menu->id, 'label' => 'SEO', 'type' => 'custom', 'url' => '/seo/', 'is_active' => true, 'sort_order' => 0]);
        MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $seo->id, 'label' => 'Local SEO', 'type' => 'custom', 'url' => '/local-seo/', 'is_active' => true]);
        $top = MenuItem::create(['menu_id' => $menu->id, 'label' => 'Digital Marketing', 'type' => 'custom', 'url' => '/digital-marketing/', 'is_mega' => true, 'is_active' => true, 'sort_order' => 1]);
        foreach (self::LINKS as $i => $label) {
            MenuItem::create(['menu_id' => $menu->id, 'parent_id' => $top->id, 'label' => $label, 'type' => 'custom', 'url' => "/x-$i/", 'is_active' => true, 'sort_order' => $i]);
        }

        return $top;
    }

    private function rows(): array
    {
        return DB::table('menu_items')->orderBy('id')->get()->map(fn ($r) => collect((array) $r)->except('updated_at')->all())->all();
    }

    public function test_it_hides_the_detailed_links_without_deleting_them_and_can_be_undone(): void
    {
        $this->menu();
        $before = $this->rows();

        $this->migration()->up();

        $tree = app(MenuService::class)->tree('main');
        $item = collect($tree)->firstWhere('label', 'Digital Marketing');
        $this->assertSame(self::SHORT, array_column($item['children'], 'label'));
        $this->assertSame($item['url'], last($item['children'])['url']);
        $this->assertFalse($item['mega']);
        $this->assertSame(['Local SEO'], array_column($tree[0]['children'], 'label'), 'other menu items are untouched');
        $this->assertCount(count($before) + 1, $this->rows(), 'nothing is deleted');

        $after = $this->rows();
        $this->migration()->up();
        $this->assertEquals($after, $this->rows(), 'running it twice changes nothing');

        $this->migration()->down();
        $this->assertEquals($before, $this->rows());
    }

    public function test_it_leaves_a_hand_edited_menu_alone(): void
    {
        $this->menu()->children()->where('label', 'Local SEO')->update(['is_active' => false]);
        $before = $this->rows();

        $this->migration()->up();
        $this->migration()->down();

        $this->assertEquals($before, $this->rows());
    }
}
