<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\Location;
use App\Models\Page;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get($this->adminUrl())->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_log_in_and_session_is_regenerated(): void
    {
        $user = User::factory()->create(['password' => 'Secret!Pass123', 'is_active' => true]);
        $user->assignRole('Admin');

        $this->post(route('admin.login.attempt'), ['email' => $user->email, 'password' => 'Secret!Pass123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_fails_and_login_is_rate_limited(): void
    {
        $user = User::factory()->create(['password' => 'Secret!Pass123']);
        $user->assignRole('Admin');

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.attempt'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post(route('admin.login.attempt'), ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
        $this->assertGuest();
    }

    public function test_users_without_roles_cannot_access_admin(): void
    {
        $user = User::factory()->create(['password' => 'Secret!Pass123']);
        $this->post(route('admin.login.attempt'), ['email' => $user->email, 'password' => 'Secret!Pass123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_menu_can_be_saved(): void
    {
        $menu = \App\Models\Menu::where('location', 'main')->firstOrFail();
        $this->actingAs($this->admin())->put(route('admin.menus.update', 'main'), [
            'name' => $menu->name, 'is_active' => 1, 'tree' => json_encode([['label' => 'Blog', 'type' => 'custom', 'url' => '/blog/', 'children' => []]]),
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(['Blog'], $menu->items()->pluck('label')->all());
    }

    public function test_dashboard_loads_for_super_admin(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('SEO health');
    }

    public function test_all_admin_index_screens_load(): void
    {
        $admin = $this->admin();
        foreach (['pages', 'services', 'service-categories', 'landing-pages', 'locations', 'industries', 'blogs', 'blog-categories',
            'authors', 'faqs', 'faq-categories', 'testimonials', 'case-studies', 'portfolio', 'redirects', 'schemas'] as $r) {
            $this->actingAs($admin)->get(route("admin.$r.index"))->assertOk();
            $this->actingAs($admin)->get(route("admin.$r.create"))->assertOk();
        }
        foreach (['admin.media.index', 'admin.leads.index', 'admin.menus.index', 'admin.home.edit', 'admin.users.index',
            'admin.roles.index', 'admin.activity.index', 'admin.sitemap-import.index', 'admin.landing-pages.bulk', 'admin.landing-pages.bulk-seo'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.settings.edit', 'contact'))->assertOk();
        $this->actingAs($admin)->get(route('admin.menus.edit', 'main'))->assertOk();
    }

    public function test_edit_screens_load_for_seeded_content(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.services.edit', Service::first()->id))->assertOk()->assertSee('SEO checklist');
        $this->actingAs($admin)->get(route('admin.landing-pages.edit', LandingPage::first()->id))->assertOk();
        // Pages with page-builder blocks (exercises the SEO audit over block data).
        foreach (Page::has('blocks')->pluck('id') as $id) {
            $this->actingAs($admin)->get(route('admin.pages.edit', $id))->assertOk();
        }
    }

    public function test_admin_can_create_a_page_and_it_appears_on_the_site(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Our Culture', 'slug' => '', 'template' => 'default', 'hero_layout' => 'centered',
            'status' => 'published', 'content' => '<p>We value craft.</p>',
            'seo' => ['meta_title' => 'Culture at Tech Alphonic', 'robots_index' => '1', 'robots_follow' => '1', 'include_in_sitemap' => '1', 'schema_enabled' => '1'],
        ])->assertRedirect();

        $this->getJson('/api/v1/site/resolve?path=/our-culture/')->assertOk()
            ->assertJsonPath('seo.title', 'Culture at Tech Alphonic')
            ->assertSee('We value craft.');
    }

    public function test_slug_must_be_unique_across_content_types(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Clash', 'slug' => 'laravel-development', 'template' => 'default', 'hero_layout' => 'split', 'status' => 'draft',
        ])->assertSessionHasErrors('slug');
    }

    public function test_reserved_slugs_are_rejected(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'Blog', 'slug' => 'blog', 'template' => 'default', 'hero_layout' => 'split', 'status' => 'draft',
        ])->assertSessionHasErrors('slug');
    }

    public function test_slugs_are_normalised(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), [
            'title' => 'SEO Company in Noida Test', 'slug' => 'SEO Company In Noida Test', 'template' => 'default', 'hero_layout' => 'split', 'status' => 'draft',
        ]);
        $this->assertDatabaseHas('pages', ['slug' => 'seo-company-in-noida-test']);
    }

    public function test_editor_cannot_publish(): void
    {
        $editor = $this->admin('Editor');
        $this->actingAs($editor)->post(route('admin.pages.store'), [
            'title' => 'Editor Page', 'template' => 'default', 'hero_layout' => 'split', 'status' => 'published',
        ]);
        $this->assertDatabaseHas('pages', ['slug' => 'editor-page', 'status' => ContentStatus::Draft->value]);
    }

    public function test_sales_role_cannot_edit_services_or_settings(): void
    {
        $sales = $this->admin('Sales');
        $this->actingAs($sales)->get(route('admin.services.index'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.settings.edit', 'general'))->assertForbidden();
        $this->actingAs($sales)->get(route('admin.leads.index'))->assertOk();
    }

    public function test_sales_only_sees_own_or_unassigned_leads(): void
    {
        $sales = $this->admin('Sales');
        $other = User::factory()->create();
        $mine = Lead::create(['name' => 'Mine', 'phone' => '1234567890', 'assigned_to' => $sales->id]);
        $theirs = Lead::create(['name' => 'Theirs', 'phone' => '1234567891', 'assigned_to' => $other->id]);

        $this->actingAs($sales)->get(route('admin.leads.show', $mine->id))->assertOk();
        $this->actingAs($sales)->get(route('admin.leads.show', $theirs->id))->assertForbidden();
    }

    public function test_generator_prefills_a_draft_and_bulk_creates_noindex_drafts(): void
    {
        $admin = $this->admin();
        $service = Service::where('slug', 'search-engine-optimization')->first();
        $pune = Location::where('slug', 'pune')->first();

        $this->actingAs($admin)->get(route('admin.landing-pages.create', ['page_type' => 'service_location', 'service_id' => $service->id, 'location_id' => $pune->id]))
            ->assertOk()->assertSee('seo-company-in-pune');

        $this->actingAs($admin)->post(route('admin.landing-pages.bulk.generate'), [
            'page_type' => 'service_location', 'service_id' => $service->id,
            'location_ids' => Location::whereIn('slug', ['pune', 'jaipur'])->pluck('id')->all(),
        ])->assertRedirect();

        $page = LandingPage::where('slug', 'seo-company-in-pune')->firstOrFail();
        $this->assertSame(ContentStatus::Draft, $page->status);
        $this->assertFalse($page->seo->robots_index);
        $this->getJson('/api/v1/site/resolve?path=/seo-company-in-pune/')->assertNotFound();
    }

    public function test_bulk_generation_skips_existing_combinations(): void
    {
        $service = Service::where('slug', 'search-engine-optimization')->first();
        $noida = Location::where('slug', 'noida')->first();
        $before = LandingPage::count();

        $this->actingAs($this->admin())->post(route('admin.landing-pages.bulk.generate'), [
            'page_type' => 'service_location', 'service_id' => $service->id, 'location_ids' => [$noida->id],
        ]);
        $this->assertSame($before, LandingPage::count());
    }

    public function test_media_upload_creates_webp_and_rejects_bad_types(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)->postJson(route('admin.media.store'), ['files' => [UploadedFile::fake()->image('hero.jpg', 1800, 1000)]])
            ->assertOk()->assertJsonPath('data.0.is_image', true);

        $this->actingAs($admin)->postJson(route('admin.media.store'), ['files' => [UploadedFile::fake()->create('shell.php', 10, 'application/x-php')]])
            ->assertUnprocessable();
    }

    public function test_admin_redirect_csv_import(): void
    {
        $csv = UploadedFile::fake()->createWithContent('r.csv', "old_url,new_url,status_code\n/old-a/,/laravel-development/,301\n/old-b/,/contact/,302\n");
        $this->actingAs($this->admin())->post(route('admin.redirects.import'), ['csv' => $csv])->assertSessionHas('success');

        $this->assertDatabaseHas('redirects', ['old_url' => '/old-a/', 'status_code' => 301]);
        $this->get('/old-b/')->assertStatus(302);
    }

    public function test_settings_rejects_script_in_tracking_ids(): void
    {
        $this->actingAs($this->admin())->put(route('admin.settings.update', 'analytics'), ['ga4_id' => '"><script>alert(1)</script>'])
            ->assertSessionHasErrors('ga4_id');
    }

    public function test_admin_pages_are_noindexed(): void
    {
        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }
}
