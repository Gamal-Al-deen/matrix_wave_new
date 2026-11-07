<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
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

    public function test_homepage_lists_categories_from_the_database(): void
    {
        $this->makeCategory('هواتف', 'phones');
        $this->makeCategory('سماعات', 'headphones');

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('aria-label="تصفية المنتجات حسب التصنيف"', false);
        $response->assertSee('الكل');
        $response->assertSee('هواتف');
        $response->assertSee('سماعات');
    }

    public function test_inactive_categories_are_hidden_from_the_homepage_list(): void
    {
        $this->makeCategory('ظاهر', 'visible-category');
        $this->makeCategory('مخفي', 'hidden-category', false);

        $this->get('/')
            ->assertOk()
            ->assertSee('ظاهر')
            ->assertDontSee('مخفي');
    }

    public function test_homepage_shows_all_public_products_by_default(): void
    {
        $phones = $this->makeCategory('هواتف', 'phones');
        $audio = $this->makeCategory('سماعات', 'headphones');

        $this->makeProduct($phones, ['name' => 'هاتف ذكي']);
        $this->makeProduct($audio, ['name' => 'سماعة لاسلكية']);

        $this->get('/')
            ->assertOk()
            ->assertSee('هاتف ذكي')
            ->assertSee('سماعة لاسلكية');
    }

    public function test_homepage_filters_products_by_the_selected_category(): void
    {
        $phones = $this->makeCategory('هواتف', 'phones');
        $audio = $this->makeCategory('سماعات', 'headphones');

        $this->makeProduct($phones, ['name' => 'هاتف ذكي']);
        $this->makeProduct($audio, ['name' => 'سماعة لاسلكية']);

        $response = $this->get('/?category=phones');

        $response->assertOk();
        $response->assertSee('هاتف ذكي');
        $response->assertDontSee('سماعة لاسلكية');
        $response->assertSee('منتجات هواتف');
    }

    public function test_homepage_category_filter_ignores_hidden_products(): void
    {
        $phones = $this->makeCategory('هواتف', 'phones');

        $this->makeProduct($phones, ['name' => 'هاتف متاح']);
        $this->makeProduct($phones, ['name' => 'هاتف غير مفعّل', 'status' => 'inactive']);
        $this->makeProduct($phones, ['name' => 'هاتف محذوف'])->delete();

        $this->get('/?category=phones')
            ->assertOk()
            ->assertSee('هاتف متاح')
            ->assertDontSee('هاتف غير مفعّل')
            ->assertDontSee('هاتف محذوف');
    }

    public function test_an_unknown_category_shows_the_empty_state(): void
    {
        $this->makeCategory('هواتف', 'phones');

        $this->get('/?category=does-not-exist')
            ->assertOk()
            ->assertSee('لا توجد منتجات معروضة حاليًا');
    }

    private function makeCategory(string $name, string $slug, bool $active = true): Category
    {
        return Category::query()->create([
            'name' => $name,
            'slug' => $slug,
            'description' => null,
            'is_active' => $active,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(Category $category, array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'product-'.uniqid(),
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ], $attributes));
    }
}
