<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_homepage_shows_only_publicly_visible_products(): void
    {
        $activeCategory = $this->makeCategory(['name' => 'إلكترونيات', 'slug' => 'electronics']);
        $inactiveCategory = $this->makeCategory([
            'name' => 'تصنيف معطّل',
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);

        $this->makeProduct($activeCategory, ['name' => 'منتج متاح', 'slug' => 'available-product']);
        $this->makeProduct($activeCategory, [
            'name' => 'منتج نافد',
            'slug' => 'out-of-stock-product',
            'status' => 'out_of_stock',
        ]);
        $this->makeProduct($activeCategory, [
            'name' => 'منتج غير مفعّل',
            'slug' => 'inactive-product',
            'status' => 'inactive',
        ]);
        $this->makeProduct($inactiveCategory, [
            'name' => 'منتج داخل تصنيف معطّل',
            'slug' => 'hidden-category-product',
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('منتج متاح');
        $response->assertSee('منتج نافد');
        $response->assertSee('إلكترونيات');
        $response->assertDontSee('منتج غير مفعّل');
        $response->assertDontSee('منتج داخل تصنيف معطّل');
        $response->assertDontSee('تصنيف معطّل');
    }

    public function test_out_of_stock_products_are_visible_without_purchase(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, [
            'name' => 'منتج نافد',
            'slug' => 'out-of-stock-product',
            'status' => 'out_of_stock',
        ]);

        $response = $this->get('/products/out-of-stock-product');

        $response->assertOk();
        $response->assertSee('هذا المنتج غير متوفر حاليًا');
        $response->assertSee('غير متوفر');
        $response->assertDontSee('أضف إلى السلة');
    }

    public function test_the_homepage_paginates_products(): void
    {
        $category = $this->makeCategory();

        foreach (range(1, 13) as $index) {
            $this->makeProduct($category, [
                'name' => 'منتج '.$index,
                'slug' => 'product-'.$index,
            ]);
        }

        $paginator = $this->get('/')->viewData('products');

        $this->assertSame(12, $paginator->count());
        $this->assertSame(13, $paginator->total());
    }

    public function test_products_can_be_searched_by_arabic_name(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name' => 'هاتف ذكي', 'slug' => 'smart-phone']);
        $this->makeProduct($category, ['name' => 'حاسوب محمول', 'slug' => 'laptop']);

        $response = $this->get('/products?q=هاتف');

        $response->assertOk();
        $response->assertSee('هاتف ذكي');
        $response->assertDontSee('حاسوب محمول');
        $response->assertSee('نتائج البحث عن');
    }

    public function test_search_is_case_insensitive_for_latin_names(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name' => 'Smart Phone', 'slug' => 'smart-phone']);

        $this->get('/products?q=phone')
            ->assertOk()
            ->assertSee('Smart Phone');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        $phones = $this->makeCategory(['name' => 'هواتف', 'slug' => 'phones']);
        $computers = $this->makeCategory(['name' => 'حاسوب', 'slug' => 'computers']);

        $this->makeProduct($phones, ['name' => 'هاتف', 'slug' => 'phone']);
        $this->makeProduct($computers, ['name' => 'جهاز مكتبي', 'slug' => 'computer']);

        $response = $this->get('/products?category=phones');

        $response->assertOk();
        $response->assertSee('هاتف');
        $response->assertDontSee('جهاز مكتبي');
    }

    public function test_products_can_be_sorted_by_price(): void
    {
        $category = $this->makeCategory();
        $expensive = $this->makeProduct($category, ['name' => 'غالي', 'slug' => 'expensive', 'price' => '900.00']);
        $cheap = $this->makeProduct($category, ['name' => 'رخيص', 'slug' => 'cheap', 'price' => '10.00']);

        $ascending = $this->get('/products?sort=price_asc')->viewData('products');
        $descending = $this->get('/products?sort=price_desc')->viewData('products');

        $this->assertSame([$cheap->id, $expensive->id], $ascending->pluck('id')->all());
        $this->assertSame([$expensive->id, $cheap->id], $descending->pluck('id')->all());
    }

    public function test_search_filter_sort_and_pagination_compose_and_are_preserved(): void
    {
        $category = $this->makeCategory(['slug' => 'phones']);
        $other = $this->makeCategory(['slug' => 'other']);

        foreach (range(1, 14) as $index) {
            $this->makeProduct($category, [
                'name' => 'هاتف '.$index,
                'slug' => 'phone-'.$index,
                'price' => (string) $index,
            ]);
        }

        $this->makeProduct($other, ['name' => 'هاتف آخر', 'slug' => 'other-phone']);

        $response = $this->get('/products?q=هاتف&category=phones&sort=price_asc');

        $response->assertOk();

        $paginator = $response->viewData('products');

        $this->assertSame(14, $paginator->total());
        $this->assertSame(12, $paginator->count());

        parse_str((string) parse_url($paginator->url(2), PHP_URL_QUERY), $parameters);

        $this->assertSame('هاتف', $parameters['q'] ?? null);
        $this->assertSame('phones', $parameters['category'] ?? null);
        $this->assertSame('price_asc', $parameters['sort'] ?? null);
        $this->assertSame('2', $parameters['page'] ?? null);
    }

    public function test_empty_search_results_explain_the_state_and_offer_reset(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['name' => 'منتج موجود', 'slug' => 'existing']);

        $response = $this->get('/products?q=غير-موجود');

        $response->assertOk();
        $response->assertSee('لا توجد منتجات مطابقة');
        $response->assertSee('إعادة تعيين البحث');
    }

    public function test_injection_like_search_filter_and_sort_values_are_handled_as_data(): void
    {
        $category = $this->makeCategory(['slug' => 'safe-category']);
        $this->makeProduct($category, ['name' => 'منتج آمن', 'slug' => 'safe-product']);

        $query = http_build_query([
            'q' => "' OR 1=1 --",
            'category' => "safe-category' OR 1=1 --",
            'sort' => "price_asc'; DROP TABLE products; --",
        ]);

        $this->get('/products?'.$query)
            ->assertOk()
            ->assertSee('لا توجد منتجات مطابقة')
            ->assertDontSee('منتج آمن')
            ->assertDontSee('SQLSTATE', false)
            ->assertDontSee('Stack trace', false);

        $this->assertDatabaseHas('products', ['name' => 'منتج آمن']);
    }

    public function test_invalid_sort_parameters_fall_back_to_newest(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category);

        $this->get('/products?sort=not-valid')->assertOk();
    }

    public function test_prices_use_the_configured_currency(): void
    {
        SiteSetting::query()->forceCreate([
            'id' => SiteSetting::SINGLETON_ID,
            'site_name' => 'Matrix Wave',
            'currency_code' => 'SAR',
        ]);

        $category = $this->makeCategory();
        $this->makeProduct($category, ['price' => '15.00']);

        $this->get('/products')
            ->assertOk()
            ->assertSee('15.00 SAR');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCategory(array $attributes = []): Category
    {
        return Category::query()->create(array_merge([
            'name' => 'تصنيف تجريبي',
            'slug' => 'test-category',
            'description' => null,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(Category $category, array $attributes = []): Product
    {
        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'test-product',
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ], $attributes));
    }
}
