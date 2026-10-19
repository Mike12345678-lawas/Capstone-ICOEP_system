<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class EventNotificationService
{
    /**
     * Send notifications to the target audience of an event.
     *
     * @param Event $event
     * @param int|null $senderId User ID who created the event (excluded from receiving notification)
     * @return int Number of notifications dispatched
     */
    public static function notifyAudience(Event $event, ?int $senderId = null): int
    {
        $senderId = $senderId ?? $event->organizer_id ?? Auth::id();
        $audience = $event->target_audience ?: Event::AUDIENCE_ALL;

        // Query active users
        $query = User::active();
        if ($senderId) {
            $query->where('id', '!=', $senderId);
        }

        // Filter users based on audience
        switch ($audience) {
            case Event::AUDIENCE_FACULTY:
                $query->whereHas('role', fn($q) => $q->where('name', 'Faculty'));
                $title = "🎓 New Faculty Event: {$event->title}";
                $prefix = "A new event for Faculty only";
                break;

            case Event::AUDIENCE_STAFF:
                $query->whereHas('role', fn($q) => $q->whereIn('name', Event::STAFF_ROLES));
                $title = "📋 New Staff Event: {$event->title}";
                $prefix = "A new event for Staff (Non-teaching) only";
                break;

            case Event::AUDIENCE_STUDENT:
                $query->whereHas('role', fn($q) => $q->where('name', 'Student'));
                $title = "🎒 New Student Event: {$event->title}";
                $prefix = "A new event for Students only";
                break;

            case Event::AUDIENCE_BSIT:
            case Event::AUDIENCE_BSHM:
            case Event::AUDIENCE_BSBA_FM:
            case Event::AUDIENCE_BSED:
            case Event::AUDIENCE_BEED:
                $deptCode = Event::DEPARTMENT_AUDIENCES[$audience];
                $query->whereHas('department', fn($q) => $q->where('code', $deptCode));
                $deptLabel = Event::AUDIENCES[$audience] ?? strtoupper($deptCode);
                $title = "📣 New {$deptLabel} Event: {$event->title}";
                $prefix = "A new event for {$deptLabel}";
                break;

            case Event::AUDIENCE_ALL:
            default:
                // All active users
                $title = "📅 New Campus Event: {$event->title}";
                $prefix = "A new campus-wide event";
                break;
        }

        $recipients = $query->get();

        if ($recipients->isEmpty()) {
            return 0;
        }

        $dateFormatted = $event->event_date ? $event->event_date->format('M d, Y') : 'Upcoming';
        $venue = $event->venue ? " at {$event->venue}" : '';
        $message = "{$prefix} has been scheduled: \"{$event->title}\" on {$dateFormatted}{$venue}. Click to view details and register.";
        $link = '/events/' . $event->id;

        $count = 0;
        foreach ($recipients as $recipient) {
            Notification::send(
                $recipient->id,
                $title,
                $message,
                'info',
                $link
            );
            $count++;
        }

        AuditLog::log('EVENT_AUDIENCE_NOTIFIED', 'events', $event->id, $senderId);

        return $count;
    }
}
