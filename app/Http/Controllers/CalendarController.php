<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\FacilityReservation;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    /**
     * Return events and facility reservations for FullCalendar.
     */
    public function getEvents(Request $request): JsonResponse
    {
        // 1. Validate inputs to prevent parsing errors
        $request->validate([
            'start' => 'nullable|string',
            'end' => 'nullable|string',
        ]);

        try {
            // 2. Use robust Carbon dates with sensible fallbacks
            $start = $request->filled('start') ? Carbon::parse($request->start)->startOfDay() : Carbon::now()->startOfMonth()->startOfDay();
            $end = $request->filled('end') ? Carbon::parse($request->end)->endOfDay() : Carbon::now()->endOfMonth()->endOfDay();

            $startDate = $start->toDateString();
            $endDate = $end->toDateString();

            // 3. Fetch facility reservations (Pending, Approved, Completed)
            $reservations = FacilityReservation::with(['facility', 'user'])
                ->whereIn('status', ['approved', 'pending', 'completed'])
                ->whereBetween('reservation_date', [$startDate, $endDate])
                ->get()
                ->map(function ($res) {
                    $isPending = $res->status === 'pending';
                    $color = $isPending ? '#f59e0b' : '#10b981';
                    $borderColor = $isPending ? '#d97706' : '#059669';
                    $statusLabel = $isPending ? '[Pending Booking] ' : '[Approved Booking] ';
                    $facilityName = $res->facility->name ?? 'Facility';
                    $dateStr = Carbon::parse($res->reservation_date)->format('Y-m-d');

                    $timeStart = $res->time_start ? substr($res->time_start, 0, 5) : null;
                    $timeEnd = $res->time_end ? substr($res->time_end, 0, 5) : null;

                    $startFormatted = $timeStart ? "{$dateStr}T{$timeStart}:00" : $dateStr;
                    $endFormatted = $timeEnd ? "{$dateStr}T{$timeEnd}:00" : ($timeStart ? "{$dateStr}T{$timeStart}:00" : null);

                    return [
                        'id' => 'res_' . $res->id,
                        'title' => $statusLabel . $facilityName . ' - ' . $res->title,
                        'start' => $startFormatted,
                        'end' => $endFormatted,
                        'allDay' => empty($timeStart),
                        'backgroundColor' => $color,
                        'borderColor' => $borderColor,
                        'textColor' => '#ffffff',
                        'url' => url('/facilities/' . $res->facility_id),
                        'extendedProps' => [
                            'type' => 'reservation',
                            'reservation_status' => $res->status,
                            'description' => $res->description,
                            'facility' => $facilityName,
                            'booker' => $res->user ? ($res->user->first_name . ' ' . $res->user->last_name) : 'User',
                            'time' => ($timeStart && $timeEnd) ? "{$timeStart} - {$timeEnd}" : 'All Day',
                        ]
                    ];
                });

            // 4. Fetch campus events (Sports, Academic, Spiritual, Cultural, etc.)
            $events = Event::whereIn('status', ['upcoming', 'ongoing', 'completed'])
                ->whereBetween('event_date', [$startDate, $endDate])
                ->get()
                ->map(function ($event) {
                    $type = strtolower($event->event_type ?? 'other');
                    switch ($type) {
                        case 'sports':
                            $color = '#3b82f6';
                            $borderColor = '#2563eb';
                            $typeLabel = '[Sports Event] ';
                            break;
                        case 'academic':
                            $color = '#8b5cf6';
                            $borderColor = '#7c3aed';
                            $typeLabel = '[Academic Event] ';
                            break;
                        case 'spiritual':
                            $color = '#6366f1';
                            $borderColor = '#4f46e5';
                            $typeLabel = '[Spiritual Event] ';
                            break;
                        case 'cultural':
                            $color = '#ec4899';
                            $borderColor = '#db2777';
                            $typeLabel = '[Cultural Event] ';
                            break;
                        default:
                            $color = '#64748b';
                            $borderColor = '#475569';
                            $typeLabel = '[' . ucfirst($type) . ' Event] ';
                            break;
                    }

                    $dateStr = Carbon::parse($event->event_date)->format('Y-m-d');
                    $timeStart = $event->time_start ? substr($event->time_start, 0, 5) : null;
                    $timeEnd = $event->time_end ? substr($event->time_end, 0, 5) : null;

                    $startFormatted = $timeStart ? "{$dateStr}T{$timeStart}:00" : $dateStr;
                    $endFormatted = $timeEnd ? "{$dateStr}T{$timeEnd}:00" : ($timeStart ? "{$dateStr}T{$timeStart}:00" : null);

                    return [
                        'id' => 'evt_' . $event->id,
                        'title' => $typeLabel . $event->title,
                        'start' => $startFormatted,
                        'end' => $endFormatted,
                        'allDay' => empty($timeStart),
                        'backgroundColor' => $color,
                        'borderColor' => $borderColor,
                        'textColor' => '#ffffff',
                        'url' => url('/events/' . $event->id),
                        'extendedProps' => [
                            'type' => 'event',
                            'event_type' => $event->event_type,
                            'description' => $event->description,
                            'venue' => $event->venue,
                            'time' => ($timeStart && $timeEnd) ? "{$timeStart} - {$timeEnd}" : 'All Day',
                        ]
                    ];
                });

            // 5. Safely merge as plain array without Eloquent collection collision
            $all = array_merge($reservations->all(), $events->all());

            return response()->json($all);

        } catch (\Exception $e) {
            // 6. Graceful error handling
            Log::error('Calendar events fetch error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch events.'], 500);
        }
    }
}
