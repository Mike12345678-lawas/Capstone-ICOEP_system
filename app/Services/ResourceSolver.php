<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Facility;
use App\Models\FacilityReservation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * ResourceSolver — Deterministic priority queue & rule-based decision engine
 * for facility reservations and equipment borrow requests.
 *
 * NO AI. Pure deterministic heuristics:
 *  1. Priority tiers: Administrator = 1, Faculty = 2, Staff = 2, Student = 3
 *  2. FCFS tie-break: same tier -> earlier created_at, then lower id
 *  3. Operating window and max-duration validation (unqualified items remain pending for admin)
 *  4. Preemption rules for facilities and equipment
 *  5. Concurrency safety via DB::transaction and lockForUpdate()
 */
class ResourceSolver
{
    /**
     * Priority tiers — lower number = higher priority.
     */
    public const ROLE_PRIORITY = [
        'Administrator'     => 1,
        'Faculty'           => 2,
        'Staff'             => 2,
        'Registrar'         => 2,
        'Librarian'         => 2,
        'Maintenance Staff' => 2,
        'ComLab Staff'      => 2,
        'Student'           => 3,
    ];

    /**
     * Get numeric priority for a user based on role (lower number = higher priority).
     */
    public static function getUserPriority(User $user): int
    {
        $roleName = $user->role->name ?? 'Student';
        return self::ROLE_PRIORITY[$roleName] ?? 3;
    }

    // ─────────────────────────────────────────────────────────────────────
    // FACILITY RESERVATIONS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Process a new facility reservation request inside a concurrency-safe lock.
     *
     * @param array{title: string, description?: string|null, reservation_date: string, time_start: string, time_end: string} $data
     * @return array{status: string, reason?: string, message?: string, reservation: FacilityReservation, suggestions?: array, preempted?: array}
     */
    public static function processFacilityReservation(
        Facility $facility,
        User $requester,
        array $data
    ): array {
        return DB::transaction(function () use ($facility, $requester, $data) {
            // Lock facility & existing reservations to prevent concurrent races
            Facility::lockForUpdate()->find($facility->id);
            FacilityReservation::where('facility_id', $facility->id)
                ->whereDate('reservation_date', $data['reservation_date'])
                ->lockForUpdate()
                ->get();

            $date      = $data['reservation_date'];
            $timeStart = $data['time_start'];
            $timeEnd   = $data['time_end'];
            $title     = $data['title'];
            $desc      = $data['description'] ?? null;

            $startDt   = Carbon::parse($date . ' ' . $timeStart);

            // Rule 1: Facility must be available and date/time not in the past
            if ($facility->status !== 'available') {
                $reason = "Facility \"{$facility->name}\" is currently {$facility->status} and cannot be reserved.";
                $suggestions = AlternativeSuggester::suggestFacilityAlternatives($facility, $date, $timeStart, $timeEnd);

                $reservation = FacilityReservation::create([
                    'facility_id'      => $facility->id,
                    'user_id'          => $requester->id,
                    'title'            => $title,
                    'description'      => $desc,
                    'reservation_date' => $date,
                    'time_start'       => $timeStart,
                    'time_end'         => $timeEnd,
                    'status'           => 'rejected',
                    'remarks'          => $reason,
                    'approved_by'      => null,
                ]);

                AuditLog::log('AUTO_REJECTED_RESERVATION', 'facility_reservations', $reservation->id, $requester->id);

                return [
                    'status'      => 'rejected',
                    'reason'      => $reason,
                    'reservation' => $reservation,
                    'suggestions' => $suggestions,
                ];
            }

            if ($startDt->isPast()) {
                $reason = 'Cannot reserve a facility for a past date or time.';
                $suggestions = AlternativeSuggester::suggestFacilityAlternatives($facility, $date, $timeStart, $timeEnd);

                $reservation = FacilityReservation::create([
                    'facility_id'      => $facility->id,
                    'user_id'          => $requester->id,
                    'title'            => $title,
                    'description'      => $desc,
                    'reservation_date' => $date,
                    'time_start'       => $timeStart,
                    'time_end'         => $timeEnd,
                    'status'           => 'rejected',
                    'remarks'          => $reason,
                    'approved_by'      => null,
                ]);

                AuditLog::log('AUTO_REJECTED_RESERVATION', 'facility_reservations', $reservation->id, $requester->id);

                return [
                    'status'      => 'rejected',
                    'reason'      => $reason,
                    'reservation' => $reservation,
                    'suggestions' => $suggestions,
                ];
            }

            // Rule 2: Operating hours and max duration check
            $autoCfg          = config('icoep.auto_approve.facility', []);
            $enabled          = (bool) ($autoCfg['enabled'] ?? true);
            $allowedRoles     = $autoCfg['allowed_roles'] ?? ['Administrator', 'Faculty', 'Staff', 'Student'];
            $opStart          = $autoCfg['operating_start'] ?? '06:00';
            $opEnd            = $autoCfg['operating_end'] ?? '23:00';
            $maxDurationHours = (int) ($autoCfg['max_duration_hours'] ?? 0);

            $reqStartDt    = Carbon::parse($timeStart);
            $reqEndDt      = Carbon::parse($timeEnd);
            $durationHours = $reqStartDt->diffInMinutes($reqEndDt) / 60;

            $withinHours = $timeStart >= $opStart && $timeEnd <= $opEnd;
            $withinDur   = $maxDurationHours === 0 || $durationHours <= $maxDurationHours;
            $roleName    = $requester->role->name ?? 'Student';
            $roleAllowed = in_array($roleName, $allowedRoles, true);

            if (!$enabled || !$roleAllowed || !$withinHours || !$withinDur) {
                // Outside operating window or limits -> keep pending for admin
                $reservation = FacilityReservation::create([
                    'facility_id'      => $facility->id,
                    'user_id'          => $requester->id,
                    'title'            => $title,
                    'description'      => $desc,
                    'reservation_date' => $date,
                    'time_start'       => $timeStart,
                    'time_end'         => $timeEnd,
                    'status'           => 'pending',
                    'remarks'          => 'Pending admin approval',
                    'approved_by'      => null,
                ]);

                AuditLog::log('CREATE_RESERVATION', 'facility_reservations', $reservation->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '⏳ Reservation Pending',
                    "Your reservation request for \"{$facility->name}\" on {$reservation->reservation_date->format('M d, Y')} ({$timeStart}–{$timeEnd}) has been submitted and is pending admin approval.",
                    'info',
                    '/facilities/' . $facility->id
                );

                // Notify Admins ONLY for items left pending
                self::notifyAdmins(
                    '🔔 New Facility Reservation',
                    "{$requester->full_name} has requested to reserve \"{$facility->name}\" for {$reservation->reservation_date->format('M d, Y')} ({$timeStart}–{$timeEnd}).",
                    '/dashboard?section=manage-bookings'
                );

                return [
                    'status'      => 'pending',
                    'reason'      => 'Request is outside standard auto-approval parameters and is pending admin review.',
                    'reservation' => $reservation,
                ];
            }

            // Rule 3: Check overlap with approved reservations
            $conflicts = FacilityReservation::conflicts($facility->id, $date, $timeStart, $timeEnd)
                ->with('user.role')
                ->get();

            if ($conflicts->isEmpty()) {
                // No overlap -> auto-approve (FCFS)
                $reservation = FacilityReservation::create([
                    'facility_id'      => $facility->id,
                    'user_id'          => $requester->id,
                    'title'            => $title,
                    'description'      => $desc,
                    'reservation_date' => $date,
                    'time_start'       => $timeStart,
                    'time_end'         => $timeEnd,
                    'status'           => 'approved',
                    'remarks'          => 'Auto-approved by system rule.',
                    'approved_by'      => null,
                ]);

                AuditLog::log('AUTO_APPROVED_RESERVATION', 'facility_reservations', $reservation->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '✅ Reservation Approved',
                    "Your reservation for \"{$facility->name}\" on {$reservation->reservation_date->format('M d, Y')} ({$timeStart}–{$timeEnd}) was automatically approved.",
                    'success',
                    '/facilities/' . $facility->id
                );

                return [
                    'status'      => 'approved',
                    'message'     => 'Reservation automatically approved — no conflicts found.',
                    'reservation' => $reservation,
                ];
            }

            // Rule 4: Overlap exists -> check preemption eligibility
            $requesterTier    = self::getUserPriority($requester);
            $preemptibleRoles = $autoCfg['preemptible_roles'] ?? ['Student'];
            $minHoursBefore   = (int) ($autoCfg['preempt_min_hours_before'] ?? 24);

            $canPreempt = true;
            foreach ($conflicts as $conflict) {
                $conflictUser = $conflict->user;
                $confTier     = self::getUserPriority($conflictUser);
                $confRole     = $conflictUser->role->name ?? 'Student';
                $confStart    = Carbon::parse($conflict->reservation_date->toDateString() . ' ' . $conflict->time_start);

                // Condition a: Requester tier strictly higher (lower number wins)
                if ($requesterTier >= $confTier) {
                    $canPreempt = false;
                    break;
                }

                // Condition b: Conflict belongs to a preemptible role
                if (!in_array($confRole, $preemptibleRoles, true)) {
                    $canPreempt = false;
                    break;
                }

                // Condition c: Conflict starts at least preempt_min_hours_before from now
                if ($confStart->lt(now()->addHours($minHoursBefore))) {
                    $canPreempt = false;
                    break;
                }
            }

            if ($canPreempt) {
                // Execute preemption: cancel overlapping approved reservations
                $preemptedReservations = [];

                foreach ($conflicts as $conflict) {
                    $conflict->update([
                        'status'  => 'cancelled',
                        'remarks' => "Cancelled: slot preempted by higher-priority request ({$roleName}).",
                    ]);

                    AuditLog::log('PREEMPTED_RESERVATION', 'facility_reservations', $conflict->id, $conflict->user_id);

                    // Build suggestions for bumped user
                    $bumpedSuggestions = AlternativeSuggester::suggestFacilityAlternatives(
                        $facility,
                        $conflict->reservation_date->toDateString(),
                        $conflict->time_start,
                        $conflict->time_end
                    );

                    $altText = '';
                    if (!empty($bumpedSuggestions)) {
                        $labels = array_map(fn($s) => $s['label'] ?? '', array_slice($bumpedSuggestions, 0, 3));
                        $altText = ' Suggested alternatives: ' . implode(', ', array_filter($labels));
                    }

                    Notification::send(
                        $conflict->user_id,
                        '⚠️ Reservation Preempted',
                        "Your reservation for \"{$facility->name}\" on {$conflict->reservation_date->format('M d, Y')} ({$conflict->time_start}–{$conflict->time_end}) was cancelled due to a higher-priority {$roleName} booking.{$altText}",
                        'warning',
                        '/facilities/' . $facility->id
                    );

                    $preemptedReservations[] = $conflict;
                }

                // Auto-approve the new reservation
                $reservation = FacilityReservation::create([
                    'facility_id'      => $facility->id,
                    'user_id'          => $requester->id,
                    'title'            => $title,
                    'description'      => $desc,
                    'reservation_date' => $date,
                    'time_start'       => $timeStart,
                    'time_end'         => $timeEnd,
                    'status'           => 'approved',
                    'remarks'          => 'Auto-approved with priority preemption.',
                    'approved_by'      => null,
                ]);

                AuditLog::log('AUTO_APPROVED_RESERVATION', 'facility_reservations', $reservation->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '✅ Reservation Approved',
                    "Your reservation for \"{$facility->name}\" on {$reservation->reservation_date->format('M d, Y')} ({$timeStart}–{$timeEnd}) was automatically approved with priority preemption.",
                    'success',
                    '/facilities/' . $facility->id
                );

                return [
                    'status'      => 'approved',
                    'message'     => 'Reservation automatically approved with priority preemption.',
                    'reservation' => $reservation,
                    'preempted'   => $preemptedReservations,
                ];
            }

            // Cannot preempt -> Auto-reject with reason & save the rejected request
            $reason = 'Time slot conflict: the facility is already reserved during this period.';
            $suggestions = AlternativeSuggester::suggestFacilityAlternatives($facility, $date, $timeStart, $timeEnd);

            $reservation = FacilityReservation::create([
                'facility_id'      => $facility->id,
                'user_id'          => $requester->id,
                'title'            => $title,
                'description'      => $desc,
                'reservation_date' => $date,
                'time_start'       => $timeStart,
                'time_end'         => $timeEnd,
                'status'           => 'rejected',
                'remarks'          => $reason,
                'approved_by'      => null,
            ]);

            AuditLog::log('AUTO_REJECTED_RESERVATION', 'facility_reservations', $reservation->id, $requester->id);

            return [
                'status'      => 'rejected',
                'reason'      => $reason,
                'reservation' => $reservation,
                'suggestions' => $suggestions,
            ];
        });
    }

    /**
     * Auto-resolve an existing pending facility reservation (for queue reprocessing).
     *
     * @return array{status: string, reason?: string, action?: string, reservation: FacilityReservation}
     */
    public static function autoResolveFacilityReservation(
        FacilityReservation $reservation,
        bool $dryRun = false
    ): array {
        return DB::transaction(function () use ($reservation, $dryRun) {
            $lockedRes = FacilityReservation::lockForUpdate()->with(['user.role', 'facility'])->find($reservation->id);
            if (!$lockedRes || $lockedRes->status !== 'pending') {
                return ['status' => 'skipped', 'reason' => 'Reservation is not pending', 'reservation' => $reservation];
            }

            $facility = Facility::lockForUpdate()->find($lockedRes->facility_id);
            $requester = $lockedRes->user;
            $date = $lockedRes->reservation_date->toDateString();
            $timeStart = substr($lockedRes->time_start, 0, 5);
            $timeEnd = substr($lockedRes->time_end, 0, 5);
            $startDt = Carbon::parse($date . ' ' . $timeStart);

            // Rule 1: Facility available & date not past
            if ($facility->status !== 'available' || $startDt->isPast()) {
                $reason = $facility->status !== 'available'
                    ? "Facility \"{$facility->name}\" is currently {$facility->status}."
                    : 'Cannot reserve a facility for a past date or time.';

                if ($dryRun) {
                    return ['status' => 'rejected', 'action' => 'would_reject', 'reason' => $reason, 'reservation' => $lockedRes];
                }

                $lockedRes->update([
                    'status'      => 'rejected',
                    'remarks'     => $reason,
                    'approved_by' => null,
                ]);

                AuditLog::log('AUTO_REJECTED_RESERVATION', 'facility_reservations', $lockedRes->id, $requester->id);

                return ['status' => 'rejected', 'action' => 'rejected', 'reason' => $reason, 'reservation' => $lockedRes];
            }

            // Rule 2: Operating hours & limits
            $autoCfg          = config('icoep.auto_approve.facility', []);
            $enabled          = (bool) ($autoCfg['enabled'] ?? true);
            $allowedRoles     = $autoCfg['allowed_roles'] ?? ['Administrator', 'Faculty', 'Staff', 'Student'];
            $opStart          = $autoCfg['operating_start'] ?? '06:00';
            $opEnd            = $autoCfg['operating_end'] ?? '23:00';
            $maxDurationHours = (int) ($autoCfg['max_duration_hours'] ?? 0);

            $reqStartDt    = Carbon::parse($timeStart);
            $reqEndDt      = Carbon::parse($timeEnd);
            $durationHours = $reqStartDt->diffInMinutes($reqEndDt) / 60;

            $withinHours = $timeStart >= $opStart && $timeEnd <= $opEnd;
            $withinDur   = $maxDurationHours === 0 || $durationHours <= $maxDurationHours;
            $roleName    = $requester->role->name ?? 'Student';
            $roleAllowed = in_array($roleName, $allowedRoles, true);

            if (!$enabled || !$roleAllowed || !$withinHours || !$withinDur) {
                return ['status' => 'pending', 'action' => 'remain_pending', 'reason' => 'Outside operating parameters', 'reservation' => $lockedRes];
            }

            // Rule 3: Check conflicts against approved bookings
            $conflicts = FacilityReservation::conflicts($facility->id, $date, $timeStart, $timeEnd)
                ->where('id', '!=', $lockedRes->id)
                ->with('user.role')
                ->get();

            if ($conflicts->isEmpty()) {
                if ($dryRun) {
                    return ['status' => 'approved', 'action' => 'would_approve', 'reservation' => $lockedRes];
                }

                $lockedRes->update([
                    'status'      => 'approved',
                    'remarks'     => 'Auto-approved by system rule.',
                    'approved_by' => null,
                ]);

                AuditLog::log('AUTO_APPROVED_RESERVATION', 'facility_reservations', $lockedRes->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '✅ Reservation Approved',
                    "Your reservation for \"{$facility->name}\" on {$lockedRes->reservation_date->format('M d, Y')} ({$timeStart}–{$timeEnd}) was automatically approved.",
                    'success',
                    '/facilities/' . $facility->id
                );

                return ['status' => 'approved', 'action' => 'approved', 'reservation' => $lockedRes];
            }

            // Rule 4: Overlap exists -> check preemption
            $requesterTier    = self::getUserPriority($requester);
            $preemptibleRoles = $autoCfg['preemptible_roles'] ?? ['Student'];
            $minHoursBefore   = (int) ($autoCfg['preempt_min_hours_before'] ?? 24);

            $canPreempt = true;
            foreach ($conflicts as $conflict) {
                $confUser  = $conflict->user;
                $confTier  = self::getUserPriority($confUser);
                $confRole  = $confUser->role->name ?? 'Student';
                $confStart = Carbon::parse($conflict->reservation_date->toDateString() . ' ' . $conflict->time_start);

                if ($requesterTier >= $confTier || !in_array($confRole, $preemptibleRoles, true) || $confStart->lt(now()->addHours($minHoursBefore))) {
                    $canPreempt = false;
                    break;
                }
            }

            if ($canPreempt) {
                if ($dryRun) {
                    return ['status' => 'approved', 'action' => 'would_preempt_and_approve', 'reservation' => $lockedRes];
                }

                foreach ($conflicts as $conflict) {
                    $conflict->update([
                        'status'  => 'cancelled',
                        'remarks' => "Cancelled: slot preempted by higher-priority request ({$roleName}).",
                    ]);

                    AuditLog::log('PREEMPTED_RESERVATION', 'facility_reservations', $conflict->id, $conflict->user_id);

                    Notification::send(
                        $conflict->user_id,
                        '⚠️ Reservation Preempted',
                        "Your reservation for \"{$facility->name}\" on {$conflict->reservation_date->format('M d, Y')} ({$conflict->time_start}–{$conflict->time_end}) was cancelled due to a higher-priority {$roleName} booking.",
                        'warning',
                        '/facilities/' . $facility->id
                    );
                }

                $lockedRes->update([
                    'status'      => 'approved',
                    'remarks'     => 'Auto-approved with priority preemption.',
                    'approved_by' => null,
                ]);

                AuditLog::log('AUTO_APPROVED_RESERVATION', 'facility_reservations', $lockedRes->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '✅ Reservation Approved',
                    "Your reservation for \"{$facility->name}\" on {$lockedRes->reservation_date->format('M d, Y')} ({$timeStart}–{$timeEnd}) was automatically approved with priority preemption.",
                    'success',
                    '/facilities/' . $facility->id
                );

                return ['status' => 'approved', 'action' => 'preempted_and_approved', 'reservation' => $lockedRes];
            }

            // Cannot preempt -> reject
            $reason = 'Time slot conflict: the facility is already reserved during this period.';

            if ($dryRun) {
                return ['status' => 'rejected', 'action' => 'would_reject', 'reason' => $reason, 'reservation' => $lockedRes];
            }

            $lockedRes->update([
                'status'      => 'rejected',
                'remarks'     => $reason,
                'approved_by' => null,
            ]);

            AuditLog::log('AUTO_REJECTED_RESERVATION', 'facility_reservations', $lockedRes->id, $requester->id);

            return ['status' => 'rejected', 'action' => 'rejected', 'reason' => $reason, 'reservation' => $lockedRes];
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // EQUIPMENT BORROW REQUESTS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Process a new equipment borrow request inside a concurrency-safe lock.
     *
     * @param array{quantity: int|string, purpose: string, date_needed: string, expected_return_date: string} $data
     * @return array{status: string, reason?: string, message?: string, request: EquipmentRequest, suggestions?: array, preempted?: array}
     */
    public static function processEquipmentRequest(
        Equipment $equipment,
        User $requester,
        array $data
    ): array {
        return DB::transaction(function () use ($equipment, $requester, $data) {
            // Lock equipment & requests to prevent concurrent races
            $lockedEquip = Equipment::lockForUpdate()->find($equipment->id);
            EquipmentRequest::where('equipment_id', $equipment->id)->lockForUpdate()->get();

            $quantity       = (int) $data['quantity'];
            $purpose        = $data['purpose'];
            $dateNeeded     = $data['date_needed'];
            $expectedReturn = $data['expected_return_date'];

            // Rule 0: User cannot borrow if having equipment overdue beyond threshold
            $thresholdDays = (int) config('icoep.block_borrowing_after_days_overdue', 3);
            $blockingOverdue = EquipmentRequest::with('equipment')
                ->where('user_id', $requester->id)
                ->where('status', 'borrowed')
                ->where('expected_return_date', '<', Carbon::now()->startOfDay())
                ->get()
                ->first(function ($r) use ($thresholdDays) {
                    return (int) $r->expected_return_date->startOfDay()->diffInDays(Carbon::now()->startOfDay()) > $thresholdDays;
                });

            if ($blockingOverdue) {
                $days = (int) $blockingOverdue->expected_return_date->startOfDay()->diffInDays(Carbon::now()->startOfDay());
                $reason = "Borrowing is blocked: you have an overdue item (\"{$blockingOverdue->equipment->name}\") that is {$days} day(s) overdue. Please return it before requesting equipment.";
                return [
                    'status'  => 'rejected',
                    'reason'  => $reason,
                    'request' => null,
                ];
            }

            // Rule 1: Equipment must be available
            if ($lockedEquip->status !== 'available') {
                $reason = "Equipment \"{$lockedEquip->name}\" is currently {$lockedEquip->status} and cannot be borrowed.";
                $suggestions = AlternativeSuggester::suggestAlternateEquipment($lockedEquip, $quantity, $dateNeeded, $expectedReturn);

                $eqRequest = EquipmentRequest::create([
                    'equipment_id'         => $lockedEquip->id,
                    'user_id'              => $requester->id,
                    'quantity'             => $quantity,
                    'purpose'              => $purpose,
                    'date_needed'          => $dateNeeded,
                    'expected_return_date' => $expectedReturn,
                    'status'               => 'rejected',
                    'remarks'              => $reason,
                    'approved_by'          => null,
                ]);

                AuditLog::log('AUTO_REJECTED_BORROW', 'equipment_requests', $eqRequest->id, $requester->id);

                return [
                    'status'      => 'rejected',
                    'reason'      => $reason,
                    'request'     => $eqRequest,
                    'suggestions' => $suggestions,
                ];
            }

            // Rule 2: Date needed cannot be before today
            $dateNeededStr = Carbon::parse($dateNeeded)->toDateString();
            $todayStr      = Carbon::today()->toDateString();
            if ($dateNeededStr < $todayStr) {
                $reason = 'Cannot borrow equipment for a past date.';
                $suggestions = AlternativeSuggester::suggestAlternateEquipment($lockedEquip, $quantity, $dateNeeded, $expectedReturn);

                $eqRequest = EquipmentRequest::create([
                    'equipment_id'         => $lockedEquip->id,
                    'user_id'              => $requester->id,
                    'quantity'             => $quantity,
                    'purpose'              => $purpose,
                    'date_needed'          => $dateNeeded,
                    'expected_return_date' => $expectedReturn,
                    'status'               => 'rejected',
                    'remarks'              => $reason,
                    'approved_by'          => null,
                ]);

                AuditLog::log('AUTO_REJECTED_BORROW', 'equipment_requests', $eqRequest->id, $requester->id);

                return [
                    'status'      => 'rejected',
                    'reason'      => $reason,
                    'request'     => $eqRequest,
                    'suggestions' => $suggestions,
                ];
            }

            // If auto-approval is enabled and sufficient stock is available, auto-approve so borrower gets the item immediately
            $autoApproveEquip = (bool) config('icoep.auto_approve.equipment.enabled', true);
            if ($autoApproveEquip && $lockedEquip->available_quantity >= $quantity) {
                $lockedEquip->decrement('available_quantity', $quantity);

                $eqRequest = EquipmentRequest::create([
                    'equipment_id'         => $lockedEquip->id,
                    'user_id'              => $requester->id,
                    'quantity'             => $quantity,
                    'purpose'              => $purpose,
                    'date_needed'          => $dateNeeded,
                    'expected_return_date' => $expectedReturn,
                    'status'               => 'approved',
                    'remarks'              => 'Auto-approved: stock reserved.',
                    'approved_by'          => null,
                ]);

                AuditLog::log('AUTO_APPROVED_BORROW', 'equipment_requests', $eqRequest->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '✅ Equipment Borrow Approved',
                    "Your borrow request for \"{$lockedEquip->name}\" (qty: {$quantity}) has been approved! You can track and return it below.",
                    'success',
                    '/dashboard?section=borrowed-equipment'
                );

                DB::afterCommit(function () use ($eqRequest) {
                    CustodianNotificationService::notifyPickupReady($eqRequest);
                });

                return [
                    'status'  => 'approved',
                    'message' => 'Equipment borrow request approved! Track and return your equipment below.',
                    'request' => $eqRequest,
                ];
            }

            // Insufficient immediate stock — keep as pending for manual admin review
            $eqRequest = EquipmentRequest::create([
                'equipment_id'         => $lockedEquip->id,
                'user_id'              => $requester->id,
                'quantity'             => $quantity,
                'purpose'              => $purpose,
                'date_needed'          => $dateNeeded,
                'expected_return_date' => $expectedReturn,
                'status'               => 'pending',
                'remarks'              => 'Pending admin approval',
                'approved_by'          => null,
            ]);

            AuditLog::log('CREATE_BORROW', 'equipment_requests', $eqRequest->id, $requester->id);

            Notification::send(
                $requester->id,
                '⏳ Equipment Request Pending',
                "Your borrow request for \"{$lockedEquip->name}\" (qty: {$quantity}) has been submitted and is pending admin approval.",
                'info',
                '/dashboard?section=borrowed-equipment'
            );

            self::notifyAdmins(
                '🔔 New Equipment Request',
                "{$requester->full_name} has requested to borrow \"{$lockedEquip->name}\" (qty: {$quantity}).",
                '/dashboard?section=manage-equipment'
            );

            return [
                'status'  => 'pending',
                'reason'  => 'Request requires manual admin review due to stock.',
                'message' => 'Equipment request submitted. Waiting for admin approval.',
                'request' => $eqRequest,
            ];
        });
    }

    /**
     * Auto-resolve an existing pending equipment request (for queue reprocessing).
     *
     * @return array{status: string, reason?: string, action?: string, request: EquipmentRequest}
     */
    public static function autoResolveEquipmentRequest(
        EquipmentRequest $request,
        bool $dryRun = false
    ): array {
        return DB::transaction(function () use ($request, $dryRun) {
            $lockedReq = EquipmentRequest::lockForUpdate()->with(['user.role', 'equipment'])->find($request->id);
            if (!$lockedReq || $lockedReq->status !== 'pending') {
                return ['status' => 'skipped', 'reason' => 'Request is not pending', 'request' => $request];
            }

            $equipment = Equipment::lockForUpdate()->find($lockedReq->equipment_id);
            $requester = $lockedReq->user;
            $quantity  = (int) $lockedReq->quantity;
            $dateNeeded = $lockedReq->date_needed->toDateString();
            $expectedReturn = $lockedReq->expected_return_date->toDateString();

            // Rule 1: Equipment available & date not before today
            $isPastDate = Carbon::parse($dateNeeded)->toDateString() < Carbon::today()->toDateString();
            if ($equipment->status !== 'available' || $isPastDate) {
                $reason = $equipment->status !== 'available'
                    ? "Equipment \"{$equipment->name}\" is currently {$equipment->status}."
                    : 'Cannot borrow equipment for a past date.';

                if ($dryRun) {
                    return ['status' => 'rejected', 'action' => 'would_reject', 'reason' => $reason, 'request' => $lockedReq];
                }

                $lockedReq->update([
                    'status'      => 'rejected',
                    'remarks'     => $reason,
                    'approved_by' => null,
                ]);

                AuditLog::log('AUTO_REJECTED_BORROW', 'equipment_requests', $lockedReq->id, $requester->id);

                return ['status' => 'rejected', 'action' => 'rejected', 'reason' => $reason, 'request' => $lockedReq];
            }

            // Rule 2: Config check
            $autoCfg      = config('icoep.auto_approve.equipment', []);
            $enabled      = (bool) ($autoCfg['enabled'] ?? false);
            $allowedRoles = $autoCfg['allowed_roles'] ?? ['Administrator', 'Faculty', 'Staff', 'Student'];
            $buffer       = (int) ($autoCfg['min_stock_buffer'] ?? 0);
            $roleName     = $requester->role->name ?? 'Student';
            $roleAllowed  = in_array($roleName, $allowedRoles, true);

            if (!$enabled || !$roleAllowed) {
                return ['status' => 'pending', 'action' => 'remain_pending', 'reason' => 'Auto-approval is disabled for equipment. Only facilities support auto-approval.', 'request' => $lockedReq];
            }

            // Rule 3: Compute stock for the date range
            $overlappingRequests = EquipmentRequest::where('equipment_id', $equipment->id)
                ->whereIn('status', ['approved', 'borrowed'])
                ->where('id', '!=', $lockedReq->id)
                ->where('date_needed', '<=', $expectedReturn)
                ->where('expected_return_date', '>=', $dateNeeded)
                ->with('user.role')
                ->get();

            $overlappingDemand = (int) $overlappingRequests->sum('quantity');
            $availableStockForRange = max(0, $equipment->quantity - $overlappingDemand);
            $neededTotal = $quantity + $buffer;

            if ($availableStockForRange >= $neededTotal && $equipment->available_quantity >= $quantity) {
                if ($dryRun) {
                    return ['status' => 'approved', 'action' => 'would_approve', 'request' => $lockedReq];
                }

                $equipment->decrement('available_quantity', $quantity);

                $lockedReq->update([
                    'status'      => 'approved',
                    'remarks'     => 'Auto-approved by system rule.',
                    'approved_by' => null,
                ]);

                AuditLog::log('AUTO_APPROVED_BORROW', 'equipment_requests', $lockedReq->id, $requester->id);

                Notification::send(
                    $requester->id,
                    '✅ Equipment Request Approved',
                    "Your borrow request for \"{$equipment->name}\" (qty: {$quantity}) was automatically approved.",
                    'success',
                    '/equipment/' . $equipment->id
                );

                DB::afterCommit(function () use ($lockedReq) {
                    CustodianNotificationService::notifyPickupReady($lockedReq);
                });

                return ['status' => 'approved', 'action' => 'approved', 'request' => $lockedReq];
            }

            // Rule 4: Not enough stock -> preemption check
            $requesterTier    = self::getUserPriority($requester);
            $preemptibleRoles = $autoCfg['preemptible_roles'] ?? ['Student'];

            $candidates = $overlappingRequests->filter(function ($req) use ($requesterTier, $preemptibleRoles) {
                if ($req->status !== 'approved') return false;
                $candRole = $req->user->role->name ?? 'Student';
                if (!in_array($candRole, $preemptibleRoles, true)) return false;
                $candTier = self::getUserPriority($req->user);
                return $requesterTier < $candTier;
            })->sort(function ($a, $b) {
                $timeDiff = $b->created_at <=> $a->created_at;
                if ($timeDiff !== 0) return $timeDiff;
                return $b->id <=> $a->id;
            });

            $totalCandidateQty = (int) $candidates->sum('quantity');
            $stockDeficit = $neededTotal - $availableStockForRange;

            if ($totalCandidateQty < $stockDeficit || ($equipment->available_quantity + $totalCandidateQty) < $quantity) {
                $reason = "Only {$availableStockForRange} of {$equipment->quantity} unit(s) available for the requested period. You requested {$quantity}.";

                if ($dryRun) {
                    return ['status' => 'rejected', 'action' => 'would_reject', 'reason' => $reason, 'request' => $lockedReq];
                }

                $lockedReq->update([
                    'status'      => 'rejected',
                    'remarks'     => $reason,
                    'approved_by' => null,
                ]);

                AuditLog::log('AUTO_REJECTED_BORROW', 'equipment_requests', $lockedReq->id, $requester->id);

                return ['status' => 'rejected', 'action' => 'rejected', 'reason' => $reason, 'request' => $lockedReq];
            }

            // Preemption succeeds
            if ($dryRun) {
                return ['status' => 'approved', 'action' => 'would_preempt_and_approve', 'request' => $lockedReq];
            }

            $freedStock = 0;
            foreach ($candidates as $cand) {
                if (($availableStockForRange + $freedStock) >= $neededTotal && ($equipment->available_quantity >= $quantity)) {
                    break;
                }

                $cand->update([
                    'status'  => 'cancelled',
                    'remarks' => "Cancelled: stock preempted by higher-priority request ({$roleName}).",
                ]);

                $equipment->increment('available_quantity', $cand->quantity);
                $freedStock += $cand->quantity;

                AuditLog::log('PREEMPTED_BORROW', 'equipment_requests', $cand->id, $cand->user_id);

                Notification::send(
                    $cand->user_id,
                    '⚠️ Equipment Request Preempted',
                    "Your borrow request for \"{$equipment->name}\" (qty: {$cand->quantity}) was cancelled due to a higher-priority {$roleName} request.",
                    'warning',
                    '/equipment/' . $equipment->id
                );
            }

            $equipment->decrement('available_quantity', $quantity);

            $lockedReq->update([
                'status'      => 'approved',
                'remarks'     => 'Auto-approved with priority preemption.',
                'approved_by' => null,
            ]);

            AuditLog::log('AUTO_APPROVED_BORROW', 'equipment_requests', $lockedReq->id, $requester->id);

            Notification::send(
                $requester->id,
                '✅ Equipment Request Approved',
                "Your borrow request for \"{$equipment->name}\" (qty: {$quantity}) was automatically approved with priority preemption.",
                'success',
                '/equipment/' . $equipment->id
            );

            DB::afterCommit(function () use ($lockedReq) {
                CustodianNotificationService::notifyPickupReady($lockedReq);
            });

            return ['status' => 'approved', 'action' => 'preempted_and_approved', 'request' => $lockedReq];
        });
    }

    // ─────────────────────────────────────────────────────────────────────
    // COMPATIBILITY HELPERS
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Backward-compatibility wrapper for resolveFacilityReservation.
     */
    public static function resolveFacilityReservation(
        Facility $facility,
        string $date,
        string $timeStart,
        string $timeEnd,
        User $requester
    ): array {
        if ($facility->status !== 'available') {
            return [
                'status'      => 'rejected',
                'reason'      => "Facility \"{$facility->name}\" is currently {$facility->status} and cannot be reserved.",
                'suggestions' => AlternativeSuggester::suggestFacilityAlternatives($facility, $date, $timeStart, $timeEnd),
            ];
        }

        if (Carbon::parse($date . ' ' . $timeStart)->isPast()) {
            return [
                'status'      => 'rejected',
                'reason'      => 'Cannot reserve a facility for a past date or time.',
                'suggestions' => AlternativeSuggester::suggestFacilityAlternatives($facility, $date, $timeStart, $timeEnd),
            ];
        }

        $conflicts = FacilityReservation::conflicts($facility->id, $date, $timeStart, $timeEnd)
            ->where('status', 'approved')
            ->count();

        if ($conflicts === 0) {
            return [
                'status' => 'approved',
                'reason' => 'No scheduling conflicts detected.',
            ];
        }

        return [
            'status'      => 'rejected',
            'reason'      => 'Time slot conflict: the facility is already reserved during this period.',
            'suggestions' => AlternativeSuggester::suggestFacilityAlternatives($facility, $date, $timeStart, $timeEnd),
        ];
    }

    /**
     * Backward-compatibility wrapper for resolveEquipmentRequest.
     */
    public static function resolveEquipmentRequest(
        Equipment $equipment,
        int $quantity,
        string $dateNeeded,
        string $expectedReturn,
        User $requester
    ): array {
        if ($equipment->status !== 'available') {
            return [
                'status'      => 'rejected',
                'reason'      => "Equipment \"{$equipment->name}\" is currently {$equipment->status} and cannot be borrowed.",
                'suggestions' => AlternativeSuggester::suggestAlternateEquipment($equipment, $quantity, $dateNeeded, $expectedReturn),
            ];
        }

        $dateConflicts = EquipmentRequest::where('equipment_id', $equipment->id)
            ->whereIn('status', ['approved', 'borrowed'])
            ->where('date_needed', '<=', $expectedReturn)
            ->where('expected_return_date', '>=', $dateNeeded)
            ->sum('quantity');

        $available = max(0, $equipment->quantity - $dateConflicts);

        if ($available < $quantity) {
            return [
                'status'      => 'rejected',
                'reason'      => "Only {$available} of {$equipment->quantity} unit(s) available for the requested period. You requested {$quantity}.",
                'suggestions' => AlternativeSuggester::suggestAlternateEquipment($equipment, $quantity, $dateNeeded, $expectedReturn),
            ];
        }

        return [
            'status' => 'approved',
            'reason' => 'Sufficient quantity available, no conflicts.',
        ];
    }

    /**
     * Helper to notify all Administrators.
     */
    protected static function notifyAdmins(string $title, string $message, string $link): void
    {
        $admins = User::whereHas('role', function ($q) {
            $q->where('name', 'Administrator');
        })->get();

        foreach ($admins as $admin) {
            Notification::send($admin->id, $title, $message, 'info', $link);
        }
    }
}
