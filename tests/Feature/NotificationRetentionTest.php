<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\WelcomeNotification;
use App\Services\Notifications\NotificationRetentionService;
use App\Services\Notifications\RegistrationNotifier;
use Carbon\CarbonInterface;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_fewer_than_ten_notifications_are_all_retained(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 5);

        $deleted = app(NotificationRetentionService::class)
            ->pruneForIdentity($user->getMorphClass(), (string) $user->id);

        $this->assertSame(0, $deleted);
        $this->assertSame(5, $user->notifications()->count());
    }

    public function test_exactly_ten_notifications_are_all_retained(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 10);

        $deleted = app(NotificationRetentionService::class)
            ->pruneForIdentity($user->getMorphClass(), (string) $user->id);

        $this->assertSame(0, $deleted);
        $this->assertSame(10, $user->notifications()->count());
    }

    public function test_more_than_ten_notifications_keep_only_the_newest_ten(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 12);

        $deleted = app(NotificationRetentionService::class)
            ->pruneForIdentity($user->getMorphClass(), (string) $user->id);

        $this->assertSame(2, $deleted);
        $this->assertSame(10, $user->notifications()->count());
        $this->assertDatabaseMissing('notifications', ['id' => $this->uuid(1)]);
        $this->assertDatabaseMissing('notifications', ['id' => $this->uuid(2)]);

        foreach (range(3, 12) as $index) {
            $this->assertDatabaseHas('notifications', ['id' => $this->uuid($index)]);
        }
    }

    public function test_read_and_unread_notifications_follow_the_same_newest_ten_rule(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 12, markEvenAsRead: true);

        app(NotificationRetentionService::class)
            ->pruneForIdentity($user->getMorphClass(), (string) $user->id);

        $this->assertSame(10, $user->notifications()->count());
        $this->assertDatabaseMissing('notifications', ['id' => $this->uuid(1)]);
        $this->assertDatabaseMissing('notifications', ['id' => $this->uuid(2)]);
        $this->assertSame(5, $user->notifications()->whereNotNull('read_at')->count());
        $this->assertSame(5, $user->notifications()->whereNull('read_at')->count());
    }

    public function test_another_users_notifications_are_never_affected(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->seedNotifications($first, 12);
        $this->seedNotifications($second, 6, idOffset: 100);

        app(NotificationRetentionService::class)
            ->pruneForIdentity($first->getMorphClass(), (string) $first->id);

        $this->assertSame(10, $first->notifications()->count());
        $this->assertSame(6, $second->notifications()->count());
    }

    public function test_different_notifiable_types_are_isolated(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 12);
        $this->seedNotifications($user, 12, notifiableType: 'App\\Models\\AnotherNotifiable', idOffset: 100);

        app(NotificationRetentionService::class)
            ->pruneForIdentity($user->getMorphClass(), (string) $user->id);

        $this->assertSame(10, DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->count());

        $this->assertSame(12, DB::table('notifications')
            ->where('notifiable_type', 'App\\Models\\AnotherNotifiable')
            ->where('notifiable_id', $user->id)
            ->count());
    }

    public function test_equal_timestamps_use_the_notification_id_as_a_deterministic_tie_breaker(): void
    {
        $user = User::factory()->create();
        $timestamp = now();

        foreach (range(1, 12) as $index) {
            $this->insertNotification($user, $this->uuid($index), $timestamp);
        }

        app(NotificationRetentionService::class)
            ->pruneForIdentity($user->getMorphClass(), (string) $user->id);

        $this->assertDatabaseMissing('notifications', ['id' => $this->uuid(1)]);
        $this->assertDatabaseMissing('notifications', ['id' => $this->uuid(2)]);

        foreach (range(3, 12) as $index) {
            $this->assertDatabaseHas('notifications', ['id' => $this->uuid($index)]);
        }
    }

    public function test_notification_creation_paths_enforce_the_limit(): void
    {
        $user = User::factory()->create();

        for ($index = 0; $index < 12; $index++) {
            $user->notify(new WelcomeNotification);
        }

        $this->assertSame(10, $user->notifications()->count());
        $this->assertSame(10, DB::table('notifications')
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->id)
            ->count());
    }

    public function test_registration_notifier_enforces_the_limit_for_the_customer(): void
    {
        User::factory()->admin()->create();
        $customer = User::factory()->create();
        $this->seedNotifications($customer, 10, notificationType: LoginNotification::class);

        app(RegistrationNotifier::class)->handle(new Registered($customer));

        $this->assertSame(10, $customer->notifications()->count());
        $this->assertSame(
            1,
            $customer->notifications()->where('type', WelcomeNotification::class)->count(),
        );
    }

    public function test_the_cleanup_command_supports_a_non_destructive_dry_run(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 12);

        $this->artisan('notifications:prune', ['--dry-run' => true])
            ->expectsOutputToContain('Would delete: 2')
            ->assertSuccessful();

        $this->assertSame(12, $user->notifications()->count());
    }

    public function test_the_cleanup_command_is_idempotent(): void
    {
        $user = User::factory()->create();
        $this->seedNotifications($user, 12);

        $this->artisan('notifications:prune')
            ->expectsOutputToContain('Deleted: 2')
            ->assertSuccessful();

        $this->assertSame(10, $user->notifications()->count());

        $this->artisan('notifications:prune')
            ->expectsOutputToContain('Deleted: 0')
            ->assertSuccessful();

        $this->assertSame(10, $user->notifications()->count());
    }

    public function test_the_cleanup_command_processes_multiple_identities_in_batches(): void
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $index => $user) {
            $this->seedNotifications($user, 12, idOffset: $index * 100);
        }

        $this->artisan('notifications:prune', ['--chunk' => 2])
            ->expectsOutputToContain('Identities scanned: 3')
            ->expectsOutputToContain('Deleted: 6')
            ->assertSuccessful();

        foreach ($users as $user) {
            $this->assertSame(10, $user->notifications()->count());
        }
    }

    /**
     * Seed notifications with increasing creation times; the highest index is
     * the newest notification.
     */
    private function seedNotifications(
        User $user,
        int $count,
        bool $markEvenAsRead = false,
        ?string $notifiableType = null,
        int $idOffset = 0,
        ?string $notificationType = null,
    ): void {
        $type = $notifiableType ?? $user->getMorphClass();

        foreach (range(1, $count) as $index) {
            $this->insertNotification(
                $user,
                $this->uuid($index + $idOffset),
                now()->subMinutes($count + 1 - $index),
                $markEvenAsRead && $index % 2 === 0 ? now() : null,
                $type,
                $notificationType,
            );
        }
    }

    private function insertNotification(
        User $user,
        string $id,
        CarbonInterface $createdAt,
        ?CarbonInterface $readAt = null,
        ?string $notifiableType = null,
        ?string $notificationType = null,
    ): void {
        DB::table('notifications')->insert([
            'id' => $id,
            'type' => $notificationType ?? WelcomeNotification::class,
            'notifiable_type' => $notifiableType ?? $user->getMorphClass(),
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'إشعار تجريبي']),
            'read_at' => $readAt,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function uuid(int $index): string
    {
        return sprintf('00000000-0000-4000-8000-%012d', $index);
    }
}
