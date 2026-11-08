<?php

namespace App\Listeners;

use App\Services\Notifications\NotificationRetentionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Applies the latest-ten retention policy after every persisted notification.
 *
 * Listening to NotificationSent covers every notify() call in the application,
 * regardless of which service or controller created the notification.
 */
class PruneNotifications
{
    public function __construct(
        private readonly NotificationRetentionService $retention,
    ) {}

    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        $notifiable = $event->notifiable;

        if (! $notifiable instanceof Model || $notifiable->getKey() === null) {
            return;
        }

        try {
            $this->retention->pruneFor($notifiable);
        } catch (Throwable $exception) {
            // Notification delivery must keep working even if cleanup fails;
            // the notifications:prune command can repair the backlog later.
            Log::error('Failed to enforce notification retention.', [
                'notifiable_type' => $notifiable->getMorphClass(),
                'notifiable_id' => (string) $notifiable->getKey(),
                'exception' => $exception::class,
            ]);
        }
    }
}
