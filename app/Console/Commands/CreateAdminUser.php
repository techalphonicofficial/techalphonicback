<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

/**
 * Production-safe way to create the first Super Admin without seeding demo data:
 *   php artisan admin:create
 */
class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {--name=} {--email=}';

    protected $description = 'Create a Super Admin user interactively';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->secret('Password (min 10 chars, mixed case, number, symbol)');

        $v = Validator::make(compact('name', 'email', 'password'), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
        ]);
        if ($v->fails()) {
            foreach ($v->errors()->all() as $e) {
                $this->error($e);
            }

            return self::FAILURE;
        }

        $user = User::create(['name' => $name, 'email' => strtolower($email), 'password' => $password, 'is_active' => true, 'email_verified_at' => now()]);
        $user->assignRole('Super Admin');
        $this->info("Super Admin {$user->email} created. Sign in at /".config('site.admin_path').'/login');

        return self::SUCCESS;
    }
}
