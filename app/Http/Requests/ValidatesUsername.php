<?php

namespace App\Http\Requests;

use App\Enums\UserIdType;
use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Shared username/employee-ID validation logic.
 *
 * Format rules:
 *  - Student   → YY-NNNNN  (e.g. 24-00123)
 *  - Admin / Faculty / Staff → NNN (exactly 3 digits, zero-padded, e.g. 001)
 */
trait ValidatesUsername
{
    /**
     * Determine the regex pattern that applies based on the role being submitted.
     * Falls back to the role already on the model when updating.
     *
     * @param  int|null  $existingRoleId  The role currently stored on the user (for updates).
     */
    protected function employeeIdRule(?int $existingRoleId = null): array
    {
        $roleId = (int) ($this->input('role_id') ?? $existingRoleId);
        $role   = Role::find($roleId);

        if (!$role) {
            return ['nullable', 'string', 'max:50'];
        }

        // Student role uses YY-NNNNN format; all others use NNN (3-digit employee number)
        $idType  = $role->name === 'Student' ? UserIdType::Student : UserIdType::Employee;
        $maxLen  = $idType === UserIdType::Student ? 20 : 3;

        return [
            'nullable',
            'string',
            'max:' . $maxLen,
            'regex:' . $idType->pattern(),
        ];
    }

    /**
     * Custom validation messages for employee_id.
     */
    protected function employeeIdMessages(): array
    {
        return [
            'employee_id.regex' => 'Student ID must follow the format YY-NNNNN (e.g. 24-00123). '
                . 'Employee number must be exactly 3 digits (e.g. 001) for Administrator, Faculty, or Staff.',
        ];
    }
}
