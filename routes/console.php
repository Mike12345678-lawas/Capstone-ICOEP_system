<?php

use App\Console\Commands\CheckOverdueEquipment;
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
