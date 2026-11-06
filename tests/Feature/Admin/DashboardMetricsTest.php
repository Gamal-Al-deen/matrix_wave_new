<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardMetricsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_and_customers_are_forbidden_from_the_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_dashboard_metrics_follow_the_documented_definitions(): void
    {
        $admin = User::factory()->admin()->create();
        $customers = User::factory()->count(2)->create();

        $this->makeOrder($customers[0], [
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'total' => '10.00',
        ]);

        $this->makeOrder($customers[1], [
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'total' => '20.00',
        ]);

        $this->makeOrder($customers[0], [
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Paid,
            'total' => '30.00',
        ]);

        $this->makeOrder($customers[1], [
            'status' => OrderStatus::Completed,
            'payment_status' => PaymentStatus::Unpaid,
            'total' => '40.00',
        ]);

        $this->makeOrder($customers[0], [
            'status' => OrderStatus::Completed,
            'payment_status' => PaymentStatus::Paid,
            'total' => '50.00',
        ]);

        $category = Category::query()->create([
            'name' => 'تصنيف لوحة التحكم',
            'slug' => 'dashboard-category-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'is_active' => true,
        ]);

        foreach (['available', 'out_of_stock', 'inactive'] as $status) {
            Product::query()->create([
                'category_id' => $category->id,
                'name' => 'منتج '.$status,
                'slug' => 'dashboard-product-'.Str::lower((string) Str::ulid()),
                'description' => null,
                'price' => '10.00',
                'status' => $status,
            ]);
        }

        $deletedProduct = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج محذوف',
            'slug' => 'dashboard-product-deleted-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
        ]);
        $deletedProduct->delete();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('إجمالي الطلبات')
            ->assertSee('العملاء المسجلون')
            ->assertSee('المنتجات')
            ->assertSee('قيمة الطلبات التشغيلية')
            ->assertSee('المبيعات المؤكدة')
            ->assertSee('المبيعات المدفوعة')
            ->assertSee('قيمة الطلبات المكتملة')
            ->assertSee('>5</p>', false)
            ->assertSee('>2</p>', false)
            ->assertSee('>3</p>', false)
            ->assertSee('120.00')
            ->assertSee('110.00')
            ->assertSee('70.00')
            ->assertSee('90.00')
            ->assertDontSee('30.00')
            ->assertSee('USD');
    }

    public function test_monetary_metrics_are_grouped_by_saved_currency_without_conversion(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $this->makeOrder($customer, [
            'currency_code' => 'USD',
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'total' => '10.00',
        ]);

        $this->makeOrder($customer, [
            'currency_code' => 'EUR',
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Paid,
            'total' => '20.00',
        ]);

        $this->makeOrder($customer, [
            'currency_code' => 'EUR',
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Paid,
            'total' => '50.00',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('10.00')
            ->assertSee('20.00')
            ->assertSee('USD')
            ->assertSee('EUR')
            ->assertDontSee('50.00')
            ->assertSee('لا تُجمع العملات أو تُحوّل تلقائيًا.');
    }

    public function test_empty_dashboard_uses_the_current_currency_for_zero_values(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('0.00')
            ->assertSee('USD');
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
            'subtotal' => $overrides['total'] ?? '10.00',
            'delivery_fee' => '0.00',
            'total' => $overrides['total'] ?? '10.00',
        ], $overrides));
    }
}
