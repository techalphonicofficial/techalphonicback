<?php

namespace Database\Seeders;

use App\Models\LandingPage;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $page = fn ($slug) => Page::where('slug', $slug)->first();
        $svc = fn ($slug) => Service::where('slug', $slug)->first();

        // ---------- Main menu with Services mega menu ----------
        $main = $this->menu('main', 'Main header menu');
        $this->link($main, null, 'Home', 'custom', null, '/', 0);
        $this->link($main, null, 'About', 'page', $page('about-us'), null, 1);

        $services = $this->link($main, null, 'Services', 'custom', null, null, 2, ['is_mega' => true]);
        foreach (ServiceCategory::orderBy('sort_order')->get() as $ci => $cat) {
            $heading = $this->link($main, $services->id, $cat->name, 'service_category', $cat, null, $ci);
            foreach ($cat->services()->orderBy('sort_order')->get() as $si => $s) {
                $this->link($main, $heading->id, $s->menu_label ?: $s->name, 'service', $s, null, $si, ['icon' => $s->icon, 'description' => Str::limit($s->short_description, 48)]);
            }
        }

        $industries = $this->link($main, null, 'Industries', 'custom', null, '/industries/', 3);
        foreach (LandingPage::serviceIndustry()->published()->with('industry')->get() as $i => $lp) {
            $this->link($main, $industries->id, $lp->industry?->name ?? $lp->title, 'landing_page', $lp, null, $i);
        }
        $this->link($main, $industries->id, 'All industries', 'custom', null, '/industries/', 99);

        $tech = $this->link($main, null, 'Technologies', 'custom', null, null, 4);
        foreach (['laravel-development', 'wordpress-development', 'react-js-development', 'shopify-development', 'app-development'] as $i => $slug) {
            if ($s = $svc($slug)) {
                $this->link($main, $tech->id, str_replace(' Development', '', $s->name), 'service', $s, null, $i);
            }
        }
        $this->link($main, null, 'Portfolio', 'custom', null, '/portfolio/', 5);
        $this->link($main, null, 'Blog', 'custom', null, '/blog/', 6);
        $this->link($main, null, 'Contact', 'page', $page('contact'), null, 7);

        // ---------- Footer columns ----------
        $company = $this->menu('footer_company', 'Footer — Company');
        foreach ([['About us', 'page', $page('about-us')], ['Portfolio', 'custom', '/portfolio/'], ['Case studies', 'custom', '/case-studies/'], ['Packages', 'page', $page('packages')], ['Contact', 'page', $page('contact')]] as $i => [$l, $t, $target]) {
            $this->link($company, null, $l, $t, is_string($target) ? null : $target, is_string($target) ? $target : null, $i);
        }

        $footerSvc = $this->menu('footer_services', 'Footer — Services');
        foreach (['pay-per-click', 'social-media', 'content-marketing', 'branding', 'app-store-seo'] as $i => $slug) {
            $this->link($footerSvc, null, $svc($slug)?->name ?? $slug, 'service', $svc($slug), null, $i);
        }
        $footerDev = $this->menu('footer_development', 'Footer — Development');
        foreach (['website-development', 'custom-web-development', 'laravel-development', 'wordpress-development', 'shopify-development', 'app-development', 'ui-ux'] as $i => $slug) {
            $this->link($footerDev, null, $svc($slug)?->name ?? $slug, 'service', $svc($slug), null, $i);
        }
        $footerSeo = $this->menu('footer_seo', 'Footer — SEO');
        $this->link($footerSeo, null, 'SEO Services', 'service', $svc('search-engine-optimization'), null, 0);
        foreach (['local-seo', 'ecommerce-seo', 'technical-seo', 'ai-seo-services'] as $i => $slug) {
            $this->link($footerSeo, null, $svc($slug)?->name ?? $slug, 'service', $svc($slug), null, $i + 1);
        }
        foreach (LandingPage::serviceLocation()->published()->get() as $i => $lp) {
            $this->link($footerSeo, null, $lp->title, 'landing_page', $lp, null, $i + 10);
        }
        $footerInd = $this->menu('footer_industries', 'Footer — Industries');
        foreach (LandingPage::serviceIndustry()->published()->get() as $i => $lp) {
            $this->link($footerInd, null, $lp->title, 'landing_page', $lp, null, $i);
        }
        $this->link($footerInd, null, 'SEO by industry', 'page', $page('seo-by-industry'), null, 50);
        $resources = $this->menu('footer_resources', 'Footer — Resources');
        $this->link($resources, null, 'Blog', 'custom', null, '/blog/', 0);
        $this->link($resources, null, 'Industries', 'custom', null, '/industries/', 1);
        $this->link($resources, null, 'Search', 'custom', null, '/search/', 2);

        $legal = $this->menu('legal', 'Footer bottom (legal)');
        $this->link($legal, null, 'Privacy Policy', 'page', $page('privacy-policy'), null, 0);
        $this->link($legal, null, 'Terms & Conditions', 'page', $page('terms-condition'), null, 1);
    }

    private function menu(string $location, string $name): Menu
    {
        $menu = Menu::updateOrCreate(['location' => $location], ['name' => $name, 'is_active' => true]);
        $menu->items()->delete();

        return $menu;
    }

    private function link(Menu $menu, ?int $parentId, string $label, string $type, $model, ?string $url, int $order, array $extra = [])
    {
        return $menu->items()->create(array_merge([
            'parent_id' => $parentId,
            'label' => $label,
            'type' => $model ? $type : 'custom',
            'linkable_type' => $model?->getMorphClass(),
            'linkable_id' => $model?->getKey(),
            'url' => $model ? null : $url,
            'is_active' => true,
            'sort_order' => $order,
        ], $extra));
    }
}
