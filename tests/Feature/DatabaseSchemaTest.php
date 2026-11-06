<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\SiteSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_expected_tables_exist(): void
    {
        $tables = [
            'users',
            'password_reset_tokens',
            'sessions',
            'categories',
            'products',
            'product_images',
            'carts',
            'cart_items',
            'wallets',
            'orders',
            'order_items',
            'site_settings',
            'banners',
            'social_links',
            'about_page_content',
            'order_status_history',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }
    }

    public function test_users_use_uuid_primary_keys_and_customer_role_by_default(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(strlen($user->id) === 36, 'User id should be a UUID string.');
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertFalse($user->isAdmin());

        $admin = User::factory()->admin()->create();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
    }

    public function test_product_belongs_to_category_and_exposes_status_enum(): void
    {
        $category = Category::query()->create([
            'name' => 'إلكترونيات',
            'slug' => 'electronics',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'جهاز تجريبي',
            'slug' => 'sample-device',
            'price' => 199.99,
            'status' => ProductStatus::Available,
        ]);

        $this->assertTrue($product->category->is($category));
        $this->assertSame(ProductStatus::Available, $product->status);
        $this->assertSame('199.99', $product->price);
        $this->assertCount(1, Product::query()->available()->get());
    }

    public function test_order_persists_historical_snapshots_with_separate_statuses(): void
    {
        $user = User::factory()->create();

        $order = Order::query()->create([
            'order_number' => 'MW-2026-000001',
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'currency_code' => 'USD',
            'customer_name_snapshot' => 'عميل تجريبي',
            'customer_phone_snapshot' => '0500000000',
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'subtotal' => 199.99,
            'delivery_fee' => 0,
            'total' => 199.99,
        ]);

        $this->assertTrue($order->user->is($user));
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame('USD', $order->currency_code);
        $this->assertSame('199.99', $order->total);
    }

    public function test_site_settings_are_a_single_row_seeded_with_safe_defaults(): void
    {
        $this->seed(SiteSettingSeeder::class);

        $settings = SiteSetting::query()->find(SiteSetting::SINGLETON_ID);

        $this->assertNotNull($settings);
        $this->assertSame('Matrix Wave', $settings->site_name);
        $this->assertSame('USD', $settings->currency_code);
        $this->assertSame(1, SiteSetting::query()->count());
    }
}
