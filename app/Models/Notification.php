<?php

namespace App\Models;

use App\Mail\AccountNotificationMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Notification extends Model
{
    protected $fillable = ['user_id', 'title', 'message', 'type', 'link', 'is_read'];

    /**
     * Global toggle for sending email notifications on creation.
     */
    public static bool $shouldSendEmails = true;

    /**
     * Instance toggle to skip email for this specific notification.
     */
    public bool $skipEmailNotification = false;

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Notification $notification) {
            if (!$notification->skipEmailNotification && static::$shouldSendEmails) {
                if (function_exists('defer') && !app()->runningUnitTests()) {
                    defer(fn() => $notification->sendEmailNotification());
                } else {
                    $notification->sendEmailNotification();
                }
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Create a notification for a user and dispatch an email to their account.
     */
    public static function send(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        ?string $link = null,
        bool $sendEmail = true
    ): self {
        $notification = new self([
            'user_id' => $userId,
            'title'   => $title,
            'message' => $message,
            'type'    => $type,
            'link'    => $link,
        ]);

        if (!$sendEmail) {
            $notification->skipEmailNotification = true;
        }

        $notification->save();

        return $notification;
    }

    /**
     * Send an email notification to the recipient user's email address.
     */
    public function sendEmailNotification(): bool
    {
        try {
            $user = $this->relationLoaded('user') ? $this->user : User::find($this->user_id);

            if (!$user) {
                return false;
            }

            $result = \App\Services\EmailNotificationService::send($this, $user);

            return $result['success'] ?? false;
        } catch (\Throwable $e) {
            Log::error("Failed to send notification email to user #{$this->user_id} ({$e->getMessage()})", [
                'notification_id' => $this->id ?? null,
                'user_id'         => $this->user_id,
                'exception'       => $e,
            ]);

            return false;
        }
    }
}

