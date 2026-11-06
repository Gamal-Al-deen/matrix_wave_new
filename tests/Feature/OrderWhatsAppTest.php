<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_whatsapp_action_is_hidden_when_no_number_is_configured(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertDontSee('مشاركة تفاصيل الطلب عبر واتساب')
            ->assertDontSee('فتح واتساب برسالة جاهزة');
    }

    public function test_whatsapp_action_explains_that_the_message_is_manual(): void
    {
        $this->configureWhatsApp();
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $this->actingAs($user)
            ->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('مشاركة تفاصيل الطلب عبر واتساب')
            ->assertSee('فتح واتساب برسالة جاهزة')
            ->assertSee('راجعها ثم أرسلها يدويًا')
            ->assertSee('فتح الرابط لا يعني إرسالها أو تأكيد الطلب أو الدفع');
    }

    public function test_customer_opens_encoded_message_with_saved_order_values_without_changing_status(): void
    {
        $this->configureWhatsApp('+966 (50) 123-4567');
        $user = User::factory()->create();
        $wallet = $this->makeWallet();
        $order = $this->makeOrder($user, [
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'wallet_id' => $wallet->id,
            'customer_name_snapshot' => 'عميل واتساب',
            'customer_phone_snapshot' => '+966 55 000 0000',
            'customer_email_snapshot' => 'customer@example.com',
            'delivery_address_snapshot' => 'الرياض، حي النخيل، شارع 5',
            'notes' => 'يرجى الاتصال قبل التوصيل.',
            'subtotal' => '45.50',
            'delivery_fee' => '5.00',
            'total' => '50.50',
        ]);
        $this->addItem($order, 'منتج الطلب', '25.50', 1);
        $this->addItem($order, 'منتج إضافي', '10.00', 2);

        $response = $this->actingAs($user)->post(route('orders.whatsapp', $order));

        $response->assertStatus(302);
        $location = $response->headers->get('Location');

        $this->assertIsString($location);
        $this->assertStringStartsWith('https://wa.me/966501234567?text=', $location);
        $this->assertStringNotContainsString(' ', $location);

        $query = [];
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $message = $query['text'] ?? '';

        $this->assertStringContainsString('رقم الطلب: '.$order->order_number, $message);
        $this->assertStringContainsString('الاسم: عميل واتساب', $message);
        $this->assertStringContainsString('الهاتف: +966 55 000 0000', $message);
        $this->assertStringContainsString('البريد الإلكتروني: customer@example.com', $message);
        $this->assertStringContainsString('الرياض، حي النخيل، شارع 5', $message);
        $this->assertStringContainsString("1. منتج الطلب\nالكمية: 1", $message);
        $this->assertStringContainsString('سعر الوحدة: 25.50 USD', $message);
        $this->assertStringContainsString('إجمالي السطر: 25.50 USD', $message);
        $this->assertStringContainsString("2. منتج إضافي\nالكمية: 2", $message);
        $this->assertStringContainsString('سعر الوحدة: 10.00 USD', $message);
        $this->assertStringContainsString('إجمالي السطر: 20.00 USD', $message);
        $this->assertStringContainsString('المجموع الفرعي: 45.50 USD', $message);
        $this->assertStringContainsString('رسوم التوصيل: 5.00 USD', $message);
        $this->assertStringContainsString('الإجمالي: 50.50 USD', $message);
        $this->assertStringContainsString('المحفظة المختارة: '.$wallet->name, $message);
        $this->assertStringContainsString('ملاحظات: يرجى الاتصال قبل التوصيل.', $message);
        $this->assertStringNotContainsString('تم الدفع', $message);
        $this->assertStringNotContainsString('تم التوصيل', $message);

        $order->refresh();

        $this->assertNotNull($order->whatsapp_opened_at);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
    }

    public function test_message_omits_zero_fee_and_absent_optional_fields_and_marks_no_wallet(): void
    {
        $this->configureWhatsApp();
        $user = User::factory()->create();
        $order = $this->makeOrder($user, [
            'customer_email_snapshot' => null,
            'notes' => null,
            'subtotal' => '25.50',
            'delivery_fee' => '0.00',
            'total' => '25.50',
        ]);
        $this->addItem($order, 'منتج الطلب', '25.50', 1);

        $response = $this->actingAs($user)->post(route('orders.whatsapp', $order));
        $location = $response->headers->get('Location');
        $query = [];
        parse_str((string) parse_url((string) $location, PHP_URL_QUERY), $query);
        $message = $query['text'] ?? '';

        $this->assertStringContainsString('المحفظة المختارة: بدون محفظة', $message);
        $this->assertStringContainsString('المجموع الفرعي: 25.50 USD', $message);
        $this->assertStringContainsString('الإجمالي: 25.50 USD', $message);
        $this->assertStringNotContainsString('رسوم التوصيل', $message);
        $this->assertStringNotContainsString('البريد الإلكتروني:', $message);
        $this->assertStringNotContainsString('ملاحظات:', $message);
    }

    public function test_other_customers_cannot_open_the_whatsapp_link(): void
    {
        $this->configureWhatsApp();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $order = $this->makeOrder($owner);

        $this->actingAs($other)
            ->post(route('orders.whatsapp', $order))
            ->assertForbidden();

        $this->assertNull($order->fresh()->whatsapp_opened_at);
    }

    public function test_guests_cannot_open_the_whatsapp_link(): void
    {
        $this->configureWhatsApp();
        $order = $this->makeOrder(User::factory()->create());

        $this->post(route('orders.whatsapp', $order))
            ->assertRedirect(route('login'));

        $this->assertNull($order->fresh()->whatsapp_opened_at);
    }

    public function test_missing_number_returns_an_error_without_recording_an_attempt(): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user);

        $this->actingAs($user)
            ->post(route('orders.whatsapp', $order))
            ->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('error');

        $this->assertNull($order->fresh()->whatsapp_opened_at);
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

    private function addItem(Order $order, string $name, string $unitPrice, int $quantity): void
    {
        $order->items()->create([
            'product_id' => null,
            'product_name_snapshot' => $name,
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'line_total' => number_format((float) $unitPrice * $quantity, 2, '.', ''),
        ]);
    }

    private function makeWallet(): Wallet
    {
        return Wallet::query()->create([
            'name' => 'محفظة تجريبية',
            'account_identifier' => 'wallet@example.com',
            'icon_storage_path' => null,
            'icon_public_url' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function configureWhatsApp(?string $number = '+966 50 123 4567'): SiteSetting
    {
        return SiteSetting::query()->forceCreate([
            'id' => SiteSetting::SINGLETON_ID,
            'site_name' => 'Matrix Wave',
            'currency_code' => 'USD',
            'whatsapp_number' => $number,
        ]);
    }
}
