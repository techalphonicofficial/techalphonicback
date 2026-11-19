<?php

namespace Tests\Feature;

use App\Models\Blog;
use App\Models\Faq;
use App\Models\LandingPage;
use App\Models\Redirect;
use App\Models\Service;
use App\Services\SettingsService;
use App\Support\ContentCache;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    /** SEO payload the Next.js frontend renders into <head> for a public path. */
    private function seo(string $path): array
    {
        return $this->getJson('/api/v1/site/resolve?path='.urlencode($path))->assertOk()->json('seo');
    }

    private function schemaTypes(string $path): array
    {
        return array_column(json_decode($this->seo($path)['schema_json'], true)['@graph'], '@type');
    }

    public function test_admin_meta_title_override_changes_the_title(): void
    {
        Service::where('slug', 'laravel-development')->firstOrFail()->syncSeo(['meta_title' => 'Custom Laravel Title For Test']);

        $this->assertSame('Custom Laravel Title For Test', $this->seo('/laravel-development/')['title']);
    }

    public function test_canonical_defaults_to_self_and_can_be_overridden(): void
    {
        $this->assertSame(url('/laravel-development').'/', $this->seo('/laravel-development/')['canonical']);

        Service::where('slug', 'laravel-development')->firstOrFail()->syncSeo(['canonical_url' => '/website-development/']);
        $this->assertSame(url('/website-development'), $this->seo('/laravel-development/')['canonical']);
    }

    public function test_listing_pages_have_trailing_slash_canonicals(): void
    {
        foreach (['portfolio', 'industries', 'case-studies'] as $p) {
            $this->assertSame(url('/'.$p).'/', $this->seo('/'.$p.'/')['canonical']);
        }
    }

    public function test_noindex_is_set_and_page_is_removed_from_sitemap(): void
    {
        $service = Service::where('slug', 'branding')->firstOrFail();
        $service->syncSeo(['robots_index' => false, 'robots_follow' => true]);

        $this->assertSame('noindex, follow', $this->seo('/branding/')['robots']);
        $this->get('/sitemap-services.xml')->assertOk()->assertDontSee('/branding/');
    }

    public function test_faq_schema_only_when_faqs_are_visible(): void
    {
        $service = Service::where('slug', 'laravel-development')->firstOrFail();
        $this->assertContains('FAQPage', $this->schemaTypes('/laravel-development/'));

        $service->syncFaqs([]);
        $this->assertNotContains('FAQPage', $this->schemaTypes('/laravel-development/'));
    }

    public function test_faq_change_updates_page_and_schema(): void
    {
        $faq = Faq::create(['question' => 'Unique test question about Laravel?', 'answer' => 'Unique answer.', 'is_active' => true]);
        Service::where('slug', 'laravel-development')->firstOrFail()->syncFaqs([$faq->id]);

        $res = $this->getJson('/api/v1/site/resolve?path=/laravel-development/')->assertOk();
        $this->assertStringContainsString('Unique test question about Laravel?', json_encode($res->json('data.faqs')));
        $this->assertStringContainsString('Unique test question about Laravel?', $res->json('seo.schema_json'));
    }

    public function test_json_ld_is_valid_and_has_no_duplicate_ids(): void
    {
        $data = json_decode($this->seo('/seo-company-in-noida/')['schema_json'], true);

        $this->assertIsArray($data);
        $ids = array_filter(array_column($data['@graph'], '@id'));
        $this->assertSame(count($ids), count(array_unique($ids)));
        $this->assertContains('BreadcrumbList', array_column($data['@graph'], '@type'));
    }

    public function test_invalid_custom_schema_is_skipped_not_rendered(): void
    {
        Service::where('slug', 'laravel-development')->firstOrFail()->syncSeo(['schema_json' => '{ broken json']);

        $this->assertNotNull(json_decode($this->seo('/laravel-development/')['schema_json'], true));
    }

    public function test_sitemap_index_and_sections(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->assertSee('sitemap-services.xml');
        $this->get('/sitemap-services.xml')->assertOk()->assertSee(url('/laravel-development').'/');
        $this->get('/sitemap-locations.xml')->assertOk()->assertSee('seo-company-in-noida');
    }

    public function test_sitemap_excludes_drafts(): void
    {
        $draft = LandingPage::where('status', 'draft')->firstOrFail();
        $this->get('/sitemap-locations.xml')->assertDontSee($draft->slug);
    }

    public function test_robots_txt_is_dynamic_and_lists_sitemap(): void
    {
        $this->get('/robots.txt')->assertOk()
            ->assertSee('Disallow: /preview/')
            ->assertDontSee('/'.config('site.admin_path'))
            ->assertSee('Sitemap: '.route('sitemap'));

        app(SettingsService::class)->set('discourage_indexing', '1');
        app(SettingsService::class)->flush();
        $this->get('/robots.txt')->assertSee('Disallow: /');
    }

    public function test_admin_redirect_is_followed_with_301(): void
    {
        Redirect::create(['old_url' => '/old-seo-page/', 'new_url' => '/seo-company-in-noida/', 'status_code' => 301]);

        $this->get('/old-seo-page/')->assertMovedPermanently()->assertRedirect(url('/seo-company-in-noida').'/');
        $this->assertSame(1, Redirect::where('old_url', '/old-seo-page/')->value('hits'));
    }

    public function test_multi_segment_legacy_urls_are_redirected(): void
    {
        // Old WordPress URLs like /post/feed/ or /category/x/ match no route; the fallback still runs the redirect map.
        Redirect::create(['old_url' => '/some-old-post/feed/', 'new_url' => '/blog/', 'status_code' => 301]);

        $this->get('/some-old-post/feed/')->assertMovedPermanently()->assertRedirect(url('/blog').'/');
        $this->get('/no/such/page/')->assertNotFound();
    }

    public function test_410_redirect_returns_gone(): void
    {
        Redirect::create(['old_url' => '/removed-page/', 'new_url' => null, 'status_code' => 410]);
        $this->get('/removed-page/')->assertStatus(410);
    }

    public function test_changing_a_slug_creates_a_301_automatically(): void
    {
        $service = Service::where('slug', 'branding')->firstOrFail();
        $service->update(['slug' => 'branding-services']);

        $this->get('/branding/')->assertMovedPermanently()->assertRedirect(url('/branding-services').'/');
        $this->getJson('/api/v1/site/resolve?path=/branding-services/')->assertOk()->assertJsonPath('kind', 'service');
        $this->assertSame(['/branding-services/', 301], array_slice($this->getJson('/api/v1/site/redirects')->json('map./branding/'), 0, 2));
    }

    public function test_absolute_links_to_the_site_are_made_relative(): void
    {
        $blog = Blog::published()->firstOrFail();
        $blog->update(['content' => '<p><a href="'.url('/seo-company-in-noida').'">a</a> <a href="https://www.old-domain.test/laravel-development#x">b</a> <a href="https://other.test/x">c</a></p>']);

        $this->artisan('content:relative-links', ['--host' => ['old-domain.test']])->assertSuccessful();

        $this->assertSame(
            '<p><a href="/seo-company-in-noida/">a</a> <a href="/laravel-development/#x">b</a> <a href="https://other.test/x">c</a></p>',
            $blog->fresh()->content
        );
    }

    public function test_slug_change_chains_are_collapsed(): void
    {
        $service = Service::where('slug', 'branding')->firstOrFail();
        $service->update(['slug' => 'brand-identity']);
        $service->update(['slug' => 'brand-design']);

        $this->assertSame('/brand-design/', Redirect::where('old_url', '/branding/')->value('new_url'));
    }

    public function test_phone_setting_change_updates_header_footer_and_call_button(): void
    {
        // Only the main phone changes — the call button falls back to it.
        app(SettingsService::class)->set('phone', '+91 11111 22222');
        app(SettingsService::class)->flush();
        ContentCache::flush();

        $this->getJson('/api/v1/site/layout')
            ->assertJsonPath('settings.phone', '+91 11111 22222')
            ->assertJsonPath('settings.phone_href', 'tel:+911111122222')
            ->assertJsonPath('settings.call_href', 'tel:+911111122222');
    }

    public function test_whatsapp_number_change_updates_floating_button(): void
    {
        app(SettingsService::class)->set('whatsapp_number', '919000000001');
        app(SettingsService::class)->flush();
        ContentCache::flush();

        $this->assertStringStartsWith('https://wa.me/919000000001', $this->getJson('/api/v1/site/layout')->json('settings.whatsapp_url'));
    }
}
