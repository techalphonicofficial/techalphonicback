<?php

namespace Tests\Feature;

use App\Enums\LandingPageType;
use App\Models\Location;
use App\Models\Service;
use App\Services\LandingPageGenerator;
use App\Support\ContentCache;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Country → city → area: areas are managed in the admin and listed on their city's pages. */
class LocationAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    private function resolve(string $path): TestResponse
    {
        return $this->getJson('/api/v1/site/resolve?'.http_build_query(['path' => $path]));
    }

    private function service(): Service
    {
        return Service::where('slug', 'website-development')->firstOrFail();
    }

    private function city(string $slug = 'noida'): Location
    {
        return Location::where('slug', $slug)->firstOrFail();
    }

    private function area(string $name = 'Sector 62', string $city = 'noida'): Location
    {
        $city = $this->city($city);

        return Location::create([
            'parent_id' => $city->id, 'name' => $name, 'slug' => str($name)->slug().'-'.$city->slug, 'type' => 'area',
            'state' => $city->state, 'country' => $city->country, 'status' => 'published',
        ]);
    }

    private function cityEntry(TestResponse $res, string $slug): array
    {
        return collect($res->json('data.locations'))->firstWhere('slug', $slug);
    }

    private function payload(array $extra): array
    {
        return $extra + ['name' => 'Sector 62', 'slug' => '', 'type' => 'area', 'country' => 'India', 'status' => 'published'];
    }

    public function test_admin_creates_an_area_that_inherits_state_country_and_gets_the_city_in_its_slug(): void
    {
        $la = $this->city('los-angeles');
        $this->actingAs($this->admin())->post(route('admin.locations.store'), $this->payload(['name' => 'Hollywood', 'slug' => 'hollywood', 'parent_id' => $la->id]))
            ->assertSessionHasNoErrors();

        $area = Location::where('slug', 'hollywood-los-angeles')->firstOrFail();
        $this->assertSame(['area', $la->id, 'California', 'United States'], [$area->type, $area->parent_id, $area->state, $area->country]);
    }

    public function test_same_area_name_can_exist_in_two_cities(): void
    {
        $admin = $this->admin();
        foreach (['noida', 'gurgaon'] as $slug) {
            $this->actingAs($admin)->post(route('admin.locations.store'), $this->payload(['parent_id' => $this->city($slug)->id]))->assertSessionHasNoErrors();
        }
        $this->assertSame(2, Location::where('name', 'Sector 62')->count());
    }

    public function test_an_area_needs_a_city_as_parent(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.locations.store'), $this->payload([]))->assertSessionHasErrors('parent_id');
        $this->actingAs($admin)->post(route('admin.locations.store'), $this->payload(['parent_id' => $this->city('india')->id]))->assertSessionHasErrors('parent_id');
    }

    public function test_a_city_with_areas_cannot_change_type_and_editing_an_area_keeps_its_slug(): void
    {
        $admin = $this->admin();
        $area = $this->area();
        $noida = $this->city();

        $this->actingAs($admin)->put(route('admin.locations.update', $noida->id), ['name' => 'Noida', 'slug' => 'noida', 'type' => 'state', 'country' => 'India', 'status' => 'published'])
            ->assertSessionHasErrors('type');
        $this->actingAs($admin)->put(route('admin.locations.update', $noida->id), ['name' => 'Noida', 'slug' => 'noida', 'type' => 'city', 'country' => 'India', 'status' => 'published', 'parent_id' => $noida->parent_id])
            ->assertSessionHasNoErrors();

        $area->update(['slug' => 'sector-62']);
        $this->actingAs($admin)->put(route('admin.locations.update', $area->id), $this->payload(['slug' => 'sector-62', 'parent_id' => $noida->id]))->assertSessionHasNoErrors();
        $this->assertSame('sector-62', $area->fresh()->slug);
    }

    public function test_admin_screens_load_and_show_the_city_of_an_area(): void
    {
        $area = $this->area();
        $admin = $this->actingAs($this->admin());

        $admin->get(route('admin.locations.index', ['type' => 'area', 'city' => $this->city()->id]))->assertOk()->assertSee('Sector 62');
        $admin->get(route('admin.locations.index', ['city' => $this->city('delhi')->id]))->assertOk()->assertDontSee('Sector 62');
        $admin->get(route('admin.locations.create'))->assertOk()->assertSee('Area (inside a city)');
        $admin->get(route('admin.locations.edit', $area->id))->assertOk();
        foreach (['create', 'bulk', 'index'] as $screen) {
            $admin->get(route('admin.landing-pages.'.$screen))->assertOk()->assertSee('Sector 62, Noida');
        }
    }

    public function test_city_screen_adds_many_areas_at_once_and_skips_existing_ones(): void
    {
        $this->area('Sector 62');
        $noida = $this->city();
        $city = ['name' => 'Noida', 'slug' => 'noida', 'type' => 'city', 'state' => $noida->state, 'country' => 'India', 'status' => 'published', 'parent_id' => $noida->parent_id];
        $admin = $this->actingAs($this->admin());

        $admin->put(route('admin.locations.update', $noida->id), $city + ['new_areas_text' => "Sector 18\r\n\r\n sector 62 \nSector 18\nFilm City"])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame(['Sector 62', 'Sector 18', 'Film City'], $noida->areas()->pluck('name')->all());
        $this->assertSame('Uttar Pradesh', Location::where('slug', 'film-city-noida')->firstOrFail()->state);
        $admin->get(route('admin.locations.edit', $noida->id))->assertOk()->assertSee('Film City')->assertSee('new_areas_text');

        $admin->put(route('admin.locations.update', $noida->id), $city + ['new_areas_text' => implode("\n", range(1, 101))])->assertSessionHasErrors('new_areas_text');
    }

    public function test_new_city_can_be_created_together_with_its_areas(): void
    {
        $this->actingAs($this->admin())->post(route('admin.locations.store'), [
            'name' => 'Dubai', 'slug' => '', 'type' => 'city', 'country' => 'UAE', 'status' => 'published', 'new_areas_text' => "Marina\nDeira",
        ])->assertSessionHasNoErrors()->assertRedirect();

        $entry = $this->cityEntry($this->resolve('/website-development/')->assertOk(), 'dubai');
        $this->assertSame('UAE', $entry['country']);
        $this->assertSame(['Marina', 'Deira'], array_column($entry['areas'], 'name'));
    }

    public function test_add_area_link_preselects_type_and_city_and_areas_have_no_areas_tab(): void
    {
        $admin = $this->actingAs($this->admin());
        $admin->get(route('admin.locations.index'))->assertOk()->assertSee('Add city')->assertSee('Add area');
        $admin->get(route('admin.locations.create', ['type' => 'area', 'parent_id' => $this->city()->id]))->assertOk()
            ->assertSee('<option value="'.$this->city()->id.'" selected>Noida</option>', false)->assertDontSee('new_areas_text');
    }

    public function test_service_page_lists_cities_with_their_areas(): void
    {
        $area = $this->area();
        ContentCache::flush();

        $res = $this->resolve('/website-development/')->assertOk()->assertJsonPath('kind', 'service');

        $this->assertNull(collect($res->json('data.locations'))->firstWhere('slug', $area->slug), 'areas are not listed as cities');
        $noida = $this->cityEntry($res, 'noida');
        $this->assertSame(['Sector 62'], array_column($noida['areas'], 'name'));
        $this->assertStringEndsWith('/location/website-development/sector-62-noida/', $noida['areas'][0]['url']);
        $this->assertSame([], $this->cityEntry($res, 'delhi')['areas']);
    }

    public function test_city_then_area_pages_resolve(): void
    {
        $this->area();

        $this->resolve('/location/website-development/noida/')->assertOk()->assertJsonPath('kind', 'landing')
            ->assertJsonPath('data.location', ['name' => 'Noida', 'slug' => 'noida', 'city_slug' => 'noida']);

        $res = $this->resolve('/location/website-development/sector-62-noida/')->assertOk()->assertJsonPath('kind', 'landing')
            ->assertJsonPath('data.location', ['name' => 'Sector 62, Noida', 'slug' => 'sector-62-noida', 'city_slug' => 'noida']);
        $this->assertStringContainsString('Sector 62, Noida', $res->json('data.h1'));
        $this->assertSame(['Noida', 'Sector 62'], array_slice(array_column($res->json('seo.breadcrumbs'), 'name'), -2));
    }

    public function test_service_location_and_industry_pages_still_generate_for_areas(): void
    {
        $area = $this->area();
        $service = $this->service();
        $industry = \App\Models\Industry::firstOrFail();

        $this->actingAs($this->admin())->post(route('admin.landing-pages.bulk.generate'), [
            'page_type' => 'service_location', 'service_id' => $service->id, 'location_ids' => [$area->id],
        ])->assertSessionHas('success');
        $page = $area->landingPages()->firstOrFail();
        $this->assertStringContainsString('Sector 62, Noida', $page->title);
        $this->assertStringEndsWith('sector-62-noida', $page->slug);

        $page->publish();
        $this->assertSame($page->url, $area->urlFor($service));
        $res = $this->resolve('/'.$page->slug.'/')->assertOk()->assertJsonPath('data.location.city_slug', 'noida');
        $this->assertSame($page->url, $this->cityEntry($res, 'noida')['areas'][0]['url']);

        $this->assertNotNull(app(LandingPageGenerator::class)->create(LandingPageType::ServiceLocationIndustry, $service, $area, $industry));
    }

    public function test_areas_of_an_inactive_city_are_not_listed(): void
    {
        $area = $this->area();
        $this->city()->update(['status' => 'draft']);

        $res = $this->resolve('/location/website-development/'.$area->slug.'/')->assertOk()->assertJsonPath('data.location.city_slug', null);
        $this->assertNull(collect($res->json('data.locations'))->firstWhere('slug', 'noida'));
    }
}
