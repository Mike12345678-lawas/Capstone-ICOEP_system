<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EquipmentRequest;
use App\Models\FacilityReservation;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InactivityService
{
    /**
     * Compute reference time for a user: latest of last_activity_at, last_login, created_at.
     */
    public function getReferenceTime(User $user): Carbon
    {
        $timestamps = array_filter([
            $user->last_activity_at,
            $user->last_login,
            $user->created_at,
        ]);

        if (empty($timestamps)) {
            return now();
        }

        $carbonDates = array_map(function ($ts) {
            return $ts instanceof Carbon ? $ts : Carbon::parse($ts);
        }, $timestamps);

        return max($carbonDates);
    }

    /**
     * Determine if an active user is dormant based on inactivity threshold.
     */
    public function isDormant(User $user, ?int $days = null): bool
    {
        if ($user->status !== 'active' || $user->trashed()) {
            return false;
        }

        $days = $days ?? (int) config('icoep.inactivity.days', 14);
        $cutoff = now()->subDays($days);

        return $this->getReferenceTime($user) < $cutoff;
    }

    /**
     * Check if a user is protected from auto-deactivation.
     * Returns ['protected' => bool, 'reason' => ?string].
     */
    public function isProtected(User $user): array
    {
        // Must be active and not soft-deleted
        if ($user->status !== 'active') {
            return ['protected' => true, 'reason' => 'User is not active'];
        }

        if ($user->trashed()) {
            return ['protected' => true, 'reason' => 'User is soft-deleted'];
        }

        $userRole = $user->role?->name;

        // Exempt roles check (e.g. Administrator)
        $exemptRoles = config('icoep.inactivity.exempt_roles', ['Administrator']);
        if ($userRole && in_array($userRole, $exemptRoles, true)) {
            return ['protected' => true, 'reason' => "Role '{$userRole}' is exempt from auto-deactivation"];
        }

        // Never deactivate the last remaining active Administrator
        if ($userRole === 'Administrator' || $user->hasRole('Administrator')) {
            $otherActiveAdmins = User::whereHas('role', fn($q) => $q->where('name', 'Administrator'))
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->whereNull('deleted_at')
                ->count();

            if ($otherActiveAdmins === 0) {
                return ['protected' => true, 'reason' => 'Last remaining active Administrator'];
            }
        }

        // Check if user currently holds borrowed equipment
        if (config('icoep.inactivity.protect_borrowed_equipment', true)) {
            $hasBorrowed = EquipmentRequest::where('user_id', $user->id)
                ->where('status', 'borrowed')
                ->exists();

            if ($hasBorrowed) {
                return ['protected' => true, 'reason' => 'User currently holds borrowed equipment'];
            }
        }

        // Check if user has an active or upcoming approved booking/request
        if (config('icoep.inactivity.protect_upcoming_bookings', true)) {
            // Facility reservations that have not ended yet
            $today = today()->toDateString();
            $currentTime = now()->format('H:i:s');

            $hasActiveFacility = FacilityReservation::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where(function ($q) use ($today, $currentTime) {
                    $q->where('reservation_date', '>', $today)
                      ->orWhere(function ($sub) use ($today, $currentTime) {
                          $sub->where('reservation_date', $today)
                              ->where('time_end', '>=', $currentTime);
                      });
                })
                ->exists();

            if ($hasActiveFacility) {
                return ['protected' => true, 'reason' => 'User has active or upcoming approved facility reservation'];
            }

            // Equipment requests that have not ended yet
            $hasActiveEquipment = EquipmentRequest::where('user_id', $user->id)
                ->where('status', 'approved')
                ->where('expected_return_date', '>=', $today)
                ->exists();

            if ($hasActiveEquipment) {
                return ['protected' => true, 'reason' => 'User has active or upcoming approved equipment request'];
            }
        }

        return ['protected' => false, 'reason' => null];
    }

    /**
     * Deactivate a user due to inactivity or admin action.
     */
    public function deactivate(User $user, string $reason = 'inactivity'): void
    {
        $user->update([
            'status'              => 'inactive',
            'deactivated_at'      => now(),
            'deactivation_reason' => $reason,
        ]);

        AuditLog::log('AUTO_DEACTIVATED', 'users', $user->id);
    }

    /**
     * Reactivate an inactive user.
     */
    public function reactivate(User $user): void
    {
        $user->update([
            'status'               => 'active',
            'last_activity_at'     => now(),
            'deactivated_at'       => null,
            'deactivation_reason'  => null,
            'inactivity_warned_at' => null,
            'login_attempts'       => 0,
            'locked_until'         => null,
        ]);

        AuditLog::log('REACTIVATED_USER', 'users', $user->id);
    }

    /**
     * Execute inactivity deactivation sweep across all eligible users.
     */
    public function processInactivitySweep(?int $days = null, bool $dryRun = false): array
    {
        $days = $days ?? (int) config('icoep.inactivity.days', 14);

        $activeUsers = User::with('role')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();

        $examined = 0;
        $deactivated = [];
        $skipped = [];

        foreach ($activeUsers as $user) {
            $examined++;

            if (!$this->isDormant($user, $days)) {
                continue;
            }

            $protection = $this->isProtected($user);

            if ($protection['protected']) {
                $skipped[] = [
                    'user_id'   => $user->id,
                    'name'      => $user->full_name,
                    'email'     => $user->email,
                    'reason'    => $protection['reason'],
                ];
                continue;
            }

            if (!$dryRun) {
                $this->deactivate($user, 'inactivity');
            }

            $deactivated[] = [
                'user_id'    => $user->id,
                'name'       => $user->full_name,
                'email'      => $user->email,
                'last_access'=> $this->getReferenceTime($user)->toDateTimeString(),
            ];
        }

        return [
            'examined'          => $examined,
            'deactivated_count' => count($deactivated),
            'deactivated'       => $deactivated,
            'skipped_count'     => count($skipped),
            'skipped'           => $skipped,
            'dry_run'           => $dryRun,
        ];
    }

    /**
     * Send advance warning notifications/emails to users nearing dormancy.
     */
    public function sendInactivityWarnings(?int $warnDaysBefore = null, bool $dryRun = false): int
    {
        $warnDaysBefore = $warnDaysBefore ?? (int) config('icoep.inactivity.warn_days_before', 2);
        if ($warnDaysBefore <= 0) {
            return 0;
        }

        $totalDays = (int) config('icoep.inactivity.days', 14);
        $warnThresholdDays = $totalDays - $warnDaysBefore;
        if ($warnThresholdDays <= 0) {
            return 0;
        }

        $now = now();
        $warnCutoff = $now->copy()->subDays($warnThresholdDays);
        $dormantCutoff = $now->copy()->subDays($totalDays);

        $activeUsers = User::with('role')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->get();

        $warnedCount = 0;

        foreach ($activeUsers as $user) {
            $protection = $this->isProtected($user);
            if ($protection['protected']) {
                continue;
            }

            $refTime = $this->getReferenceTime($user);

            // User is in the warning window: inactive for >= warnThresholdDays but < totalDays
            if ($refTime < $warnCutoff && $refTime >= $dormantCutoff) {
                // Check if already warned during this current period of inactivity
                if ($user->inactivity_warned_at && $user->inactivity_warned_at >= $refTime) {
                    continue;
                }

                $daysInactive = (int) $refTime->diffInDays($now);
                $daysRemaining = max(1, $totalDays - $daysInactive);

                if (!$dryRun) {
                    // Send in-app notification
                    Notification::send(
                        $user->id,
                        'Account Inactivity Warning',
                        "Your account has been inactive for {$daysInactive} days and will be automatically deactivated in {$daysRemaining} day(s) without activity.",
                        'warning',
                        '/dashboard'
                    );

                    // Send email if configured
                    if ($user->email && filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            Mail::send(
                                'emails.inactivity_warning',
                                [
                                    'user'          => $user,
                                    'daysInactive'  => $daysInactive,
                                    'daysRemaining' => $daysRemaining,
                                ],
                                function ($message) use ($user) {
                                    $message->to($user->email, $user->full_name)
                                        ->subject('Account Inactivity Warning — ' . config('icoep.app_name', 'ICOEP'));
                                }
                            );
                        } catch (\Throwable $e) {
                            Log::warning("Failed to send inactivity warning email to {$user->email}: " . $e->getMessage());
                        }
                    }

                    $user->forceFill(['inactivity_warned_at' => $now])->saveQuietly();
                }

                $warnedCount++;
            }
        }

        return $warnedCount;
    }
}
