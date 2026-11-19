<?php

namespace Database\Seeders;

use App\Models\Redirect;
use Illuminate\Database\Seeder;

/**
 * Legacy URLs from the old site whose content now lives elsewhere. Import the full XML sitemap via
 * Admin → Sitemap Import to map the rest.
 */
class RedirectSeeder extends Seeder
{
    public function run(): void
    {
        $map = [
            '/our-work/' => '/portfolio/',
        ];

        foreach ($map as $old => $new) {
            Redirect::updateOrCreate(['old_url' => Redirect::normalizePath($old)], ['new_url' => $new, 'status_code' => 301, 'is_active' => true, 'source' => 'import']);
        }
    }
}
