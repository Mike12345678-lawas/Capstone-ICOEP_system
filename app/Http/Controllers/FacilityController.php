<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\Notification;
use App\Models\User;
use App\Services\ResourceSolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FacilityController extends Controller
{
    public function index(Request $request)
    {
        $query = Facility::query();

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $facilities = $query->orderBy('name')->paginate(config('icoep.items_per_page'));

        return view('facilities.index', [
            'pageTitle'    => 'Campus Facilities',
            'breadcrumb'   => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Facilities'],
            ],
            'facilities'   => $facilities,
            'search'       => $search,
            'statusFilter' => $status,
        ]);
    }

    public function show(Facility $facility)
    {
        $reservations = $facility->reservations()
            ->with('user')
            ->where('reservation_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'approved'])
            ->orderBy('reservation_date')
            ->orderBy('time_start')
            ->get();

        return view('facilities.show', [
            'pageTitle' => $facility->name,
            'breadcrumb' => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Facilities', 'url' => '/dashboard?section=facilities'],
                ['label' => $facility->name],
            ],
            'facility'     => $facility,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Reserve a facility — processed through the deterministic ResourceSolver.
     * Auto-approves or auto-rejects based on:
     *  1. Double-booking prevention (hard block)
     *  2. Role-based priority (Admin > Faculty/Staff > Student)
     *  3. FCFS within the same tier
     *  4. Alternative suggestions on rejection
     */
    public function reserve(Request $request, Facility $facility)
    {
        $request->validate([
            'title'            => 'required|string|max:255',
            'description'      => 'nullable|string',
            'reservation_date' => 'required|date|after_or_equal:today',
            'time_start'       => 'required|date_format:H:i',
            'time_end'         => 'required|date_format:H:i|after:time_start',
        ]);

        $user = Auth::user();

        // ─── Run deterministic solver ───
        $result = DB::transaction(function () use ($request, $facility, $user) {
            // Lock the facility's reservations to prevent race conditions
            FacilityReservation::where('facility_id', $facility->id)
                ->lockForUpdate()
                ->get();

            return ResourceSolver::resolveFacilityReservation(
                $facility,
                $request->reservation_date,
                $request->time_start,
                $request->time_end,
                $user
            );
        });

        if ($result['status'] === 'rejected') {
            $errorMsg = $result['reason'];

            // Format suggestions if available
            if (!empty($result['suggestions'])) {
                $errorMsg .= ' | Alternatives: ';
                $alts = [];
                foreach (array_slice($result['suggestions'], 0, 3) as $s) {
                    if ($s['type'] === 'time_slot' || $s['type'] === 'alternate_date') {
                        $alts[] = "{$s['date']} {$s['start']}–{$s['end']}";
                    } elseif ($s['type'] === 'alternate_facility') {
                        $alts[] = "{$s['name']} ({$s['location']})";
                    }
                }
                $errorMsg .= implode('; ', $alts);
            }

            return back()->withInput()->with('error', $errorMsg);
        }

        // ─── Create the reservation with pending status ───
        $reservation = FacilityReservation::create([
            'facility_id'      => $facility->id,
            'user_id'          => Auth::id(),
            'title'            => $request->title,
            'description'      => $request->description,
            'reservation_date' => $request->reservation_date,
            'time_start'       => $request->time_start,
            'time_end'         => $request->time_end,
            'status'           => 'pending',
            'remarks'          => 'Pending admin approval',
            'approved_by'      => null, // requires admin approval
        ]);

        AuditLog::log('CREATE_RESERVATION', 'facility_reservations', $reservation->id);

        Notification::send(
            $user->id,
            '⏳ Reservation Pending',
            "Your reservation request for \"{$facility->name}\" on {$reservation->reservation_date->format('M d, Y')} ({$request->time_start}–{$request->time_end}) has been submitted and is pending admin approval.",
            'info',
            '/facilities/' . $facility->id
        );

        // Notify Admins
        $admins = \App\Models\User::whereHas('role', function($q) {
            $q->where('name', 'Administrator');
        })->get();

        foreach ($admins as $admin) {
            Notification::send(
                $admin->id,
                '🔔 New Facility Reservation',
                "{$user->full_name} has requested to reserve \"{$facility->name}\" for {$reservation->reservation_date->format('M d, Y')}.",
                'info',
                '/dashboard?section=manage-bookings'
            );
        }

        return redirect('/facilities/' . $facility->id)
            ->with('success', 'Reservation request submitted successfully. Waiting for admin approval.');
    }

    // ─── Admin Management ───

    public function manage(Request $request)
    {
        $query = FacilityReservation::with(['facility', 'user']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        } else {
            $query->pending();
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($q2) => $q2->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))
                  ->orWhereHas('facility', fn($q2) => $q2->where('name', 'like', "%{$search}%"));
            });
        }

        $reservations = $query->orderByDesc('created_at')
            ->paginate(config('icoep.items_per_page'));

        return view('facilities.manage', [
            'pageTitle'    => 'Manage Facility Bookings',
            'breadcrumb'   => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Manage Bookings'],
            ],
            'reservations' => $reservations,
            'statusFilter' => $status ?? 'pending',
            'search'       => $search,
        ]);
    }

    public function updateStatus(Request $request, FacilityReservation $reservation)
    {
        $request->validate([
            'status'  => 'required|in:approved,rejected,cancelled',
            'remarks' => 'nullable|string|max:500',
        ]);

        // Double-booking prevention on manual approval
        if ($request->status === 'approved') {
            $conflicts = FacilityReservation::conflicts(
                $reservation->facility_id,
                $reservation->reservation_date->toDateString(),
                $reservation->time_start,
                $reservation->time_end
            )->where('id', '!=', $reservation->id)->count();

            if ($conflicts > 0) {
                return back()->with('error', 'Cannot approve: time slot conflicts with an existing approved reservation.');
            }
        }

        $reservation->update([
            'status'      => $request->status,
            'remarks'     => $request->remarks,
            'approved_by' => Auth::id(),
        ]);

        AuditLog::log(strtoupper($request->status) . '_RESERVATION', 'facility_reservations', $reservation->id);

        $statusLabel = ucfirst($request->status);
        Notification::send(
            $reservation->user_id,
            "Reservation {$statusLabel}",
            "Your reservation for \"{$reservation->facility->name}\" on {$reservation->reservation_date->format('M d, Y')} has been {$request->status}.",
            $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'error' : 'warning'),
            '/facilities/' . $reservation->facility_id
        );

        return back()->with('success', "Reservation {$request->status} successfully.");
    }
}
