<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\Storage\ImageStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductPermanentDeletionTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_deleting_a_product_permanently_removes_it_and_its_transient_records(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $category = $this->makeCategory();
        $product = $this->makeProduct($category, ['name' => 'منتج محذوف']);
        $image = $this->attachImage($product);

        $otherProduct = $this->makeProduct($category, [
            'name' => 'منتج آخر',
            'slug' => 'other-product',
        ]);
        $otherImage = $this->attachImage($otherProduct);

        $cart = Cart::query()->create(['user_id' => $customer->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2]);
        $otherCartItem = $cart->items()->create(['product_id' => $otherProduct->id, 'quantity' => 1]);

        $order = $this->makeOrder($customer);
        $orderItem = $order->items()->create([
            'product_id' => $product->id,
            'product_name_snapshot' => 'اسم المنتج وقت الطلب',
            'unit_price' => '10.50',
            'quantity' => 3,
            'line_total' => '31.50',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
        $this->assertDatabaseHas('cart_items', ['id' => $otherCartItem->id]);
        $this->assertDatabaseHas('carts', ['id' => $cart->id]);

        $orderItem->refresh();

        $this->assertNull($orderItem->product_id);
        $this->assertSame('اسم المنتج وقت الطلب', $orderItem->product_name_snapshot);
        $this->assertSame('10.50', $orderItem->unit_price);
        $this->assertSame(3, (int) $orderItem->quantity);
        $this->assertSame('31.50', $orderItem->line_total);
        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertSame('31.50', $order->refresh()->total);
        $this->assertSame('31.50', $order->subtotal);

        Storage::disk('supabase')->assertMissing($image->storage_path);
        Storage::disk('supabase')->assertExists($otherImage->storage_path);
        $this->assertDatabaseHas('products', ['id' => $otherProduct->id]);
        $this->assertDatabaseHas('product_images', ['id' => $otherImage->id]);
    }

    public function test_product_deletion_preserves_a_storage_object_shared_with_another_module(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->makeProduct($this->makeCategory());
        $image = $this->attachImage($product);

        $banner = Banner::query()->create([
            'title' => 'بانر يستخدم الصورة نفسها',
            'image_storage_path' => $image->storage_path,
            'image_public_url' => $image->public_url,
            'alt_text' => 'صورة مشتركة',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseHas('banners', ['id' => $banner->id]);
        Storage::disk('supabase')->assertExists($image->storage_path);
    }

    public function test_product_deletion_handles_a_missing_storage_object(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->makeProduct($this->makeCategory());
        $image = $this->attachImage($product, store: false);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['id' => $image->id]);
    }

    public function test_storage_failures_do_not_keep_the_product_row(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->makeProduct($this->makeCategory());
        $this->attachImage($product);

        $this->app->instance(ImageStorageService::class, new class extends ImageStorageService
        {
            public function delete(?string $path): bool
            {
                return false;
            }
        });

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseMissing('product_images', ['product_id' => $product->id]);
    }

    public function test_customers_cannot_delete_products(): void
    {
        $product = $this->makeProduct($this->makeCategory());

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.products.destroy', $product))
            ->assertForbidden();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleting_the_same_product_twice_returns_not_found(): void
    {
        $admin = User::factory()->admin()->create();
        $product = $this->makeProduct($this->makeCategory());

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertNotFound();

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_deletion_removes_only_cart_lines_for_the_deleted_product(): void
    {
        $admin = User::factory()->admin()->create();
        $firstCustomer = User::factory()->create();
        $secondCustomer = User::factory()->create();

        $category = $this->makeCategory();
        $product = $this->makeProduct($category);
        $keptProduct = $this->makeProduct($category, [
            'name' => 'منتج باقٍ',
            'slug' => 'kept-product',
        ]);

        $firstCart = Cart::query()->create(['user_id' => $firstCustomer->id]);
        $firstCart->items()->create(['product_id' => $product->id, 'quantity' => 1]);
        $keptItem = $firstCart->items()->create(['product_id' => $keptProduct->id, 'quantity' => 1]);

        $secondCart = Cart::query()->create(['user_id' => $secondCustomer->id]);
        $secondCart->items()->create(['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('cart_items', ['id' => $keptItem->id]);
        $this->assertDatabaseHas('carts', ['id' => $firstCart->id]);
        $this->assertDatabaseHas('carts', ['id' => $secondCart->id]);
        $this->assertDatabaseMissing('cart_items', ['product_id' => $product->id]);
    }

    private function makeCategory(): Category
    {
        return Category::query()->create([
            'name' => 'تصنيف تجريبي',
            'slug' => 'test-category',
            'description' => null,
            'is_active' => true,
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
            'slug' => 'test-product',
            'description' => null,
            'price' => '10.50',
            'status' => 'available',
        ], $attributes));
    }

    private function attachImage(Product $product, bool $store = true): ProductImage
    {
        $path = 'products/'.$product->id.'/'.Str::uuid().'.png';

        if ($store) {
            Storage::disk('supabase')->put($path, base64_decode(self::ONE_PIXEL_PNG));
        }

        return $product->images()->create([
            'storage_path' => $path,
            'public_url' => 'https://example.test/'.$path,
            'original_name' => 'photo.png',
            'mime_type' => 'image/png',
            'size_bytes' => 68,
            'sort_order' => (int) $product->images()->max('sort_order') + 1,
            'alt_text' => $product->name,
        ]);
    }

    private function makeOrder(User $customer): Order
    {
        return Order::query()->create([
            'order_number' => 'MW-'.Str::ulid(),
            'user_id' => $customer->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'currency_code' => 'USD',
            'customer_name_snapshot' => 'عميل تجريبي',
            'customer_phone_snapshot' => '771234567',
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'subtotal' => '31.50',
            'delivery_fee' => '0.00',
            'total' => '31.50',
        ]);
    }
}
