<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function user(string $role): User
    {
        return tap(User::factory()->create(['is_active' => true]))->assignRole($role);
    }

    public function test_super_admin_sees_commands_and_checks(): void
    {
        $this->actingAs($this->user('Super Admin'))->get(route('admin.maintenance.index'))
            ->assertOk()->assertSee('Clear &amp; rebuild caches', false)->assertSee('Storage link')->assertSee('Check frontend setup');
    }

    public function test_other_roles_cannot_open_or_run_maintenance(): void
    {
        $admin = $this->user('Admin');
        $this->actingAs($admin)->get(route('admin.maintenance.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.maintenance.run', 'views'))->assertForbidden();
    }

    public function test_simlink_shortcut_no_longer_runs_commands(): void
    {
        $this->get('/simlink')->assertRedirect(route('admin.maintenance.index'));
        $this->get(route('admin.maintenance.index'))->assertRedirect(route('admin.login'));
    }

    public function test_only_whitelisted_commands_run(): void
    {
        $this->actingAs($this->user('Super Admin'))->post(route('admin.maintenance.run', 'down'))->assertNotFound();
    }

    public function test_running_a_command_shows_output_and_is_logged(): void
    {
        $this->actingAs($this->user('Super Admin'))->from(route('admin.maintenance.index'))
            ->post(route('admin.maintenance.run', 'views'))
            ->assertRedirect(route('admin.maintenance.index'))
            ->assertSessionHas('success')
            ->assertSessionHas('maintenance_output', fn ($out) => str_contains($out, 'php artisan view:clear'));

        $this->assertTrue(ActivityLog::where('action', 'maintenance')->where('description', 'Clear compiled views')->exists());
    }

    public function test_cache_clear_also_refreshes_the_frontend(): void
    {
        config(['services.frontend.url' => 'https://www.example.com', 'services.frontend.revalidate_secret' => 's3cret']);
        Http::fake(['www.example.com/next-api/revalidate' => Http::response(['ok' => true])]);

        $this->actingAs($this->user('Super Admin'))->post(route('admin.maintenance.run', 'cache'))->assertSessionHas('success');

        Http::assertSent(fn ($r) => $r->url() === 'https://www.example.com/next-api/revalidate' && $r['secret'] === 's3cret' && $r['tag'] === 'site');
    }

    public function test_frontend_status_is_reported_and_secret_mismatch_explained(): void
    {
        config(['services.frontend.url' => 'https://www.example.com', 'services.frontend.revalidate_secret' => 's3cret']);
        Http::fake(['www.example.com/next-api/status' => Http::response(['ok' => false], 401)]);

        $this->actingAs($this->user('Super Admin'))->post(route('admin.maintenance.frontend', 'status'))
            ->assertSessionHas('frontend_status', fn ($s) => ! $s['ok'] && str_contains($s['error'], 'does not match'));
    }
}
