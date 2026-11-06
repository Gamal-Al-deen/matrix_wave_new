<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_before_viewing_an_invoice(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->get(route('orders.invoice', $order))->assertRedirect(route('login'));
        $this->get(route('orders.invoice.image', $order))->assertRedirect(route('login'));
    }

    public function test_owner_and_administrator_can_view_a_snapshot_invoice(): void
    {
        $this->createSettings();
        $owner = User::factory()->create();
        $order = $this->makeOrder($owner, [
            'customer_name_snapshot' => 'عميل الفاتورة',
            'customer_phone_snapshot' => '0504444444',
            'customer_email_snapshot' => 'customer@example.test',
            'delivery_address_snapshot' => "عنوان أول\nعنوان ثانٍ",
            'payment_status' => PaymentStatus::Paid,
            'subtotal' => '30.00',
            'delivery_fee' => '5.00',
            'total' => '35.00',
        ], [
            ['product_name_snapshot' => 'منتج أول', 'unit_price' => '12.50', 'quantity' => 2, 'line_total' => '25.00'],
            ['product_name_snapshot' => 'منتج ثانٍ', 'unit_price' => '5.00', 'quantity' => 1, 'line_total' => '5.00'],
        ]);

        $response = $this->actingAs($owner)->get(route('orders.invoice', $order));

        $response->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('متجر مخصص')
            ->assertSee('matrix-wave-logo-2x.jpg')
            ->assertSee($order->order_number)
            ->assertSee('عميل الفاتورة')
            ->assertSee('0504444444')
            ->assertSee('customer@example.test')
            ->assertSee('عنوان أول')
            ->assertSee('عنوان ثانٍ')
            ->assertSee('منتج أول')
            ->assertSee('منتج ثانٍ')
            ->assertSee('12.50 USD')
            ->assertSee('25.00 USD')
            ->assertSee('المجموع الفرعي')
            ->assertSee('رسوم التوصيل')
            ->assertSee('5.00 USD')
            ->assertSee('35.00 USD')
            ->assertSee('مدفوع')
            ->assertSee('تنزيل كصورة SVG')
            ->assertSee('data-print-invoice', false)
            ->assertSee('invoice-document', false)
            ->assertSee('contact@matrix-wave.test')
            ->assertSee('+966500000001');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee('عميل الفاتورة');
    }

    public function test_other_customers_cannot_view_an_invoice_and_unknown_orders_are_not_found(): void
    {
        $order = $this->makeOrder(User::factory()->create());

        $this->actingAs(User::factory()->create())
            ->get(route('orders.invoice', $order))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('orders.invoice.image', $order))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->get(route('orders.invoice', 'MW-UNKNOWN'))
            ->assertNotFound();
    }

    public function test_invoice_money_and_product_values_remain_historical_snapshots(): void
    {
        $this->createSettings([
            'site_name' => 'متجر حالي',
            'currency_code' => 'EUR',
        ]);

        $category = Category::query()->create([
            'name' => 'تصنيف حالي',
            'slug' => 'invoice-category-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'اسم المنتج وقت إنشاء الطلب',
            'slug' => 'invoice-product-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'price' => '12.50',
            'status' => 'available',
        ]);

        $owner = User::factory()->create();
        $order = $this->makeOrder($owner, [
            'currency_code' => 'USD',
            'subtotal' => '25.00',
            'delivery_fee' => '0.00',
            'total' => '25.00',
        ], [
            [
                'product_id' => $product->id,
                'product_name_snapshot' => 'اسم المنتج وقت إنشاء الطلب',
                'unit_price' => '12.50',
                'quantity' => 2,
                'line_total' => '25.00',
            ],
        ]);

        $product->update([
            'name' => 'اسم المنتج الحالي بعد التعديل',
            'price' => '99.00',
        ]);

        $this->actingAs($owner)
            ->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee('اسم المنتج وقت إنشاء الطلب')
            ->assertDontSee('اسم المنتج الحالي بعد التعديل')
            ->assertSee('12.50 USD')
            ->assertSee('25.00 USD')
            ->assertDontSee('99.00')
            ->assertDontSee('EUR')
            ->assertSee('لم تُسجّل رسوم توصيل على هذا الطلب.');

        $this->actingAs($owner)
            ->get(route('orders.invoice', $order))
            ->assertDontSee('رسوم التوصيل');
    }

    public function test_invoice_svg_export_contains_snapshot_values_and_embedded_logo(): void
    {
        $this->createSettings();
        $owner = User::factory()->create();
        $order = $this->makeOrder($owner, [
            'subtotal' => '25.00',
            'delivery_fee' => '0.00',
            'total' => '25.00',
        ], [
            ['product_name_snapshot' => 'منتج التصدير', 'unit_price' => '12.50', 'quantity' => 2, 'line_total' => '25.00'],
        ]);

        $response = $this->actingAs($owner)->get(route('orders.invoice.image', $order));

        $response->assertOk()
            ->assertHeader('content-type', 'image/svg+xml; charset=UTF-8')
            ->assertHeader('content-disposition', 'attachment; filename="invoice-'.$order->order_number.'.svg"');

        $svg = $response->getContent();

        $this->assertStringContainsString('<svg', $svg);
        $this->assertStringContainsString('data:image/jpeg;base64,', $svg);
        $this->assertStringContainsString($order->order_number, $svg);
        $this->assertStringContainsString('منتج التصدير', $svg);
        $this->assertStringContainsString('12.50 USD', $svg);
        $this->assertStringContainsString('25.00 USD', $svg);
        $this->assertStringContainsString('لم تُسجّل رسوم توصيل على هذا الطلب.', $svg);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSettings(array $overrides = []): SiteSetting
    {
        return SiteSetting::query()->forceCreate(array_merge([
            'id' => SiteSetting::SINGLETON_ID,
            'site_name' => 'متجر مخصص',
            'logo_storage_path' => null,
            'logo_public_url' => '/images/branding/matrix-wave-logo-2x.jpg',
            'description' => null,
            'currency_code' => 'USD',
            'whatsapp_number' => null,
            'contact_phone' => '+966500000001',
            'contact_email' => 'contact@matrix-wave.test',
        ], $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<array<string, mixed>>  $items
     */
    private function makeOrder(User $user, array $overrides = [], array $items = []): Order
    {
        $order = Order::query()->create(array_merge([
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

        foreach ($items as $item) {
            OrderItem::query()->create(array_merge([
                'order_id' => $order->id,
                'product_id' => null,
                'product_name_snapshot' => 'منتج',
                'unit_price' => '10.00',
                'quantity' => 1,
                'line_total' => '10.00',
            ], $item));
        }

        return $order;
    }
}
