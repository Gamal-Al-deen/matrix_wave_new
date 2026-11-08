<?php

namespace App\Services\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Enforces the per-notifiable notification retention policy.
 *
 * Each notifiable entity keeps only its newest notifications. Ordering is
 * deterministic: created_at first, then the UUID as the tie-breaker.
 */
class NotificationRetentionService
{
    /**
     * Number of notifications retained for each notifiable entity.
     */
    public const KEEP = 10;

    /**
     * Prune the given notifiable and serialize concurrent prunes for it.
     *
     * The row lock keeps two simultaneous notification deliveries from both
     * keeping the same tenth row and leaving an extra notification behind on
     * databases that support SELECT ... FOR UPDATE. SQLite ignores the lock,
     * which is acceptable for the isolated test database.
     */
    public function pruneFor(Model $notifiable): int
    {
        if ($notifiable->getKey() === null) {
            return 0;
        }

        return DB::transaction(function () use ($notifiable): int {
            DB::table($notifiable->getTable())
                ->where($notifiable->getKeyName(), $notifiable->getKey())
                ->lockForUpdate()
                ->first();

            return $this->pruneForIdentity(
                $notifiable->getMorphClass(),
                (string) $notifiable->getKey(),
            );
        });
    }

    /**
     * Permanently delete notifications older than the newest KEEP rows for one
     * polymorphic identity.
     */
    public function pruneForIdentity(string $notifiableType, string $notifiableId): int
    {
        $keepIds = DB::table('notifications')
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::KEEP)
            ->pluck('id')
            ->all();

        if (count($keepIds) < self::KEEP) {
            return 0;
        }

        return DB::table('notifications')
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    /**
     * Count how many rows exceed the retention limit for one identity.
     */
    public function excessCount(string $notifiableType, string $notifiableId): int
    {
        $count = DB::table('notifications')
            ->where('notifiable_type', $notifiableType)
            ->where('notifiable_id', $notifiableId)
            ->count();

        return max(0, $count - self::KEEP);
    }
}
