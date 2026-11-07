<?php

namespace Tests\Feature;

use App\Exceptions\OrderCreationException;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderPlacedNotification;
use App\Notifications\WelcomeNotification;
use App\Services\Orders\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

/**
 * Covers the administrator alerts emitted after a successful order creation
 * and the polling endpoint that powers the live badges.
 *
 * Runs on the isolated in-memory sqlite database configured in phpunit.xml.
 */
class NewOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_receive_one_notification_per_new_order(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct();
        $this->addToCart($customer, $product, 1);

        $order = $this->service()->place($customer, $this->payload());

        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $customer->notifications()->count());

        $notification = $admin->notifications()->firstOrFail();

        $this->assertSame(NewOrderPlacedNotification::class, $notification->type);
        $this->assertSame('order', $notification->data['category']);
        $this->assertSame($order->order_number, $notification->data['order_number']);
    }

    public function test_each_admin_receives_exactly_one_notification(): void
    {
        $firstAdmin = User::factory()->admin()->create();
        $secondAdmin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct();
        $this->addToCart($customer, $product, 1);

        $this->service()->place($customer, $this->payload());

        $this->assertSame(1, $firstAdmin->notifications()->count());
        $this->assertSame(1, $secondAdmin->notifications()->count());
        $this->assertSame(0, $customer->notifications()->count());
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_notification_contains_real_order_values_and_a_direct_admin_link(): void
    {
        User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct(['price' => '12.50']);
        $this->addToCart($customer, $product, 2);

        $order = $this->service()->place($customer, $this->payload([
            'name' => 'سالم أحمد',
        ]));

        $notification = User::query()->where('role', 'admin')->firstOrFail()
            ->notifications()
            ->firstOrFail();

        $data = $notification->data;

        $this->assertSame('طلب جديد', $data['title']);
        $this->assertStringContainsString((string) $order->order_number, $data['body']);
        $this->assertStringContainsString('سالم أحمد', $data['body']);
        $this->assertStringContainsString('25.00', $data['body']);
        $this->assertStringContainsString($order->currency_code, $data['body']);
        $this->assertStringNotContainsString('{', $data['body']);
        $this->assertStringNotContainsString('sprintf', $data['body']);

        $this->assertSame(
            route('admin.orders.show', $order),
            $data['url'],
        );
        $this->assertStringEndsWith('/admin/orders/'.$order->order_number, $data['url']);
    }

    public function test_failed_order_creation_sends_no_notification(): void
    {
        User::factory()->admin()->create();
        $customer = User::factory()->create();

        try {
            $this->service()->place($customer, $this->payload());
            $this->fail('Expected an OrderCreationException for an empty cart.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('فارغة', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_repeated_submission_does_not_duplicate_the_notification(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct();
        $this->addToCart($customer, $product, 1);

        $this->service()->place($customer, $this->payload());

        try {
            $this->service()->place($customer, $this->payload());
            $this->fail('Expected an OrderCreationException on a repeated cart submission.');
        } catch (OrderCreationException $exception) {
            $this->assertStringContainsString('فارغة', $exception->getMessage());
        }

        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_notification_failure_never_breaks_order_creation(): void
    {
        User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct();
        $this->addToCart($customer, $product, 1);

        Log::spy();

        Event::listen(NotificationSending::class, function (NotificationSending $event): void {
            if ($event->notification instanceof NewOrderPlacedNotification) {
                throw new RuntimeException('Forced notification failure.');
            }
        });

        $order = $this->service()->place($customer, $this->payload());

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', ['id' => $order->getKey()]);
        $this->assertDatabaseCount('notifications', 0);

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => str_contains($message, 'Failed to notify administrators'))
            ->once();
    }

    public function test_poll_returns_unread_and_pending_counts_for_admins(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct();
        $this->addToCart($customer, $product, 1);

        $order = $this->service()->place($customer, $this->payload());

        $response = $this->actingAs($admin)->getJson(route('notifications.poll'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('pending_orders', 1)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'طلب جديد')
            ->assertJsonPath('notifications.0.category', 'order')
            ->assertJsonPath('notifications.0.url', route('admin.orders.show', $order));
    }

    public function test_poll_never_leaks_other_users_notifications_or_pending_counts(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->create();

        $product = $this->makeProduct();
        $this->addToCart($customer, $product, 1);

        $this->service()->place($customer, $this->payload());

        $customer->notify(new WelcomeNotification);

        $response = $this->actingAs($customer)->getJson(route('notifications.poll'));

        $response->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonPath('pending_orders', null)
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.category', 'account');

        $ids = collect($response->json('notifications'))->pluck('id');

        $this->assertFalse($ids->contains(
            $admin->notifications()->firstOrFail()->id,
        ));
    }

    public function test_admin_layout_renders_one_poller_and_live_badges(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();

        $html = $response->getContent();

        $this->assertSame(1, substr_count($html, 'data-notification-poller'));
        $this->assertStringContainsString('data-notification-badge', $html);
        $this->assertStringContainsString('data-admin-orders-badge', $html);
        $this->assertStringContainsString(route('notifications.index'), $html);
    }

    public function test_storefront_layout_renders_exactly_one_poller(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->get(route('notifications.index'));

        $response->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'data-notification-poller'));
    }

    public function test_storefront_admin_links_carry_no_duplicate_notification_badge(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('notifications.index'));

        $response->assertOk();

        $html = $response->getContent();

        preg_match_all('/<a\b[^>]*href="[^"]*\/admin"[^>]*>.*?<\/a>/su', $html, $matches);

        $this->assertNotEmpty($matches[0]);

        foreach ($matches[0] as $adminLink) {
            $this->assertStringNotContainsString('data-notification-badge', $adminLink);
        }

        // The dedicated notification bell keeps its badge.
        $this->assertGreaterThanOrEqual(1, substr_count($html, 'data-notification-badge'));
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
            'slug' => 'new-order-notification-category-'.uniqid(),
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
            'slug' => 'new-order-notification-product-'.uniqid(),
            'description' => null,
            'price' => '10.00',
            'status' => 'available',
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
