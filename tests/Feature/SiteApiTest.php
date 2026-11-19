<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\Blog;
use App\Models\HeroSlide;
use App\Models\LandingPage;
use App\Models\Media;
use App\Models\Page;
use App\Models\Service;
use App\Services\SettingsService;
use App\Support\ContentCache;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * The public website is rendered by the Next.js app from the site API, so public pages are tested through it.
 */
class SiteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function resolve(string $path, array $query = []): TestResponse
    {
        return $this->getJson('/api/v1/site/resolve?'.http_build_query(['path' => $path] + $query));
    }

    public function test_homepage_payload_has_dynamic_hero_and_seo(): void
    {
        $res = $this->resolve('/')->assertOk()->assertJsonPath('kind', 'home');

        $this->assertStringContainsString('Grow Your Business With', $res->json('data.hero.heading'));
        $this->assertNotEmpty($res->json('seo.title'));
        $this->assertSame(url('/').'/', $res->json('seo.canonical'));
        $this->assertNotNull(json_decode((string) $res->json('seo.schema_json'), true));
    }

    public function test_layout_exposes_contact_details_and_popup_settings(): void
    {
        $this->getJson('/api/v1/site/layout')->assertOk()
            ->assertJsonPath('settings.phone', '+91 98991 84918')
            ->assertJsonPath('settings.popup_enabled', true)
            ->assertJsonPath('settings.quote_tab_enabled', true);
    }

    public function test_popup_can_be_disabled_from_settings(): void
    {
        app(SettingsService::class)->set('popup_enabled', '0');
        app(SettingsService::class)->set('quote_tab_enabled', '0');
        app(SettingsService::class)->flush();
        ContentCache::flush();

        $this->getJson('/api/v1/site/layout')
            ->assertJsonPath('settings.popup_enabled', false)
            ->assertJsonPath('settings.quote_tab_enabled', false);
    }

    public function test_hero_background_video_is_included_when_set(): void
    {
        $video = Media::create(['disk' => 'public', 'path' => 'uploads/test.mp4', 'filename' => 'test.mp4', 'mime_type' => 'video/mp4', 'size' => 10]);
        HeroSlide::query()->update(['background_video_id' => $video->id]);
        ContentCache::flush();

        $this->assertStringContainsString('test.mp4', (string) $this->resolve('/')->json('data.hero.background_video.url'));
    }

    public function test_service_pages_resolve_from_database(): void
    {
        $service = Service::where('slug', 'laravel-development')->firstOrFail();

        $res = $this->resolve('/laravel-development/')->assertOk()
            ->assertJsonPath('kind', 'service')
            ->assertJsonPath('data.service.heading', $service->heading);

        $types = array_column(json_decode($res->json('seo.schema_json'), true)['@graph'], '@type');
        $this->assertContains('Service', $types);
        $this->assertContains('BreadcrumbList', $types);
    }

    public function test_service_case_studies_only_when_the_service_has_its_own(): void
    {
        $service = Service::where('slug', 'branding')->firstOrFail();
        $this->assertFalse($service->caseStudies()->published()->exists());

        $this->resolve('/branding/')->assertOk()->assertJsonPath('data.case_studies', []);
    }

    public function test_service_category_hub_resolves(): void
    {
        $this->resolve('/digital-marketing/')->assertOk()->assertJsonPath('kind', 'category');
    }

    public function test_programmatic_location_and_industry_pages_resolve(): void
    {
        $res = $this->resolve('/seo-company-in-noida/')->assertOk()->assertJsonPath('kind', 'landing')->assertJsonPath('data.h1', 'SEO Company in Noida');
        $this->assertStringContainsString('Sector 63', json_encode($res->json('data')));

        $this->resolve('/seo-for-pest-control-companies/')->assertOk()->assertJsonPath('data.h1', 'SEO for Pest Control Companies');
    }

    public function test_draft_programmatic_pages_are_not_public(): void
    {
        $draft = LandingPage::where('status', ContentStatus::Draft->value)->firstOrFail();
        $this->resolve('/'.$draft->slug.'/')->assertNotFound()->assertJsonPath('kind', 'not_found');
    }

    public function test_template_variables_are_resolved_and_never_leak(): void
    {
        LandingPage::where('slug', 'seo-company-in-noida')->firstOrFail()->update(['h1' => '{{service_name}} experts in {{location_name}}']);

        $res = $this->resolve('/seo-company-in-noida/')->assertOk()->assertJsonPath('data.h1', 'SEO Services experts in Noida');
        $this->assertStringNotContainsString('{{location_name}}', $res->getContent());
    }

    public function test_blog_index_and_post_resolve_with_article_schema(): void
    {
        $blog = Blog::published()->firstOrFail();

        $this->assertStringContainsString($blog->title, $this->resolve('/blog/')->assertOk()->getContent());

        $res = $this->resolve('/blog/'.$blog->slug.'/')->assertOk()->assertJsonPath('kind', 'blog')->assertJsonPath('data.blog.title', $blog->title);
        $this->assertContains('BlogPosting', array_column(json_decode($res->json('seo.schema_json'), true)['@graph'], '@type'));
    }

    public function test_cms_pages_resolve(): void
    {
        $this->resolve('/about-us/')->assertOk()->assertJsonPath('kind', 'page');
        $this->resolve('/privacy-policy/')->assertOk();
        $this->getJson('/api/v1/site/layout')->assertJsonPath('settings.email', 'info@techalphonic.com');
    }

    public function test_unknown_path_is_not_found(): void
    {
        $this->resolve('/this-page-does-not-exist/')->assertNotFound()->assertJsonPath('kind', 'not_found');
    }

    public function test_search_returns_results(): void
    {
        $this->assertStringContainsString('Laravel Development', $this->resolve('/search/', ['q' => 'laravel'])->assertOk()->getContent());
    }

    public function test_unpublished_page_is_not_found(): void
    {
        Page::where('slug', 'about-us')->update(['status' => ContentStatus::Unpublished->value]);
        ContentCache::flush();

        $this->resolve('/about-us/')->assertNotFound();
    }

    public function test_preview_token_resolves_draft_as_noindex(): void
    {
        $draft = LandingPage::where('status', ContentStatus::Draft->value)->firstOrFail();
        $token = $draft->generatePreviewToken();

        $res = $this->resolve('/preview/landing_page/'.$token.'/')->assertOk()->assertJsonPath('data.is_preview', true);
        $this->assertStringContainsString('noindex', $res->json('seo.robots'));
    }

    public function test_public_urls_on_the_backend_redirect_to_the_frontend(): void
    {
        config(['services.frontend.url' => 'https://www.example.com']);

        $this->get('/')->assertMovedPermanently()->assertRedirect('https://www.example.com/');
        $this->get('/laravel-development/')->assertRedirect('https://www.example.com/laravel-development/');
        $this->get('/blog/some-post/')->assertRedirect('https://www.example.com/blog/some-post/');
        $this->get('/search/?q=seo')->assertRedirect('https://www.example.com/search/?q=seo');
    }

    public function test_admin_preview_link_opens_the_frontend_preview(): void
    {
        config(['services.frontend.url' => 'https://www.example.com']);
        $token = str_repeat('a', 48);

        $this->get('/preview/landing_page/'.$token.'/')->assertRedirect('https://www.example.com/preview/landing_page/'.$token.'/');
    }

    public function test_urls_without_trailing_slash_redirect_permanently(): void
    {
        config(['site.force_trailing_slash' => true]);
        $this->get('/laravel-development')->assertMovedPermanently()->assertRedirect(url('/laravel-development').'/');
    }
}
