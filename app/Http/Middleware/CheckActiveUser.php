<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUser
{
    /**
     * Ensure the authenticated user's account is still active and track activity.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $inactivityDays = (int) config('icoep.inactivity.days', 14);

            if ($user->isLocked()) {
                $message = 'Your account has been locked due to too many failed login attempts.';
            } elseif ($user->status === 'inactive' && $user->deactivation_reason === 'inactivity') {
                $message = "Your account was deactivated after {$inactivityDays} days without activity. Please contact the administrator to reactivate it.";
            } else {
                $message = 'Your account has been deactivated. Please contact the administrator.';
            }

            return redirect()->route('login')->with('error', $message);
        }

        // If authenticated and active, track activity throttled to activity_throttle_minutes (default 10)
        if ($user && $user->isActive()) {
            $throttleMinutes = (int) config('icoep.inactivity.activity_throttle_minutes', 10);
            $cutoff = now()->subMinutes($throttleMinutes);

            if ($user->last_activity_at === null || $user->last_activity_at < $cutoff) {
                $user->forceFill([
                    'last_activity_at' => now(),
                ])->saveQuietly();
            }
        }

        return $next($request);
    }
}
