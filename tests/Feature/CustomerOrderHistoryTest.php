<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerOrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_view_order_history_or_details(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->get(route('orders.index'))->assertRedirect(route('login'));
        $this->get(route('orders.show', $order))->assertRedirect(route('login'));
    }

    public function test_history_lists_only_the_customers_own_orders(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $firstOrder = $this->makeOrder($owner);
        $secondOrder = $this->makeOrder($owner, ['status' => OrderStatus::Confirmed]);
        $otherOrder = $this->makeOrder($other);

        $response = $this->actingAs($owner)->get(route('orders.index'));

        $response->assertOk()
            ->assertSee('طلباتي')
            ->assertSee($firstOrder->order_number)
            ->assertSee($secondOrder->order_number)
            ->assertDontSee($otherOrder->order_number)
            ->assertSee('قيد الانتظار')
            ->assertSee('مؤكد')
            ->assertSee('غير مدفوع');

        $this->assertSame(2, $response->viewData('orders')->total());
    }

    public function test_customer_can_view_own_order_with_historical_snapshots(): void
    {
        $owner = User::factory()->create();
        $product = $this->makeProduct([
            'name' => 'الاسم الحالي للمنتج',
            'price' => '99.00',
        ]);

        $order = $this->makeOrder($owner, [
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'customer_name_snapshot' => 'اسم العميل وقت الطلب',
            'customer_phone_snapshot' => '0500000000',
            'customer_email_snapshot' => 'snapshot@example.com',
            'delivery_address_snapshot' => 'عنوان التوصيل وقت الطلب',
            'notes' => 'ملاحظة محفوظة مع الطلب',
            'subtotal' => '25.50',
            'delivery_fee' => '5.00',
            'total' => '30.50',
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name_snapshot' => 'اسم المنتج وقت الطلب',
            'unit_price' => '25.50',
            'quantity' => 1,
            'line_total' => '25.50',
        ]);

        $product->update([
            'name' => 'اسم محدّث لاحقًا',
            'price' => '99.00',
        ]);

        $response = $this->actingAs($owner)->get(route('orders.show', $order));

        $response->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('اسم المنتج وقت الطلب')
            ->assertDontSee('اسم محدّث لاحقًا')
            ->assertSee('25.50')
            ->assertSee('5.00')
            ->assertSee('30.50')
            ->assertSee('اسم العميل وقت الطلب')
            ->assertSee('عنوان التوصيل وقت الطلب')
            ->assertSee('ملاحظة محفوظة مع الطلب')
            ->assertSee('بيانات التوصيل')
            ->assertSee('مؤكد')
            ->assertSee('مدفوع')
            ->assertDontSee('تم إنشاء طلبك بنجاح');
    }

    public function test_customer_cannot_view_another_customers_order(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->makeOrder($owner);

        $this->actingAs($other)
            ->get(route('orders.show', $order))
            ->assertForbidden();
    }

    public function test_administrator_can_view_a_customer_order(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder($owner);

        $this->actingAs($admin)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_missing_order_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('orders.show', ['order' => 'MW-NOT-FOUND']))
            ->assertNotFound();
    }

    public function test_malformed_order_identifiers_return_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('orders.show', ['order' => 'not-a-valid-order-number']))
            ->assertNotFound();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.show', ['order' => 'not-a-valid-order-number']))
            ->assertNotFound();
    }

    public function test_history_is_paginated_newest_first(): void
    {
        $user = User::factory()->create();

        for ($index = 0; $index < 13; $index++) {
            $this->makeOrder($user);
        }

        $response = $this->actingAs($user)->get(route('orders.index'));

        $response->assertOk()
            ->assertViewHas('orders', fn ($orders): bool => $orders->count() === 12 && $orders->total() === 13)
            ->assertSee('page=2');
    }

    public function test_order_details_omit_zero_delivery_fee_and_show_it_when_recorded(): void
    {
        $user = User::factory()->create();
        $withoutFee = $this->makeOrder($user, [
            'subtotal' => '20.00',
            'delivery_fee' => '0.00',
            'total' => '20.00',
        ]);
        $withFee = $this->makeOrder($user, [
            'subtotal' => '20.00',
            'delivery_fee' => '5.00',
            'total' => '25.00',
        ]);

        $this->actingAs($user)
            ->get(route('orders.show', $withoutFee))
            ->assertOk()
            ->assertSee('لم تُسجّل رسوم توصيل على هذا الطلب.')
            ->assertDontSee('رسوم التوصيل');

        $this->actingAs($user)
            ->get(route('orders.show', $withFee))
            ->assertOk()
            ->assertSee('رسوم التوصيل')
            ->assertSee('5.00')
            ->assertSee('25.00');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOrder(User $user, array $overrides = []): Order
    {
        return Order::query()->create(array_merge([
            'order_number' => 'MW-'.Str::ulid(),
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'wallet_id' => null,
            'currency_code' => 'USD',
            'customer_name_snapshot' => $user->name,
            'customer_phone_snapshot' => '0500000000',
            'customer_email_snapshot' => null,
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'notes' => null,
            'subtotal' => '25.50',
            'delivery_fee' => '0.00',
            'total' => '25.50',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeProduct(array $attributes = []): Product
    {
        $category = Category::query()->create([
            'name' => 'تصنيف تجريبي',
            'slug' => 'customer-orders-category-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'is_active' => true,
        ]);

        return Product::query()->create(array_merge([
            'category_id' => $category->id,
            'name' => 'منتج تجريبي',
            'slug' => 'customer-orders-product-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'price' => '25.50',
            'status' => 'available',
        ], $attributes));
    }
}
