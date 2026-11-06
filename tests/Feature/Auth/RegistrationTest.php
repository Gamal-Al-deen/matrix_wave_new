<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('إنشاء حساب')
            ->assertSee('الاسم الكامل')
            ->assertSee('_token', false);
    }

    public function test_a_customer_can_register_and_is_logged_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'عميل جديد',
            'email' => 'Customer@Example.com',
            'phone' => '0500000000',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
        ]);

        $response->assertRedirect(route('home'));

        $user = User::query()->where('email', 'customer@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame('عميل جديد', $user->name);
        $this->assertSame('0500000000', $user->phone);
        $this->assertSame(UserRole::Customer, $user->role);
        $this->assertTrue(Hash::check('strong-password-123', $user->password));
        $this->assertAuthenticatedAs($user);
    }

    public function test_role_cannot_be_injected_through_registration(): void
    {
        $this->post('/register', [
            'name' => 'مستخدم',
            'email' => 'user@example.com',
            'role' => 'admin',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
        ])->assertRedirect(route('home'));

        $this->assertSame(
            UserRole::Customer,
            User::query()->where('email', 'user@example.com')->firstOrFail()->role,
        );
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'name' => 'مستخدم',
            'email' => 'taken@example.com',
            'password' => 'strong-password-123',
            'password_confirmation' => 'strong-password-123',
        ])->assertSessionHasErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_must_be_confirmed_and_meet_minimum_length(): void
    {
        $this->post('/register', [
            'name' => 'مستخدم',
            'email' => 'user@example.com',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['password']);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_required_fields_are_validated(): void
    {
        $this->post('/register', [])
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_authenticated_users_are_redirected_away_from_registration(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/register')
            ->assertRedirect('/');
    }
}
