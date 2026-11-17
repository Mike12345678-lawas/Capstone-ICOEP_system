<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePrivacyConsent
{
    /**
     * Handle an incoming request.
     * Ensure the user has consented to the privacy policy before accessing the system.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->privacy_consent) {
            // Whitelisted routes that an unconsented user is allowed to access
            $allowedRouteNames = [
                'privacy.consent',
                'privacy.consent.accept',
                'privacy.policy',
                'logout',
            ];

            foreach ($allowedRouteNames as $name) {
                if ($request->routeIs($name)) {
                    return $next($request);
                }
            }

            // Fallback path matching
            if ($request->is('privacy/consent', 'privacy/consent/*', 'privacy/policy', 'logout')) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message'  => 'Privacy consent required before accessing the system.',
                    'redirect' => route('privacy.consent'),
                ], 403);
            }

            return redirect()->route('privacy.consent');
        }

        return $next($request);
    }
}
