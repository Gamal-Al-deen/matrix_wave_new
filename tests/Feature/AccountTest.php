<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_customer_login(): void
    {
        $this->get(route('account'))->assertRedirect(route('login'));
        $this->patch(route('account.update'), [])->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_customers_can_view_and_update_their_own_profile_data(): void
    {
        $user = User::factory()->create([
            'name' => 'الاسم القديم',
            'email' => 'fixed@example.com',
            'phone' => '771234567',
            'default_address' => 'العنوان القديم',
        ]);

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('حسابي')
            ->assertSee('الاسم القديم')
            ->assertSee('fixed@example.com')
            ->assertSee('العنوان القديم')
            ->assertSee('البريد الإلكتروني وكلمة المرور غير قابلين للتعديل');

        $this->actingAs($user)
            ->patch(route('account.update'), [
                'name' => '  الاسم الجديد  ',
                'phone' => ' ٧٨١٢٣٤٥٦٧ ',
                'default_address' => '  الرياض، حي النخيل  ',
                'email' => 'changed@example.com',
                'password' => 'new-password',
                'role' => UserRole::Admin->value,
            ])
            ->assertRedirect(route('account'))
            ->assertSessionHas('status');

        $user->refresh();

        $this->assertSame('الاسم الجديد', $user->name);
        $this->assertSame('781234567', $user->phone);
        $this->assertSame('الرياض، حي النخيل', $user->default_address);
        $this->assertSame('fixed@example.com', $user->email);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_profile_update_preserves_an_unchanged_legacy_phone_number(): void
    {
        $user = User::factory()->create([
            'name' => 'الاسم القديم',
            'phone' => '+9647701234567',
        ]);

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertSee('الرقم المحفوظ حاليًا بصيغة قديمة');

        $this->actingAs($user)
            ->patch(route('account.update'), [
                'name' => 'الاسم الجديد',
                'phone' => '+9647701234567',
                'default_address' => null,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame('+9647701234567', $user->refresh()->phone);
    }

    public function test_profile_update_rejects_a_modified_legacy_phone_number(): void
    {
        $user = User::factory()->create([
            'phone' => '+9647701234567',
        ]);

        $this->actingAs($user)
            ->patch(route('account.update'), [
                'name' => 'اسم محفوظ',
                'phone' => '+9647701234568',
                'default_address' => null,
            ])
            ->assertSessionHasErrors('phone');

        $this->assertSame('+9647701234567', $user->refresh()->phone);
    }

    public function test_profile_update_validates_contact_and_address_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'الاسم المحفوظ',
            'phone' => '771234567',
            'default_address' => 'العنوان المحفوظ',
        ]);

        $this->actingAs($user)
            ->patch(route('account.update'), [
                'name' => '',
                'phone' => 'not-a-phone',
                'default_address' => str_repeat('ن', 1001),
            ])
            ->assertSessionHasErrors([
                'name',
                'phone' => 'يرجى إدخال رقم هاتف يمني صحيح مكوّن من 9 أرقام ويبدأ بأحد الأرقام التالية: 70 أو 71 أو 73 أو 77 أو 78.',
                'default_address',
            ]);

        $user->refresh();

        $this->assertSame('الاسم المحفوظ', $user->name);
        $this->assertSame('771234567', $user->phone);
        $this->assertSame('العنوان المحفوظ', $user->default_address);
    }

    public function test_optional_profile_fields_can_be_cleared(): void
    {
        $user = User::factory()->create([
            'phone' => '771234567',
            'default_address' => 'عنوان قديم',
        ]);

        $this->actingAs($user)
            ->patch(route('account.update'), [
                'name' => 'اسم محفوظ',
                'phone' => '',
                'default_address' => '',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNull($user->phone);
        $this->assertNull($user->default_address);
    }

    public function test_profile_values_are_escaped_in_the_account_page(): void
    {
        $user = User::factory()->create([
            'name' => '<script>alert(1)</script>',
        ]);

        $this->actingAs($user)
            ->get(route('account'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }
}
