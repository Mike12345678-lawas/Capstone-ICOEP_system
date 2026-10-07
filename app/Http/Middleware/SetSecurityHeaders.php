<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Handle an incoming request and set security headers.
     * Explicitly allows camera for self origin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Permissions-Policy', 'camera=(self)');

        return $response;
    }
}
