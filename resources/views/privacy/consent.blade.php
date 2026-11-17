@extends('layouts.app')

@section('title', 'Data Privacy Consent — ' . config('icoep.app_name'))

@section('content')
<div style="max-width: 760px; margin: 1.5rem auto 3rem; padding: 0 1rem;">
    {{-- Header Banner --}}
    <div class="card animate-fade-in-up" style="border-top: 4px solid var(--color-brand-600, #0284c7); margin-bottom: 1.5rem;">
        <div class="card-body" style="padding: 2rem;">
            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.75rem;">
                <span class="badge badge-brand" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">
                    Republic Act No. 10173 (Data Privacy Act of 2012)
                </span>
            </div>
            <h1 style="font-size: 1.625rem; font-weight: 800; color: var(--color-text, #0f172a); margin: 0 0 0.5rem;">
                Data Privacy Notice &amp; Consent Agreement
            </h1>
            <p style="color: var(--color-text-muted, #64748b); font-size: 0.95rem; line-height: 1.5; margin: 0;">
                Welcome, <strong>{{ $user->full_name }}</strong> (<code>{{ $user->employee_id ?: $user->email }}</code>).
                To protect your rights and ensure transparency in compliance with Philippine data privacy laws, please review and accept our Data Privacy Agreement before accessing the portal.
            </p>
        </div>
    </div>

    {{-- Information Summary Card --}}
    <div class="card animate-fade-in-up" style="animation-delay: 50ms; margin-bottom: 1.5rem;">
        <div class="card-header" style="background: rgba(2, 132, 199, 0.04); border-bottom: 1px solid var(--color-border, #e2e8f0);">
            <h3 class="card-title" style="font-size: 1rem; color: var(--color-brand-700, #0369a1); display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Summary of Data Collection &amp; Use
            </h3>
        </div>
        <div class="card-body" style="padding: 1.5rem; font-size: 0.9rem; line-height: 1.6; color: var(--color-text, #334155);">
            <div style="margin-bottom: 1.25rem;">
                <h4 style="font-weight: 700; font-size: 0.925rem; margin-bottom: 0.35rem; color: var(--color-text, #0f172a);">
                    1. What Information We Process
                </h4>
                <p style="margin: 0;">
                    Your institutional profile (name, official email, ID number, assigned role, department, and contact number), reservation and borrowing requests, activity logs, and event participation records.
                </p>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <h4 style="font-weight: 700; font-size: 0.925rem; margin-bottom: 0.35rem; color: var(--color-text, #0f172a);">
                    2. Institutional Purpose
                </h4>
                <p style="margin: 0;">
                    Your data is strictly processed to manage campus facilities, equipment logistics, event organization, attendance verification, and automated operational notifications.
                </p>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <h4 style="font-weight: 700; font-size: 0.925rem; margin-bottom: 0.35rem; color: var(--color-text, #0f172a);">
                    3. Protection &amp; Confidentiality
                </h4>
                <p style="margin: 0;">
                    Passcodes are cryptographically hashed using Bcrypt. Sensitive information is never sold or shared with unauthorized third parties. Personal schedules are masked on public calendars.
                </p>
            </div>

            <div>
                <h4 style="font-weight: 700; font-size: 0.925rem; margin-bottom: 0.35rem; color: var(--color-text, #0f172a);">
                    4. Your Rights Under RA 10173
                </h4>
                <p style="margin: 0 0 0.5rem;">
                    You retain the right to be informed, to access your personal data, to rectify errors, and to object to unauthorized processing.
                </p>
                <a href="{{ route('privacy.policy') }}" target="_blank" rel="noopener" style="color: var(--color-brand-600, #0284c7); font-weight: 600; text-decoration: underline;">
                    Read our full Institutional Data Privacy Policy &rarr;
                </a>
            </div>
        </div>
    </div>

    {{-- Consent Form Action Card --}}
    <div class="card animate-fade-in-up" style="animation-delay: 100ms; border: 1px solid var(--color-brand-200, #bae6fd); background: var(--bg-card, #ffffff);">
        <div class="card-body" style="padding: 1.75rem;">
            @if($errors->any())
                <div class="alert alert-danger" style="margin-bottom: 1.25rem; padding: 0.75rem 1rem; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 0.875rem;">
                    <strong>Action Required:</strong> {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('privacy.consent.accept') }}">
                @csrf

                <div style="background: rgba(2, 132, 199, 0.05); padding: 1rem 1.25rem; border-radius: 8px; border: 1px solid rgba(2, 132, 199, 0.15); margin-bottom: 1.5rem;">
                    <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; font-size: 0.9rem; line-height: 1.5; color: var(--color-text, #1e293b);">
                        <input type="checkbox" name="agree" value="1" id="consentCheckbox" required style="width: 1.25rem; height: 1.25rem; margin-top: 0.15rem; accent-color: var(--color-brand-600, #0284c7); cursor: pointer;" {{ old('agree') ? 'checked' : '' }}>
                        <span>
                            <strong>I have read, understood, and accept the Data Privacy Notice.</strong>
                            I hereby grant my free, voluntary, and informed consent to the collection, processing, and storage of my personal information by {{ config('icoep.institution_name') }} in connection with the operations of {{ config('icoep.app_full_name') }} (ICOEP) in accordance with Republic Act No. 10173.
                        </span>
                    </label>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary" id="acceptBtn" style="padding: 0.65rem 1.5rem; font-size: 0.95rem; font-weight: 600;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Accept &amp; Continue to Portal
                    </button>

                    <button type="button" class="btn btn-secondary" onclick="document.getElementById('logoutForm').submit();" style="font-size: 0.875rem;">
                        Decline &amp; Sign Out
                    </button>
                </div>
            </form>

            <form id="logoutForm" method="POST" action="{{ route('logout') }}" style="display: none;">
                @csrf
            </form>
        </div>
    </div>
</div>
@endsection
