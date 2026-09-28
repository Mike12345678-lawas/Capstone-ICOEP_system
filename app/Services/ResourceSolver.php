<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * ResourceSolver — Deterministic priority queue for auto-approving/rejecting
 * facility reservations and equipment borrow requests.
 *
 * NO AI. Pure rule-based heuristics:
 *  1. Double-booking prevention (hard block)
 *  2. Role-based priority tiers (Admin > Staff/Faculty > Student)
 *  3. First-Come-First-Served (FCFS) within the same tier
 *  4. Availability checks (quantity for equipment, time slots for facilities)
 */
class ResourceSolver
{
    /**
     * Priority tiers — lower number = higher priority.
     * Admin-created events/bookings outrank student requests.
     */
    protected const ROLE_PRIORITY = [
        'Administrator' => 1,
        'Faculty'       => 2,
        'Staff'         => 2,
        'Student'       => 3,
    ];

    // ─── FACILITY RESERVATIONS ───────────────────────────────────────────

    /**
     * Attempt to auto-resolve a facility reservation.
     *
     * @return array{status: string, reason: string, suggestions?: array}
     */
    public static function resolveFacilityReservation(
        Facility $facility,
        string $date,
        string $timeStart,
        string $timeEnd,
        User $requester
    ): array {
        // Rule 1: Facility must be available (not in maintenance)
        if ($facility->status !== 'available') {
            return [
                'status' => 'rejected',
                'reason' => "Facility \"{$facility->name}\" is currently under {$facility->status} and cannot be reserved.",
                'suggestions' => AlternativeSuggester::suggestAlternateFacilities($facility, $date, $timeStart, $timeEnd),
            ];
        }

        // Rule 2: Date must be in the future
        if (Carbon::parse($date)->isPast()) {
            return [
                'status' => 'rejected',
                'reason' => 'Cannot reserve a facility for a past date.',
            ];
        }

        // Rule 3: Check for double-booking (hard block)
        $conflicts = FacilityReservation::conflicts($facility->id, $date, $timeStart, $timeEnd)->get();

        if ($conflicts->isEmpty()) {
            // No conflicts — auto-approve (FCFS: first valid request wins)
            return [
                'status' => 'approved',
                'reason' => 'Auto-approved: no scheduling conflicts detected (FCFS).',
            ];
        }

        // Rule 4: Priority-based preemption check
        $requesterPriority = self::getUserPriority($requester);

        // Check if ALL conflicting reservations have lower priority
        $canPreempt = true;
        $preemptTargets = [];

        foreach ($conflicts as $conflict) {
            $conflictPriority = self::getUserPriority($conflict->user);

            if ($requesterPriority < $conflictPriority) {
                // Requester has higher priority — mark conflict for preemption
                $preemptTargets[] = $conflict;
            } else {
                // Equal or lower priority — cannot preempt
                $canPreempt = false;
                break;
            }
        }

        if ($canPreempt && !empty($preemptTargets)) {
            // Higher-priority user bumps lower-priority bookings
            return [
                'status'          => 'approved',
                'reason'          => 'Auto-approved: higher role-priority preempts existing booking(s).',
                'preempt_targets' => $preemptTargets,
            ];
        }

        // Rule 5: Rejected — suggest alternatives
        return [
            'status'      => 'rejected',
            'reason'      => 'Time slot conflict: the facility is already reserved during this period by a same-or-higher priority user.',
            'suggestions' => AlternativeSuggester::suggestAlternateTimeSlots($facility, $date, $timeStart, $timeEnd),
        ];
    }

    // ─── EQUIPMENT BORROW REQUESTS ───────────────────────────────────────

    /**
     * Attempt to auto-resolve an equipment borrow request.
     *
     * @return array{status: string, reason: string, suggestions?: array}
     */
    public static function resolveEquipmentRequest(
        Equipment $equipment,
        int $quantity,
        string $dateNeeded,
        string $expectedReturn,
        User $requester
    ): array {
        // Rule 1: Equipment must be available (not retired/maintenance)
        if ($equipment->status !== 'available') {
            return [
                'status'      => 'rejected',
                'reason'      => "Equipment \"{$equipment->name}\" is currently {$equipment->status} and cannot be borrowed.",
                'suggestions' => AlternativeSuggester::suggestAlternateEquipment($equipment, $quantity),
            ];
        }

        // Rule 2: Check quantity availability
        if ($equipment->available_quantity < $quantity) {
            if ($equipment->available_quantity > 0) {
                return [
                    'status'      => 'rejected',
                    'reason'      => "Only {$equipment->available_quantity} of {$equipment->quantity} units available. You requested {$quantity}.",
                    'suggestions' => AlternativeSuggester::suggestAlternateEquipment($equipment, $quantity),
                ];
            }

            // Check for overlapping borrow periods — maybe some will be returned before date_needed
            $returningBefore = EquipmentRequest::where('equipment_id', $equipment->id)
                ->where('status', 'borrowed')
                ->where('expected_return_date', '<=', $dateNeeded)
                ->sum('quantity');

            if ($returningBefore >= $quantity) {
                return [
                    'status' => 'approved',
                    'reason' => "Auto-approved: {$returningBefore} units expected to be returned before your requested date.",
                ];
            }

            return [
                'status'      => 'rejected',
                'reason'      => 'All units are currently borrowed and not expected back before your requested date.',
                'suggestions' => AlternativeSuggester::suggestAlternateEquipment($equipment, $quantity),
            ];
        }

        // Rule 3: Check for overlapping date conflicts (same equipment, same date range)
        $dateConflicts = EquipmentRequest::where('equipment_id', $equipment->id)
            ->whereIn('status', ['pending', 'approved', 'borrowed'])
            ->where('date_needed', '<=', $expectedReturn)
            ->where('expected_return_date', '>=', $dateNeeded)
            ->sum('quantity');

        $totalDemand = $dateConflicts + $quantity;

        if ($totalDemand > $equipment->quantity) {
            // Priority check: can this user preempt lower-priority requests?
            $requesterPriority = self::getUserPriority($requester);

            $conflictingRequests = EquipmentRequest::with('user')
                ->where('equipment_id', $equipment->id)
                ->whereIn('status', ['pending', 'approved'])
                ->where('date_needed', '<=', $expectedReturn)
                ->where('expected_return_date', '>=', $dateNeeded)
                ->orderByDesc('created_at')
                ->get();

            $freedQuantity = 0;
            $preemptTargets = [];

            foreach ($conflictingRequests as $cr) {
                $crPriority = self::getUserPriority($cr->user);
                if ($requesterPriority < $crPriority) {
                    $freedQuantity += $cr->quantity;
                    $preemptTargets[] = $cr;
                    if ($equipment->available_quantity + $freedQuantity >= $quantity) {
                        break;
                    }
                }
            }

            if ($equipment->available_quantity + $freedQuantity >= $quantity && !empty($preemptTargets)) {
                return [
                    'status'          => 'approved',
                    'reason'          => 'Auto-approved: higher role-priority preempts lower-priority request(s).',
                    'preempt_targets' => $preemptTargets,
                ];
            }

            return [
                'status'      => 'rejected',
                'reason'      => "Projected demand ({$totalDemand}) exceeds total inventory ({$equipment->quantity}) for the requested period.",
                'suggestions' => AlternativeSuggester::suggestAlternateEquipment($equipment, $quantity),
            ];
        }

        // All clear — auto-approve (FCFS)
        return [
            'status' => 'approved',
            'reason' => 'Auto-approved: sufficient quantity available, no conflicts (FCFS).',
        ];
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────

    protected static function getUserPriority(User $user): int
    {
        $roleName = $user->role->name ?? 'Student';
        return self::ROLE_PRIORITY[$roleName] ?? 3;
    }
}
