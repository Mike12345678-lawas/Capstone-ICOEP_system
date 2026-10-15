<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('venue', 'like', "%{$search}%");
            });
        }

        if ($type = $request->get('type')) {
            $query->where('event_type', $type);
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $events = $query->withCount('registrations')
            ->orderBy('event_date')
            ->paginate(config('icoep.items_per_page'));

        return view('events.index', [
            'pageTitle'    => 'Campus Events',
            'breadcrumb'   => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Events'],
            ],
            'events'       => $events,
            'search'       => $search,
            'typeFilter'   => $type,
            'statusFilter' => $status,
        ]);
    }

    public function show(Event $event)
    {
        $event->load(['organizer', 'registrations.user']);

        $registration = $event->registrations()->where('user_id', Auth::id())->first();
        $isRegistered = $registration !== null;

        return view('events.show', [
            'pageTitle' => $event->title,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Events', 'url' => '/dashboard?section=events'],
                ['label' => $event->title],
            ],
            'event'        => $event,
            'isRegistered' => $isRegistered,
            'registration' => $registration,
        ]);
    }

    public function create()
    {
        return view('events.create', [
            'pageTitle' => 'Create Event',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Events', 'url' => '/dashboard?section=events'],
                ['label' => 'Create Event'],
            ],
        ]);
    }

    /**
     * Store a new event — objectives/targets field is MANDATORY.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'objectives'       => 'required|string|min:10',
            'description'      => 'nullable|string',
            'event_type'       => 'required|in:academic,cultural,sports,spiritual,other',
            'event_date'       => 'required|date|after_or_equal:today',
            'time_start'       => 'required|date_format:H:i',
            'time_end'         => 'required|date_format:H:i|after:time_start',
            'venue'            => 'nullable|string|max:255',
            'max_participants' => 'nullable|integer|min:0',
        ], [
            'objectives.required' => 'Event Targets/Objectives is required and cannot be empty.',
            'objectives.min'      => 'Event Targets/Objectives must be at least 10 characters.',
        ]);

        $event = Event::create([
            'title'            => $request->title,
            'objectives'       => $request->objectives,
            'description'      => $request->description,
            'event_type'       => $request->event_type,
            'event_date'       => $request->event_date,
            'time_start'       => $request->time_start,
            'time_end'         => $request->time_end,
            'venue'            => $request->venue,
            'max_participants' => $request->max_participants ?? 0,
            'organizer_id'     => Auth::id(),
            'status'           => 'upcoming',
        ]);

        AuditLog::log('CREATE_EVENT', 'events', $event->id);

        return redirect('/events/' . $event->id)->with('success', 'Event created successfully!');
    }

    /**
     * Pre-registration with automatic QR code generation.
     * On success, generates a unique QR token linked to user + event.
     */
    public function register(Event $event)
    {
        if (!$event->isUpcoming()) {
            return back()->with('error', 'Registration is closed for this event.');
        }

        if ($event->isFull()) {
            return back()->with('error', 'This event has reached maximum capacity.');
        }

        $alreadyRegistered = EventRegistration::where('event_id', $event->id)
            ->where('user_id', Auth::id())
            ->exists();

        if ($alreadyRegistered) {
            return back()->with('warning', 'You are already registered for this event.');
        }

        // Transaction with lock to prevent race conditions
        $registration = DB::transaction(function () use ($event) {
            $freshEvent = Event::lockForUpdate()->find($event->id);

            if ($freshEvent->max_participants > 0) {
                $currentCount = EventRegistration::where('event_id', $event->id)->count();
                if ($currentCount >= $freshEvent->max_participants) {
                    throw new \Exception('Event is full');
                }
            }

            // Generate unique QR code token
            $qrToken = EventRegistration::generateQrToken($event->id, Auth::id());

            return EventRegistration::create([
                'event_id'      => $event->id,
                'user_id'       => Auth::id(),
                'qr_code'       => $qrToken,
                'registered_at' => now(),
            ]);
        });

        AuditLog::log('REGISTER_EVENT', 'event_registrations', $event->id);

        Notification::send(
            Auth::id(),
            '🎫 Event Registration Confirmed',
            "You're registered for \"{$event->title}\" on {$event->event_date->format('M d, Y')}. Your QR code is ready — present it at the venue for check-in.",
            'success',
            '/events/' . $event->id
        );

        return back()->with('success', '🎫 Registered! Your QR code is ready below.');
    }

    public function cancelRegistration(Event $event)
    {
        EventRegistration::where('event_id', $event->id)
            ->where('user_id', Auth::id())
            ->delete();

        return back()->with('success', 'Your registration has been cancelled.');
    }

    // ─── Admin: Manage Events ───

    public function manage(Event $event, Request $request)
    {
        $event->load(['registrations.user.role', 'registrations.user.department']);

        return view('events.manage', [
            'pageTitle' => 'Manage: ' . $event->title,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Events', 'url' => '/dashboard?section=events'],
                ['label' => 'Manage'],
            ],
            'event' => $event,
        ]);
    }

    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:upcoming,ongoing,completed,cancelled',
        ]);

        $event->update(['status' => $request->status]);
        AuditLog::log('UPDATE_EVENT_STATUS', 'events', $event->id);

        return back()->with('success', 'Event status updated to ' . ucfirst($request->status) . '.');
    }

    /**
     * Mark attendance (legacy bulk method via checkboxes).
     */
    public function markAttendance(Request $request, Event $event)
    {
        $request->validate([
            'user_ids'   => 'nullable|array',
            'user_ids.*' => 'integer|exists:event_registrations,user_id',
        ]);

        $userIds = $request->input('user_ids', []);

        EventRegistration::where('event_id', $event->id)
            ->whereIn('user_id', $userIds)
            ->update(['has_attended' => true, 'checked_in_at' => now()]);

        EventRegistration::where('event_id', $event->id)
            ->whereNotIn('user_id', $userIds)
            ->update(['has_attended' => false, 'checked_in_at' => null]);

        AuditLog::log('MARK_ATTENDANCE', 'events', $event->id);

        return back()->with('success', 'Attendance updated successfully.');
    }

    // ─── QR Scanner & Attendance API ──────────────────────────────────────

    /**
     * Scanner UI page — organizers use this to scan QR codes at the venue.
     */
    public function scanner(Event $event)
    {
        $event->load(['registrations.user']);

        $attendeeSummary = [
            'total'      => $event->registrations->count(),
            'checked_in' => $event->registrations->where('has_attended', true)->count(),
        ];

        return view('events.scanner', [
            'pageTitle' => 'Scanner: ' . $event->title,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Events', 'url' => '/dashboard?section=events'],
                ['label' => $event->title, 'url' => '/events/' . $event->id],
                ['label' => 'Scanner'],
            ],
            'event'   => $event,
            'summary' => $attendeeSummary,
        ]);
    }

    /**
     * API: Scan a QR code and check the attendee in.
     * Returns JSON for AJAX consumption by the scanner UI.
     */
    public function scanQr(Request $request, Event $event)
    {
        $request->validate([
            'qr_code' => 'required|string|max:64',
        ]);

        $registration = EventRegistration::with('user')
            ->where('event_id', $event->id)
            ->where('qr_code', $request->qr_code)
            ->first();

        if (!$registration) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid QR code — not registered for this event.',
            ], 404);
        }

        if ($registration->has_attended) {
            return response()->json([
                'status'  => 'warning',
                'message' => "Already checked in at {$registration->checked_in_at->format('h:i A')}.",
                'user'    => [
                    'name'        => $registration->user->full_name,
                    'employee_id' => $registration->user->employee_id,
                ],
            ]);
        }

        $registration->update([
            'has_attended'  => true,
            'checked_in_at' => now(),
        ]);

        AuditLog::log('QR_CHECKIN', 'event_registrations', $registration->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Check-in successful!',
            'user'    => [
                'name'        => $registration->user->full_name,
                'employee_id' => $registration->user->employee_id,
                'checked_in'  => now()->format('h:i A'),
            ],
        ]);
    }

    /**
     * API: Manual check-in by user ID (fallback when QR scan fails).
     */
    public function manualCheckin(Request $request, Event $event)
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $registration = EventRegistration::where('event_id', $event->id)
            ->where('user_id', $request->user_id)
            ->first();

        if (!$registration) {
            return response()->json([
                'status'  => 'error',
                'message' => 'User is not registered for this event.',
            ], 404);
        }

        if ($registration->has_attended) {
            return response()->json([
                'status'  => 'warning',
                'message' => 'Already checked in.',
            ]);
        }

        $registration->update([
            'has_attended'  => true,
            'checked_in_at' => now(),
        ]);

        AuditLog::log('MANUAL_CHECKIN', 'event_registrations', $registration->id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Manual check-in successful!',
        ]);
    }

    public function myEvents()
    {
        $registrations = EventRegistration::where('user_id', Auth::id())
            ->with('event')
            ->orderByDesc('registered_at')
            ->paginate(config('icoep.items_per_page'));

        return view('events.my_events', [
            'pageTitle' => 'My Events',
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Events', 'url' => '/dashboard?section=events'],
                ['label' => 'My Events'],
            ],
            'registrations' => $registrations,
        ]);
    }

    public function destroy(Event $event)
    {
        $event->delete();
        AuditLog::log('DELETE_EVENT', 'events', $event->id);

        return redirect('/dashboard?section=events')->with('success', 'Event deleted successfully.');
    }
}
