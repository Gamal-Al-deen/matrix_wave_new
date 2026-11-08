<?php

namespace App\Console\Commands;

use App\Services\Notifications\NotificationRetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Removes notifications that exceed the latest-ten policy for every notifiable
 * identity already stored in the database.
 */
class PruneNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune
                            {--dry-run : Report how many notifications would be deleted without deleting any}
                            {--chunk=500 : Number of notifiable identities to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'حذف الإشعارات الأقدم والاحتفاظ بأحدث 10 إشعارات لكل مستخدم';

    /**
     * Execute the console command.
     */
    public function handle(NotificationRetentionService $retention): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $identities = 0;
        $affected = 0;
        $errors = 0;

        DB::table('notifications')
            ->select('notifiable_type', 'notifiable_id')
            ->distinct()
            ->orderBy('notifiable_type')
            ->orderBy('notifiable_id')
            ->chunk($chunk, function ($rows) use ($retention, $dryRun, &$identities, &$affected, &$errors): void {
                foreach ($rows as $row) {
                    $identities++;

                    try {
                        $affected += $dryRun
                            ? $retention->excessCount($row->notifiable_type, $row->notifiable_id)
                            : $retention->pruneForIdentity($row->notifiable_type, $row->notifiable_id);
                    } catch (Throwable $exception) {
                        $errors++;

                        Log::error('Notification retention cleanup failed for one identity.', [
                            'notifiable_type' => $row->notifiable_type,
                            'notifiable_id' => $row->notifiable_id,
                            'exception' => $exception::class,
                        ]);
                    }
                }
            });

        $this->line('Identities scanned: '.$identities);
        $this->line($dryRun ? 'Would delete: '.$affected : 'Deleted: '.$affected);

        if ($errors > 0) {
            $this->warn('Some identities could not be processed. Check the application log.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->info('Dry run complete. No notifications were deleted.');
        } else {
            $this->info('Notification retention cleanup complete.');
        }

        return self::SUCCESS;
    }
}
