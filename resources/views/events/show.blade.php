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
            @if(auth()->user()->hasPermission('event.manage'))
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

<div class="grid-sidebar-layout">
    <div>
        {{-- Description --}}
        <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="card-header">
                <div class="flex gap-sm">
                    @php $statusBadge = match($event->status) { 'upcoming' => 'badge-general', 'ongoing' => 'badge-success', 'completed' => 'badge-warning', 'cancelled' => 'badge-error', default => 'badge-general' }; @endphp
                    <span class="badge {{ $statusBadge }}">{{ ucfirst($event->status) }}</span>
                    <span class="badge badge-general">{{ ucfirst($event->event_type) }}</span>
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
@endsection
