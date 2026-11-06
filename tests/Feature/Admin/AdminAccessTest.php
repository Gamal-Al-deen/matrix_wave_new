<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_admin_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_customers_receive_a_forbidden_response(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_administrators_can_open_the_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin')
            ->assertOk()
            ->assertSee('لوحة التحكم');
    }

    public function test_the_admin_login_screen_is_separate_from_the_customer_login(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('دخول لوحة الإدارة')
            ->assertSee('_token', false);
    }

    public function test_customer_credentials_are_rejected_on_the_admin_login(): void
    {
        $customer = User::factory()->create(['email' => 'customer@example.com']);

        $this->post('/admin/login', [
            'email' => 'customer@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_administrators_can_log_in_through_the_admin_login(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_authenticated_administrators_are_redirected_away_from_the_admin_login(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_links_are_not_exposed_to_guests_or_customers(): void
    {
        $this->get('/')->assertDontSee(route('admin.login'));

        $this->actingAs(User::factory()->create())
            ->get('/')
            ->assertDontSee(route('admin.dashboard'));
    }

    public function test_administrators_can_log_out_from_the_admin_area(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }
}
