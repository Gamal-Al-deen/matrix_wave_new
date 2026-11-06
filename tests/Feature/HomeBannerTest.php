<?php

namespace Tests\Feature;

use App\Models\Banner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeBannerTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_only_active_banners_inside_their_schedule_in_sort_order(): void
    {
        $this->makeBanner([
            'title' => 'بانر ثانٍ',
            'alt_text' => 'بانر ثانٍ',
            'image_public_url' => 'https://example.test/second.jpg',
            'sort_order' => 2,
        ]);

        $this->makeBanner([
            'title' => 'بانر أول',
            'alt_text' => 'بانر أول',
            'image_public_url' => 'https://example.test/first.jpg',
            'sort_order' => 1,
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addHour(),
        ]);

        $this->makeBanner([
            'alt_text' => 'بانر معطّل',
            'is_active' => false,
        ]);

        $this->makeBanner([
            'alt_text' => 'بانر مستقبلي',
            'starts_at' => now()->addDay(),
        ]);

        $this->makeBanner([
            'alt_text' => 'بانر منتهي',
            'ends_at' => now()->subMinute(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-home-banners', false)
            ->assertDontSee('data-home-hero', false)
            ->assertSeeInOrder([
                'https://example.test/first.jpg',
                'https://example.test/second.jpg',
            ], false)
            ->assertSee('بانر أول')
            ->assertSee('بانر ثانٍ')
            ->assertDontSee('بانر معطّل')
            ->assertDontSee('بانر مستقبلي')
            ->assertDontSee('بانر منتهي');
    }

    public function test_branded_fallback_is_shown_when_no_banner_is_publicly_visible(): void
    {
        $this->makeBanner([
            'alt_text' => 'بانر غير منشور',
            'is_active' => false,
        ]);

        $this->makeBanner([
            'alt_text' => 'بانر منتهي',
            'ends_at' => now()->subDay(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('data-home-hero', false)
            ->assertDontSee('data-home-banners', false)
            ->assertSee('هوية Matrix Wave — الشعار الرسمي للموقع.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeBanner(array $attributes = []): Banner
    {
        return Banner::query()->create(array_merge([
            'title' => null,
            'image_storage_path' => 'banners/test/'.uniqid().'.jpg',
            'image_public_url' => 'https://example.test/banner.jpg',
            'alt_text' => 'وصف البانر',
            'is_active' => true,
            'sort_order' => 0,
            'starts_at' => null,
            'ends_at' => null,
        ], $attributes));
    }
}
