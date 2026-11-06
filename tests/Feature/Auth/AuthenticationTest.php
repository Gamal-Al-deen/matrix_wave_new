<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('تسجيل الدخول')
            ->assertSee('البريد الإلكتروني')
            ->assertSee('_token', false);
    }

    public function test_customers_can_authenticate_and_the_session_is_regenerated(): void
    {
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->get('/login');
        $previousSessionId = session()->getId();

        $this->post('/login', [
            'email' => 'CUSTOMER@example.com',
            'password' => 'password',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_invalid_credentials_return_a_generic_error(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        $this->from('/login')
            ->post('/login', [
                'email' => 'customer@example.com',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors(['email' => trans('auth.failed')]);

        $this->assertGuest();
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        User::factory()->create(['email' => 'customer@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', [
                'email' => 'customer@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('محاولات', (string) session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_customers_can_logout_and_the_session_is_invalidated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $tokenBeforeLogout = session()->token();

        $this->post('/logout')->assertRedirect(route('home'));

        $this->assertGuest();
        $this->assertNotSame($tokenBeforeLogout, session()->token());
    }

    public function test_unauthenticated_requests_are_redirected_to_login_and_return_to_the_intended_page(): void
    {
        Route::middleware('auth')->get('/checkout-test', fn () => 'checkout')->name('checkout.test');

        $user = User::factory()->create(['email' => 'customer@example.com']);

        $this->get('/checkout-test')->assertRedirect(route('login'));

        $this->post('/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertRedirect('/checkout-test');

        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/login')
            ->assertRedirect('/');
    }
}
