@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Data Privacy Policy</h1>
        <p class="page-subtitle">In compliance with Republic Act No. 10173 (Data Privacy Act of 2012)</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 900px;">
    <div class="card-body" style="font-size: var(--text-base); line-height: 1.9;">
        <h2 style="margin-top: 0;">Introduction</h2>
        <p>{{ config('icoep.institution_name') }} ("Institution") is committed to protecting the privacy and security of personal data collected and processed through the <strong>{{ config('icoep.app_full_name') }} ({{ config('icoep.app_name') }})</strong> system.</p>
        <p>This policy outlines how we collect, use, store, and protect your personal information in compliance with Republic Act No. 10173, also known as the <strong>Data Privacy Act of 2012</strong>, and its Implementing Rules and Regulations.</p>

        <h2>Information We Collect</h2>
        <ul>
            <li><strong>Personal Information:</strong> Name, email address, employee/student ID, department, phone number</li>
            <li><strong>Account Information:</strong> Login credentials (password stored in encrypted/hashed form)</li>
            <li><strong>System Activity:</strong> Login timestamps, IP addresses, browser information, and audit trail records</li>
            <li><strong>Transaction Data:</strong> Facility reservations, equipment borrowing records, event registrations</li>
        </ul>

        <h2>How We Use Your Information</h2>
        <ul>
            <li>To authenticate and authorize access to the system</li>
            <li>To process facility reservations and equipment borrowing requests</li>
            <li>To manage event registrations and attendance tracking</li>
            <li>To generate reports for institutional planning and improvement</li>
            <li>To maintain audit trails for security and compliance purposes</li>
            <li>To communicate important announcements and notifications</li>
        </ul>

        <h2>Data Retention</h2>
        <p>Personal data is retained for the duration of your active enrollment or employment with the Institution, plus an additional period as required by applicable laws and regulations. Transaction records and audit logs are retained for a minimum of five (5) years.</p>

        <h2>Your Rights</h2>
        <p>Under the Data Privacy Act of 2012, you have the following rights:</p>
        <ul>
            <li><strong>Right to be Informed</strong> — You have the right to know how your data is collected and processed</li>
            <li><strong>Right to Access</strong> — You may request access to your personal data stored in the system</li>
            <li><strong>Right to Correction</strong> — You may request correction of inaccurate or incomplete personal data</li>
            <li><strong>Right to Erasure</strong> — Under certain conditions, you may request deletion of your personal data</li>
            <li><strong>Right to Object</strong> — You may object to the processing of your personal data</li>
            <li><strong>Right to Data Portability</strong> — You may request a copy of your personal data in a structured format</li>
        </ul>

        <h2>Data Security</h2>
        <p>We implement appropriate technical and organizational measures to protect personal data, including:</p>
        <ul>
            <li>Secure password hashing using industry-standard algorithms (bcrypt)</li>
            <li>Role-based access control (RBAC) to limit data access</li>
            <li>Session management and automatic logout mechanisms</li>
            <li>Comprehensive audit logging of all system activities</li>
            <li>Account lockout protection against brute-force attacks</li>
        </ul>

        <h2>Contact Information</h2>
        <p>For questions, concerns, or requests regarding your personal data, please contact the Data Protection Officer (DPO) at the Administration Office of {{ config('icoep.institution_name') }}.</p>

        <div style="margin-top: 2rem; padding: 1.5rem; background: #fff; border-radius: .75rem; text-align: center;">
            <p class="text-muted" style="margin: 0;">Last updated: {{ date('F d, Y') }}</p>
        </div>
    </div>
</div>
@endsection
