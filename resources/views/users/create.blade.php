@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Create User</h1>
        <p class="page-subtitle">Add a new user account to the system.</p>
    </div>
    <a href="{{ url('/users') }}" class="btn btn-secondary">Cancel</a>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="{{ url('/users') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required autofocus value="{{ old('first_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required value="{{ old('last_name') }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" required value="{{ old('email') }}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="employee_id">Employee/Student ID</label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" value="{{ old('employee_id') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-control form-select">
                        <option value="">— Select Department —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="role_id">Role *</label>
                    <select id="role_id" name="role_id" class="form-control form-select" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status *</label>
                    <select id="status" name="status" class="form-control form-select" required>
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)" aria-label="Show password" title="Show password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirm Password *</label>
                    <div class="password-wrapper">
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required placeholder="Re-enter password">
                        <button type="button" class="password-toggle" onclick="togglePassword('password_confirmation', this)" aria-label="Show password" title="Show password">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            <div style="border-top: 1px solid var(--color-surface-200); margin: 1.5rem 0;"></div>
            <button type="submit" class="btn btn-primary btn-lg">Create User</button>
        </form>
    </div>
</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        btn.title = isPassword ? 'Hide password' : 'Show password';
        btn.innerHTML = isPassword
            ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="23" x2="23" y2="23"></line></svg>'
            : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    }
</script>
@endsection
