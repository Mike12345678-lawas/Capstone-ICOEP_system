<?php

namespace App\Services;

use App\Mail\AccountNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailNotificationService
{
    /**
     * Dispatch an email notification to the recipient user.
     * Uses Google Apps Script HTTPS Relay (Port 443) when configured, or standard Laravel Mailer.
     *
     * @return array{success: bool, provider: string, message: string, details?: mixed}
     */
    public static function send(Notification $notification, User $user): array
    {
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success'  => false,
                'provider' => 'none',
                'message'  => "Invalid or missing recipient email for user #{$user->id}",
            ];
        }

        $relayUrl = env('GMAIL_RELAY_URL');

        // Priority 1: Google Apps Script Web Relay (Sends to any school/personal email over HTTPS)
        if (!empty($relayUrl)) {
            return self::sendViaGoogleRelay($notification, $user, $relayUrl);
        }

        // Priority 2: Standard Laravel Mailer (SMTP, Log, etc.)
        $activeMailer = config('mail.default', 'log');
        try {
            Mail::to($user->email, $user->full_name ?: $user->first_name)
                ->send(new AccountNotificationMail($notification, $user));

            return [
                'success'  => true,
                'provider' => $activeMailer,
                'message'  => "Dispatched via Laravel '{$activeMailer}' mailer to {$user->email}.",
            ];
        } catch (\Throwable $e) {
            $msg = "Mailer exception: " . $e->getMessage();
            Log::error("EmailNotificationService: {$msg}", ['user_id' => $user->id, 'exception' => $e]);
            error_log("EmailNotificationService error: {$msg}");

            return [
                'success'  => false,
                'provider' => $activeMailer,
                'message'  => $msg,
            ];
        }
    }

    /**
     * Send email via Google Apps Script Web App Relay over HTTPS (port 443).
     * Works with personal or institutional Google accounts to deliver to any recipient without domain limits.
     */
    public static function sendViaGoogleRelay(Notification $notification, User $user, string $relayUrl): array
    {
        try {
            $mailable = new AccountNotificationMail($notification, $user);
            $htmlContent = view('emails.account_notification', [
                'notification' => $notification,
                'user'         => $user,
                'actionUrl'    => $mailable->actionUrl,
                'typeTheme'    => $mailable->getTypeTheme(),
            ])->render();

            $response = Http::timeout(25)
                ->withOptions(['allow_redirects' => true])
                ->asJson()
                ->post($relayUrl, [
                    'to'      => $user->email,
                    'subject' => $mailable->envelope()->subject,
                    'html'    => $htmlContent,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (is_array($data) && !empty($data['success'])) {
                    Log::info("Email sent to {$user->email} via Google Apps Script Relay.");
                    return [
                        'success'  => true,
                        'provider' => 'gmail-relay',
                        'message'  => "Delivered to {$user->email} via Google Apps Script Relay!",
                        'details'  => $data,
                    ];
                }

                if (is_array($data) && !empty($data['error'])) {
                    return [
                        'success'  => false,
                        'provider' => 'gmail-relay',
                        'message'  => "Google Apps Script error: " . $data['error'],
                    ];
                }
            }

            // In case Google returns an HTML redirect or login page
            $body = $response->body();
            if (str_contains($body, 'ServiceLogin') || str_contains($body, 'accounts.google.com')) {
                return [
                    'success'  => false,
                    'provider' => 'gmail-relay',
                    'message'  => 'Google Relay requires authentication. In Google Apps Script, set "Who has access" to "Anyone" (not restricted to domain).',
                ];
            }

            return [
                'success'  => true,
                'provider' => 'gmail-relay',
                'message'  => "Dispatched to {$user->email} via Google Apps Script Relay.",
            ];
        } catch (\Throwable $e) {
            $msg = "Google Relay error: " . $e->getMessage();
            Log::error($msg);
            error_log($msg);

            return [
                'success'  => false,
                'provider' => 'gmail-relay',
                'message'  => $msg,
            ];
        }
    }

    /**
     * Send a standalone live test email without saving a notification to the database.
     */
    public static function testSend(string $recipientEmail, string $title, string $message, string $type = 'info'): array
    {
        $dummyUser = new User([
            'first_name' => 'Test',
            'last_name'  => 'Recipient',
            'email'      => $recipientEmail,
        ]);
        $dummyUser->id = 0;

        $dummyNotification = new Notification([
            'user_id' => 0,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'link'    => '/dashboard',
        ]);

        return self::send($dummyNotification, $dummyUser);
    }
}
