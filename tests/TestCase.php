<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite(); // tests don't depend on compiled front-end assets
    }

    protected function admin(string $role = 'Super Admin'): User
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    protected function adminUrl(string $path = ''): string
    {
        return '/'.config('site.admin_path').($path ? '/'.ltrim($path, '/') : '');
    }
}
