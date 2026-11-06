<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_verified_admin_account(): void
    {
        $this->artisan('app:create-admin', [
            'email' => 'admin@example.com',
            '--name' => 'مدير الموقع',
            '--password' => 'strong-password-123',
        ])->assertSuccessful();

        $admin = User::query()->where('email', 'admin@example.com')->first();

        $this->assertNotNull($admin);
        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertNotNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('strong-password-123', $admin->password));
    }

    public function test_it_rejects_short_passwords(): void
    {
        $this->artisan('app:create-admin', [
            'email' => 'admin@example.com',
            '--password' => 'short',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_rejects_invalid_email_addresses(): void
    {
        $this->artisan('app:create-admin', [
            'email' => 'not-an-email',
            '--password' => 'strong-password-123',
        ])->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
