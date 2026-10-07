<?php

use App\Console\Commands\CheckOverdueEquipment;
use App\Console\Commands\DeactivateInactiveUsers;
use App\Console\Commands\ExpirePendingRequests;
use App\Console\Commands\SendAutomatedReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * ─── ICOEP Scheduled Tasks ────────────────────────────────────────────────
 *
 * To activate the scheduler, add this single cron entry on your server:
 *   * * * * * php /path-to-your-project/artisan schedule:run >> /dev/null 2>&1
 *
 * On Windows (XAMPP) you can run: php artisan schedule:work
 */

// Daily overdue equipment check — runs every day at 8:00 AM
Schedule::command(CheckOverdueEquipment::class)
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/overdue_checks.log'))
    ->description('Daily check and alert for overdue borrowed equipment');

// Daily upcoming reservations check — runs every day at 8:30 AM
Schedule::command(SendAutomatedReminders::class)
    ->dailyAt('08:30')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/reservation_reminders.log'))
    ->description('Daily reminders for reservations happening tomorrow');

// Daily expiry sweep — runs at midnight + 5 min to catch requests that expired overnight
Schedule::command(ExpirePendingRequests::class)
    ->dailyAt('00:05')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/expired_requests.log'))
    ->description('Cancel pending requests whose date has already passed');

// Auto-resolve pending requests sweep — runs every 5 minutes
Schedule::command(\App\Console\Commands\AutoResolveRequests::class)
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/auto_resolve.log'))
    ->description('Auto-resolve pending facility reservations and equipment requests based on deterministic priority rules');

// Daily inactive user deactivation sweep — runs every day at 1:00 AM
Schedule::command(\App\Console\Commands\DeactivateInactiveUsers::class)
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/inactive_users.log'))
    ->description('Deactivate dormant user accounts after 14 days without access');

// Daily notification cleanup — runs every day at 2:00 AM so inboxes never flood
Schedule::command(\App\Console\Commands\PruneNotifications::class)
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/notification_prune.log'))
    ->description('Delete old read notifications and cap each user inbox');


