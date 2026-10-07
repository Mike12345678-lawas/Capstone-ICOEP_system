<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Equipment;
use App\Models\EquipmentRequest;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EquipmentReturnController extends Controller
{
    /**
     * Borrower action: request return of borrowed equipment.
     * Only the borrower, only when status is 'borrowed'.
     * Sets return_requested_at = now() without changing status or stock.
     */
    public function requestReturn(Request $request, EquipmentRequest $equipmentRequest)
    {
        if ($equipmentRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        if (!in_array($equipmentRequest->status, ['borrowed', 'approved'])) {
            $msg = 'Only active borrowed or approved items can be marked as returned.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if ($equipmentRequest->return_requested_at !== null) {
            $msg = 'Return request already submitted. Waiting for admin check.';
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 200);
            }
            return back()->with('info', $msg);
        }

        $request->validate([
            'return_photo' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ], [
            'return_photo.required' => 'Please attach a photo of the equipment you are returning.',
            'return_photo.image'    => 'The return proof must be an image (JPG, PNG or WEBP).',
            'return_photo.max'      => 'The return photo must not be larger than 5 MB.',
        ]);

        $photoPath = $request->file('return_photo')->store('return-photos', 'public');

        $equipmentRequest->update([
            'return_requested_at' => now(),
            'return_photo'        => $photoPath,
        ]);

        AuditLog::log('RETURN_REQUESTED', 'equipment_requests', $equipmentRequest->id);

        // Notify admins / approvers
        $admins = User::whereHas('role', function ($q) {
            $q->where('name', 'Administrator');
        })->orWhereHas('role.permissions', function ($q) {
            $q->where('name', 'equipment.approve');
        })->distinct()->get();

        foreach ($admins as $admin) {
            Notification::send(
                $admin->id,
                'Equipment Return Requested',
                "{$equipmentRequest->user->full_name} says they returned \"{$equipmentRequest->equipment->name}\", please check.",
                'info',
                '/equipment/returns?request_id=' . $equipmentRequest->id
            );
        }

        $msg = 'Return notice submitted. Waiting for admin check.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Borrower action: cancel their own pending return request.
     * Clears return_requested_at until confirmed.
     */
    public function cancelReturnRequest(Request $request, EquipmentRequest $equipmentRequest)
    {
        if ($equipmentRequest->user_id !== Auth::id()) {
            abort(403, 'Unauthorized.');
        }

        if (!in_array($equipmentRequest->status, ['borrowed', 'approved']) || is_null($equipmentRequest->return_requested_at)) {
            $msg = 'No pending return check to cancel.';
            if ($request->expectsJson()) {
                return response()->json(['error' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        if ($equipmentRequest->return_photo) {
            Storage::disk('public')->delete($equipmentRequest->return_photo);
        }

        $equipmentRequest->update([
            'return_requested_at' => null,
            'return_photo'        => null,
        ]);

        AuditLog::log('CANCELLED_RETURN_REQUEST', 'equipment_requests', $equipmentRequest->id);

        $msg = 'Return check request cancelled.';
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }
        return back()->with('success', $msg);
    }

    /**
     * Admin Return Screen (new page)
     * Accessible by users with 'equipment.approve' permission.
     */
    public function index(Request $request)
    {
        $today = now()->startOfDay();

        // 1. Top Counters
        $returningTodayCount = EquipmentRequest::whereIn('status', ['borrowed', 'approved'])
            ->whereDate('expected_return_date', $today->toDateString())
            ->count();

        $overdueCount = EquipmentRequest::whereIn('status', ['borrowed', 'approved'])
            ->where('expected_return_date', '<', $today)
            ->count();

        $needsCheckCount = EquipmentRequest::whereIn('status', ['borrowed', 'approved'])
            ->whereNotNull('return_requested_at')
            ->count();

        // 2. Search / Lookup
        $search = trim($request->get('search', ''));
        $selectedRequestId = $request->get('request_id');
        $matchingRequests = collect();
        $selectedRequest = null;

        if ($selectedRequestId) {
            $selectedRequest = EquipmentRequest::with(['user.role', 'equipment', 'approver'])
                ->whereIn('status', ['borrowed', 'approved'])
                ->find($selectedRequestId);
        }

        if ($search !== '') {
            $query = EquipmentRequest::with(['user.role', 'equipment', 'approver'])
                ->whereIn('status', ['borrowed', 'approved']);

            $cleanSearch = ltrim(strtoupper($search), 'REQ-');
            $query->where(function ($q) use ($search, $cleanSearch) {
                if (is_numeric($cleanSearch)) {
                    $q->orWhere('id', (int) $cleanSearch);
                }
                $q->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('employee_id', $search)
                       ->orWhere('first_name', 'like', "%{$search}%")
                       ->orWhere('last_name', 'like', "%{$search}%")
                       ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })->orWhereHas('equipment', function ($eq) use ($search) {
                    $eq->where('name', 'like', "%{$search}%");
                });
            });

            $matchingRequests = $query->orderBy('expected_return_date')->get();

            // If exactly one match and none explicitly selected, select it automatically
            if ($matchingRequests->count() === 1 && !$selectedRequest) {
                $selectedRequest = $matchingRequests->first();
            }
        }

        // 3. Overdue List
        $overdueRequests = EquipmentRequest::with(['user.role', 'equipment'])
            ->whereIn('status', ['borrowed', 'approved'])
            ->where('expected_return_date', '<', $today)
            ->orderBy('expected_return_date')
            ->get();

        // 4. Needs Check List (borrower requested return)
        $needsCheckRequests = EquipmentRequest::with(['user.role', 'equipment'])
            ->whereIn('status', ['borrowed', 'approved'])
            ->whereNotNull('return_requested_at')
            ->orderBy('return_requested_at')
            ->get();

        return view('equipment.returns', [
            'pageTitle'           => 'Equipment Returns Desk',
            'breadcrumb'          => [
                ['label' => 'Dashboard', 'url' => '/dashboard'],
                ['label' => 'Equipment Returns'],
            ],
            'counters'            => [
                'returning_today' => $returningTodayCount,
                'overdue'         => $overdueCount,
                'needs_check'     => $needsCheckCount,
            ],
            'search'              => $search,
            'matchingRequests'    => $matchingRequests,
            'selectedRequest'     => $selectedRequest,
            'overdueRequests'     => $overdueRequests,
            'needsCheckRequests'  => $needsCheckRequests,
        ]);
    }

    /**
     * Admin action: Confirm return (Full or Partial)
     * Runs in DB::transaction with lockForUpdate on request and equipment.
     */
    public function confirmReturn(Request $request, EquipmentRequest $equipmentRequest)
    {
        $request->validate([
            'condition'         => 'required|in:good,damaged,missing',
            'return_type'       => 'nullable|in:full,partial',
            'quantity_returned' => 'nullable|integer|min:1',
            'return_notes'      => 'nullable|string|max:1000',
        ]);

        $txError = DB::transaction(function () use ($equipmentRequest, $request, &$auditPayload, &$notifyPayload) {
            $lockedReq = EquipmentRequest::lockForUpdate()->find($equipmentRequest->id);
            if (!$lockedReq) {
                return 'Equipment request not found.';
            }

            // Idempotent: check if already fully returned
            if ($lockedReq->status === 'returned') {
                return 'already_returned';
            }

            if (!in_array($lockedReq->status, ['borrowed', 'approved'])) {
                return "Cannot return request with status '{$lockedReq->status}'. Only 'borrowed' or 'approved' requests can be returned.";
            }

            $lockedEquip = Equipment::lockForUpdate()->find($lockedReq->equipment_id);
            if (!$lockedEquip) {
                return 'Equipment inventory item not found.';
            }

            $remainingNeeded = $lockedReq->quantity - $lockedReq->returned_quantity;
            $returnType = $request->input('return_type', 'full');
            $requestedQty = (int) $request->input('quantity_returned', $remainingNeeded);

            $unitsBack = ($returnType === 'partial')
                ? min($requestedQty, $remainingNeeded)
                : $remainingNeeded;

            if ($unitsBack < 1) {
                return 'Invalid returned quantity specified.';
            }

            $newReturnedQuantity = $lockedReq->returned_quantity + $unitsBack;
            $isFullReturn = ($newReturnedQuantity >= $lockedReq->quantity);

            $condition = $request->condition;
            $notes = $request->return_notes;
            $now = Carbon::now();
            $expectedReturn = $lockedReq->expected_return_date ? Carbon::parse($lockedReq->expected_return_date)->startOfDay() : null;
            $today = $now->copy()->startOfDay();

            $daysLate = 0;
            if ($expectedReturn && $today->gt($expectedReturn)) {
                $daysLate = (int) $expectedReturn->diffInDays($today);
            }

            $updateData = [
                'returned_quantity'   => $newReturnedQuantity,
                'confirmed_by'        => Auth::id(),
                'return_condition'    => $condition,
                'return_notes'        => $notes,
                'return_requested_at' => null,
            ];

            if ($isFullReturn) {
                $updateData['status']             = 'returned';
                $updateData['returned_at']        = $now;
                $updateData['actual_return_date'] = $now->toDateString();
                $updateData['days_late']          = $daysLate;
            }

            $lockedReq->update($updateData);

            // Stock restoration logic:
            // Restore only good, usable units. If damaged or missing, do NOT restore stock.
            if ($condition === 'good') {
                $lockedEquip->increment('available_quantity', $unitsBack);
            } else {
                // Set equipment status to maintenance
                $lockedEquip->update(['status' => 'maintenance']);
            }

            $auditPayload = [
                'req_id'     => $lockedReq->id,
                'condition'  => $condition,
                'units'      => $unitsBack,
                'days_late'  => $daysLate,
                'action'     => "RETURN_CONFIRMED: {$condition}, {$unitsBack} unit(s), {$daysLate} day(s) late",
            ];

            $notifyPayload = [
                'user_id'       => $lockedReq->user_id,
                'equipment_id'  => $lockedEquip->id,
                'equipment_name'=> $lockedEquip->name,
                'is_full'       => $isFullReturn,
                'units_back'    => $unitsBack,
                'remaining'     => $lockedReq->quantity - $newReturnedQuantity,
                'condition'     => $condition,
                'borrower_name' => $lockedReq->user->full_name,
            ];

            return null;
        });

        if ($txError === 'already_returned') {
            $msg = 'Return already confirmed previously.';
            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }
            return redirect('/equipment/returns')->with('info', $msg);
        }

        if ($txError) {
            if ($request->expectsJson()) {
                return response()->json(['error' => $txError], 422);
            }
            return back()->with('error', $txError);
        }

        // Audit log
        AuditLog::log($auditPayload['action'], 'equipment_requests', $auditPayload['req_id']);

        // Borrower notification
        if ($notifyPayload['is_full']) {
            Notification::send(
                $notifyPayload['user_id'],
                'Equipment Return Confirmed',
                "Your return of \"{$notifyPayload['equipment_name']}\" ({$equipmentRequest->quantity} unit(s)) has been confirmed by staff.",
                'success',
                '/equipment/' . $notifyPayload['equipment_id']
            );
        } else {
            Notification::send(
                $notifyPayload['user_id'],
                'Partial Return Recorded',
                "Partial return recorded: {$notifyPayload['units_back']} unit(s) of \"{$notifyPayload['equipment_name']}\" confirmed back. {$notifyPayload['remaining']} unit(s) still due.",
                'warning',
                '/equipment/' . $notifyPayload['equipment_id']
            );
        }

        // If condition was damaged or missing, notify admins with alert
        if ($notifyPayload['condition'] !== 'good') {
            $admins = User::whereHas('role', function ($q) {
                $q->where('name', 'Administrator');
            })->orWhereHas('role.permissions', function ($q) {
                $q->where('name', 'equipment.approve');
            })->distinct()->get();

            foreach ($admins as $admin) {
                Notification::send(
                    $admin->id,
                    'Equipment Damaged/Missing Alert',
                    "Equipment \"{$notifyPayload['equipment_name']}\" was returned by {$notifyPayload['borrower_name']} in {$notifyPayload['condition']} condition. Unit(s) set to maintenance and not restored to inventory.",
                    'error',
                    '/equipment/' . $notifyPayload['equipment_id']
                );
            }
        }

        $successMsg = $notifyPayload['is_full']
            ? "Return confirmed successfully for \"{$notifyPayload['equipment_name']}\"."
            : "Partial return recorded: {$notifyPayload['units_back']} unit(s) confirmed back. {$notifyPayload['remaining']} unit(s) still due.";

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $successMsg]);
        }
        return redirect('/equipment/returns')->with('success', $successMsg);
    }

    /**
     * Send overdue reminder to borrower
     */
    public function sendReminder(EquipmentRequest $equipmentRequest)
    {
        $today = now()->startOfDay();
        $expectedReturn = Carbon::parse($equipmentRequest->expected_return_date)->startOfDay();
        $daysOverdue = max(1, (int) $expectedReturn->diffInDays($today));

        $equipment = $equipmentRequest->equipment;
        $borrower = $equipmentRequest->user;

        Notification::send(
            $borrower->id,
            'Overdue Equipment Alert',
            "The equipment \"{$equipment->name}\" was due on " .
                $equipmentRequest->expected_return_date->format('M d, Y') .
                " ({$daysOverdue} day(s) overdue). Please return it immediately.",
            'error',
            '/equipment/' . $equipment->id
        );

        // Send email using existing overdue template if email available
        try {
            Mail::send(
                'emails.overdue_equipment',
                [
                    'borrower'    => $borrower,
                    'equipment'   => $equipment,
                    'request'     => $equipmentRequest,
                    'daysOverdue' => $daysOverdue,
                ],
                function ($message) use ($borrower, $equipment) {
                    $message->to($borrower->email, $borrower->full_name)
                            ->subject("Overdue Equipment: {$equipment->name} — Please Return Immediately");
                }
            );
        } catch (\Throwable $e) {
            // Log or ignore mail transport issues in local dev
        }

        AuditLog::log('OVERDUE_ALERT_SENT', 'equipment_requests', $equipmentRequest->id);

        return back()->with('success', "Reminder sent to {$borrower->full_name}.");
    }
}
