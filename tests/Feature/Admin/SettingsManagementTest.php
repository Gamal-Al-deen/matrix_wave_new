<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Orders\OrderWhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_guests_are_redirected_and_customers_cannot_manage_settings(): void
    {
        $this->get(route('admin.settings.edit'))->assertRedirect(route('admin.login'));

        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->get(route('admin.settings.edit'))
            ->assertForbidden();

        $this->actingAs($customer)
            ->put(route('admin.settings.update'), [
                'site_name' => 'متجر غير مصرح',
                'currency_code' => 'USD',
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->post(route('admin.settings.logo.store'), [
                'logo' => $this->png('logo.png'),
            ])
            ->assertForbidden();

        $this->actingAs($customer)
            ->delete(route('admin.settings.logo.destroy'))
            ->assertForbidden();
    }

    public function test_administrators_can_view_settings_and_the_currency_warning(): void
    {
        $this->createSettings([
            'site_name' => 'متجر الموجة',
            'currency_code' => 'SAR',
            'contact_phone' => '+966500000001',
            'contact_email' => 'contact@matrix-wave.test',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.settings.edit'))
            ->assertOk()
            ->assertSee('إعدادات الموقع')
            ->assertSee('متجر الموجة')
            ->assertSee('SAR')
            ->assertSee('تغيير عملة الموقع لا يحوّل أسعار المنتجات الحالية ولا يغيّر الطلبات السابقة.')
            ->assertSee('مراجعة أسعار جميع المنتجات يدويًا')
            ->assertSee('شعار الموقع');
    }

    public function test_administrators_can_update_settings_and_currency_is_normalized(): void
    {
        $this->createSettings(['currency_code' => 'USD']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), [
                'site_name' => '  متجر الموجة  ',
                'description' => '  وصف عربي واضح  ',
                'currency_code' => ' sar ',
                'whatsapp_number' => ' +966 50 123 4567 ',
                'contact_phone' => ' +966 50 000 0000 ',
                'contact_email' => ' hello@example.test ',
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        $settings = SiteSetting::current();

        $this->assertSame('متجر الموجة', $settings->site_name);
        $this->assertSame('وصف عربي واضح', $settings->description);
        $this->assertSame('SAR', $settings->currency_code);
        $this->assertSame('+966 50 123 4567', $settings->whatsapp_number);
        $this->assertSame('+966 50 000 0000', $settings->contact_phone);
        $this->assertSame('hello@example.test', $settings->contact_email);
        $this->assertTrue(app(OrderWhatsAppService::class)->isConfigured());
    }

    public function test_currency_change_preserves_product_prices_and_order_snapshots(): void
    {
        $this->createSettings(['currency_code' => 'USD']);
        $category = $this->makeCategory();
        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'منتج محفوظ',
            'slug' => 'settings-product-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'price' => '12.50',
            'status' => 'available',
        ]);
        $order = $this->makeOrder([
            'currency_code' => 'USD',
            'subtotal' => '25.00',
            'total' => '25.00',
        ], [
            [
                'product_id' => $product->id,
                'product_name_snapshot' => 'منتج محفوظ',
                'unit_price' => '12.50',
                'quantity' => 2,
                'line_total' => '25.00',
            ],
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), [
                'site_name' => 'Matrix Wave',
                'currency_code' => 'EUR',
            ])
            ->assertRedirect(route('admin.settings.edit'))
            ->assertSessionHas('status');

        $this->assertSame('EUR', SiteSetting::current()->currency_code);
        $this->assertSame('12.50', $product->refresh()->price);
        $this->assertSame('USD', $order->refresh()->currency_code);
        $this->assertSame('25.00', $order->total);

        $item = OrderItem::query()->where('order_id', $order->id)->sole();
        $this->assertSame('USD', $order->currency_code);
        $this->assertSame('12.50', $item->unit_price);

        $this->actingAs($order->user)
            ->get(route('orders.invoice', $order))
            ->assertOk()
            ->assertSee('12.50 USD')
            ->assertSee('25.00 USD')
            ->assertDontSee('EUR');
    }

    public function test_settings_validation_rejects_invalid_values_without_changes(): void
    {
        $settings = $this->createSettings(['currency_code' => 'USD']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.settings.update'), [
                'site_name' => '',
                'currency_code' => 'US',
                'whatsapp_number' => 'رقم بلا أرقام',
                'contact_email' => 'not-an-email',
            ])
            ->assertSessionHasErrors(['site_name', 'currency_code', 'whatsapp_number', 'contact_email']);

        $settings->refresh();

        $this->assertSame('متجر تجريبي', $settings->site_name);
        $this->assertSame('USD', $settings->currency_code);
        $this->assertNull($settings->whatsapp_number);
        $this->assertNull($settings->contact_email);
    }

    public function test_administrators_can_upload_and_replace_the_site_logo(): void
    {
        $settings = $this->createSettings();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.settings.logo.store'), [
                'logo' => $this->png('logo.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $settings->refresh();
        $firstPath = $settings->logo_storage_path;

        $this->assertStringStartsWith('site/', $firstPath);
        $this->assertNotEmpty($settings->logo_public_url);
        $this->assertTrue($settings->hasCustomLogo());
        Storage::disk('supabase')->assertExists($firstPath);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.settings.logo.store'), [
                'logo' => $this->png('replacement.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'تم استبدال شعار الموقع.');

        $settings->refresh();

        $this->assertNotSame($firstPath, $settings->logo_storage_path);
        Storage::disk('supabase')->assertMissing($firstPath);
        Storage::disk('supabase')->assertExists($settings->logo_storage_path);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.settings.logo.store'), [
                'logo' => UploadedFile::fake()->createWithContent('notes.txt', 'not an image'),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_resetting_the_logo_clears_it_and_restores_the_brand_fallback(): void
    {
        $path = 'site/manual/logo.png';
        Storage::disk('supabase')->put($path, base64_decode(self::ONE_PIXEL_PNG));

        $settings = $this->createSettings([
            'logo_storage_path' => $path,
            'logo_public_url' => 'https://example.test/custom-logo.png',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.settings.logo.destroy'))
            ->assertRedirect()
            ->assertSessionHas('status');

        $settings->refresh();

        $this->assertNull($settings->logo_storage_path);
        $this->assertNull($settings->logo_public_url);
        $this->assertFalse($settings->hasCustomLogo());
        Storage::disk('supabase')->assertMissing($path);

        $this->get('/')
            ->assertOk()
            ->assertSee('images/branding/matrix-wave-logo-2x.jpg', false)
            ->assertDontSee('https://example.test/custom-logo.png', false);
    }

    public function test_invoice_svg_embeds_the_uploaded_site_logo(): void
    {
        $path = 'site/manual/logo.png';
        Storage::disk('supabase')->put($path, base64_decode(self::ONE_PIXEL_PNG));

        $this->createSettings([
            'site_name' => 'متجر الموجة',
            'logo_storage_path' => $path,
            'logo_public_url' => 'https://example.test/custom-logo.png',
        ]);

        $order = $this->makeOrder([
            'customer_name_snapshot' => 'عميل الفاتورة',
        ], [
            [
                'product_name_snapshot' => 'منتج الفاتورة',
                'unit_price' => '25.00',
                'quantity' => 1,
                'line_total' => '25.00',
            ],
        ]);

        $this->actingAs($order->user)
            ->get(route('orders.invoice.image', $order))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml; charset=UTF-8')
            ->assertSee('data:image/png;base64,', false)
            ->assertSee('25.00 USD', false)
            ->assertSee('متجر الموجة', false);
    }

    public function test_configured_identity_and_contact_details_appear_on_the_storefront(): void
    {
        $settings = $this->createSettings([
            'site_name' => 'متجر الموجة',
            'description' => 'وصف مخصص للمتجر',
            'logo_storage_path' => 'site/manual/logo.png',
            'logo_public_url' => 'https://example.test/custom-logo.png',
            'contact_phone' => '+966500000001',
            'contact_email' => 'contact@matrix-wave.test',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('متجر الموجة')
            ->assertSee('وصف مخصص للمتجر')
            ->assertSee('+966500000001')
            ->assertSee('contact@matrix-wave.test')
            ->assertSee('https://example.test/custom-logo.png', false);

        $this->assertSame('متجر الموجة', $settings->displayName());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createSettings(array $overrides = []): SiteSetting
    {
        return SiteSetting::query()->forceCreate(array_merge([
            'id' => SiteSetting::SINGLETON_ID,
            'site_name' => 'متجر تجريبي',
            'logo_storage_path' => null,
            'logo_public_url' => '/images/branding/matrix-wave-logo-2x.jpg',
            'description' => null,
            'currency_code' => 'USD',
            'whatsapp_number' => null,
            'contact_phone' => null,
            'contact_email' => null,
        ], $overrides));
    }

    private function makeCategory(): Category
    {
        return Category::query()->create([
            'name' => 'تصنيف تجريبي',
            'slug' => 'settings-category-'.Str::lower((string) Str::ulid()),
            'description' => null,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  list<array<string, mixed>>  $items
     */
    private function makeOrder(array $overrides = [], array $items = []): Order
    {
        $user = User::factory()->create();

        $order = Order::query()->create(array_merge([
            'order_number' => 'MW-'.Str::ulid(),
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'wallet_id' => null,
            'currency_code' => 'USD',
            'customer_name_snapshot' => 'عميل تجريبي',
            'customer_phone_snapshot' => '0500000000',
            'customer_email_snapshot' => null,
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'notes' => null,
            'subtotal' => '25.00',
            'delivery_fee' => '0.00',
            'total' => '25.00',
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

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::ONE_PIXEL_PNG),
        );
    }
}
