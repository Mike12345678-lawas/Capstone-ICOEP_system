<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|string',
            'password'    => 'required',
        ]);

        $user = User::with('role')
            ->where('employee_id', $request->employee_id)
            ->first();

        if (!$user) {
            AuditLog::log('LOGIN_FAILED', 'users');
            return back()->withInput()->with('error', 'Invalid ID or password.');
        }

        // Check if temporary lockout is active
        if ($user->locked_until && $user->locked_until->isFuture()) {
            $minutes = max(1, (int) ceil($user->locked_until->diffInSeconds(now()) / 60));
            return back()->withInput()->with('error', "Too many failed attempts. Please try again in {$minutes} minute(s).");
        }
        if ($user->locked_until && $user->locked_until->isPast()) {
            $user->update(['login_attempts' => 0, 'locked_until' => null]);
        }

        $inactivityDays = (int) config('icoep.inactivity.days', 14);
        $inactivityMsg = "Your account was deactivated after {$inactivityDays} days without activity. Please contact the administrator to reactivate it.";
        $inactivityService = app(\App\Services\InactivityService::class);

        // Lazy inactivity check: If active user is dormant, deactivate before password check
        if ($user->status === 'active') {
            if ($inactivityService->isDormant($user) && !$inactivityService->isProtected($user)['protected']) {
                $allowSelfReactivation = (bool) config('icoep.inactivity.allow_self_reactivation', false);

                if ($allowSelfReactivation && Hash::check($request->password, $user->password)) {
                    // Password matches and self-reactivation allowed: auto-reactivate
                    $inactivityService->reactivate($user);
                } else {
                    $inactivityService->deactivate($user, 'inactivity');
                    return back()->withInput()->with('error', $inactivityMsg);
                }
            }
        }

        // Check if account is inactive
        if ($user->status === 'inactive') {
            $allowSelfReactivation = (bool) config('icoep.inactivity.allow_self_reactivation', false);

            if ($allowSelfReactivation && $user->deactivation_reason === 'inactivity' && Hash::check($request->password, $user->password)) {
                // Auto-reactivate on correct password
                $inactivityService->reactivate($user);
            } else {
                $errorMessage = $user->deactivation_reason === 'inactivity'
                    ? $inactivityMsg
                    : 'Your account has been deactivated. Please contact administration.';
                return back()->withInput()->with('error', $errorMessage);
            }
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            $attempts = $user->login_attempts + 1;
            $maxAttempts = config('icoep.max_login_attempts', 5);
            $lockoutMinutes = config('icoep.lockout_minutes', 30);

            if ($attempts >= $maxAttempts) {
                $user->update([
                    'login_attempts' => $attempts,
                    'locked_until'   => now()->addMinutes($lockoutMinutes),
                ]);
                AuditLog::log('LOGIN_FAILED_LOCKED', 'users', $user->id);
                return back()->withInput()->with('error', "Too many failed attempts. Account locked for {$lockoutMinutes} minutes.");
            }

            $user->update(['login_attempts' => $attempts]);
            $remaining = $maxAttempts - $attempts;
            AuditLog::log('LOGIN_FAILED', 'users', $user->id);
            return back()->withInput()->with('error', "Invalid ID or password. {$remaining} attempt(s) remaining.");
        }

        // Successful login
        Auth::login($user);
        $request->session()->regenerate();

        $user->update([
            'login_attempts'       => 0,
            'locked_until'         => null,
            'last_login'           => now(),
            'last_activity_at'     => now(),
            'inactivity_warned_at' => null,
        ]);

        AuditLog::log('LOGIN_SUCCESS', 'users', $user->id);

        return redirect()->intended('/dashboard')->with('success', 'Welcome back, ' . $user->first_name . '!');
    }

    public function logout(Request $request)
    {
        AuditLog::log('LOGOUT', 'users', Auth::id());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }
}
