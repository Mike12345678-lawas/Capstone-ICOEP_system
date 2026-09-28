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
            'start' => 'nullable|date',
            'end' => 'nullable|date',
        ]);

        try {
            // 2. Use robust Carbon dates with sensible fallbacks
            $start = $request->filled('start') ? Carbon::parse($request->start)->startOfDay() : Carbon::now()->startOfMonth();
            $end = $request->filled('end') ? Carbon::parse($request->end)->endOfDay() : Carbon::now()->endOfMonth();

            // 3. Fetch facility reservations with null-safety on relations
            $reservations = FacilityReservation::with('facility')
                ->where('status', 'approved')
                ->whereBetween('reservation_date', [$start, $end])
                ->get()
                ->map(function ($res) {
                    return [
                        'id' => 'res_' . $res->id,
                        'title' => ($res->facility->name ?? 'Unknown Facility') . ' - ' . $res->title,
                        'start' => Carbon::parse($res->reservation_date)->format('Y-m-d') . 'T' . $res->time_start,
                        'end' => Carbon::parse($res->reservation_date)->format('Y-m-d') . 'T' . $res->time_end,
                        'backgroundColor' => '#10b981', // green for facilities
                        'borderColor' => '#059669',
                        'textColor' => '#ffffff',
                        'url' => url('/facilities/' . $res->facility_id),
                        'extendedProps' => [
                            'type' => 'reservation',
                            'description' => $res->description
                        ]
                    ];
                });

            // 4. Fetch events
            $events = Event::whereIn('status', ['upcoming', 'ongoing'])
                ->whereBetween('event_date', [$start, $end])
                ->get()
                ->map(function ($event) {
                    return [
                        'id' => 'evt_' . $event->id,
                        'title' => '[Event] ' . $event->title,
                        'start' => Carbon::parse($event->event_date)->format('Y-m-d') . 'T' . $event->time_start,
                        'end' => Carbon::parse($event->event_date)->format('Y-m-d') . 'T' . $event->time_end,
                        'backgroundColor' => '#3b82f6', // blue for events
                        'borderColor' => '#2563eb',
                        'textColor' => '#ffffff',
                        'url' => url('/events/' . $event->id),
                        'extendedProps' => [
                            'type' => 'event',
                            'description' => $event->description
                        ]
                    ];
                });

            return response()->json($reservations->merge($events));
            
        } catch (\Exception $e) {
            // 5. Graceful error handling
            Log::error('Calendar events fetch error: ' . $e->getMessage());
            return response()->json(['error' => 'Unable to fetch events.'], 500);
        }
    }
}
