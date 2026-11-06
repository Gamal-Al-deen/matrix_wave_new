<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_route_is_named_and_registered(): void
    {
        $this->assertTrue(Route::has('home'));
        $this->assertSame(url('/'), route('home'));
    }

    public function test_home_page_renders_arabic_rtl_shell(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('lang="ar"', false);
        $response->assertSee('dir="rtl"', false);
        $response->assertSee('Matrix Wave');
        $response->assertSee('تخطي إلى المحتوى');
        $response->assertSee('الرئيسية');
        $response->assertSee('المنتجات');
        $response->assertSee('سلة المشتريات');
        $response->assertSee('لا توجد منتجات معروضة حاليًا');
    }

    public function test_available_links_render_in_navigation_and_footer(): void
    {
        $response = $this->get('/');

        $response->assertSee('href="'.url('/products').'"', false);
        $response->assertSee('href="'.url('/cart').'"', false);
        $response->assertSee('href="'.url('/about').'"', false);
    }

    public function test_home_page_uses_the_brand_logo_with_alt_text(): void
    {
        $response = $this->get('/');

        $response->assertSee('images/branding/matrix-wave-logo-2x.jpg', false);
        $response->assertSee('شعار Matrix Wave');
    }

    public function test_all_layouts_render_with_rtl_direction(): void
    {
        view()->share('errors', new ViewErrorBag);

        foreach (['layouts.app', 'layouts.admin', 'layouts.auth', 'layouts.invoice'] as $layout) {
            $html = view($layout)->render();

            $this->assertStringContainsString('dir="rtl"', $html, "Layout {$layout} is missing RTL direction.");
            $this->assertStringContainsString('lang="ar"', $html, "Layout {$layout} is missing Arabic language.");
        }
    }
}
