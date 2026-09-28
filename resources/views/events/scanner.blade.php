@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">📷 Attendance Scanner</h1>
        <p class="page-subtitle">{{ $event->title }} — {{ $event->event_date->format('F d, Y') }}</p>
    </div>
    <a href="{{ url('/events/' . $event->id . '/manage') }}" class="btn btn-secondary">&larr; Back to Manage</a>
</div>

{{-- Summary Stats --}}
<div class="grid grid-4 animate-fade-in-up" style="animation-delay: 50ms;">
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-value" id="stat-total">{{ $summary['total'] }}</span>
            <span class="stat-label">Total Registered</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-value" id="stat-checked" style="color: #10b981;">{{ $summary['checked_in'] }}</span>
            <span class="stat-label">Checked In</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-value" id="stat-remaining" style="color: #f59e0b;">{{ $summary['total'] - $summary['checked_in'] }}</span>
            <span class="stat-label">Not Yet Arrived</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-content">
            <span class="stat-value" id="stat-rate">{{ $summary['total'] > 0 ? round(($summary['checked_in'] / $summary['total']) * 100) : 0 }}%</span>
            <span class="stat-label">Attendance Rate</span>
        </div>
    </div>
</div>

<div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">

    {{-- Scanner Panel --}}
    <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
        <div class="card-header"><h3 class="card-title">📷 QR Code Scanner</h3></div>
        <div class="card-body">
            {{-- Camera Scanner --}}
            <div id="scanner-area" style="background: #0f172a; border-radius: 12px; overflow: hidden; position: relative; margin-bottom: 1rem;">
                <video id="scanner-video" style="width: 100%; display: block; min-height: 280px;" autoplay playsinline></video>
                <div id="scanner-overlay" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                    <div style="width: 200px; height: 200px; border: 3px solid rgba(14,165,233,.6); border-radius: 16px; animation: pulse 2s infinite;"></div>
                </div>
            </div>

            <div class="btn-group" style="width: 100%; margin-bottom: 1rem;">
                <button type="button" id="btn-start-scan" class="btn btn-primary" style="flex: 1;" onclick="startCamera()">
                    📷 Start Camera
                </button>
                <button type="button" id="btn-stop-scan" class="btn btn-secondary" style="flex: 1; display: none;" onclick="stopCamera()">
                    ⏹ Stop Camera
                </button>
            </div>

            {{-- Manual QR Input --}}
            <div style="border-top: 1px solid var(--color-surface-200); padding-top: 1rem; margin-top: .5rem;">
                <label class="form-label">Or enter QR code manually:</label>
                <div style="display: flex; gap: .5rem;">
                    <input type="text" id="qr-manual-input" class="form-control" placeholder="e.g., EVT-1-USR-5-ABCDEF123456" style="flex: 1;">
                    <button type="button" class="btn btn-primary" onclick="submitManualQR()">Scan</button>
                </div>
            </div>

            {{-- Scan Result --}}
            <div id="scan-result" style="margin-top: 1rem; display: none;">
                <div id="scan-result-inner" style="padding: 1rem; border-radius: 8px; text-align: center;"></div>
            </div>
        </div>
    </div>

    {{-- Manual Check-In Panel --}}
    <div class="card animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="card-header">
            <h3 class="card-title">✋ Manual Check-In</h3>
            <span class="badge badge-warning">Fallback</span>
        </div>
        <div class="card-body">
            <div style="margin-bottom: 1rem;">
                <input type="text" id="manual-search" class="form-control" placeholder="Search by name or ID..." oninput="filterAttendees()">
            </div>

            <div id="attendee-list" style="max-height: 500px; overflow-y: auto;">
                @foreach($event->registrations as $reg)
                <div class="attendee-row" data-user-id="{{ $reg->user_id }}" data-name="{{ strtolower($reg->user->full_name) }}" data-eid="{{ strtolower($reg->user->employee_id ?? '') }}"
                     style="display: flex; align-items: center; justify-content: space-between; padding: .75rem; border-bottom: 1px solid var(--color-surface-100); {{ $reg->has_attended ? 'opacity: .6;' : '' }}">
                    <div style="display: flex; align-items: center; gap: .75rem;">
                        <div class="user-avatar small">{{ substr($reg->user->first_name, 0, 1) }}{{ substr($reg->user->last_name, 0, 1) }}</div>
                        <div>
                            <div style="font-weight: 600; font-size: .875rem;">{{ $reg->user->full_name }}</div>
                            <div style="font-size: .75rem; color: var(--color-surface-500);">
                                {{ $reg->user->employee_id ?? '—' }}
                                @if($reg->has_attended)
                                    &bull; <span style="color: #10b981;">✓ {{ $reg->checked_in_at ? $reg->checked_in_at->format('h:i A') : 'Checked in' }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        @if($reg->has_attended)
                            <span class="badge badge-success">Attended</span>
                        @else
                            <button type="button" class="btn btn-success btn-sm" onclick="manualCheckin({{ $reg->user_id }}, this)" id="checkin-btn-{{ $reg->user_id }}">
                                Check In
                            </button>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- Scan Log --}}
<div class="card animate-fade-in-up" style="animation-delay: 300ms; margin-top: 1.5rem;">
    <div class="card-header"><h3 class="card-title">📋 Scan Log</h3></div>
    <div class="card-body" style="padding: 0;">
        <table class="data-table">
            <thead><tr><th>Time</th><th>Name</th><th>ID</th><th>Method</th><th>Status</th></tr></thead>
            <tbody id="scan-log-body">
                <tr id="scan-log-empty"><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No scans yet. Start the camera or use manual check-in.</td></tr>
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
const eventId = {{ $event->id }};
const csrfToken = '{{ csrf_token() }}';
let videoStream = null;
let scanInterval = null;

// ─── Camera Scanner ──────────────────────────────────────────────────

async function startCamera() {
    const video = document.getElementById('scanner-video');
    const btnStart = document.getElementById('btn-start-scan');
    const btnStop = document.getElementById('btn-stop-scan');

    try {
        videoStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment', width: { ideal: 640 }, height: { ideal: 480 } }
        });
        video.srcObject = videoStream;
        btnStart.style.display = 'none';
        btnStop.style.display = 'block';

        // Start periodic frame scanning for QR codes
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');

        scanInterval = setInterval(() => {
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0);
                // Note: Real QR decoding requires a library like jsQR.
                // For this implementation, we use the manual input as primary
                // and the camera as a visual aid. In production, add:
                // const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                // const code = jsQR(imageData.data, imageData.width, imageData.height);
            }
        }, 500);

        showResult('info', 'Camera active. Scan a QR code or enter it manually below.');
    } catch (err) {
        showResult('error', 'Camera access denied. Use the manual input below instead.');
    }
}

function stopCamera() {
    if (videoStream) {
        videoStream.getTracks().forEach(t => t.stop());
        videoStream = null;
    }
    if (scanInterval) {
        clearInterval(scanInterval);
        scanInterval = null;
    }
    document.getElementById('scanner-video').srcObject = null;
    document.getElementById('btn-start-scan').style.display = 'block';
    document.getElementById('btn-stop-scan').style.display = 'none';
}

// ─── QR Submission ───────────────────────────────────────────────────

function submitManualQR() {
    const input = document.getElementById('qr-manual-input');
    const code = input.value.trim();
    if (!code) { showResult('error', 'Please enter a QR code.'); return; }
    processQrCode(code);
    input.value = '';
}

async function processQrCode(qrCode) {
    showResult('info', 'Processing...');
    try {
        const res = await fetch(`/events/${eventId}/scan-qr`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ qr_code: qrCode })
        });
        const data = await res.json();
        showResult(data.status, data.message);
        addLogEntry(data.user?.name || 'Unknown', data.user?.employee_id || '—', 'QR Scan', data.status);

        if (data.status === 'success') { updateStats(1); playSound('success'); }
        else if (data.status === 'warning') { playSound('warning'); }
        else { playSound('error'); }
    } catch (err) {
        showResult('error', 'Network error. Please try again.');
    }
}

// ─── Manual Check-In (Fallback) ──────────────────────────────────────

async function manualCheckin(userId, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const res = await fetch(`/events/${eventId}/manual-checkin`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();

        if (data.status === 'success') {
            btn.outerHTML = '<span class="badge badge-success">Attended</span>';
            const row = document.querySelector(`[data-user-id="${userId}"]`);
            if (row) row.style.opacity = '0.6';
            const name = row?.querySelector('[style*="font-weight: 600"]')?.textContent || 'User';
            addLogEntry(name, '—', 'Manual', 'success');
            updateStats(1);
            playSound('success');
        } else {
            showResult(data.status, data.message);
            btn.disabled = false;
            btn.textContent = 'Check In';
        }
    } catch (err) {
        btn.disabled = false;
        btn.textContent = 'Check In';
        showResult('error', 'Network error.');
    }
}

// ─── Search Filter ───────────────────────────────────────────────────

function filterAttendees() {
    const query = document.getElementById('manual-search').value.toLowerCase();
    document.querySelectorAll('.attendee-row').forEach(row => {
        const name = row.dataset.name || '';
        const eid = row.dataset.eid || '';
        row.style.display = (name.includes(query) || eid.includes(query)) ? 'flex' : 'none';
    });
}

// ─── UI Helpers ──────────────────────────────────────────────────────

function showResult(status, message) {
    const el = document.getElementById('scan-result');
    const inner = document.getElementById('scan-result-inner');
    el.style.display = 'block';

    const colors = { success: '#d1fae5;color:#065f46', warning: '#fef3c7;color:#92400e', error: '#fee2e2;color:#991b1b', info: '#dbeafe;color:#1e40af' };
    inner.style.background = (colors[status] || colors.info).split(';')[0].replace('background:', '');
    inner.style.color = (colors[status] || colors.info).split('color:')[1];
    inner.innerHTML = `<strong>${status === 'success' ? '✅' : status === 'warning' ? '⚠️' : status === 'error' ? '❌' : 'ℹ️'}</strong> ${message}`;

    if (status !== 'info') {
        setTimeout(() => { el.style.display = 'none'; }, 5000);
    }
}

function addLogEntry(name, eid, method, status) {
    const emptyRow = document.getElementById('scan-log-empty');
    if (emptyRow) emptyRow.remove();

    const tbody = document.getElementById('scan-log-body');
    const statusBadge = status === 'success' ? 'badge-success' : status === 'warning' ? 'badge-warning' : 'badge-error';
    const now = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    const row = document.createElement('tr');
    row.innerHTML = `<td>${now}</td><td style="font-weight:600">${name}</td><td><code>${eid}</code></td><td><span class="badge badge-general">${method}</span></td><td><span class="badge ${statusBadge}">${status === 'success' ? 'Checked In' : status === 'warning' ? 'Duplicate' : 'Failed'}</span></td>`;
    tbody.insertBefore(row, tbody.firstChild);
}

function updateStats(delta) {
    const checked = document.getElementById('stat-checked');
    const remaining = document.getElementById('stat-remaining');
    const rate = document.getElementById('stat-rate');
    const total = parseInt(document.getElementById('stat-total').textContent);

    const newChecked = parseInt(checked.textContent) + delta;
    checked.textContent = newChecked;
    remaining.textContent = total - newChecked;
    rate.textContent = total > 0 ? Math.round((newChecked / total) * 100) + '%' : '0%';
}

function playSound(type) {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        gain.gain.value = 0.1;
        osc.frequency.value = type === 'success' ? 800 : type === 'warning' ? 400 : 200;
        osc.start(); osc.stop(ctx.currentTime + 0.15);
    } catch(e) {}
}

// Handle Enter key on QR input
document.getElementById('qr-manual-input')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') submitManualQR();
});
</script>
@endpush

<style>
@keyframes pulse { 0%, 100% { opacity: .4; } 50% { opacity: 1; } }
</style>
@endsection
