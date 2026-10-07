<?php

namespace App\Console\Commands;

use App\Services\InactivityService;
use Illuminate\Console\Command;

class DeactivateInactiveUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:deactivate-inactive
                            {--days= : Override inactivity threshold in days}
                            {--dry-run : Simulate execution without updating records}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically deactivate active accounts with no activity for 14 days and send warnings';

    public function handle(InactivityService $inactivityService): int
    {
        $daysOption = $this->option('days');
        $days = $daysOption !== null ? (int) $daysOption : (int) config('icoep.inactivity.days', 14);
        $isDryRun = (bool) $this->option('dry-run');

        $this->info("Running automated inactivity check (Threshold: {$days} days" . ($isDryRun ? ' [DRY-RUN]' : '') . ")...");

        // 1. Process deactivation sweep
        $results = $inactivityService->processInactivitySweep($days, $isDryRun);

        $this->line("Total active users examined: {$results['examined']}");

        if (!empty($results['skipped'])) {
            $this->warn("Skipped {$results['skipped_count']} dormant user(s) due to protection rules:");
            foreach ($results['skipped'] as $s) {
                $this->line("  ↳ [ID: {$s['user_id']}] {$s['name']} ({$s['email']}) — Reason: {$s['reason']}");
            }
        }

        if (!empty($results['deactivated'])) {
            $actionWord = $isDryRun ? 'Would deactivate' : 'Deactivated';
            $this->info("{$actionWord} {$results['deactivated_count']} dormant user(s):");
            foreach ($results['deactivated'] as $d) {
                $this->line("  ↳ [ID: {$d['user_id']}] {$d['name']} ({$d['email']}) — Last access: {$d['last_access']}");
            }
        } else {
            $this->info('No users required deactivation.');
        }

        // 2. Process warning notifications
        $warnDaysBefore = (int) config('icoep.inactivity.warn_days_before', 2);
        if ($warnDaysBefore > 0) {
            $warnedCount = $inactivityService->sendInactivityWarnings($warnDaysBefore, $isDryRun);
            if ($warnedCount > 0) {
                $warnWord = $isDryRun ? 'Would warn' : 'Warned';
                $this->line("{$warnWord} {$warnedCount} at-risk user(s) nearing dormancy.");
            }
        }

        $this->info($isDryRun ? 'Dry run complete. No records were modified.' : 'Inactivity check completed successfully.');

        return self::SUCCESS;
    }
}
