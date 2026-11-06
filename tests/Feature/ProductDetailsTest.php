<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_details_show_the_gallery_price_category_and_description(): void
    {
        $category = $this->makeCategory(['name' => 'هواتف', 'slug' => 'phones']);
        $product = $this->makeProduct($category, [
            'name' => 'هاتف ذكي',
            'slug' => 'smart-phone',
            'description' => "شاشة كبيرة\nبطارية طويلة",
            'price' => '249.50',
        ]);

        $this->makeImage($product, 'https://example.test/one.jpg', 'الصورة الأولى');
        $this->makeImage($product, 'https://example.test/two.jpg', 'الصورة الثانية');

        $response = $this->get('/products/smart-phone');

        $response->assertOk();
        $response->assertSee('هاتف ذكي');
        $response->assertSee('249.50 USD');
        $response->assertSee('هواتف');
        $response->assertSee('شاشة كبيرة');
        $response->assertSee('بطارية طويلة');
        $response->assertSee('https://example.test/one.jpg', false);
        $response->assertSee('https://example.test/two.jpg', false);
        $response->assertSee('الصورة الأولى');
        $response->assertSee('عرض الصورة 2 من 2');
    }

    public function test_inactive_products_and_products_in_inactive_categories_are_not_public(): void
    {
        $activeCategory = $this->makeCategory(['slug' => 'active-category']);
        $inactiveCategory = $this->makeCategory(['slug' => 'inactive-category', 'is_active' => false]);

        $this->makeProduct($activeCategory, ['slug' => 'inactive-product', 'status' => 'inactive']);
        $this->makeProduct($inactiveCategory, ['slug' => 'hidden-product']);

        $this->get('/products/inactive-product')->assertNotFound();
        $this->get('/products/hidden-product')->assertNotFound();
        $this->get('/products/unknown-product')->assertNotFound();
    }

    public function test_related_products_are_same_category_newest_first_and_limited_to_four(): void
    {
        $category = $this->makeCategory(['slug' => 'phones']);
        $otherCategory = $this->makeCategory(['slug' => 'computers']);

        $current = $this->makeProduct($category, ['slug' => 'current-product']);

        $related = [];

        foreach (range(1, 5) as $index) {
            $related[$index] = $this->makeProduct($category, [
                'name' => 'مشابه '.$index,
                'slug' => 'related-'.$index,
            ]);

            $related[$index]->forceFill(['created_at' => now()->addMinutes($index)])->save();
        }

        $inactive = $this->makeProduct($category, [
            'name' => 'مشابه معطّل',
            'slug' => 'related-inactive',
            'status' => 'inactive',
        ]);
        $inactive->forceFill(['created_at' => now()->addMinutes(30)])->save();

        $other = $this->makeProduct($otherCategory, [
            'name' => 'منتج تصنيف آخر',
            'slug' => 'other-category-product',
        ]);
        $other->forceFill(['created_at' => now()->addMinutes(40)])->save();

        $response = $this->get('/products/current-product');

        $response->assertOk();
        $response->assertSee('منتجات مشابهة');

        $ids = $response->viewData('related')->pluck('id')->all();

        $this->assertSame([
            $related[5]->id,
            $related[4]->id,
            $related[3]->id,
            $related[2]->id,
        ], $ids);

        $response->assertDontSee('مشابه معطّل');
        $response->assertDontSee('منتج تصنيف آخر');
        $this->assertNotContains($current->id, $ids);
    }

    public function test_the_related_section_is_omitted_when_no_related_products_exist(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['slug' => 'only-product']);

        $this->get('/products/only-product')
            ->assertOk()
            ->assertDontSee('منتجات مشابهة');
    }

    public function test_product_descriptions_are_escaped(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, [
            'slug' => 'xss-product',
            'description' => "<script>alert('x')</script>",
        ]);

        $this->get('/products/xss-product')
            ->assertOk()
            ->assertDontSee("<script>alert('x')</script>", false);
    }

    public function test_out_of_stock_details_disable_the_purchase_action(): void
    {
        $category = $this->makeCategory();
        $this->makeProduct($category, ['slug' => 'out-of-stock', 'status' => 'out_of_stock']);

        $this->get('/products/out-of-stock')
            ->assertOk()
            ->assertSee('هذا المنتج غير متوفر حاليًا')
            ->assertDontSee('أضف إلى السلة');
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

    private function makeImage(Product $product, string $url, string $alt): ProductImage
    {
        return $product->images()->create([
            'storage_path' => 'products/'.$product->id.'/'.uniqid().'.jpg',
            'public_url' => $url,
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 100,
            'sort_order' => (int) $product->images()->max('sort_order') + 1,
            'alt_text' => $alt,
        ]);
    }
}
