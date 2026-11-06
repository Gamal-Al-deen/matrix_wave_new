<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WalletManagementTest extends TestCase
{
    use RefreshDatabase;

    private const ONE_PIXEL_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('supabase');
    }

    public function test_guests_are_redirected_from_wallet_management(): void
    {
        $this->get(route('admin.wallets.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customers_cannot_manage_wallets(): void
    {
        $wallet = $this->makeWallet();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.wallets.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create())
            ->post(route('admin.wallets.icon.store', $wallet), [
                'icon' => $this->png('wallet.png'),
            ])
            ->assertForbidden();
    }

    public function test_administrators_can_view_wallets_and_their_forms(): void
    {
        $wallet = $this->makeWallet([
            'name' => 'محفظة بنكية',
            'account_identifier' => 'wallet@example.com',
            'sort_order' => 3,
        ]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.wallets.index'))
            ->assertOk()
            ->assertSee('محفظة بنكية')
            ->assertSee('wallet@example.com')
            ->assertSee('3');

        $this->actingAs($admin)
            ->get(route('admin.wallets.create'))
            ->assertOk()
            ->assertSee('إضافة محفظة')
            ->assertSee('بعد حفظ المحفظة ستنتقل إلى صفحة التعديل');

        $this->actingAs($admin)
            ->get(route('admin.wallets.edit', $wallet))
            ->assertOk()
            ->assertSee('محفظة بنكية')
            ->assertSee('أيقونة المحفظة')
            ->assertSee('لا توجد أيقونة')
            ->assertSee('الطلب يحفظ معرف المحفظة فقط');
    }

    public function test_administrators_can_create_an_active_wallet(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.wallets.store'), [
                'name' => '  محفظة جديدة  ',
                'account_identifier' => '  +966500000000  ',
                'sort_order' => '2',
                'is_active' => '1',
            ]);

        $wallet = Wallet::query()->sole();

        $response->assertRedirect(route('admin.wallets.edit', $wallet))
            ->assertSessionHas('status');

        $this->assertSame('محفظة جديدة', $wallet->name);
        $this->assertSame('+966500000000', $wallet->account_identifier);
        $this->assertSame(2, $wallet->sort_order);
        $this->assertTrue($wallet->is_active);
    }

    public function test_wallets_are_inactive_by_default_when_the_checkbox_is_not_selected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.wallets.store'), [
                'name' => 'محفظة معطلة',
                'account_identifier' => 'wallet-2',
                'sort_order' => '0',
            ])
            ->assertRedirect();

        $this->assertFalse(Wallet::query()->sole()->is_active);
    }

    public function test_wallet_validation_rejects_invalid_values(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.wallets.store'), [
                'name' => '',
                'account_identifier' => '',
                'sort_order' => '-1',
            ])
            ->assertSessionHasErrors(['name', 'account_identifier', 'sort_order']);

        $this->assertDatabaseCount('wallets', 0);
    }

    public function test_administrators_can_update_a_wallet(): void
    {
        $wallet = $this->makeWallet();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.wallets.update', $wallet), [
                'name' => 'اسم محدّث',
                'account_identifier' => 'updated-identifier',
                'sort_order' => '7',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.wallets.edit', $wallet));

        $wallet->refresh();

        $this->assertSame('اسم محدّث', $wallet->name);
        $this->assertSame('updated-identifier', $wallet->account_identifier);
        $this->assertSame(7, $wallet->sort_order);
        $this->assertTrue($wallet->is_active);
    }

    public function test_administrators_can_toggle_wallet_status(): void
    {
        $wallet = $this->makeWallet(['is_active' => true]);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.wallets.status', $wallet))
            ->assertRedirect();

        $this->assertFalse($wallet->refresh()->is_active);
    }

    public function test_wallets_linked_to_orders_cannot_be_deleted(): void
    {
        $wallet = $this->makeWallet();
        $order = $this->makeOrder($wallet);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.wallets.destroy', $wallet))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
        $this->assertSame($wallet->id, $order->fresh()->wallet_id);
    }

    public function test_unlinked_wallets_are_deleted_with_their_icon(): void
    {
        $wallet = $this->makeWallet();
        $path = $this->attachIcon($wallet);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.wallets.destroy', $wallet))
            ->assertRedirect(route('admin.wallets.index'));

        $this->assertDatabaseMissing('wallets', ['id' => $wallet->id]);
        Storage::disk('supabase')->assertMissing($path);
    }

    public function test_administrators_can_upload_a_wallet_icon(): void
    {
        $wallet = $this->makeWallet();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.wallets.icon.store', $wallet), [
                'icon' => $this->png('wallet.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $wallet->refresh();

        $this->assertNotEmpty($wallet->icon_storage_path);
        $this->assertNotEmpty($wallet->icon_public_url);
        $this->assertStringStartsWith('wallets/'.$wallet->id.'/', $wallet->icon_storage_path);
        Storage::disk('supabase')->assertExists($wallet->icon_storage_path);
    }

    public function test_replacing_a_wallet_icon_removes_the_previous_file(): void
    {
        $wallet = $this->makeWallet();
        $oldPath = $this->attachIcon($wallet);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->post(route('admin.wallets.icon.store', $wallet), [
                'icon' => $this->png('replacement.png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $wallet->refresh();

        $this->assertNotSame($oldPath, $wallet->icon_storage_path);
        Storage::disk('supabase')->assertMissing($oldPath);
        Storage::disk('supabase')->assertExists($wallet->icon_storage_path);
    }

    public function test_administrators_can_remove_a_wallet_icon_without_deleting_the_wallet(): void
    {
        $wallet = $this->makeWallet();
        $path = $this->attachIcon($wallet);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.wallets.icon.destroy', $wallet))
            ->assertRedirect()
            ->assertSessionHas('status');

        $wallet->refresh();

        $this->assertNull($wallet->icon_storage_path);
        $this->assertNull($wallet->icon_public_url);
        $this->assertDatabaseHas('wallets', ['id' => $wallet->id]);
        Storage::disk('supabase')->assertMissing($path);
    }

    public function test_invalid_wallet_icon_files_are_rejected(): void
    {
        $wallet = $this->makeWallet();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.wallets.icon.store', $wallet), [
                'icon' => UploadedFile::fake()->createWithContent('notes.txt', 'not an image'),
            ])
            ->assertSessionHasErrors('icon');

        $this->assertNull($wallet->fresh()->icon_storage_path);
    }

    private function png(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode(self::ONE_PIXEL_PNG),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeWallet(array $attributes = []): Wallet
    {
        return Wallet::query()->create(array_merge([
            'name' => 'محفظة تجريبية',
            'account_identifier' => 'wallet@example.com',
            'icon_storage_path' => null,
            'icon_public_url' => null,
            'is_active' => true,
            'sort_order' => 0,
        ], $attributes));
    }

    private function attachIcon(Wallet $wallet): string
    {
        $path = 'wallets/'.$wallet->id.'/'.uniqid().'.png';

        Storage::disk('supabase')->put($path, base64_decode(self::ONE_PIXEL_PNG));

        $wallet->update([
            'icon_storage_path' => $path,
            'icon_public_url' => 'https://example.test/'.$path,
        ]);

        return $path;
    }

    private function makeOrder(Wallet $wallet): Order
    {
        $user = User::factory()->create();

        return Order::query()->create([
            'order_number' => 'MW-TEST-'.uniqid(),
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'wallet_id' => $wallet->id,
            'currency_code' => 'USD',
            'customer_name_snapshot' => $user->name,
            'customer_phone_snapshot' => '0500000000',
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'subtotal' => '10.00',
            'delivery_fee' => '0.00',
            'total' => '10.00',
        ]);
    }
}
