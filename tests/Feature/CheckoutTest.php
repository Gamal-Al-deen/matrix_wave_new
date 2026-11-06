<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_requires_authentication(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
        $this->post(route('checkout.store'), [])->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_login_preserves_the_intended_checkout_route(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->get(route('checkout.index'))->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect(route('checkout.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_empty_cart_cannot_reach_checkout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_checkout_page_shows_contact_address_wallet_notes_and_item_review(): void
    {
        $user = User::factory()->create([
            'name' => 'أحمد محمد',
            'email' => 'ahmed@example.com',
            'phone' => '0500000000',
            'default_address' => 'الرياض، حي النخيل، شارع 1',
        ]);

        $product = $this->makeProduct([
            'name' => 'منتج للدفع',
            'slug' => 'checkout-product',
            'price' => '25.50',
        ]);
        $this->addToCart($user, $product, 2);

        $activeWallet = $this->makeWallet([
            'name' => 'محفظة بنكك',
            'account_identifier' => '1234567890',
            'is_active' => true,
        ]);
        $inactiveWallet = $this->makeWallet([
            'name' => 'محفظة معطلة',
            'account_identifier' => '9999999999',
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->get(route('checkout.index'));

        $response->assertOk();
        $response->assertSee('إتمام الطلب');
        $response->assertSee('بيانات التواصل');
        $response->assertSee('عنوان التوصيل');
        $response->assertSee('المحفظة الإلكترونية');
        $response->assertSee('ملاحظات إضافية');
        $response->assertSee('مراجعة المنتجات');
        $response->assertSee('أحمد محمد');
        $response->assertSee('0500000000');
        $response->assertSee('الرياض، حي النخيل، شارع 1');
        $response->assertSee('منتج للدفع');
        $response->assertSee('51.00 USD');
        $response->assertSee('محفظة بنكك');
        $response->assertSee('1234567890');
        $response->assertDontSee('محفظة معطلة');
        $response->assertSee('اختيار المحفظة يحدد وسيلة التحويل المفضلة فقط');
        $response->assertSee('لا تُضاف أي رسوم غير مسجلة');
        $response->assertSee('name="checkout_token"', false);
        $response->assertSee('action="'.route('checkout.store').'"', false);
    }

    public function test_checkout_rejects_a_cart_with_unavailable_products(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $product->update(['status' => 'out_of_stock']);

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');
    }

    public function test_checkout_requires_contact_and_delivery_data(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $this->post(route('checkout.store'), [
            'checkout_token' => $token,
        ])->assertSessionHasErrors(['name', 'phone', 'delivery_address']);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_validates_phone_email_notes_and_wallet_availability(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $activeWallet = $this->makeWallet(['is_active' => true]);
        $inactiveWallet = $this->makeWallet(['is_active' => false, 'name' => 'محفظة معطلة']);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => 'not-a-phone',
            'email' => 'not-an-email',
            'delivery_address' => 'عنوان تجريبي',
            'wallet_id' => $inactiveWallet->id,
            'notes' => str_repeat('ن', 2001),
        ])->assertSessionHasErrors(['phone', 'email', 'wallet_id', 'notes']);

        $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => '------',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
        ])->assertSessionHasErrors('phone');

        $response = $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => '+966 50 123 4567',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
            'wallet_id' => $activeWallet->id,
            'notes' => 'ملاحظة',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'wallet_id' => $activeWallet->id,
            'customer_phone_snapshot' => '+966 50 123 4567',
        ]);

        $response->assertRedirect(route('orders.show', Order::query()->firstOrFail()));
    }

    public function test_checkout_requires_a_valid_one_time_token(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $payload = [
            'name' => 'عميل',
            'phone' => '0500000000',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
        ];

        $this->post(route('checkout.store'), $payload)
            ->assertSessionHasErrors('checkout_token');

        $this->post(route('checkout.store'), array_merge($payload, [
            'checkout_token' => str_repeat('x', 40),
        ]))->assertSessionHasErrors('checkout_token');

        $response = $this->post(route('checkout.store'), array_merge($payload, [
            'checkout_token' => $token,
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertNull(session('checkout.token'));

        $response->assertRedirect(route('orders.show', Order::query()->firstOrFail()));
    }

    public function test_valid_checkout_creates_an_atomic_order_with_snapshots_and_clears_the_cart(): void
    {
        SiteSetting::query()->create([
            'id' => SiteSetting::SINGLETON_ID,
            'site_name' => 'Matrix Wave',
            'currency_code' => 'USD',
        ]);

        $user = User::factory()->create([
            'name' => 'اسم الحساب',
            'email' => 'account@example.com',
            'phone' => '1111111111',
        ]);

        $product = $this->makeProduct([
            'name' => 'منتج الطلب الأول',
            'price' => '25.50',
        ]);
        $this->addToCart($user, $product, 2);

        $wallet = $this->makeWallet([
            'name' => 'محفظة الطلب',
            'account_identifier' => '5555555555',
            'is_active' => true,
        ]);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $response = $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل الطلب',
            'phone' => '0500000000',
            'email' => 'order@example.com',
            'delivery_address' => 'الرياض، حي الياسمين',
            'wallet_id' => $wallet->id,
            'notes' => 'يرجى الاتصال قبل التوصيل.',
        ])->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order))
            ->assertSessionHas('order_created', $order->id);

        $this->assertSame($user->id, $order->user_id);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame($wallet->id, $order->wallet_id);
        $this->assertSame('USD', $order->currency_code);
        $this->assertSame('عميل الطلب', $order->customer_name_snapshot);
        $this->assertSame('0500000000', $order->customer_phone_snapshot);
        $this->assertSame('order@example.com', $order->customer_email_snapshot);
        $this->assertSame('الرياض، حي الياسمين', $order->delivery_address_snapshot);
        $this->assertSame('يرجى الاتصال قبل التوصيل.', $order->notes);
        $this->assertSame('51.00', $order->subtotal);
        $this->assertSame('0.00', $order->delivery_fee);
        $this->assertSame('51.00', $order->total);
        $this->assertMatchesRegularExpression('/^MW-[0-9A-Z]{26}$/', $order->order_number);

        $item = $order->items()->firstOrFail();

        $this->assertSame($product->id, $item->product_id);
        $this->assertSame('منتج الطلب الأول', $item->product_name_snapshot);
        $this->assertSame('25.50', $item->unit_price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('51.00', $item->line_total);

        $history = $order->statusHistory()->firstOrFail();

        $this->assertNull($history->from_status);
        $this->assertSame(OrderStatus::Pending, $history->to_status);
        $this->assertSame($user->id, $history->changed_by);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('order_status_history', 1);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertNull(session('checkout.draft'));
        $this->assertNull(session('checkout.token'));
        $this->assertSame($order->id, session('order_created'));

        $this->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('تم إنشاء طلبك بنجاح')
            ->assertSee($order->order_number)
            ->assertSee('منتج الطلب الأول')
            ->assertSee('51.00')
            ->assertSee('بيانات التوصيل');

        $product->update(['name' => 'اسم منتج جديد', 'price' => '99.00']);
        $user->update(['name' => 'اسم حساب جديد']);
        SiteSetting::query()->whereKey(SiteSetting::SINGLETON_ID)->update(['currency_code' => 'EUR']);

        $order->refresh();
        $item->refresh();

        $this->assertSame('منتج الطلب الأول', $item->product_name_snapshot);
        $this->assertSame('25.50', $item->unit_price);
        $this->assertSame('51.00', $order->total);
        $this->assertSame('USD', $order->currency_code);
        $this->assertSame('عميل الطلب', $order->customer_name_snapshot);
    }

    public function test_checkout_can_create_an_order_without_a_wallet(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['price' => '12.00']);
        $this->addToCart($user, $product, 1);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $response = $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل بدون محفظة',
            'phone' => '0500000000',
            'email' => '',
            'delivery_address' => 'عنوان تجريبي',
            'wallet_id' => '',
            'notes' => '',
        ])->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertNull($order->wallet_id);
        $this->assertNull($order->customer_email_snapshot);
        $this->assertNull($order->notes);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
    }

    public function test_duplicate_submission_creates_at_most_one_order(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $payload = [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => '0500000000',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
        ];

        $response = $this->post(route('checkout.store'), $payload)
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('orders', 1);

        $response->assertRedirect(route('orders.show', Order::query()->firstOrFail()));

        $this->post(route('checkout.store'), $payload)
            ->assertSessionHasErrors('checkout_token');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_rejects_a_product_that_became_unavailable_after_the_form_was_opened(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $product->update(['status' => 'out_of_stock']);

        $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => '0500000000',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
        ])->assertRedirect(route('cart.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame($token, session('checkout.token'));
    }

    public function test_order_uses_the_current_price_even_if_it_changed_after_the_form_was_opened(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['price' => '10.00']);
        $this->addToCart($user, $product, 2);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $product->update(['price' => '15.00']);

        $response = $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => '0500000000',
            'email' => 'customer@example.com',
            'delivery_address' => 'عنوان تجريبي',
        ])->assertSessionHasNoErrors();

        $order = Order::query()->firstOrFail();
        $item = $order->items()->firstOrFail();

        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame('15.00', $item->unit_price);
        $this->assertSame('30.00', $item->line_total);
        $this->assertSame('30.00', $order->subtotal);
        $this->assertSame('30.00', $order->total);
    }

    public function test_checkout_accepts_arabic_digits_and_an_active_wallet(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $wallet = $this->makeWallet(['is_active' => true]);

        $this->actingAs($user)->get(route('checkout.index'));
        $token = session('checkout.token');

        $response = $this->post(route('checkout.store'), [
            'checkout_token' => $token,
            'name' => 'عميل',
            'phone' => '+٩٦٦ ٥٠ ١٢٣ ٤٥٦٧',
            'email' => '',
            'delivery_address' => 'عنوان تجريبي',
            'wallet_id' => $wallet->id,
            'notes' => '',
        ])->assertSessionHasNoErrors();

        $response->assertRedirect(route('orders.show', Order::query()->firstOrFail()));

        $this->assertDatabaseHas('orders', [
            'customer_phone_snapshot' => '+٩٦٦ ٥٠ ١٢٣ ٤٥٦٧',
            'wallet_id' => $wallet->id,
        ]);
    }

    public function test_checkout_displays_the_current_product_price(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(['price' => '10.00']);
        $this->addToCart($user, $product, 2);

        $product->update(['price' => '15.00']);

        $this->actingAs($user)
            ->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('30.00 USD')
            ->assertDontSee('20.00 USD');
    }

    public function test_the_cart_links_to_checkout_for_a_valid_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->addToCart($user, $product, 1);

        $this->actingAs($user)
            ->get(route('cart.index'))
            ->assertOk()
            ->assertSee('href="'.route('checkout.index').'"', false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeCategory(array $attributes = []): Category
    {
        return Category::query()->create(array_merge([
            'name' => 'تصنيف تجريبي',
            'slug' => 'checkout-category-'.uniqid(),
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
            'slug' => 'checkout-product-'.uniqid(),
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
