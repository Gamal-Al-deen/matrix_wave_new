<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class SiteSettingSeeder extends Seeder
{
    /**
     * Seed the single site settings row with safe, non-secret defaults.
     */
    public function run(): void
    {
        SiteSetting::query()->updateOrCreate(
            ['id' => SiteSetting::SINGLETON_ID],
            [
                'site_name' => 'Matrix Wave',
                'logo_public_url' => '/images/branding/matrix-wave-logo-2x.jpg',
                'description' => null,
                'currency_code' => 'USD',
                'whatsapp_number' => null,
                'contact_phone' => null,
                'contact_email' => null,
            ],
        );
    }
}
