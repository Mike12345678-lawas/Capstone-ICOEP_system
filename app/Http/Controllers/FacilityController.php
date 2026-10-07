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

        $myReservations = $facility->reservations()
            ->where('user_id', Auth::id())
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
            'facility'       => $facility,
            'reservations'   => $reservations,
            'myReservations' => $myReservations,
        ]);
    }

    /**
     * Reserve a facility — processed entirely through the deterministic ResourceSolver.
     * The decision engine handles deterministic approval, priority preemption, or pending status.
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

        $result = ResourceSolver::processFacilityReservation(
            $facility,
            $user,
            $request->only(['title', 'description', 'reservation_date', 'time_start', 'time_end'])
        );

        if ($result['status'] === 'rejected') {
            $suggestions = [];
            if (!empty($result['suggestions'])) {
                foreach (array_slice($result['suggestions'], 0, 5) as $s) {
                    if (($s['type'] ?? '') === 'time_slot' || ($s['type'] ?? '') === 'alternate_date') {
                        $suggestions[] = [
                            'type'  => $s['type'],
                            'label' => $s['label'] ?? "{$s['date']} {$s['start']}–{$s['end']}",
                            'date'  => $s['date'] ?? null,
                            'start' => $s['start'] ?? null,
                            'end'   => $s['end'] ?? null,
                        ];
                    } elseif (($s['type'] ?? '') === 'alternate_facility') {
                        $suggestions[] = [
                            'type'        => 'alternate_facility',
                            'label'       => $s['label'] ?? "{$s['name']} ({$s['location']})",
                            'facility_id' => $s['facility_id'] ?? $s['id'] ?? null,
                            'name'        => $s['name'] ?? null,
                            'date'        => $s['date'] ?? null,
                            'start'       => $s['start'] ?? null,
                            'end'         => $s['end'] ?? null,
                        ];
                    }
                }
            }

            return back()->withInput()
                ->with('error', $result['reason'])
                ->with('suggestions', $suggestions);
        }

        if ($result['status'] === 'approved') {
            return redirect('/dashboard?section=booked-facilities')
                ->with('success', $result['message'] ?? 'Facility reservation approved! You can view and manage your booked facilities below.');
        }

        return redirect('/dashboard?section=booked-facilities')
            ->with('success', 'Facility reservation request submitted successfully! Your booking is displayed below.');
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
            'pageTitle'    => 'Booking Requests',
            'breadcrumb'   => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Booking Requests'],
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

        // ─── Allowed state transitions ───
        $allowedTransitions = [
            'pending'  => ['approved', 'rejected', 'cancelled'],
            'approved' => ['cancelled'],
        ];

        $currentStatus = $reservation->status;
        $allowed = $allowedTransitions[$currentStatus] ?? [];

        if (!in_array($request->status, $allowed)) {
            return back()->with('error', "Cannot change status from '{$currentStatus}' to '{$request->status}'.");
        }

        // Block approving a reservation whose date is already past
        if ($request->status === 'approved' && $reservation->reservation_date->lt(now()->startOfDay())) {
            return back()->with('error', 'Cannot approve a reservation whose date has already passed.');
        }

        // Wrap conflict check + update in a transaction with row lock
        $conflictError = DB::transaction(function () use ($reservation, $request) {
            // Re-fetch with lock to prevent concurrent approval races
            $lockedReservation = FacilityReservation::lockForUpdate()->find($reservation->id);

            // Double-booking prevention on manual approval
            if ($request->status === 'approved') {
                $conflicts = FacilityReservation::conflicts(
                    $lockedReservation->facility_id,
                    $lockedReservation->reservation_date->toDateString(),
                    $lockedReservation->time_start,
                    $lockedReservation->time_end
                )->where('id', '!=', $lockedReservation->id)->count();

                if ($conflicts > 0) {
                    return 'Cannot approve: time slot conflicts with an existing approved reservation.';
                }
            }

            $lockedReservation->update([
                'status'      => $request->status,
                'remarks'     => $request->remarks,
                'approved_by' => Auth::id(),
            ]);

            return null;
        });

        if ($conflictError) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $conflictError], 422);
            }
            return back()->with('error', $conflictError);
        }

        AuditLog::log(strtoupper($request->status) . '_RESERVATION', 'facility_reservations', $reservation->id);

        $statusLabel = ucfirst($request->status);
        $remarkText  = '';
        if ($request->status === 'rejected' && $request->filled('remarks')) {
            $remarkText = ' Reason: ' . $request->remarks;
        }
        Notification::send(
            $reservation->user_id,
            "Reservation {$statusLabel}",
            "Your reservation for \"{$reservation->facility->name}\" on {$reservation->reservation_date->format('M d, Y')} has been {$request->status}.{$remarkText}",
            $request->status === 'approved' ? 'success' : ($request->status === 'rejected' ? 'error' : 'warning'),
            '/facilities/' . $reservation->facility_id
        );

        if ($request->expectsJson()) {
            $reservation->refresh();
            return response()->json(['success' => true, 'status' => $reservation->status]);
        }
        return back()->with('success', "Reservation {$request->status} successfully.");
    }

    // ─── Bulk Approve ───

    public function bulkApprove(Request $request)
    {
        $request->validate(['ids' => 'required|array', 'ids.*' => 'integer']);

        $succeeded = 0;
        $failures  = [];

        foreach ($request->ids as $id) {
            $reservation = FacilityReservation::find($id);
            if (!$reservation || $reservation->status !== 'pending') {
                $failures[] = "#{$id}: not found or not pending";
                continue;
            }
            if ($reservation->reservation_date->lt(now()->startOfDay())) {
                $failures[] = "#{$id}: date already passed";
                continue;
            }

            $conflictError = DB::transaction(function () use ($reservation) {
                $locked = FacilityReservation::lockForUpdate()->find($reservation->id);
                $conflicts = FacilityReservation::conflicts(
                    $locked->facility_id,
                    $locked->reservation_date->toDateString(),
                    $locked->time_start,
                    $locked->time_end
                )->where('id', '!=', $locked->id)->count();
                if ($conflicts > 0) {
                    return 'conflicts with an existing approved reservation';
                }
                $locked->update(['status' => 'approved', 'approved_by' => Auth::id()]);
                return null;
            });

            if ($conflictError) {
                $failures[] = "#{$id}: {$conflictError}";
                continue;
            }

            AuditLog::log('APPROVED_RESERVATION', 'facility_reservations', $reservation->id);
            Notification::send(
                $reservation->user_id,
                'Reservation Approved',
                "Your reservation for \"{$reservation->facility->name}\" on {$reservation->reservation_date->format('M d, Y')} has been approved.",
                'success',
                '/facilities/' . $reservation->facility_id
            );
            $succeeded++;
        }

        $msg = "{$succeeded} approved";
        if ($failures) {
            $msg .= '; ' . count($failures) . ' failed: ' . implode(', ', $failures);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => $succeeded, 'failures' => $failures, 'message' => $msg]);
        }
        return back()->with($failures ? 'error' : 'success', $msg);
    }

    /**
     * Auto-resolve all pending facility reservations using deterministic priority rules.
     * Only applies to facility bookings (equipment requests require manual admin approval).
     */
    public function autoResolveAll(Request $request)
    {
        $pendingReservations = FacilityReservation::where('status', 'pending')
            ->with(['user.role', 'facility'])
            ->get()
            ->sort(function ($a, $b) {
                $tierA = ResourceSolver::getUserPriority($a->user);
                $tierB = ResourceSolver::getUserPriority($b->user);
                if ($tierA !== $tierB) {
                    return $tierA <=> $tierB;
                }
                $timeCmp = $a->created_at <=> $b->created_at;
                if ($timeCmp !== 0) {
                    return $timeCmp;
                }
                return $a->id <=> $b->id;
            });

        $stats = ['approved' => 0, 'rejected' => 0, 'pending' => 0];

        foreach ($pendingReservations as $reservation) {
            $result = ResourceSolver::autoResolveFacilityReservation($reservation);
            $status = $result['status'] ?? 'pending';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        $msgParts = [];
        if ($stats['approved'] > 0) {
            $msgParts[] = "{$stats['approved']} automatically approved";
        }
        if ($stats['rejected'] > 0) {
            $msgParts[] = "{$stats['rejected']} automatically rejected";
        }
        if ($stats['pending'] > 0) {
            $msgParts[] = "{$stats['pending']} remained pending";
        }

        $message = empty($msgParts)
            ? 'No pending facility reservations found to auto-resolve.'
            : 'Facility automation complete: ' . implode(', ', $msgParts) . '.';

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'stats'   => $stats,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    // ─── User Cancel Own Pending Reservation ───

    public function cancelOwn(Request $request, FacilityReservation $reservation)
    {
        if ($reservation->user_id !== Auth::id()) {
            abort(403);
        }
        if ($reservation->status !== 'pending') {
            $msg = 'Only pending reservations can be cancelled.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $reservation->update(['status' => 'cancelled', 'remarks' => 'Cancelled by requester']);
        AuditLog::log('CANCELLED_RESERVATION', 'facility_reservations', $reservation->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => 'cancelled']);
        }
        return back()->with('success', 'Reservation cancelled.');
    }

    /**
     * User / Admin action: Complete a facility reservation early and free the facility,
     * immediately turning its state from Occupied to Open.
     */
    public function complete(Request $request, FacilityReservation $reservation)
    {
        $user = Auth::user();
        if ($reservation->user_id !== $user->id && !$user->hasPermission('facility.approve')) {
            abort(403, 'Unauthorized.');
        }

        if (!in_array($reservation->status, ['approved', 'pending'])) {
            return back()->with('error', 'Only active or approved reservations can be completed.');
        }

        $now = \Illuminate\Support\Carbon::now();
        $reservation->update([
            'status'   => 'completed',
            'time_end' => $now->format('H:i:s'),
            'remarks'  => trim(($reservation->remarks ? $reservation->remarks . ' | ' : '') . 'Completed & facility freed to open at ' . $now->format('g:i A')),
        ]);

        AuditLog::log('FACILITY_FREED_OPEN', 'facility_reservations', $reservation->id);

        $msg = "Facility \"{$reservation->facility->name}\" is now open and available for use.";
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return redirect('/dashboard?section=booked-facilities')->with('success', $msg);
    }
}

