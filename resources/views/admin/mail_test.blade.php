@extends('layouts.app')

@section('title', 'Email Diagnostics & Live Testing — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Email Diagnostics & Live Testing</h1>
        <p class="page-subtitle">Verify email delivery, test notification dispatching, and diagnose cloud network settings.</p>
    </div>
    <div class="header-actions-mobile">
        <a href="{{ url('/dashboard') }}" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

@if(session('mail_success'))
    <div class="alert alert-success animate-fade-in-up" style="display: flex; gap: 0.75rem; align-items: flex-start; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: #ecfdf5; border: 1px solid #10b981; color: #065f46;">
        <span style="font-size: 1.25rem;">✅</span>
        <div>
            <strong>Email Sent Successfully!</strong>
            <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem;">{{ session('mail_success') }}</p>
            <p style="margin: 0.5rem 0 0 0; font-size: 0.8rem; color: #047857;">If using a personal email, check your <strong>Inbox</strong> and <strong>Spam / Junk</strong> folder.</p>
        </div>
    </div>
@endif

@if(session('mail_error'))
    <div class="alert alert-error animate-fade-in-up" style="display: flex; gap: 0.75rem; align-items: flex-start; padding: 1rem 1.25rem; border-radius: 8px; margin-bottom: 1.5rem; background-color: #fef2f2; border: 1px solid #ef4444; color: #991b1b;">
        <span style="font-size: 1.25rem;">🚨</span>
        <div>
            <strong>Email Dispatch Failed</strong>
            <p style="margin: 0.25rem 0 0 0; font-size: 0.9rem; font-family: monospace;">{{ session('mail_error') }}</p>
        </div>
    </div>
@endif

<div class="grid-sidebar-layout">
    {{-- Left: Live Test Form --}}
    <div>
        <div class="card animate-fade-in-up" style="animation-delay: 50ms;">
            <div class="card-header">
                <h3 class="card-title">Send Live Test Email</h3>
                <span class="badge {{ $activeMailer === 'resend' ? 'badge-success' : ($activeMailer === 'smtp' ? 'badge-warning' : 'badge-neutral') }}">
                    Driver: {{ strtoupper($activeMailer) }}
                </span>
            </div>
            <div class="card-body">
                <form action="{{ url('/admin/mail-test') }}" method="POST">
                    @csrf

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="email" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">
                            Recipient Email Address <span style="color: #ef4444;">*</span>
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email', $currentUser->email ?? '') }}"
                            required
                            class="form-control"
                            placeholder="e.g. yourname@gmail.com"
                            style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px;"
                        >
                        <span class="text-muted" style="font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            @if($activeMailer === 'resend')
                                With free Resend test sender (<code>onboarding@resend.dev</code>), use the email address registered on your Resend account.
                            @else
                                Enter any accessible email address to test live delivery.
                            @endif
                        </span>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="title" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Notification Title</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            value="{{ old('title', 'ICOEP System Live Email Test') }}"
                            class="form-control"
                            style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px;"
                        >
                    </div>

                    <div class="form-group" style="margin-bottom: 1.25rem;">
                        <label for="type" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Alert Type Theme</label>
                        <select name="type" id="type" class="form-control" style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px;">
                            <option value="info" selected>Info (Blue)</option>
                            <option value="success">Success (Green)</option>
                            <option value="warning">Warning (Amber)</option>
                            <option value="error">Error (Red)</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 1.5rem;">
                        <label for="message" style="display: block; font-weight: 600; margin-bottom: 0.5rem;">Message Body</label>
                        <textarea
                            id="message"
                            name="message"
                            rows="3"
                            class="form-control"
                            style="width: 100%; padding: 0.65rem 0.85rem; border: 1px solid #d1d5db; border-radius: 6px;"
                        >{{ old('message', 'This is a test notification confirming that live email alerts are functioning properly.') }}</textarea>
                    </div>

                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m22 2-7 20-4-9-9-4Z"></path>
                                <path d="M22 2 11 13"></path>
                            </svg>
                            Send Live Test Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Right: Diagnostic Status and Setup Guide --}}
    <div>
        {{-- Environment Status Card --}}
        <div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-bottom: 1.5rem;">
            <div class="card-header">
                <h3 class="card-title">Current Configuration</h3>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table" style="width: 100%; margin: 0;">
                    <tbody>
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">Active Mailer</td>
                            <td style="padding: 0.75rem 1rem;">
                                <code>{{ $activeMailer }}</code>
                                @if($activeMailer === 'gmail-relay')
                                    <span style="color: #059669; font-size: 0.8rem; margin-left: 0.25rem;">(Google Apps Script Relay — Unlimited School Recipients!)</span>
                                @elseif($activeMailer === 'resend')
                                    <span style="color: #059669; font-size: 0.8rem; margin-left: 0.25rem;">(HTTPS Port 443 — Render Free friendly)</span>
                                @elseif($activeMailer === 'smtp')
                                    <span style="color: #d97706; font-size: 0.8rem; margin-left: 0.25rem;">(Standard SMTP)</span>
                                @endif
                            </td>
                        </tr>
                        @if($relayUrlSet)
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">Google Relay</td>
                            <td style="padding: 0.75rem 1rem;">
                                <span style="color: #059669;">✔ Active (Delivers to all @ciccebu.edu.ph school accounts)</span>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">Resend API Key</td>
                            <td style="padding: 0.75rem 1rem;">
                                @if($resendKeySet)
                                    <span style="color: #059669;">✔ Configured ({{ $resendMasked }})</span>
                                @else
                                    <span style="color: #dc2626;">✖ Not Configured</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">From Address</td>
                            <td style="padding: 0.75rem 1rem;"><code>{{ $fromAddress ?: 'Not Set' }}</code></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">From Name</td>
                            <td style="padding: 0.75rem 1rem;">{{ $fromName ?: 'Not Set' }}</td>
                        </tr>
                        @if($activeMailer === 'smtp')
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">SMTP Host / Port</td>
                            <td style="padding: 0.75rem 1rem;"><code>{{ $smtpHost }}:{{ $smtpPort }}</code></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 600; padding: 0.75rem 1rem;">SMTP Username</td>
                            <td style="padding: 0.75rem 1rem;"><code>{{ $smtpUsername ?: 'Not Set' }}</code></td>
                        </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Free Tier Resend Guide Card --}}
        <div class="card animate-fade-in-up" style="animation-delay: 150ms; border-left: 4px solid var(--color-brand-500, #0ea5e9);">
            <div class="card-header">
                <h3 class="card-title">💡 Why Resend on Render Free?</h3>
            </div>
            <div class="card-body" style="font-size: 0.875rem; line-height: 1.6;">
                <p>Render's Free tier blocks all outgoing SMTP ports (<strong>25, 465, and 587</strong>) to prevent spam.</p>
                <p><strong>Resend</strong> works by sending emails over <strong>HTTPS (Port 443)</strong>, which Render never blocks.</p>
                <div style="background-color: var(--color-bg-secondary, #f8fafc); padding: 0.75rem; border-radius: 6px; margin-top: 0.75rem;">
                    <strong>Quick Setup in 2 minutes:</strong>
                    <ol style="padding-left: 1.25rem; margin: 0.5rem 0 0 0;">
                        <li>Sign up at <a href="https://resend.com" target="_blank" rel="noopener" style="color: #0ea5e9; text-decoration: underline;">resend.com</a> (Free: 3,000 emails/mo).</li>
                        <li>Create an API Key and copy it (starts with <code>re_...</code>).</li>
                        <li>In Render Dashboard → Environment, add:
                            <ul style="padding-left: 1rem; margin-top: 0.25rem;">
                                <li><code>MAIL_MAILER</code> = <code>resend</code></li>
                                <li><code>RESEND_API_KEY</code> = <code>re_your_api_key</code></li>
                                <li><code>MAIL_FROM_ADDRESS</code> = <code>onboarding@resend.dev</code></li>
                                <li><code>MAIL_FROM_NAME</code> = <code>ICOEP System</code></li>
                            </ul>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
