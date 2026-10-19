<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * Usage: Route::middleware('permission:facility.view')
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();
        if (!$user) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            abort(403, 'You do not have permission to access this resource.');
        }

        $flattened = [];
        foreach ($permissions as $perm) {
            foreach (explode(',', $perm) as $p) {
                $trimmed = trim($p);
                if ($trimmed !== '') {
                    $flattened[] = $trimmed;
                }
            }
        }

        $hasAccess = false;
        foreach ($flattened as $perm) {
            if ($user->hasPermission($perm)) {
                $hasAccess = true;
                break;
            }
        }

        if (!$hasAccess) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            abort(403, 'You do not have permission to access this resource.');
        }

        return $next($request);
    }
}
