<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\LoginNotification;
use App\Notifications\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Opening the notifications page must clear the unread badge for the current
 * user only, leaving every other account untouched.
 */
class NotificationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_the_notifications_page_marks_the_users_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $user->notify(new WelcomeNotification);
        $user->notify(new LoginNotification);

        $this->assertSame(2, $user->unreadNotifications()->count());

        $response = $this->actingAs($user)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertDontSee('عدد الإشعارات غير المقروءة');
        $response->assertSee('تسجيل دخول جديد');

        $this->assertSame(0, $user->fresh()->unreadNotifications()->count());
        $this->assertSame(2, $user->notifications()->whereNotNull('read_at')->count());

        $html = $response->getContent();

        preg_match_all('/<span\b[^>]*data-notification-badge[^>]*>/su', $html, $matches);

        $this->assertNotEmpty($matches[0]);

        foreach ($matches[0] as $badgeTag) {
            $this->assertStringContainsString('hidden', $badgeTag);
        }
    }

    public function test_opening_the_notifications_page_never_reads_another_users_notifications(): void
    {
        $viewer = User::factory()->create();
        $otherUser = User::factory()->create();
        $otherUser->notify(new WelcomeNotification);

        $this->actingAs($viewer)->get(route('notifications.index'))->assertOk();

        $this->assertSame(1, $otherUser->fresh()->unreadNotifications()->count());
        $this->assertSame(0, $viewer->fresh()->unreadNotifications()->count());
    }

    public function test_poll_returns_zero_unread_after_the_notifications_page_was_opened(): void
    {
        $user = User::factory()->create();
        $user->notify(new WelcomeNotification);

        $this->actingAs($user)->get(route('notifications.index'))->assertOk();

        $this->actingAs($user)
            ->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('unread_count', 0)
            ->assertJsonCount(0, 'notifications');
    }
}
