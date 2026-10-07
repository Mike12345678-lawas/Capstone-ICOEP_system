<?php

namespace App\Console\Commands;

use App\Models\EquipmentRequest;
use App\Models\FacilityReservation;
use App\Services\ResourceSolver;
use Illuminate\Console\Command;

class AutoResolveRequests extends Command
{
    protected $signature = 'requests:auto-resolve
                            {--dry-run : Simulate auto-resolution without modifying records}';

    protected $description = 'Auto-resolve pending facility reservations using deterministic priority rules (equipment requests require manual admin approval)';

    public function handle(): int
    {
        $isDry = (bool) $this->option('dry-run');

        if ($isDry) {
            $this->warn('Running in --dry-run mode. No database records will be modified.');
        }

        // ─── 1. Facility Reservations ─────────────────────────────────────
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

        $this->info("Found {$pendingReservations->count()} pending facility reservation(s).");

        $facilityStats = ['approved' => 0, 'rejected' => 0, 'pending' => 0, 'skipped' => 0];

        foreach ($pendingReservations as $reservation) {
            $tier = ResourceSolver::getUserPriority($reservation->user);
            $role = $reservation->user->role->name ?? 'Student';

            $result = ResourceSolver::autoResolveFacilityReservation($reservation, $isDry);
            $action = $result['action'] ?? $result['status'];
            $facilityStats[$result['status']] = ($facilityStats[$result['status']] ?? 0) + 1;

            $this->line("  ↳ [Facility #{$reservation->id}] {$reservation->facility->name} — {$reservation->user->full_name} ({$role}, Tier {$tier}) => {$action}");
        }

        // ─── 2. Equipment Requests ────────────────────────────────────────
        $pendingEquipment = EquipmentRequest::where('status', 'pending')
            ->with(['user.role', 'equipment'])
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

        $this->info("Found {$pendingEquipment->count()} pending equipment request(s).");

        $equipmentStats = ['approved' => 0, 'rejected' => 0, 'pending' => 0, 'skipped' => 0];

        foreach ($pendingEquipment as $eqReq) {
            $tier = ResourceSolver::getUserPriority($eqReq->user);
            $role = $eqReq->user->role->name ?? 'Student';

            $result = ResourceSolver::autoResolveEquipmentRequest($eqReq, $isDry);
            $action = $result['action'] ?? $result['status'];
            $equipmentStats[$result['status']] = ($equipmentStats[$result['status']] ?? 0) + 1;

            $this->line("  ↳ [Equipment #{$eqReq->id}] {$eqReq->equipment->name} ×{$eqReq->quantity} — {$eqReq->user->full_name} ({$role}, Tier {$tier}) => {$action}");
        }

        $modePrefix = $isDry ? '[DRY RUN] ' : '';
        $this->info("{$modePrefix}Processing complete:");
        $this->line("  Facilities: {$facilityStats['approved']} approved, {$facilityStats['rejected']} rejected, {$facilityStats['pending']} pending.");
        $this->line("  Equipment:  {$equipmentStats['approved']} approved, {$equipmentStats['rejected']} rejected, {$equipmentStats['pending']} pending.");

        return self::SUCCESS;
    }
}
