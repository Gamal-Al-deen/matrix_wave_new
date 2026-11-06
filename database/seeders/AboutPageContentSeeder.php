<?php

namespace Database\Seeders;

use App\Models\AboutPageContent;
use Illuminate\Database\Seeder;

class AboutPageContentSeeder extends Seeder
{
    /**
     * Seed the single About Us row so the public page has a stable record.
     */
    public function run(): void
    {
        AboutPageContent::query()->updateOrCreate(
            ['id' => AboutPageContent::SINGLETON_ID],
            [
                'title' => 'من نحن',
                'body' => '',
            ],
        );
    }
}
