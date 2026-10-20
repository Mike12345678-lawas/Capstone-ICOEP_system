<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Notification $notification;
    public User $user;
    public string $actionUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Notification $notification, User $user)
    {
        $this->notification = $notification;
        $this->user = $user;

        $link = $notification->link;
        if ($link) {
            $this->actionUrl = (str_starts_with($link, 'http://') || str_starts_with($link, 'https://'))
                ? $link
                : url($link);
        } else {
            $this->actionUrl = url('/dashboard');
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $appName = config('icoep.app_name', 'ICOEP');
        $subject = "[{$appName}] " . $this->notification->title;

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.account_notification',
            with: [
                'notification' => $this->notification,
                'user'         => $this->user,
                'actionUrl'    => $this->actionUrl,
                'typeTheme'    => $this->getTypeTheme(),
            ],
        );
    }

    /**
     * Color theme and visual metadata based on notification type.
     */
    public function getTypeTheme(): array
    {
        return match ($this->notification->type) {
            'success' => [
                'primary'    => '#059669',
                'bg_badge'   => '#ecfdf5',
                'text_badge' => '#065f46',
                'border'     => '#10b981',
                'border_sub' => '#a7f3d0',
                'icon'       => '✅',
                'badge'      => 'Success Notification',
            ],
            'warning' => [
                'primary'    => '#d97706',
                'bg_badge'   => '#fffbeb',
                'text_badge' => '#92400e',
                'border'     => '#f59e0b',
                'border_sub' => '#fde68a',
                'icon'       => '⚠️',
                'badge'      => 'Important Notice',
            ],
            'error'   => [
                'primary'    => '#dc2626',
                'bg_badge'   => '#fef2f2',
                'text_badge' => '#991b1b',
                'border'     => '#ef4444',
                'border_sub' => '#fecaca',
                'icon'       => '🚨',
                'badge'      => 'Urgent Alert',
            ],
            default   => [
                'primary'    => '#0284c7',
                'bg_badge'   => '#f0f9ff',
                'text_badge' => '#075985',
                'border'     => '#0ea5e9',
                'border_sub' => '#bae6fd',
                'icon'       => '🔔',
                'badge'      => 'Account Notification',
            ],
        };
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
