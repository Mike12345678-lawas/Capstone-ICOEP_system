<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use App\Http\Requests\RegisterRequest;
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

        // Check if account is locked
        if ($user->status === 'locked') {
            if ($user->locked_until && $user->locked_until->isFuture()) {
                $minutes = $user->locked_until->diffInMinutes(now());
                return back()->withInput()->with('error', "Account is locked. Please try again in {$minutes} minute(s).");
            }
            // Unlock expired lockout
            $user->update(['status' => 'active', 'login_attempts' => 0, 'locked_until' => null]);
        }

        if ($user->status === 'inactive') {
            return back()->withInput()->with('error', 'Your account has been deactivated. Please contact administration.');
        }

        // Verify password
        if (!Hash::check($request->password, $user->password)) {
            $attempts = $user->login_attempts + 1;
            $maxAttempts = config('icoep.max_login_attempts', 5);
            $lockoutMinutes = config('icoep.lockout_minutes', 30);

            if ($attempts >= $maxAttempts) {
                $user->update([
                    'login_attempts' => $attempts,
                    'status'         => 'locked',
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
            'login_attempts' => 0,
            'locked_until'   => null,
            'last_login'     => now(),
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

    public function showRegisterForm()
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }
        return view('auth.register');
    }

    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name'           => $validated['first_name'],
            'last_name'            => $validated['last_name'],
            'email'                => $validated['email'],
            'employee_id'          => $validated['employee_id'] ?? null,
            'department_id'        => $validated['department_id'] ?? null,
            'password'             => $validated['password'],
            'role_id'              => 4, // Student by default
            'status'               => 'active',
            'privacy_consent'      => true,
            'privacy_consent_date' => now(),
        ]);

        AuditLog::log('REGISTER', 'users', $user->id);

        return redirect()->route('login')->with('success', 'Account created successfully! Please sign in.');
    }
}
