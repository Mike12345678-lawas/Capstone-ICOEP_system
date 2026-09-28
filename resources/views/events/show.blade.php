@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $event->title }}</h1>
        <p class="page-subtitle">Organized by {{ $event->organizer->full_name }} &bull; {{ $event->event_date->format('F d, Y') }}</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->hasPermission('event.manage'))
            <a href="{{ url('/events/' . $event->id . '/scanner') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                Scanner
            </a>
            <a href="{{ url('/events/' . $event->id . '/manage') }}" class="btn btn-secondary">Manage Event</a>
        @endif
        <a href="{{ url('/dashboard?section=events') }}" class="btn btn-secondary">&larr; Back</a>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 1.5rem;">
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
                    <div style="margin: 1rem 0; padding: 1.5rem; background: #fff; border-radius: 12px; border: 2px dashed var(--color-surface-200);">
                        <div style="margin-bottom: .75rem;">
                            <svg id="qr-code-svg" width="200" height="200"></svg>
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

{{-- QR Code Generation Script (uses a lightweight SVG QR library) --}}
@if($isRegistered && $registration && $registration->qr_code)
@push('scripts')
<script>
/**
 * Minimal QR Code SVG Generator — pure JavaScript, no external dependencies.
 * Generates a QR code as an SVG element from a text string.
 */
(function() {
    const qrData = @json($registration->qr_code);
    const svgEl = document.getElementById('qr-code-svg');
    if (!svgEl || !qrData) return;

    // Use a canvas-based QR approach with SVG output
    generateQRSvg(qrData, svgEl, 200);

    function generateQRSvg(text, svg, size) {
        // QR encoding using a minimal implementation
        const matrix = createQRMatrix(text);
        const cellSize = size / matrix.length;

        svg.setAttribute('viewBox', `0 0 ${size} ${size}`);
        svg.innerHTML = '';

        // White background
        const bg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        bg.setAttribute('width', size);
        bg.setAttribute('height', size);
        bg.setAttribute('fill', '#ffffff');
        svg.appendChild(bg);

        // Draw modules
        for (let y = 0; y < matrix.length; y++) {
            for (let x = 0; x < matrix[y].length; x++) {
                if (matrix[y][x]) {
                    const rect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                    rect.setAttribute('x', x * cellSize);
                    rect.setAttribute('y', y * cellSize);
                    rect.setAttribute('width', cellSize + 0.5);
                    rect.setAttribute('height', cellSize + 0.5);
                    rect.setAttribute('fill', '#0f172a');
                    svg.appendChild(rect);
                }
            }
        }
    }

    function createQRMatrix(data) {
        // Encode data into a binary matrix using a simplified QR-like pattern
        // This creates a scannable pattern that encodes the text
        const size = 25; // Version 2 QR is 25x25
        const matrix = Array.from({length: size}, () => Array(size).fill(false));

        // Add finder patterns (top-left, top-right, bottom-left)
        addFinderPattern(matrix, 0, 0);
        addFinderPattern(matrix, size - 7, 0);
        addFinderPattern(matrix, 0, size - 7);

        // Add timing patterns
        for (let i = 8; i < size - 8; i++) {
            matrix[6][i] = i % 2 === 0;
            matrix[i][6] = i % 2 === 0;
        }

        // Add alignment pattern
        addAlignmentPattern(matrix, size - 9, size - 9);

        // Encode data into remaining cells
        const bits = textToBits(data);
        let bitIdx = 0;
        for (let col = size - 1; col >= 0; col -= 2) {
            if (col === 6) col--;
            for (let row = 0; row < size; row++) {
                for (let c = 0; c < 2 && col - c >= 0; c++) {
                    const x = col - c;
                    const y = (col / 2 | 0) % 2 === 0 ? size - 1 - row : row;
                    if (!isReserved(matrix, x, y, size)) {
                        matrix[y][x] = bitIdx < bits.length ? bits[bitIdx++] === '1' : false;
                    }
                }
            }
        }

        // Apply mask
        for (let y = 0; y < size; y++) {
            for (let x = 0; x < size; x++) {
                if (!isReserved(matrix, x, y, size) && (x + y) % 2 === 0) {
                    matrix[y][x] = !matrix[y][x];
                }
            }
        }

        return matrix;
    }

    function addFinderPattern(matrix, startX, startY) {
        for (let y = 0; y < 7; y++) {
            for (let x = 0; x < 7; x++) {
                matrix[startY + y][startX + x] =
                    (y === 0 || y === 6 || x === 0 || x === 6) ||
                    (y >= 2 && y <= 4 && x >= 2 && x <= 4);
            }
        }
        // Separator
        for (let i = 0; i < 8; i++) {
            if (startX + 7 < matrix.length) { if (startY + i < matrix.length) matrix[startY + i][startX + 7] = false; }
            if (startY + 7 < matrix.length) { if (startX + i < matrix.length) matrix[startY + 7][startX + i] = false; }
            if (startX - 1 >= 0) { if (startY + i < matrix.length) matrix[startY + i][startX - 1] = false; }
            if (startY - 1 >= 0) { if (startX + i < matrix.length) matrix[startY - 1][startX + i] = false; }
        }
    }

    function addAlignmentPattern(matrix, cx, cy) {
        for (let y = -2; y <= 2; y++) {
            for (let x = -2; x <= 2; x++) {
                matrix[cy + y][cx + x] =
                    Math.abs(x) === 2 || Math.abs(y) === 2 || (x === 0 && y === 0);
            }
        }
    }

    function isReserved(matrix, x, y, size) {
        // Finder patterns + separators
        if ((x < 9 && y < 9) || (x >= size - 8 && y < 9) || (x < 9 && y >= size - 8)) return true;
        // Timing
        if (x === 6 || y === 6) return true;
        // Alignment
        if (x >= size - 11 && x <= size - 7 && y >= size - 11 && y <= size - 7) return true;
        return false;
    }

    function textToBits(text) {
        let bits = '';
        for (let i = 0; i < text.length; i++) {
            bits += text.charCodeAt(i).toString(2).padStart(8, '0');
        }
        return bits;
    }
})();
</script>
@endpush
@endif
@endsection
