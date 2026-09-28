@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-brand">
            <div class="auth-brand-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <h1>Create Account</h1>
            <p>Register for {{ config('icoep.app_full_name') }}</p>
        </div>

        @if($errors->any())
            <div class="flash-message flash-error">
                <span class="flash-text">
                    @foreach($errors->all() as $error)
                        {{ $error }}<br>
                    @endforeach
                </span>
            </div>
        @endif

        <form method="POST" action="{{ url('/register') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required value="{{ old('first_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required value="{{ old('last_name') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" required placeholder="you@slmcb.edu.ph" value="{{ old('email') }}">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="employee_id">School / Employee ID</label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" value="{{ old('employee_id') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department_id">Department / Course</label>
                    <select id="department_id" name="department_id" class="form-control form-select">
                        <option value="">— Select Department —</option>
                        @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                </div>
                <div class="form-group">
                    <label class="form-label" for="password_confirmation">Confirm Password *</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-top: 1rem;">
                <label class="form-check">
                    <input type="checkbox" name="privacy_consent" value="1" required>
                    <span class="form-check-label">I agree to the <a href="{{ url('/privacy/policy') }}" target="_blank">Data Privacy Policy</a> in compliance with RA 10173.</span>
                </label>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="{{ url('/login') }}">Sign in here</a>
        </div>
    </div>
</div>
@endsection
