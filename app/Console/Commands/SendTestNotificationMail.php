<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTestNotificationMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-notification
                            {email? : The recipient email address (e.g. user@gmail.com)}
                            {--title=Test System Notification : The notification title}
                            {--message=This is a test notification confirming that email alerts are delivered to your Gmail account. : The notification body}
                            {--type=info : Notification type (info, success, warning, error)}
                            {--link=/dashboard : Direct link destination}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test notification email to an account or given email address to verify Gmail/SMTP delivery';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetEmail = $this->argument('email');

        if (!$targetEmail) {
            $user = User::active()->whereNotNull('email')->first();
            if (!$user) {
                $this->error('No active user with an email address found in the database. Please provide an email as argument.');
                return self::FAILURE;
            }
            $targetEmail = $user->email;
        } else {
            $user = User::where('email', $targetEmail)->first();
            if (!$user) {
                $this->info("User with email '{$targetEmail}' not found. Using an existing user record for recipient context...");
                $user = User::first();
                if (!$user) {
                    $this->error('No users found in database to associate with notification.');
                    return self::FAILURE;
                }
                // Temporary email override for testing
                $user->email = $targetEmail;
            }
        }

        $title   = (string) $this->option('title');
        $message = (string) $this->option('message');
        $type    = (string) $this->option('type');
        $link    = (string) $this->option('link');

        $this->info("Sending test notification to: {$targetEmail}");
        $this->line("• Mailer: " . config('mail.default'));
        $this->line("• From: " . config('mail.from.name') . " <" . config('mail.from.address') . ">");
        $this->line("• Title: {$title}");
        $this->line("• Type:  {$type}");

        try {
            $notification = Notification::send(
                $user->id,
                $title,
                $message,
                $type,
                $link
            );

            $this->info("✔ Notification created successfully (ID: {$notification->id}).");

            if (config('mail.default') === 'log') {
                $this->warn("Note: MAIL_MAILER is set to 'log'. The email was written to storage/logs/laravel.log instead of live delivery.");
            } elseif (env('GMAIL_RELAY_URL')) {
                $this->info("✔ Email dispatched via Google Apps Script Relay (HTTPS) to {$targetEmail}!");
            } else {
                $this->info("✔ Email successfully dispatched to {$targetEmail}!");
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Failed to dispatch email: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
