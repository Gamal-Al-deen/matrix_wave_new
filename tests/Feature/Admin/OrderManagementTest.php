<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_order_management(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->get(route('admin.orders.index'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.orders.show', $order))->assertRedirect(route('admin.login'));
    }

    public function test_customers_cannot_view_or_change_orders(): void
    {
        $order = $this->makeOrder(User::factory()->create());
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.orders.index'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch(route('admin.orders.status', $order), ['status' => OrderStatus::Confirmed->value])
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch(route('admin.orders.payment-status', $order), ['payment_status' => PaymentStatus::Paid->value])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
    }

    public function test_administrators_can_search_and_filter_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $firstOrder = $this->makeOrder(User::factory()->create(), [
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'customer_name_snapshot' => 'أحمد التجريبي',
            'customer_phone_snapshot' => '0501111111',
        ]);
        $secondOrder = $this->makeOrder(User::factory()->create(), [
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'customer_name_snapshot' => 'سارة التجريبية',
            'customer_phone_snapshot' => '0502222222',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee($firstOrder->order_number)
            ->assertSee($secondOrder->order_number);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['status' => OrderStatus::Confirmed->value]))
            ->assertOk()
            ->assertSee($secondOrder->order_number)
            ->assertDontSee($firstOrder->order_number);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['payment_status' => PaymentStatus::Unpaid->value]))
            ->assertOk()
            ->assertSee($firstOrder->order_number)
            ->assertDontSee($secondOrder->order_number);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => 'سارة']))
            ->assertOk()
            ->assertSee($secondOrder->order_number)
            ->assertDontSee($firstOrder->order_number);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => $firstOrder->order_number]))
            ->assertOk()
            ->assertSee($firstOrder->order_number)
            ->assertDontSee($secondOrder->order_number);
    }

    public function test_order_details_show_snapshots_history_and_allowed_transitions(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder(User::factory()->create(), [
            'customer_name_snapshot' => 'عميل محفوظ',
            'customer_phone_snapshot' => '0503333333',
            'delivery_address_snapshot' => 'عنوان محفوظ',
            'notes' => 'ملاحظة محفوظة',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => null,
            'product_name_snapshot' => 'منتج محفوظ',
            'unit_price' => '12.50',
            'quantity' => 2,
            'line_total' => '25.00',
        ]);

        $order->statusHistory()->create([
            'from_status' => null,
            'to_status' => OrderStatus::Pending,
            'changed_by' => $order->user_id,
            'note' => 'إنشاء الطلب',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('عميل محفوظ')
            ->assertSee('0503333333')
            ->assertSee('عنوان محفوظ')
            ->assertSee('منتج محفوظ')
            ->assertSee('12.50')
            ->assertSee('25.00')
            ->assertSee('ملاحظة محفوظة')
            ->assertSee('سجل حالات الطلب')
            ->assertSee('إنشاء الطلب')
            ->assertSee('تحديث حالة الطلب')
            ->assertSee('مؤكد')
            ->assertSee('ملغي')
            ->assertDontSee('قيد التجهيز');
    }

    public function test_administrators_can_move_an_order_forward_and_record_history(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Confirmed->value,
                'note' => '  تم الاتصال بالعميل  ',
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertSame(OrderStatus::Confirmed, $order->status);
        $this->assertNotNull($order->confirmed_at);
        $this->assertNull($order->completed_at);
        $this->assertNull($order->cancelled_at);

        $this->assertDatabaseHas('order_status_history', [
            'order_id' => $order->id,
            'from_status' => OrderStatus::Pending->value,
            'to_status' => OrderStatus::Confirmed->value,
            'changed_by' => $admin->id,
            'note' => 'تم الاتصال بالعميل',
        ]);
    }

    public function test_invalid_or_backward_transitions_are_rejected_without_changes(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder(User::factory()->create(), ['status' => OrderStatus::Confirmed]);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Pending->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertDatabaseCount('order_status_history', 0);

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Completed->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Confirmed, $order->refresh()->status);
        $this->assertNull($order->completed_at);
        $this->assertDatabaseCount('order_status_history', 0);
    }

    public function test_non_completed_orders_can_be_cancelled_and_become_terminal(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Cancelled->value,
                'note' => 'بناءً على طلب العميل',
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertNotNull($order->cancelled_at);
        $this->assertSame([], $order->status->allowedTransitions());

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Confirmed->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Cancelled, $order->refresh()->status);
        $this->assertDatabaseCount('order_status_history', 1);
    }

    public function test_payment_status_is_updated_separately_from_order_status(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder(User::factory()->create());
        $originalUpdatedAt = $order->updated_at;

        $this->travel(1)->seconds();

        $this->actingAs($admin)
            ->patch(route('admin.orders.payment-status', $order), [
                'payment_status' => PaymentStatus::Paid->value,
            ])
            ->assertRedirect(route('admin.orders.show', $order))
            ->assertSessionHas('status');

        $order->refresh();

        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertNull($order->confirmed_at);
        $this->assertNull($order->completed_at);
        $this->assertNull($order->cancelled_at);
        $this->assertTrue($order->updated_at->greaterThan($originalUpdatedAt));
        $this->assertDatabaseCount('order_status_history', 0);
    }

    public function test_invalid_status_values_are_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs($admin)
            ->patch(route('admin.orders.status', $order), ['status' => 'unknown'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)
            ->patch(route('admin.orders.payment-status', $order), ['payment_status' => 'unknown'])
            ->assertSessionHasErrors('payment_status');

        $this->assertSame(OrderStatus::Pending, $order->refresh()->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
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
            'subtotal' => '10.00',
            'delivery_fee' => '0.00',
            'total' => '10.00',
        ], $overrides));
    }
}
