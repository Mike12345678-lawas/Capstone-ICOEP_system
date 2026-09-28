<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckActiveUser
{
    /**
     * Ensure the authenticated user's account is still active.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isActive()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = $user->isLocked()
                ? 'Your account has been locked due to too many failed login attempts.'
                : 'Your account has been deactivated. Please contact the administrator.';

            return redirect()->route('login')->with('error', $message);
        }

        return $next($request);
    }
}
