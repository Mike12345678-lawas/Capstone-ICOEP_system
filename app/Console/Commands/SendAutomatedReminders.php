<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\FacilityReservation;
use App\Models\Notification;
use App\Mail\UpcomingReservationReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendAutomatedReminders extends Command
{
    protected $signature = 'reminders:upcoming-reservations {--dry-run : List reservations without sending notifications}';
    protected $description = 'Send automated reminders for approved facility reservations happening tomorrow';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->startOfDay();
        $isDry = $this->option('dry-run');

        $upcoming = FacilityReservation::with(['user', 'facility'])
            ->where('status', 'approved')
            ->whereDate('reservation_date', $tomorrow->toDateString())
            ->get();

        if ($upcoming->isEmpty()) {
            $this->info('No upcoming reservations found for tomorrow.');
            return self::SUCCESS;
        }

        $this->info("Found {$upcoming->count()} upcoming reservation(s) for tomorrow.");

        foreach ($upcoming as $reservation) {
            $facility = $reservation->facility;
            $user = $reservation->user;

            $this->line("  ↳ [{$reservation->id}] {$facility->name} — {$user->full_name}");

            if ($isDry) {
                continue;
            }

            // In-app Notification
            $notificationLink = '/dashboard?section=facilities&reservation=' . $reservation->id;
            
            $alreadyNotified = Notification::where('user_id', $user->id)
                ->where('link', $notificationLink)
                ->whereDate('created_at', now()->toDateString())
                ->where('title', 'like', '%Upcoming%')
                ->exists();

            if (!$alreadyNotified) {
                $formattedTime = \Carbon\Carbon::parse($reservation->time_start)->format('h:i A');
                Notification::send(
                    $user->id,
                    'Upcoming Reservation Reminder',
                    "Just a reminder that your reservation for \"{$facility->name}\" is scheduled for tomorrow at {$formattedTime}.",
                    'info',
                    $notificationLink
                );
            }

            // Email Notification
            if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::to($user->email)->send(new UpcomingReservationReminder($reservation));
                    $this->line("     ✉  Email sent to {$user->email}");
                } catch (\Exception $e) {
                    $this->warn("     ✉  Email failed for {$user->email}: {$e->getMessage()}");
                }
            }

            AuditLog::log('UPCOMING_REMINDER_SENT', 'facility_reservations', $reservation->id);
        }

        $this->info($isDry ? 'Dry run complete — no notifications sent.' : "Alerts sent for {$upcoming->count()} upcoming reservation(s).");
        return self::SUCCESS;
    }
}
