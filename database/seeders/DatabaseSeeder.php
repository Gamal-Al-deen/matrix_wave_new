<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed only safe, non-secret defaults.
     *
     * Administrator accounts are never seeded here; use `php artisan app:create-admin`.
     */
    public function run(): void
    {
        $this->call([
            SiteSettingSeeder::class,
            AboutPageContentSeeder::class,
        ]);
    }
}
