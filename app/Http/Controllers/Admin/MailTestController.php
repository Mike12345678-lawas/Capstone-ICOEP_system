<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MailTestController extends Controller
{
    /**
     * Show the mail diagnostics and testing dashboard.
     */
    public function index()
    {
        $user = Auth::user();

        $resendKey = (string) (config('services.resend.key') ?: env('RESEND_API_KEY'));
        $resendMasked = $resendKey
            ? substr($resendKey, 0, 7) . '••••••••' . substr($resendKey, -4)
            : 'Not Configured';

        $smtpPassword = (string) config('mail.mailers.smtp.password');

        return view('admin.mail_test', [
            'pageTitle'    => 'Email Diagnostics & Testing',
            'breadcrumb'   => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Email Diagnostics'],
            ],
            'currentUser'  => $user,
            'activeMailer' => config('mail.default'),
            'resendKeySet' => !empty($resendKey),
            'resendMasked' => $resendMasked,
            'fromAddress'  => config('mail.from.address'),
            'fromName'     => config('mail.from.name'),
            'smtpHost'     => config('mail.mailers.smtp.host'),
            'smtpPort'     => config('mail.mailers.smtp.port'),
            'smtpUsername' => config('mail.mailers.smtp.username'),
            'smtpPassSet'  => !empty($smtpPassword),
        ]);
    }

    /**
     * Dispatch a live test email directly from the web browser.
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'email'   => 'required|email|max:255',
            'title'   => 'nullable|string|max:120',
            'message' => 'nullable|string|max:500',
            'type'    => 'nullable|in:info,success,warning,error',
        ]);

        $recipientEmail = trim($validated['email']);
        $title          = $validated['title'] ?: 'Test Email Notification';
        $message        = $validated['message'] ?: 'This is a live test notification from your ICOEP system confirming that email delivery is working.';
        $type           = $validated['type'] ?? 'info';

        $result = EmailNotificationService::testSend($recipientEmail, $title, $message, $type);

        if ($result['success']) {
            return back()->with('mail_success', $result['message']);
        }

        return back()->withInput()->with('mail_error', $result['message']);
    }
}
