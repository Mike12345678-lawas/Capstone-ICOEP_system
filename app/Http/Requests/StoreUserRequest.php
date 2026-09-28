<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validates the creation of a new user by an administrator.
 *
 * employee_id validation:
 *  - Student   → YY-NNNNN  (e.g. 24-00123)
 *  - Admin / Faculty / Staff → NNN (3 digits, zero-padded, e.g. 001)
 */
class StoreUserRequest extends FormRequest
{
    use ValidatesUsername;

    public function authorize(): bool
    {
        return Auth::user()?->hasPermission('user.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name'  => ['required', 'string', 'max:100'],
            'last_name'   => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_id' => $this->employeeIdRule(),
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'role_id'     => ['required', 'integer', 'exists:roles,id'],
            'status'      => ['required', 'in:active,inactive'],
            'password'    => ['required', 'string', 'alpha_num', 'max:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->employeeIdMessages(), [
            'role_id.required' => 'A role must be assigned to the user.',
            'role_id.exists'   => 'The selected role is invalid.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);
    }
}
