<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Validates the self-registration of a new student account.
 *
 * Self-registration is fixed to role_id = 4 (Student), so employee_id
 * must always follow the student format: YY-NNNNN (e.g. 24-00123).
 */
class RegisterRequest extends FormRequest
{
    use ValidatesUsername;

    public function authorize(): bool
    {
        return !Auth::check(); // Only guests can register
    }

    public function rules(): array
    {
        // Force role_id to 4 (Student) so the trait selects the student regex
        $this->merge(['role_id' => 4]);

        return [
            'first_name'      => ['required', 'string', 'max:100'],
            'last_name'       => ['required', 'string', 'max:100'],
            'email'           => ['required', 'email', 'max:255', 'unique:users,email'],
            'employee_id'     => $this->employeeIdRule(),   // → YY-NNNNN for students
            'department_id'   => ['nullable', 'integer', 'exists:departments,id'],
            'password'        => ['required', 'string', 'alpha_num', 'max:8', 'confirmed'],
            'privacy_consent' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return array_merge($this->employeeIdMessages(), [
            'email.unique'          => 'An account with this email address already exists.',
            'password.confirmed'    => 'Password confirmation does not match.',
            'privacy_consent.accepted' => 'You must accept the Data Privacy Policy to register.',
        ]);
    }

    /**
     * Override attribute names for friendlier error labels.
     */
    public function attributes(): array
    {
        return [
            'employee_id'     => 'Student ID',
            'privacy_consent' => 'Data Privacy Policy agreement',
        ];
    }
}
