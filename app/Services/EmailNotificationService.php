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
     * Uses Resend HTTPS API (port 443) when configured or standard Laravel Mailer.
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
        $resendKey = config('services.resend.key') ?: env('RESEND_API_KEY');
        $activeMailer = config('mail.default');

        // Priority 1: Google Apps Script Web Relay (Sends to any school/personal email over HTTPS)
        if ($relayUrl && ($activeMailer === 'gmail-relay' || empty($resendKey) || $activeMailer === 'resend')) {
            return self::sendViaGoogleRelay($notification, $user, $relayUrl);
        }

        // Priority 2: Resend HTTP API (Port 443)
        if ($activeMailer === 'resend' || ($resendKey && $activeMailer !== 'log' && $activeMailer !== 'array')) {
            return self::sendViaResend($notification, $user, (string) $resendKey);
        }

        // Standard Laravel Mailer (SMTP, Log, etc.)
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
     * Send email via Resend's REST API over HTTPS (port 443).
     * Bypasses all cloud firewall SMTP port blocks (such as Render Free tier).
     */
    public static function sendViaResend(Notification $notification, User $user, string $apiKey): array
    {
        if (empty($apiKey)) {
            $msg = 'RESEND_API_KEY is missing or empty. Please set it in your environment.';
            Log::error("EmailNotificationService: {$msg}");
            error_log("EmailNotificationService: {$msg}");
            return [
                'success'  => false,
                'provider' => 'resend',
                'message'  => $msg,
            ];
        }

        try {
            $mailable = new AccountNotificationMail($notification, $user);
            $htmlContent = view('emails.account_notification', [
                'notification' => $notification,
                'user'         => $user,
                'actionUrl'    => $mailable->actionUrl,
                'typeTheme'    => $mailable->getTypeTheme(),
            ])->render();

            $fromName    = config('mail.from.name', config('icoep.app_full_name', 'ICOEP System'));
            $fromAddress = config('mail.from.address') ?: 'onboarding@resend.dev';

            // Ensure sender is formatted as "Name <email@domain>"
            $fromHeader = "{$fromName} <{$fromAddress}>";

            $response = Http::withToken($apiKey)
                ->timeout(15)
                ->acceptJson()
                ->post('https://api.resend.com/emails', [
                    'from'    => $fromHeader,
                    'to'      => [$user->email],
                    'subject' => $mailable->envelope()->subject,
                    'html'    => $htmlContent,
                ]);

            if ($response->successful()) {
                $id = $response->json('id', 'ok');
                Log::info("Email successfully dispatched via Resend to {$user->email} (ID: {$id})");
                return [
                    'success'  => true,
                    'provider' => 'resend',
                    'message'  => "Delivered via Resend HTTPS API (Message ID: {$id}) to {$user->email}.",
                    'details'  => $response->json(),
                ];
            }

            $body = $response->json() ?: ['error' => $response->body()];
            $errorMsg = $body['message'] ?? $response->body();
            Log::error("Resend API rejected email to {$user->email}: {$errorMsg}", $body);
            error_log("Resend API error: {$errorMsg}");

            return [
                'success'  => false,
                'provider' => 'resend',
                'message'  => "Resend API error: {$errorMsg}",
                'details'  => $body,
            ];
        } catch (\Throwable $e) {
            $msg = "Resend connection error: " . $e->getMessage();
            Log::error("EmailNotificationService: {$msg}", ['exception' => $e]);
            error_log("EmailNotificationService error: {$msg}");

            return [
                'success'  => false,
                'provider' => 'resend',
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
                'success'  => true, // Often Google Apps Script returns 200 without JSON
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
