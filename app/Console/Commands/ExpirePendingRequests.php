<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\EquipmentRequest;
use App\Models\FacilityReservation;
use App\Models\Notification;
use Illuminate\Console\Command;

class ExpirePendingRequests extends Command
{
    protected $signature = 'requests:expire-pending
                            {--dry-run : List expired items without making changes}';

    protected $description = 'Cancel pending facility reservations and equipment requests whose requested date has already passed';

    public function handle(): int
    {
        $isDry = $this->option('dry-run');
        $today = now()->startOfDay();

        // ─── Facility Reservations ───────────────────────────────────────
        $expiredReservations = FacilityReservation::with(['user', 'facility'])
            ->where('status', 'pending')
            ->where('reservation_date', '<', $today->toDateString())
            ->get();

        $this->info("Found {$expiredReservations->count()} expired pending facility reservation(s).");

        foreach ($expiredReservations as $reservation) {
            $this->line("  ↳ [{$reservation->id}] {$reservation->facility->name} — {$reservation->user->full_name} ({$reservation->reservation_date->format('Y-m-d')})");

            if ($isDry) {
                continue;
            }

            $reservation->update([
                'status'  => 'cancelled',
                'remarks' => 'Expired: no admin action before the requested date',
            ]);

            AuditLog::log('EXPIRED_RESERVATION', 'facility_reservations', $reservation->id);

            Notification::send(
                $reservation->user_id,
                'Reservation Expired',
                "Your pending reservation for \"{$reservation->facility->name}\" on {$reservation->reservation_date->format('M d, Y')} was automatically cancelled because the date passed without admin action.",
                'warning',
                '/dashboard?section=facilities'
            );
        }

        // ─── Equipment Requests ──────────────────────────────────────────
        $expiredEquipment = EquipmentRequest::with(['user', 'equipment'])
            ->where('status', 'pending')
            ->where('date_needed', '<', $today->toDateString())
            ->get();

        $this->info("Found {$expiredEquipment->count()} expired pending equipment request(s).");

        foreach ($expiredEquipment as $req) {
            $this->line("  ↳ [{$req->id}] {$req->equipment->name} ×{$req->quantity} — {$req->user->full_name} (needed {$req->date_needed->format('Y-m-d')})");

            if ($isDry) {
                continue;
            }

            $req->update([
                'status'  => 'cancelled',
                'remarks' => 'Expired: no admin action before the requested date',
            ]);

            AuditLog::log('EXPIRED_BORROW', 'equipment_requests', $req->id);

            Notification::send(
                $req->user_id,
                'Equipment Request Expired',
                "Your pending borrow request for \"{$req->equipment->name}\" (needed {$req->date_needed->format('M d, Y')}) was automatically cancelled because the date passed without admin action.",
                'warning',
                '/dashboard?section=equipment'
            );
        }

        $totalExpired = $expiredReservations->count() + $expiredEquipment->count();

        if ($isDry) {
            $this->info("Dry run complete — {$totalExpired} item(s) would be cancelled.");
        } else {
            $this->info("Done — {$totalExpired} expired pending item(s) cancelled.");
        }

        return self::SUCCESS;
    }
}
