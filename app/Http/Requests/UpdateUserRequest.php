<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Validates an update to an existing user.
 *
 * employee_id validation:
 *  - Student   → YY-NNNNN  (e.g. 24-00123)
 *  - Admin / Faculty / Staff → NNN (3 digits, zero-padded, e.g. 001)
 *
 * When the request doesn't include a role_id (self-edit), the user's
 * existing role_id is used to determine the correct format.
 */
class UpdateUserRequest extends FormRequest
{
    use ValidatesUsername;

    public function authorize(): bool
    {
        $targetUser = $this->route('user');
        $isSelf     = $targetUser?->id === Auth::id();

        return $isSelf || Auth::user()?->hasPermission('user.edit');
    }

    public function rules(): array
    {
        $user = $this->route('user');

        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            // role_id is optional on self-edit; falls back to existing role
            'employee_id' => $this->employeeIdRule($user?->role_id),
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'custodian_unit_id' => [
                'nullable',
                'integer',
                'exists:custodian_units,id',
                Rule::requiredIf(function () use ($user) {
                    $roleId = $this->input('role_id') ?? $user?->role_id;
                    $role = Role::find($roleId);
                    return $role && in_array($role->name, ['Registrar', 'Librarian', 'Maintenance Staff', 'ComLab Staff'], true);
                }),
            ],
            'phone'       => ['nullable', 'string', 'max:20'],
            // Password is optional on update; require confirmation if provided
            'password'    => ['nullable', 'string', 'alpha_num', 'max:8', 'confirmed'],
            // Admin-only fields — validated but access is gated in the controller
            'role_id'     => ['nullable', 'integer', 'exists:roles,id'],
            'status'      => ['nullable', 'in:active,inactive'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->employeeIdMessages(), [
            'email.unique'               => 'This email address is already in use.',
            'custodian_unit_id.required' => 'A custodian unit must be selected for this custodian role.',
            'password.confirmed'         => 'Password confirmation does not match.',
        ]);
    }
}
