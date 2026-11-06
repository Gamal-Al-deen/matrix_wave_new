<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_visibility_is_limited_to_the_owner_and_administrators(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->orderFor($owner);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $order));
        $this->assertTrue(Gate::forUser($owner)->allows('viewInvoice', $order));
        $this->assertFalse(Gate::forUser($other)->allows('view', $order));
        $this->assertFalse(Gate::forUser($other)->allows('viewInvoice', $order));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $order));
        $this->assertTrue(Gate::forUser($admin)->allows('viewInvoice', $order));
    }

    public function test_only_administrators_may_change_order_or_payment_status(): void
    {
        $owner = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $order = $this->orderFor($owner);

        $this->assertFalse(Gate::forUser($owner)->allows('updateStatus', $order));
        $this->assertFalse(Gate::forUser($owner)->allows('updatePaymentStatus', $order));
        $this->assertTrue(Gate::forUser($admin)->allows('updateStatus', $order));
        $this->assertTrue(Gate::forUser($admin)->allows('updatePaymentStatus', $order));
    }

    public function test_account_data_is_visible_only_to_its_owner_and_administrators(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->assertTrue(Gate::forUser($owner)->allows('view', $owner));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $owner));
        $this->assertFalse(Gate::forUser($other)->allows('view', $owner));
        $this->assertFalse(Gate::forUser($other)->allows('update', $owner));
        $this->assertFalse(Gate::forUser($owner)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $owner));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $owner));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
    }

    private function orderFor(User $user): Order
    {
        return Order::query()->create([
            'order_number' => 'MW-TEST-'.uniqid(),
            'user_id' => $user->id,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Unpaid,
            'currency_code' => 'USD',
            'customer_name_snapshot' => $user->name,
            'customer_phone_snapshot' => '0500000000',
            'delivery_address_snapshot' => 'عنوان تجريبي',
            'subtotal' => 10,
            'delivery_fee' => 0,
            'total' => 10,
        ]);
    }
}
