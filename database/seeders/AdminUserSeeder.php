<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Creates the initial Super Admin. Credentials come from SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD in .env;
 * if no password is set a random one is generated and printed once to the console.
 * In production prefer `php artisan admin:create` and skip demo seeders.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@techalphonic.com');
        if (User::where('email', $email)->exists()) {
            return;
        }

        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(16);

        $admin = User::create([
            'name' => 'Site Administrator',
            'email' => $email,
            'password' => $password,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
        $admin->assignRole('Super Admin');

        Author::firstOrCreate(['slug' => 'tech-alphonic-team'], [
            'name' => 'Tech Alphonic Team',
            'user_id' => $admin->id,
            'designation' => 'Digital Growth & Engineering',
            'bio' => 'Our in-house team of SEO specialists, performance marketers and engineers shares what works for growing businesses.',
        ]);

        if (! env('SEED_ADMIN_PASSWORD')) {
            $this->command?->warn("Super Admin created: {$email}");
            $this->command?->warn("Generated password (shown once — change it after first login): {$password}");
        }
    }
}
