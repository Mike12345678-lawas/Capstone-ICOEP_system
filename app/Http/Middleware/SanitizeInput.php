<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Global field-level sanitization middleware.
 *
 * - Trims whitespace from all string inputs
 * - Strips HTML/PHP tags from string fields (except password fields)
 * - Converts empty strings to null
 * - Validates and casts fields that should be numeric (integer IDs, quantities)
 * - Leaves non-string types (files, arrays) untouched
 */
class SanitizeInput
{
    /**
     * Fields that are expected to be numeric (integer) values.
     * These will be cast to int if present and numeric, or set to null.
     */
    protected array $numericFields = [
        'role_id',
        'department_id',
        'facility_id',
        'equipment_id',
        'event_id',
        'user_id',
        'quantity',
        'max_participants',
    ];

    /**
     * Fields that should never be sanitized (passwords, tokens).
     */
    protected array $excludedFields = [
        'password',
        'password_confirmation',
        'current_password',
        '_token',
        '_method',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $input = $request->all();
        $sanitized = $this->sanitize($input);
        $request->merge($sanitized);

        return $next($request);
    }

    /**
     * Recursively sanitize the input array.
     */
    protected function sanitize(array $input, string $prefix = ''): array
    {
        $result = [];

        foreach ($input as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            // Skip excluded fields entirely
            if (in_array($key, $this->excludedFields, true)) {
                $result[$key] = $value;
                continue;
            }

            // Recurse into nested arrays (but not file uploads)
            if (is_array($value)) {
                $result[$key] = $this->sanitize($value, $fullKey);
                continue;
            }

            // Leave non-string types untouched
            if (!is_string($value)) {
                $result[$key] = $value;
                continue;
            }

            // ─── String sanitization ───
            // 1. Trim whitespace
            $value = trim($value);

            // 2. Strip HTML/PHP tags
            $value = strip_tags($value);

            // 3. Convert empty strings to null
            if ($value === '') {
                $result[$key] = null;
                continue;
            }

            // ─── Numeric field validation ───
            if (in_array($key, $this->numericFields, true)) {
                // Must be a valid integer; reject non-numeric input
                if (ctype_digit($value)) {
                    $result[$key] = (int) $value;
                } else {
                    // Set to null — Form Request validation will catch invalid values
                    $result[$key] = null;
                }
                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
