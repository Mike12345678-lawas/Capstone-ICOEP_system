<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EquipmentRequest;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class CustodianNotificationService
{
    /**
     * Notify active custodian unit users that an equipment request has been approved
     * and should be readied for pickup.
     */
    public static function notifyPickupReady(EquipmentRequest $request, ?int $approverId = null): void
    {
        $request->loadMissing(['equipment.custodianUnit', 'user']);
        $equipment = $request->equipment;

        $approver = $approverId ?? $request->approved_by ?? Auth::id();

        // If unassigned equipment, notify no one but log it
        if (!$equipment || !$equipment->custodian_unit_id) {
            AuditLog::log('UNASSIGNED_EQUIPMENT_APPROVED', 'equipment_requests', $request->id, $approver);
            return;
        }

        // Avoid duplicate notification if request was already notified
        $alreadyNotified = AuditLog::where('table_name', 'equipment_requests')
            ->where('record_id', $request->id)
            ->where('action', 'CUSTODIAN_NOTIFIED')
            ->exists();

        if ($alreadyNotified) {
            return;
        }

        $unit = $equipment->custodianUnit;
        $unitId = $equipment->custodian_unit_id;

        // Active users belonging to the owning custodian unit, excluding the approving admin
        $recipientQuery = User::active()->where('custodian_unit_id', $unitId);
        if ($approver) {
            $recipientQuery->where('id', '!=', $approver);
        }
        $recipients = $recipientQuery->get();

        $borrower = $request->user;
        $borrowerName = $borrower ? $borrower->full_name : 'Requester';
        $dateNeeded = $request->date_needed ? $request->date_needed->format('M d, Y') : 'N/A';
        $expectedReturn = $request->expected_return_date ? $request->expected_return_date->format('M d, Y') : 'N/A';

        $title = "📦 Ready for Pickup: {$equipment->name}";
        $message = "Borrow request for \"{$equipment->name}\" (qty: {$request->quantity}) by {$borrowerName} (needed: {$dateNeeded}, return: {$expectedReturn}) is already admin-approved and should be readied for pickup.";
        $link = '/dashboard?section=pickup-queue';

        foreach ($recipients as $recipient) {
            Notification::send(
                $recipient->id,
                $title,
                $message,
                'info',
                $link
            );
        }

        AuditLog::log('CUSTODIAN_NOTIFIED', 'equipment_requests', $request->id, $approver);
    }
}
