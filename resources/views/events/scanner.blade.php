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

<div class="grid-split-layout">

    {{-- Scanner Panel --}}
    <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
        <div class="card-header"><h3 class="card-title">📷 QR Code Scanner</h3></div>
        <div class="card-body">

            {{-- Insecure Context Warning (shown when on http://<LAN-IP>) --}}
            <div id="insecure-context-warning" style="display: none; background: #fffbeb; border: 1px solid #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; color: #92400e;">
                <div style="display: flex; gap: 0.75rem; align-items: flex-start;">
                    <span style="font-size: 1.25rem; line-height: 1;">🔒</span>
                    <div style="font-size: 0.875rem;">
                        <strong>Camera Access Requires HTTPS or Localhost</strong>
                        <p style="margin: 0.35rem 0 0.5rem 0;">
                            Web browsers block camera access on non-secure origins (current URL: <code id="current-url-display" style="word-break: break-all;"></code>).
                        </p>
                        <p style="margin: 0 0 0.35rem 0;">To use the camera on a LAN device:</p>
                        <ul style="margin: 0 0 0.5rem 1.25rem; padding: 0; font-size: 0.8125rem; list-style-type: disc;">
                            <li>Access via HTTPS reverse proxy (e.g. <code>https://&lt;LAN-IP&gt;:8443</code>)</li>
                            <li>Or open on this computer at <a href="http://localhost:8000" id="localhost-link" style="color: #b45309; font-weight: 600; text-decoration: underline;">http://localhost:8000</a></li>
                        </ul>
                        <p style="margin: 0; font-size: 0.8125rem; font-weight: 600; color: #b45309;">
                            💡 Manual QR input and attendee check-in below remain fully available.
                        </p>
                    </div>
                </div>
            </div>

            {{-- Camera Scanner --}}
            <div id="scanner-area" style="background: #0f172a; border-radius: 12px; overflow: hidden; position: relative; margin-bottom: 1rem;">
                <video id="scanner-video" style="width: 100%; display: block; min-height: 280px; object-fit: cover;" autoplay playsinline muted></video>
                <div id="scanner-overlay" style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none;">
                    <div id="scanner-target-box" style="width: 200px; height: 200px; border: 3px solid rgba(14,165,233,.8); border-radius: 16px; animation: pulse 2s infinite; transition: border-color 0.2s ease;"></div>
                </div>
            </div>

            <div class="btn-group" style="width: 100%; margin-bottom: 1rem; display: flex; gap: 0.5rem;">
                <button type="button" id="btn-start-scan" class="btn btn-primary" style="flex: 1;" onclick="startCamera()">
                    📷 Start Camera
                </button>
                <button type="button" id="btn-switch-cam" class="btn btn-secondary" style="display: none;" onclick="switchCamera()">
                    🔄 Switch Camera
                </button>
                <button type="button" id="btn-stop-scan" class="btn btn-secondary" style="flex: 1; display: none;" onclick="stopCamera()">
                    ⏹ Stop Camera
                </button>
            </div>

            {{-- Manual QR Input --}}
            <div style="border-top: 1px solid var(--color-surface-200); padding-top: 1rem; margin-top: .5rem;">
                <label class="form-label" for="qr-manual-input">Or enter QR code manually:</label>
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
@vite(['resources/js/qr-scanner.js'])
<script>
const eventId = {{ $event->id }};
const csrfToken = '{{ csrf_token() }}';

let videoStream = null;
let animationFrameId = null;
let isScanningActive = false;
let isRequestInFlight = false;
let currentFacingMode = 'environment';
let lastScannedCode = null;
let lastScannedTimestamp = 0;
const DEBOUNCE_MS = 3000;
const SCAN_INTERVAL_MS = 180;
let lastFrameScanTime = 0;

let nativeBarcodeDetector = null;
let barcodeDetectorInitialized = false;

// Off-screen canvas for frame capture and downscaling
const scanCanvas = document.createElement('canvas');
const scanCtx = scanCanvas.getContext('2d', { willReadFrequently: true });

// Check security context upfront on page load
document.addEventListener('DOMContentLoaded', () => {
    checkContextSecurity();
});

function isSecureEnvironment() {
    return window.isSecureContext === true ||
           location.hostname === 'localhost' ||
           location.hostname === '127.0.0.1';
}

function checkContextSecurity() {
    const isSecure = isSecureEnvironment();
    const hasMedia = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    if (!isSecure || !hasMedia) {
        const warnEl = document.getElementById('insecure-context-warning');
        const urlEl = document.getElementById('current-url-display');
        const localLink = document.getElementById('localhost-link');

        if (urlEl) urlEl.textContent = window.location.href;
        if (localLink) {
            const port = window.location.port ? ':' + window.location.port : '';
            localLink.href = 'http://localhost' + port + window.location.pathname;
            localLink.textContent = 'http://localhost' + port + window.location.pathname;
        }
        if (warnEl) warnEl.style.display = 'block';
    }
}

// ─── Camera Scanner ──────────────────────────────────────────────────

async function initBarcodeDetector() {
    if (barcodeDetectorInitialized) return;
    barcodeDetectorInitialized = true;

    if ('BarcodeDetector' in window) {
        try {
            const formats = await BarcodeDetector.getSupportedFormats();
            if (formats && formats.includes('qr_code')) {
                nativeBarcodeDetector = new BarcodeDetector({ formats: ['qr_code'] });
            }
        } catch (e) {
            nativeBarcodeDetector = null;
        }
    }
}

async function startCamera() {
    const isSecure = isSecureEnvironment();
    const hasMedia = !!(navigator.mediaDevices && navigator.mediaDevices.getUserMedia);

    if (!isSecure || !hasMedia) {
        checkContextSecurity();
        showResult('error', 'Camera blocked: Secure context (HTTPS or localhost) is required. Current URL: ' + window.location.origin + '. Please access via HTTPS or use manual entry below.');
        return;
    }

    const video = document.getElementById('scanner-video');
    const btnStart = document.getElementById('btn-start-scan');
    const btnStop = document.getElementById('btn-stop-scan');

    // Stop any existing stream
    if (videoStream) {
        videoStream.getTracks().forEach(t => t.stop());
        videoStream = null;
    }

    try {
        await initBarcodeDetector();

        // Primary camera constraint attempt
        const constraints = {
            video: {
                facingMode: { ideal: currentFacingMode },
                width: { ideal: 1280 },
                height: { ideal: 720 }
            },
            audio: false
        };

        try {
            videoStream = await navigator.mediaDevices.getUserMedia(constraints);
        } catch (primaryErr) {
            if (primaryErr.name === 'OverconstrainedError' || primaryErr.name === 'ConstraintNotSatisfiedError') {
                // Fallback to basic video constraint
                videoStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
            } else {
                throw primaryErr;
            }
        }

        video.srcObject = videoStream;
        video.setAttribute('playsinline', 'true');
        video.setAttribute('muted', 'true');
        video.muted = true;
        await video.play();

        btnStart.style.display = 'none';
        btnStop.style.display = 'block';

        // Check if multiple cameras are available to show switch button
        checkCameraCount();

        // Start scanning loop
        isScanningActive = true;
        lastFrameScanTime = 0;
        requestScanFrame();

        showResult('info', 'Camera active. Point at an attendee QR code or use manual entry.');
    } catch (err) {
        handleCameraError(err);
    }
}

function handleCameraError(err) {
    let msg = 'Unable to access camera.';

    switch (err.name) {
        case 'NotAllowedError':
        case 'PermissionDeniedError':
            msg = 'Camera permission was denied. Please allow camera access in your browser\'s site settings (lock or settings icon in address bar) and refresh.';
            break;
        case 'NotFoundError':
        case 'DevicesNotFoundError':
            msg = 'No camera device found on this device.';
            break;
        case 'NotReadableError':
        case 'TrackStartError':
            msg = 'Camera is currently in use by another application or blocked by the operating system.';
            break;
        case 'OverconstrainedError':
        case 'ConstraintNotSatisfiedError':
            msg = 'Requested camera resolution or mode is not supported by this hardware.';
            break;
        case 'SecurityError':
            msg = 'Security error: Camera access requires a secure origin (HTTPS or localhost). Current origin: ' + window.location.origin;
            break;
        default:
            msg = err.message ? 'Camera error: ' + err.message : 'Unable to access camera. Please use manual entry.';
    }

    showResult('error', msg);
    stopCamera();
}

async function checkCameraCount() {
    const switchBtn = document.getElementById('btn-switch-cam');
    if (!switchBtn || !navigator.mediaDevices?.enumerateDevices) return;

    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videoInputs = devices.filter(d => d.kind === 'videoinput');
        if (videoInputs.length > 1) {
            switchBtn.style.display = 'block';
        } else {
            switchBtn.style.display = 'none';
        }
    } catch (e) {
        switchBtn.style.display = 'none';
    }
}

function switchCamera() {
    currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
    startCamera();
}

function stopCamera() {
    isScanningActive = false;

    if (animationFrameId) {
        cancelAnimationFrame(animationFrameId);
        animationFrameId = null;
    }

    if (videoStream) {
        videoStream.getTracks().forEach(t => t.stop());
        videoStream = null;
    }

    const video = document.getElementById('scanner-video');
    if (video) video.srcObject = null;

    const btnStart = document.getElementById('btn-start-scan');
    const btnStop = document.getElementById('btn-stop-scan');
    const switchBtn = document.getElementById('btn-switch-cam');

    if (btnStart) btnStart.style.display = 'block';
    if (btnStop) btnStop.style.display = 'none';
    if (switchBtn) switchBtn.style.display = 'none';
}

// ─── Frame Processing & Decoding Loop ────────────────────────────────

function requestScanFrame() {
    if (!isScanningActive) return;
    animationFrameId = requestAnimationFrame(scanTick);
}

async function scanTick(now) {
    if (!isScanningActive) return;

    const video = document.getElementById('scanner-video');

    if (video && video.readyState >= video.HAVE_CURRENT_DATA && video.videoWidth > 0) {
        if (now - lastFrameScanTime >= SCAN_INTERVAL_MS && !isRequestInFlight) {
            lastFrameScanTime = now;
            await processVideoFrame(video);
        }
    }

    if (isScanningActive) {
        requestScanFrame();
    }
}

async function processVideoFrame(video) {
    // Downscale large frames to max 640px for fast decoding and low CPU usage
    const maxDim = 640;
    let w = video.videoWidth;
    let h = video.videoHeight;

    if (w > maxDim || h > maxDim) {
        if (w > h) {
            h = Math.round((h * maxDim) / w);
            w = maxDim;
        } else {
            w = Math.round((w * maxDim) / h);
            h = maxDim;
        }
    }

    scanCanvas.width = w;
    scanCanvas.height = h;
    scanCtx.drawImage(video, 0, 0, w, h);

    let decodedValue = null;

    // 1. Try modern native BarcodeDetector
    if (nativeBarcodeDetector) {
        try {
            const barcodes = await nativeBarcodeDetector.detect(scanCanvas);
            if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                decodedValue = barcodes[0].rawValue;
            }
        } catch (e) {
            // Fall through to jsQR
        }
    }

    // 2. Fall back to jsQR
    if (!decodedValue && window.jsQR) {
        try {
            const imageData = scanCtx.getImageData(0, 0, w, h);
            const result = window.jsQR(imageData.data, imageData.width, imageData.height, {
                inversionAttempts: 'attemptBoth'
            });
            if (result && result.data) {
                decodedValue = result.data;
            }
        } catch (e) {
            // Frame processing error ignored
        }
    }

    if (decodedValue) {
        handleScannedCode(decodedValue);
    }
}

function handleScannedCode(rawCode) {
    const code = (rawCode || '').trim();
    if (!code || isRequestInFlight) return;

    const now = Date.now();

    // Debounce: ignore same code scanned within 3 seconds
    if (code === lastScannedCode && (now - lastScannedTimestamp < DEBOUNCE_MS)) {
        return;
    }

    lastScannedCode = code;
    lastScannedTimestamp = now;

    // Visual scan feedback
    flashScanBox();

    // Submit for check-in
    processQrCode(code);
}

function flashScanBox() {
    const box = document.getElementById('scanner-target-box');
    if (box) {
        box.style.borderColor = '#10b981';
        setTimeout(() => {
            box.style.borderColor = 'rgba(14,165,233,.8)';
        }, 300);
    }
}

// ─── QR Submission ───────────────────────────────────────────────────

function submitManualQR() {
    const input = document.getElementById('qr-manual-input');
    const code = input.value.trim();
    if (!code) {
        showResult('error', 'Please enter a QR code.');
        return;
    }
    processQrCode(code);
    input.value = '';
}

async function processQrCode(qrCode) {
    if (isRequestInFlight) return;
    isRequestInFlight = true;

    showResult('info', 'Processing check-in...');

    try {
        const res = await fetch(`/events/${eventId}/scan-qr`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ qr_code: qrCode })
        });

        const data = await res.json();
        showResult(data.status, data.message);
        addLogEntry(data.user?.name || 'Unknown', data.user?.employee_id || '—', 'QR Scan', data.status);

        if (data.status === 'success') {
            updateStats(1);
            playSound('success');
            markAttendeeCheckedIn(data.user?.employee_id, data.user?.name);
        } else if (data.status === 'warning') {
            playSound('warning');
        } else {
            playSound('error');
        }
    } catch (err) {
        showResult('error', 'Network error. Please try again.');
        playSound('error');
    } finally {
        isRequestInFlight = false;
    }
}

function markAttendeeCheckedIn(eid, name) {
    if (!eid && !name) return;
    document.querySelectorAll('.attendee-row').forEach(row => {
        const rowEid = row.dataset.eid || '';
        const rowName = row.dataset.name || '';
        if ((eid && rowEid && rowEid === eid.toLowerCase()) || (name && rowName === name.toLowerCase())) {
            row.style.opacity = '0.6';
            const btn = row.querySelector('button');
            if (btn) {
                btn.outerHTML = '<span class="badge badge-success">Attended</span>';
            }
        }
    });
}

// ─── Manual Check-In (Fallback) ──────────────────────────────────────

async function manualCheckin(userId, btn) {
    btn.disabled = true;
    btn.textContent = '...';
    try {
        const res = await fetch(`/events/${eventId}/manual-checkin`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ user_id: userId })
        });
        const data = await res.json();

        if (data.status === 'success') {
            btn.outerHTML = '<span class="badge badge-success">Attended</span>';
            const row = document.querySelector(`[data-user-id="${userId}"]`);
            if (row) row.style.opacity = '0.6';
            const name = row?.querySelector('[style*="font-weight: 600"]')?.textContent || 'User';
            const eid = row?.dataset.eid || '—';
            addLogEntry(name, eid, 'Manual', 'success');
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

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    const div = document.createElement('div');
    div.textContent = String(str);
    return div.innerHTML;
}

function showResult(status, message) {
    const el = document.getElementById('scan-result');
    const inner = document.getElementById('scan-result-inner');
    if (!el || !inner) return;

    el.style.display = 'block';

    const colors = {
        success: '#d1fae5;color:#065f46',
        warning: '#fef3c7;color:#92400e',
        error: '#fee2e2;color:#991b1b',
        info: '#dbeafe;color:#1e40af'
    };

    const chosen = colors[status] || colors.info;
    inner.style.background = chosen.split(';')[0].replace('background:', '');
    inner.style.color = chosen.split('color:')[1];

    const icon = status === 'success' ? '✅' : status === 'warning' ? '⚠️' : status === 'error' ? '❌' : 'ℹ️';
    inner.innerHTML = `<strong>${icon}</strong> ${escapeHtml(message)}`;

    if (status !== 'info') {
        setTimeout(() => {
            el.style.display = 'none';
        }, 5000);
    }
}

function addLogEntry(name, eid, method, status) {
    const emptyRow = document.getElementById('scan-log-empty');
    if (emptyRow) emptyRow.remove();

    const tbody = document.getElementById('scan-log-body');
    if (!tbody) return;

    const statusBadge = status === 'success' ? 'badge-success' : status === 'warning' ? 'badge-warning' : 'badge-error';
    const statusText = status === 'success' ? 'Checked In' : status === 'warning' ? 'Duplicate' : 'Failed';
    const now = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

    const safeName = escapeHtml(name);
    const safeEid = escapeHtml(eid);
    const safeMethod = escapeHtml(method);

    const row = document.createElement('tr');
    row.innerHTML = `<td>${now}</td><td style="font-weight:600">${safeName}</td><td><code>${safeEid}</code></td><td><span class="badge badge-general">${safeMethod}</span></td><td><span class="badge ${statusBadge}">${statusText}</span></td>`;
    tbody.insertBefore(row, tbody.firstChild);
}

function updateStats(delta) {
    const checked = document.getElementById('stat-checked');
    const remaining = document.getElementById('stat-remaining');
    const rate = document.getElementById('stat-rate');
    const totalEl = document.getElementById('stat-total');

    if (!checked || !remaining || !rate || !totalEl) return;

    const total = parseInt(totalEl.textContent) || 0;
    const newChecked = (parseInt(checked.textContent) || 0) + delta;

    checked.textContent = newChecked;
    remaining.textContent = Math.max(0, total - newChecked);
    rate.textContent = total > 0 ? Math.round((newChecked / total) * 100) + '%' : '0%';
}

function playSound(type) {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain);
        gain.connect(ctx.destination);
        gain.gain.value = 0.1;
        osc.frequency.value = type === 'success' ? 800 : type === 'warning' ? 400 : 200;
        osc.start();
        osc.stop(ctx.currentTime + 0.15);
    } catch(e) {}
}

// Lifecycle listeners: Stop camera when page leaves or tab is hidden
window.addEventListener('beforeunload', stopCamera);
window.addEventListener('pagehide', stopCamera);
document.addEventListener('visibilitychange', () => {
    if (document.hidden && videoStream) {
        stopCamera();
    }
});

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
