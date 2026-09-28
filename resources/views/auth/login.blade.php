@extends('layouts.auth')

@section('title', 'Sign In')

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
            <h1>{{ config('icoep.app_name') }}</h1>
            <p>{{ config('icoep.app_full_name') }}</p>
            <p class="institution">{{ config('icoep.institution_name') }}</p>
        </div>

        @if(session('error'))
            <div class="flash-message flash-error">
                <span class="flash-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                </span>
                <span class="flash-text">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('success'))
            <div class="flash-message flash-success">
                <span class="flash-text">{{ session('success') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ url('/login') }}" id="loginForm">
            @csrf

            <div class="form-group">
                <label class="form-label" for="employee_id">Employee / Student ID</label>
                <input type="text" id="employee_id" name="employee_id" class="form-control"
                       placeholder="e.g. 001 or 24-00001"
                       value="{{ old('employee_id') }}"
                       required autofocus>
                <span class="form-hint">Staff: 3-digit number (001) &bull; Student: YY-NNNNN (24-00001)</span>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Password
                </label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password" required maxlength="8">
                    <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
                <span class="form-hint">Alphanumeric only, max 8 characters</span>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10 17 15 12 10 7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Sign In
            </button>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="{{ url('/register') }}">Register here</a>
        </div>
    </div>
</div>

<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        if (input.type === 'password') {
            input.type = 'text';
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
        } else {
            input.type = 'password';
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        }
    }
</script>
@endsection
