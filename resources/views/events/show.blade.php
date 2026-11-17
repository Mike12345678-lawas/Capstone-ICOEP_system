@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $event->title }}</h1>
        <p class="page-subtitle">Organized by {{ $event->organizer->full_name }} &bull; {{ $event->event_date->format('F d, Y') }}</p>
    </div>
    <div class="header-actions-mobile">
        <div class="btn-row-mobile">
            @if(auth()->user()->hasRole('Administrator') && $event->status === 'pending')
                <form method="POST" action="{{ url('/events/' . $event->id . '/approve') }}" style="display: inline;">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981;">
                        ✓ Approve Event
                    </button>
                </form>
                <button type="button" class="btn btn-secondary" style="color: #ef4444;" onclick="document.getElementById('rejectModal').style.display='flex'">
                    ✕ Reject
                </button>
            @endif
            @if(auth()->user()->hasPermission('event.manage') && $event->status !== 'pending' && $event->status !== 'rejected')
                <a href="{{ url('/events/' . $event->id . '/scanner') }}" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    Scanner
                </a>
                <a href="{{ url('/events/' . $event->id . '/manage') }}" class="btn btn-secondary">Manage</a>
            @endif
            <a href="{{ url('/dashboard?section=events') }}" class="btn btn-secondary">&larr; Back</a>
        </div>
    </div>
</div>

@if($event->status === 'pending')
<div class="card animate-fade-in-up" style="animation-delay: 50ms; margin-bottom: 1.5rem; background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.3);">
    <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
        <div>
            <h3 style="color: #b45309; font-size: 1.1rem; font-weight: 700; margin: 0 0 0.25rem 0;">⏳ Pending Administrative Approval</h3>
            <p style="color: var(--color-surface-700); margin: 0; font-size: 0.9rem;">
                @if(auth()->user()->hasRole('Administrator'))
                    This event request was submitted by <strong>{{ $event->organizer->full_name }}</strong> and is waiting for your review.
                @else
                    Your event request has been submitted to the administration. You will be notified once an administrator approves or reviews it.
                @endif
            </p>
        </div>
        @if(auth()->user()->hasRole('Administrator'))
            <div style="display: flex; gap: 0.5rem;">
                <form method="POST" action="{{ url('/events/' . $event->id . '/approve') }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary" style="background: #10b981; border-color: #10b981;">Approve</button>
                </form>
                <button type="button" class="btn btn-secondary" style="color: #ef4444;" onclick="document.getElementById('rejectModal').style.display='flex'">Reject</button>
            </div>
        @endif
    </div>
</div>
@elseif($event->status === 'rejected')
<div class="card animate-fade-in-up" style="animation-delay: 50ms; margin-bottom: 1.5rem; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.3);">
    <div class="card-body">
        <h3 style="color: #dc2626; font-size: 1.1rem; font-weight: 700; margin: 0 0 0.25rem 0;">✕ Event Request Rejected</h3>
        <p style="color: var(--color-surface-700); margin: 0; font-size: 0.9rem;">
            This event request was rejected by the administrator.{{ $event->remarks ? ' Reason: ' . $event->remarks : '' }}
        </p>
    </div>
</div>
@endif

<div class="grid-sidebar-layout">
    <div>
        {{-- Description --}}
        <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="card-header">
                <div class="flex gap-sm" style="flex-wrap: wrap;">
                    @php $statusBadge = match($event->status) { 'upcoming' => 'badge-general', 'ongoing' => 'badge-success', 'completed' => 'badge-warning', 'cancelled', 'rejected' => 'badge-error', 'pending' => 'badge-warning', default => 'badge-general' }; @endphp
                    <span class="badge {{ $statusBadge }}">{{ ucfirst($event->status) }}</span>
                    <span class="badge badge-general">{{ ucfirst($event->event_type) }}</span>
                    @php $auds = $event->getTargetAudiences(); @endphp
                    @if(in_array('all', $auds, true) || empty($auds))
                        <span class="badge badge-general">All Campus</span>
                    @else
                        @foreach($auds as $aud)
                            @if($aud === 'faculty')
                                <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.3); font-weight: 600;">Faculty Only</span>
                            @elseif($aud === 'staff')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3); font-weight: 600;">Staff (Non-Teaching) Only</span>
                            @elseif($aud === 'student')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.3); font-weight: 600;">Students Only</span>
                            @elseif(in_array($aud, ['bsit', 'bshm', 'bsba-fm', 'bsed', 'beed']))
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #047857; border: 1px solid rgba(16, 185, 129, 0.3); font-weight: 600;">{{ \App\Models\Event::AUDIENCES[$aud] ?? strtoupper($aud) }}</span>
                            @else
                                <span class="badge badge-general">{{ ucfirst($aud) }}</span>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div style="font-size: var(--text-base); line-height: 1.8;">
                    {!! nl2br(e($event->description)) !!}
                </div>
            </div>
        </div>

        {{-- Objectives --}}
        @if($event->objectives)
        <div class="card animate-fade-in-up" style="animation-delay: 150ms; margin-top: 1.5rem;">
            <div class="card-header"><h3 class="card-title">🎯 Event Targets / Objectives</h3></div>
            <div class="card-body">
                <div style="font-size: var(--text-base); line-height: 1.8; color: var(--color-surface-700);">
                    {!! nl2br(e($event->objectives)) !!}
                </div>
            </div>
        </div>
        @endif
    </div>

    <div>
        {{-- Event Details --}}
        <div class="card animate-fade-in-up" style="animation-delay: 200ms;">
            <div class="card-header"><h3 class="card-title">Event Details</h3></div>
            <div class="card-body">
                <div style="margin-bottom: 1rem;"><strong>Date</strong><br>{{ $event->event_date->format('F d, Y') }}</div>
                <div style="margin-bottom: 1rem;"><strong>Time</strong><br>{{ \Carbon\Carbon::parse($event->time_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($event->time_end)->format('h:i A') }}</div>
                <div style="margin-bottom: 1rem;"><strong>Venue</strong><br>{{ $event->venue ?: 'TBA' }}</div>
                <div style="margin-bottom: 1rem;"><strong>Audience</strong><br><span style="font-weight: 600;">{{ $event->target_audience_label }}</span></div>
                <div style="margin-bottom: 1rem;"><strong>Registered</strong><br>{{ $event->registrations->count() }}{{ $event->max_participants > 0 ? ' / ' . $event->max_participants : '' }}</div>
                <div><strong>Type</strong><br>{{ ucfirst($event->event_type) }}</div>
            </div>
        </div>

        {{-- Registration / QR Code --}}
        @if($event->isUpcoming())
        <div class="card animate-fade-in-up" style="animation-delay: 300ms; margin-top: 1.5rem;">
            <div class="card-body" style="text-align: center;">
                @if($isRegistered)
                    <p style="color: #10b981; font-weight: 600; margin-bottom: .5rem;">✓ You are registered!</p>

                    {{-- QR Code Display --}}
                    @if($registration && $registration->qr_code)
                    <div style="margin: 1rem 0; padding: 1.5rem; background: #fff; border-radius: 12px; border: 2px dashed var(--color-surface-200); text-align: center;">
                        <div style="display: flex; justify-content: center; margin-bottom: .75rem;">
                            <canvas id="qr-code-canvas" width="240" height="240" style="max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08);"></canvas>
                        </div>
                        <p style="font-size: .75rem; color: var(--color-surface-500); margin-bottom: .5rem;">Present this QR code at the venue for check-in</p>
                        <code style="font-size: .7rem; color: var(--color-surface-400); word-break: break-all;">{{ $registration->qr_code }}</code>
                    </div>
                    @endif

                    <form method="POST" action="{{ url('/events/' . $event->id . '/register') }}">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Cancel your registration?')">Cancel Registration</button>
                    </form>
                @elseif($event->isFull())
                    <p class="text-muted" style="font-weight: 500;">Event is full — registration closed.</p>
                @elseif(!auth()->user() || !$event->canUserRegister(auth()->user()))
                    <div style="padding: 1rem; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 8px; color: #b91c1c; font-size: 0.875rem;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-bottom: 0.5rem; display: inline-block;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <div style="font-weight: 600;">Restricted Registration</div>
                        <p style="margin: 0.25rem 0 0 0;">This event is open for registration only to <strong>{{ $event->target_audience_label }}</strong>.</p>
                    </div>
                @else
                    <form method="POST" action="{{ url('/events/' . $event->id . '/register') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">Register for This Event</button>
                    </form>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Real Standards-Compliant QR Code Generation Script --}}
@if($isRegistered && $registration && $registration->qr_code)
@push('scripts')
@vite(['resources/js/qr-generator.js'])
<script>
(function() {
    const qrData = @json($registration->qr_code);
    const canvas = document.getElementById('qr-code-canvas');
    if (!canvas || !qrData) return;

    function renderQRCode() {
        if (window.QRCode && typeof window.QRCode.toCanvas === 'function') {
            window.QRCode.toCanvas(canvas, qrData, {
                errorCorrectionLevel: 'M',
                width: 240,
                margin: 4,
                color: {
                    dark: '#0f172a',
                    light: '#ffffff'
                }
            }, function (error) {
                if (error) {
                    console.error('Error rendering QR code:', error);
                }
            });
        }
    }

    if (window.QRCode) {
        renderQRCode();
    } else {
        window.addEventListener('qrcode-ready', renderQRCode, { once: true });
        setTimeout(renderQRCode, 100);
    }
})();
</script>
@endpush
@endif
@if(auth()->user()->hasRole('Administrator') && $event->status === 'pending')
<div id="rejectModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #ffffff; border-radius: 12px; max-width: 480px; width: 100%; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; font-size: 1.15rem; color: #dc2626;">Reject Event Request</h3>
        <p style="color: var(--color-surface-600); font-size: 0.9rem; margin-bottom: 1rem;">
            Are you sure you want to reject "{{ $event->title }}"? You may optionally provide a reason for the organizer.
        </p>
        <form method="POST" action="{{ url('/events/' . $event->id . '/reject') }}">
            @csrf @method('PATCH')
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="reject_remarks">Reason / Remarks (Optional)</label>
                <textarea name="remarks" id="reject_remarks" class="form-control" rows="3" placeholder="Explain why this event request cannot be approved..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
