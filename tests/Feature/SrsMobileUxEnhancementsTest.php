<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Covers the functional enhancements that ship with the mobile UX SRS:
 * the floating WhatsApp button (FR-05), the general administration header
 * link (FR-07.A), the session-based new-orders indicator (FR-07.B), the
 * administrator account page (FR-07.C) and the fixed developer WhatsApp
 * credit in the footer (FR-08).
 */
class SrsMobileUxEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    // FR-05: floating WhatsApp button on the homepage.

    public function test_home_shows_the_floating_whatsapp_button_when_the_admin_number_is_configured(): void
    {
        $this->configureWhatsApp('+967 71 234 5678');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://wa.me/967712345678', false)
            ->assertSee('تواصل معنا عبر واتساب');
    }

    public function test_home_hides_the_floating_whatsapp_button_when_no_number_is_configured(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('تواصل معنا عبر واتساب');
    }

    // FR-07.A: the general administration link in the storefront header.

    public function test_admin_sees_the_general_administration_link_in_the_header(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('home'))
            ->assertOk()
            ->assertSee('الإدارة العامة')
            ->assertSee('href="'.route('admin.dashboard').'"', false);
    }

    public function test_customer_sees_the_account_link_and_not_the_admin_link(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->get(route('home'))
            ->assertOk()
            ->assertSee('حسابي')
            ->assertDontSee('الإدارة العامة');
    }

    public function test_guest_does_not_see_any_administration_link(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('الإدارة العامة');
    }

    // FR-07.B: the session-based new-orders indicator.

    public function test_new_orders_is_zero_until_the_admin_opens_the_orders_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->makeOrder();

        $this->actingAs($admin)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('new_orders', 0);
    }

    public function test_pending_orders_created_before_the_visit_are_not_treated_as_new(): void
    {
        $admin = User::factory()->admin()->create();

        Carbon::setTestNow('2026-02-01 09:00:00');
        $this->makeOrder();

        // Opening the orders list records the baseline for this session.
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();

        Carbon::setTestNow('2026-02-01 09:10:00');

        $this->actingAs($admin)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('new_orders', 0);

        // The order still exists; it is only excluded from the "new" counter.
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_opening_the_orders_page_clears_the_badge_and_later_orders_are_counted(): void
    {
        $admin = User::factory()->admin()->create();

        Carbon::setTestNow('2026-03-01 08:00:00');
        $this->makeOrder();

        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();

        Carbon::setTestNow('2026-03-01 08:05:00');
        $this->makeOrder();
        $this->makeOrder();

        $this->actingAs($admin)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('new_orders', 2);

        Carbon::setTestNow('2026-03-01 08:06:00');
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();

        $this->actingAs($admin)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('new_orders', 0);
    }

    public function test_new_orders_count_is_not_exposed_to_customers(): void
    {
        $customer = User::factory()->create();

        $this->actingAs($customer)->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('new_orders', null);
    }

    public function test_admin_layout_renders_the_new_orders_badge_element(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-admin-new-orders-badge', false);
    }

    public function test_admin_layout_orders_badge_tracks_new_orders_and_hides_after_viewing(): void
    {
        $admin = User::factory()->admin()->create();

        // Visiting the orders page establishes the "viewed" baseline.
        Carbon::setTestNow('2026-04-01 09:00:00');
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();

        // An order placed afterwards counts as new.
        Carbon::setTestNow('2026-04-01 09:05:00');
        $this->makeOrder();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        [$classes, $text] = $this->ordersBadge($html);

        $this->assertSame('1', $text);
        $this->assertNotContains('hidden', $classes);

        // Re-opening the orders page clears the counter again.
        Carbon::setTestNow('2026-04-01 09:10:00');
        $this->actingAs($admin)->get(route('admin.orders.index'))->assertOk();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        [$classes, $text] = $this->ordersBadge($html);

        $this->assertSame('0', $text);
        $this->assertContains('hidden', $classes);
    }

    public function test_admin_navigation_items_render_an_icon_beside_their_label(): void
    {
        $admin = User::factory()->admin()->create();

        $html = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->getContent();

        preg_match('/<nav\b[^>]*aria-label="أقسام الإدارة"[^>]*>(.*?)<\/nav>/su', $html, $matches);

        $this->assertNotEmpty($matches, 'The admin navigation was not rendered.');

        // Every navigation entry pairs an inline-SVG icon with a visible label.
        $this->assertSame(11, substr_count($matches[1], '<svg'));
        $this->assertSame(11, substr_count($matches[1], 'class="min-w-0 flex-1 truncate"'));
        $this->assertStringContainsString('لوحة التحكم', $matches[1]);
        $this->assertStringContainsString('الطلبات', $matches[1]);
    }

    // FR-07.C: the administrator account page.

    public function test_guests_are_redirected_from_the_admin_account_page(): void
    {
        $this->get(route('admin.account.edit'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_customers_cannot_open_the_admin_account_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.account.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_view_the_account_page_without_exposing_the_stored_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.account.edit'))
            ->assertOk()
            ->assertSee('حدّث بريد الدخول وكلمة المرور')
            ->assertSee($admin->email)
            ->assertSee('action="'.route('admin.account.update').'"', false)
            ->assertDontSee($admin->getAuthPassword(), false);
    }

    public function test_admin_can_update_email_and_password_with_hashing(): void
    {
        $admin = User::factory()->admin()->create();
        $originalHash = $admin->password;

        $this->actingAs($admin)->put(route('admin.account.update'), [
            'current_password' => 'password',
            'email' => 'new-admin@example.com',
            'password' => 'new-strong-password',
            'password_confirmation' => 'new-strong-password',
        ])->assertRedirect(route('admin.account.edit'));

        $admin->refresh();

        $this->assertSame('new-admin@example.com', $admin->email);
        $this->assertNotSame($originalHash, $admin->password);
        $this->assertTrue(Hash::check('new-strong-password', $admin->password));
    }

    public function test_leaving_the_password_field_empty_keeps_the_current_password(): void
    {
        $admin = User::factory()->admin()->create();
        $originalHash = $admin->password;

        $this->actingAs($admin)->put(route('admin.account.update'), [
            'current_password' => 'password',
            'email' => 'kept@example.com',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.account.edit'));

        $admin->refresh();

        $this->assertSame('kept@example.com', $admin->email);
        $this->assertSame($originalHash, $admin->password);
    }

    public function test_wrong_current_password_is_rejected_and_changes_nothing(): void
    {
        $admin = User::factory()->admin()->create();
        $originalEmail = $admin->email;
        $originalHash = $admin->password;

        $this->actingAs($admin)->put(route('admin.account.update'), [
            'current_password' => 'wrong-password',
            'email' => 'changed@example.com',
        ])->assertSessionHasErrors('current_password');

        $admin->refresh();

        $this->assertSame($originalEmail, $admin->email);
        $this->assertSame($originalHash, $admin->password);
    }

    public function test_admin_account_email_must_stay_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $admin = User::factory()->admin()->create();
        $originalEmail = $admin->email;

        $this->actingAs($admin)->put(route('admin.account.update'), [
            'current_password' => 'password',
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');

        $this->assertSame($originalEmail, $admin->fresh()->email);
    }

    // FR-08: the fixed developer WhatsApp credit in the footer.

    public function test_footer_links_to_the_fixed_developer_whatsapp_number(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://wa.me/967770569805', false)
            ->assertSee('770569805');
    }

    public function test_developer_number_stays_fixed_and_independent_from_the_admin_number(): void
    {
        $this->configureWhatsApp('+967 71 234 5678');

        $html = $this->get(route('home'))->assertOk()->getContent();

        // The configurable admin number powers the storefront contact button.
        $this->assertStringContainsString('https://wa.me/967712345678?text=', $html);
        $this->assertSame(1, substr_count($html, 'wa.me/967712345678'));

        // The developer credit keeps its own fixed number regardless of settings.
        $this->assertStringContainsString('https://wa.me/967770569805?text=', $html);
        $this->assertSame(1, substr_count($html, 'wa.me/967770569805'));

        // The two numbers are used in separate places and never merged.
        $this->assertStringNotContainsString('wa.me/967712345678'.'967770569805', $html);
    }

    /**
     * Extract the class tokens and inner text of the orders counter badge.
     *
     * @return array{0: list<string>, 1: string}
     */
    private function ordersBadge(string $html): array
    {
        preg_match('/<span\b[^>]*data-admin-new-orders-badge[^>]*>(.*?)<\/span>/s', $html, $matches);

        $this->assertNotEmpty($matches, 'The orders counter badge was not rendered.');

        preg_match('/class="([^"]*)"/', $matches[0], $classMatch);

        $classes = preg_split('/\s+/', trim($classMatch[1] ?? ''), -1, PREG_SPLIT_NO_EMPTY);

        return [$classes, trim($matches[1])];
    }

    private function makeOrder(array $overrides = []): Order
    {
        $customer = User::factory()->create();

        return Order::query()->create(array_merge([
            'order_number' => 'MW-'.Str::ulid(),
            'user_id' => $customer->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'wallet_id' => null,
            'currency_code' => 'USD',
            'customer_name_snapshot' => 'عميل تجريبي',
            'customer_phone_snapshot' => '771234567',
            'customer_email_snapshot' => null,
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'notes' => null,
            'subtotal' => '25.50',
            'delivery_fee' => '0.00',
            'total' => '25.50',
        ], $overrides));
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
