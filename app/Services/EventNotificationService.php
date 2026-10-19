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
        $audiences = $event->getTargetAudiences();

        // Query active users
        $query = User::active();
        if ($senderId) {
            $query->where('id', '!=', $senderId);
        }

        if (in_array(Event::AUDIENCE_ALL, $audiences, true)) {
            // Campus-wide event
            $title = "📅 New Campus Event: {$event->title}";
            $prefix = "A new campus-wide event";
        } else {
            $query->where(function ($q) use ($audiences) {
                foreach ($audiences as $audience) {
                    if ($audience === Event::AUDIENCE_FACULTY) {
                        $q->orWhereHas('role', fn($rq) => $rq->where('name', 'Faculty'));
                    } elseif ($audience === Event::AUDIENCE_STAFF) {
                        $q->orWhereHas('role', fn($rq) => $rq->whereIn('name', Event::STAFF_ROLES));
                    } elseif ($audience === Event::AUDIENCE_STUDENT) {
                        $q->orWhereHas('role', fn($rq) => $rq->where('name', 'Student'));
                    } elseif (array_key_exists($audience, Event::DEPARTMENT_AUDIENCES)) {
                        $deptCode = Event::DEPARTMENT_AUDIENCES[$audience];
                        $q->orWhereHas('department', fn($dq) => $dq->where('code', $deptCode));
                    }
                }
            });

            $label = $event->target_audience_label;
            $title = "📣 New Event ({$label}): {$event->title}";
            $prefix = "A new event for {$label}";
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
