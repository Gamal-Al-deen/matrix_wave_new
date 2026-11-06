<?php

namespace Tests\Feature;

use App\Exceptions\OrderCreationException;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_rejects_an_empty_cart(): void
    {
        $user = User::factory()->create();

        try {
            $this->service()->place($user, $this->payload());
            $this->fail('Expected an OrderCreationException for an empty cart.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('فارغة', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_service_second_call_for_the_same_cart_creates_no_second_order(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $firstOrder = $this->service()->place($user, $this->payload());

        try {
            $this->service()->place($user, $this->payload());
            $this->fail('Expected an OrderCreationException on a repeated cart submission.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('فارغة', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertDatabaseHas('orders', ['id' => $firstOrder->id]);
    }

    public function test_service_rejects_a_wallet_that_became_inactive(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $wallet = $this->makeWallet(['is_active' => true]);
        $wallet->update(['is_active' => false]);

        try {
            $this->service()->place($user, $this->payload(['wallet_id' => $wallet->id]));
            $this->fail('Expected an OrderCreationException for an inactive wallet.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('المحفظة', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_service_rejects_a_product_that_became_out_of_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $product->update(['status' => 'out_of_stock']);

        try {
            $this->service()->place($user, $this->payload());
            $this->fail('Expected an OrderCreationException for an out-of-stock product.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('لم يعد متاحًا', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_service_rejects_a_product_inside_an_inactive_category(): void
    {
        $user = User::factory()->create();
        $category = $this->makeCategory();
        $product = $this->makeProduct(['category_id' => $category->id]);
        $this->addToCart($user, $product, 1);

        $category->update(['is_active' => false]);

        try {
            $this->service()->place($user, $this->payload());
            $this->fail('Expected an OrderCreationException for an inactive category.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('لم يعد متاحًا', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_service_rejects_a_deleted_product(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $product->delete();

        try {
            $this->service()->place($user, $this->payload());
            $this->fail('Expected an OrderCreationException for a deleted product.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('لم يعد متوفرًا', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_order_creation_rolls_back_the_order_and_items_on_failure(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        OrderItem::creating(function (): void {
            throw new RuntimeException('Forced order item failure.');
        });

        try {
            $this->service()->place($user, $this->payload());
            $this->fail('Expected the forced item failure to abort the transaction.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced order item failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('order_status_history', 0);
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_order_numbers_are_unique_across_orders(): void
    {
        $user = User::factory()->create();
        $firstProduct = $this->makeProduct(['name' => 'المنتج الأول']);
        $this->addToCart($user, $firstProduct, 1);

        $firstOrder = $this->service()->place($user, $this->payload());

        $secondProduct = $this->makeProduct(['name' => 'المنتج الثاني']);
        $this->addToCart($user, $secondProduct, 1);

        $secondOrder = $this->service()->place($user, $this->payload());

        $this->assertNotSame($firstOrder->order_number, $secondOrder->order_number);
        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(2, Order::query()->distinct()->count('order_number'));
    }

    public function test_zero_price_products_create_zero_value_orders_without_errors(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['price' => '0.00', 'name' => 'منتج مجاني']);
        $this->addToCart($user, $product, 2);

        $order = $this->service()->place($user, $this->payload());
        $item = $order->items()->firstOrFail();

        $this->assertSame('0.00', $order->subtotal);
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('0.00', $order->total);
        $this->assertSame('0.00', $item->unit_price);
        $this->assertSame('0.00', $item->line_total);
        $this->assertSame(2, $item->quantity);
    }

    private function service(): OrderService
    {
        return app(OrderService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'عميل تجريبي',
            'phone' => '771234567',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
            'wallet_id' => null,
            'notes' => null,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCategory(array $attributes = []): Category
    {
        return Category::query()->create(array_merge([
            'name' => 'تصنيف تجريبي',
            'slug' => 'order-service-category-'.uniqid(),
            'description' => null,
            'is_active' => true,
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $category = isset($attributes['category_id'])
            ? Category::query()->findOrFail($attributes['category_id'])
            : $this->makeCategory();

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'order-service-product-'.uniqid(),
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeWallet(array $attributes = []): Wallet
    {
        return Wallet::query()->create(array_merge([
            'name' => 'محفظة تجريبية',
            'account_identifier' => '0000000000',
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    private function addToCart(User $user, Product $product, int $quantity): Cart
    {
        $cart = Cart::query()->firstOrCreate(['user_id' => $user->id]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $cart;
    }
}
