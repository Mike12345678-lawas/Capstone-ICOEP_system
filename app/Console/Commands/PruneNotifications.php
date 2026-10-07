<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Keeps the notifications table from flooding:
 *  1. Deletes READ notifications older than --read-days (default 30).
 *  2. Deletes ANY notification older than --max-days (default 90).
 *  3. Caps each user to the newest --keep notifications (default 100).
 */
class PruneNotifications extends Command
{
    protected $signature = 'notifications:prune
                            {--read-days=30 : Delete read notifications older than this many days}
                            {--max-days=90 : Delete all notifications older than this many days}
                            {--keep=100 : Maximum notifications kept per user}
                            {--dry-run : Show what would be deleted without deleting}';

    protected $description = 'Delete old notifications so user inboxes do not flood';

    public function handle(): int
    {
        $readDays = max(1, (int) $this->option('read-days'));
        $maxDays  = max($readDays, (int) $this->option('max-days'));
        $keep     = max(1, (int) $this->option('keep'));
        $dryRun   = (bool) $this->option('dry-run');

        $oldRead = Notification::where('is_read', true)
            ->where('created_at', '<', now()->subDays($readDays));
        $tooOld = Notification::where('created_at', '<', now()->subDays($maxDays));

        $readCount = (clone $oldRead)->count();
        $oldCount  = (clone $tooOld)->count();

        if (! $dryRun) {
            $oldRead->delete();
            $tooOld->delete();
        }

        // Per-user cap: delete everything beyond the newest $keep rows.
        $capCount = 0;
        $heavyUsers = Notification::select('user_id', DB::raw('COUNT(*) as total'))
            ->groupBy('user_id')
            ->having('total', '>', $keep)
            ->pluck('user_id');

        foreach ($heavyUsers as $userId) {
            $cutoffId = Notification::where('user_id', $userId)
                ->orderByDesc('id')
                ->skip($keep - 1)
                ->value('id');

            if (! $cutoffId) {
                continue;
            }

            $excess = Notification::where('user_id', $userId)->where('id', '<', $cutoffId);
            $capCount += (clone $excess)->count();

            if (! $dryRun) {
                $excess->delete();
            }
        }

        $prefix = $dryRun ? '[DRY RUN] Would delete' : 'Deleted';
        $this->info("{$prefix}: {$readCount} read (> {$readDays}d), {$oldCount} old (> {$maxDays}d), {$capCount} over per-user cap of {$keep}.");

        return self::SUCCESS;
    }
}
