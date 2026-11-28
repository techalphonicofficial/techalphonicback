<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminUserSeeder::class,
            SettingsSeeder::class,
            ServiceSeeder::class,
            LocationSeeder::class,
            IndustrySeeder::class,
            FaqSeeder::class,
            PageSeeder::class,
            HomeSeeder::class,
            HomeMediaSeeder::class,
            LandingPageSeeder::class,
            TestimonialSeeder::class,
            ShowcaseSeeder::class,
            BlogSeeder::class,
            MenuSeeder::class,
            RedirectSeeder::class,
            OldSiteImageMigrationSeeder::class,
        ]);
    }
}
