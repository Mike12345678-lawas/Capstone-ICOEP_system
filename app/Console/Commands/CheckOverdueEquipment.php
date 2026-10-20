<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\EquipmentRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * CheckOverdueEquipment
 *
 * Runs daily via the Laravel Scheduler.
 * Finds all borrowed equipment where expected_return_date < today,
 * sends a system notification to the borrower, and optionally emails them.
 */
class CheckOverdueEquipment extends Command
{
    protected $signature = 'equipment:check-overdue
                            {--dry-run : List overdue items without sending notifications}';

    protected $description = 'Send overdue alerts for equipment not returned by the expected return date';

    public function handle(): int
    {
        $today   = now()->startOfDay();
        $isDry   = $this->option('dry-run');

        // Find all borrowed requests past their expected return date
        $overdue = EquipmentRequest::with(['user', 'equipment'])
            ->where('status', 'borrowed')
            ->where('expected_return_date', '<', $today)
            ->get();

        if ($overdue->isEmpty()) {
            $this->info('No overdue equipment found.');
            return self::SUCCESS;
        }

        $this->info("Found {$overdue->count()} overdue equipment request(s).");

        foreach ($overdue as $request) {
            $daysOverdue = $today->diffInDays($request->expected_return_date);
            $equipment   = $request->equipment;
            $borrower    = $request->user;

            $this->line("  ↳ [{$request->id}] {$equipment->name} — {$borrower->full_name} ({$daysOverdue}d overdue)");

            if ($isDry) {
                continue;
            }

            // ─── System Notification (in-app) ───────────────────────────
            // Only send if one hasn't been sent today for this request
            $alreadyNotified = Notification::where('user_id', $borrower->id)
                ->where('link', '/equipment/' . $equipment->id)
                ->whereDate('created_at', now()->toDateString())
                ->where('title', 'like', '%Overdue%')
                ->exists();

            if (!$alreadyNotified) {
                Notification::send(
                    $borrower->id,
                    'Overdue Equipment Alert',
                    "Your borrowed item \"{$equipment->name}\" was due on " .
                        $request->expected_return_date->format('M d, Y') .
                        " ({$daysOverdue} day(s) overdue). Please return it immediately.",
                    'error',
                    '/equipment/' . $equipment->id
                );
                $this->line("     ✉  Notification & email dispatched to {$borrower->email}");
            }

            // ─── Audit Log ───────────────────────────────────────────────
            AuditLog::log(
                'OVERDUE_ALERT_SENT',
                'equipment_requests',
                $request->id
            );
        }

        // Also notify all Admins with a summary
        if (!$isDry && $overdue->count() > 0) {
            $adminUsers = User::whereHas('role', fn($q) => $q->where('name', 'Administrator'))
                ->where('status', 'active')
                ->get();

            foreach ($adminUsers as $admin) {
                Notification::send(
                    $admin->id,
                    "{$overdue->count()} Overdue Equipment Item(s)",
                    "There are {$overdue->count()} borrowed equipment item(s) past their return date. Review them in the Equipment Management section.",
                    'warning',
                    '/dashboard?section=manage-equipment&status=borrowed'
                );
            }
        }

        $this->info($isDry ? 'Dry run complete — no notifications sent.' : "Alerts sent for {$overdue->count()} overdue item(s).");

        return self::SUCCESS;
    }
}
