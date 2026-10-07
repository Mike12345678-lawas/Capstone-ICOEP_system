{{-- ═══ REJECT MODAL ═══ --}}
<div id="reject-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:var(--color-surface,#fff);border-radius:12px;padding:1.5rem;width:min(420px,92vw);box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h3 style="margin:0 0 1rem;font-size:1.05rem;">Reject Reservation</h3>
        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Reason</label>
        <select id="reject-reason" style="width:100%;padding:.5rem .75rem;border:1px solid var(--color-border,#e2e8f0);border-radius:8px;margin-bottom:.75rem;font-size:.9rem;">
            <option value="Facility/equipment unavailable">Facility unavailable</option>
            <option value="Insufficient stock">Insufficient stock</option>
            <option value="Incomplete details">Incomplete details</option>
            <option value="Conflicts with another booking">Conflicts with another booking</option>
            <option value="Other">Other</option>
        </select>
        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Additional note (optional)</label>
        <textarea id="reject-note" rows="2" style="width:100%;padding:.5rem .75rem;border:1px solid var(--color-border,#e2e8f0);border-radius:8px;font-size:.9rem;resize:vertical;" placeholder="Add a note…"></textarea>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button id="reject-cancel-btn" class="btn btn-secondary btn-sm">Cancel</button>
            <button id="reject-confirm-btn" class="btn btn-danger btn-sm">Confirm Reject</button>
        </div>
    </div>
</div>

{{-- ═══ TOAST ═══ --}}
<div id="booking-toast" style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;z-index:10000;padding:.75rem 1.25rem;border-radius:8px;font-size:.9rem;font-weight:600;color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.18);"></div>

<div class="page-header animate-fade-in-up" style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem;">
    <div>
        <h1 class="page-title">Booking Requests</h1>
        <p class="page-subtitle">Review and approve or reject facility booking requests.</p>
    </div>
    @if(auth()->user()->hasPermission('facility.approve'))
    <div style="display: flex; gap: .5rem; align-items: center;">
        <button id="run-auto-resolve-btn" class="btn btn-primary btn-sm" onclick="runFacilityAutomation(this)" style="display: inline-flex; align-items: center; gap: .4rem;" title="Automatically evaluate all pending facility reservations using deterministic priority and conflict rules">
            <span>⚡</span>
            <span>Run Facility Automation</span>
        </button>
    </div>
    @endif
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="manage-bookings">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by name..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=manage-bookings') }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</a>
                <a href="{{ url('/dashboard?section=manage-bookings&status=pending') }}" class="filter-btn {{ ($statusFilter ?? '') === 'pending' ? 'active' : '' }}">Pending</a>
                <a href="{{ url('/dashboard?section=manage-bookings&status=approved') }}" class="filter-btn {{ ($statusFilter ?? '') === 'approved' ? 'active' : '' }}">Approved</a>
                <a href="{{ url('/dashboard?section=manage-bookings&status=rejected') }}" class="filter-btn {{ ($statusFilter ?? '') === 'rejected' ? 'active' : '' }}">Rejected</a>
            </div>
        </form>

        {{-- Bulk approve bar (Admin only) --}}
        @if(auth()->user()->hasPermission('facility.approve'))
        <div id="bulk-bar-bookings" style="display:none;align-items:center;gap:.75rem;margin-bottom:.75rem;padding:.5rem .75rem;background:var(--color-surface-alt,#f8fafc);border-radius:8px;">
            <span id="bulk-count-bookings" style="font-size:.9rem;font-weight:600;"></span>
            <button id="bulk-approve-bookings-btn" class="btn btn-success btn-sm">Approve Selected</button>
        </div>
        @endif

        <div class="table-responsive manage-table-responsive">
            <table class="data-table responsive-card-table">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="select-all-bookings" title="Select all pending"></th>
                        <th>Requestor</th><th>Facility</th><th>Date &amp; Time</th><th>Purpose</th>
                        <th>Conflict</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reservations as $res)
                    <tr id="booking-row-{{ $res->id }}">
                        <td data-label="Select">
                            @if(auth()->user()->hasPermission('facility.approve') && $res->status === 'pending')
                                <input type="checkbox" class="booking-check" value="{{ $res->id }}">
                            @endif
                        </td>
                        <td data-label="Requestor" style="font-weight: 600;">{{ $res->user->full_name }}</td>
                        <td data-label="Facility">{{ $res->facility->name }}</td>
                        <td data-label="Date &amp; Time">{{ $res->reservation_date->format('M d, Y') }}<br><small class="text-muted">{{ \Carbon\Carbon::parse($res->time_start)->format('h:i A') }} – {{ \Carbon\Carbon::parse($res->time_end)->format('h:i A') }}</small></td>
                        <td data-label="Purpose" title="{{ $res->title }}">{{ Str::limit($res->title, 40) }}</td>
                        <td data-label="Conflict">
                            @if($res->status === 'pending' && isset($conflictMap[$res->id]))
                                @php $cf = $conflictMap[$res->id]; @endphp
                                <span class="badge {{ $cf === 'No conflict' ? 'badge-success' : 'badge-error' }}" style="font-size:.75rem;">{{ $cf }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-label="Status">
                            <x-status-badge :status="$res->status" id="booking-badge-{{ $res->id }}" />
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            @if(auth()->user()->hasPermission('facility.approve') && $res->status === 'pending')
                                <div class="btn-group" id="booking-actions-{{ $res->id }}">
                                    <button class="btn btn-success btn-sm btn-action-large"
                                        onclick="approveBooking({{ $res->id }}, this)">Approve</button>
                                    <button class="btn btn-danger btn-sm btn-action-large"
                                        onclick="openRejectModal({{ $res->id }}, this)">Reject</button>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted empty-state-cell" style="padding: 2.5rem 1rem;">
                            <div class="empty-state-content">
                                <p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 500;">No reservations found.</p>
                                @if(request('status') || request('search'))
                                    <a href="{{ url('/dashboard?section=manage-bookings') }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                                @else
                                    <a href="{{ url('/facilities') }}" class="btn btn-secondary btn-sm">Browse Facilities</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $reservations->withQueryString()->links('partials.pagination') }}
    </div>
</div>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

// ─── Toast ───
function showToast(msg, ok) {
    const t = document.getElementById('booking-toast');
    t.textContent = msg;
    t.style.background = ok ? '#10b981' : '#ef4444';
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

// ─── Approve (fetch) ───
function approveBooking(id, btn) {
    setRowBusy(id, true);
    fetch(`/facility-reservations/${id}/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
                   'X-HTTP-Method-Override': 'PATCH' },
        body: JSON.stringify({ status: 'approved' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { updateRow(id, 'approved'); showToast('Reservation approved ✓', true); }
        else { showToast(data.error || 'Error', false); setRowBusy(id, false); }
    })
    .catch(() => { showToast('Network error', false); setRowBusy(id, false); });
}

// ─── Reject modal state ───
let _rejectId = null, _rejectBtn = null;
function openRejectModal(id, btn) {
    _rejectId = id; _rejectBtn = btn;
    document.getElementById('reject-note').value = '';
    document.getElementById('reject-reason').selectedIndex = 0;
    const m = document.getElementById('reject-modal');
    m.style.display = 'flex';
}
document.getElementById('reject-cancel-btn').onclick = () => {
    document.getElementById('reject-modal').style.display = 'none';
};
document.getElementById('reject-confirm-btn').onclick = () => {
    const reason = document.getElementById('reject-reason').value;
    const note   = document.getElementById('reject-note').value.trim();
    const remarks = note ? `${reason}. ${note}` : reason;
    document.getElementById('reject-modal').style.display = 'none';
    setRowBusy(_rejectId, true);
    fetch(`/facility-reservations/${_rejectId}/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json',
                   'X-HTTP-Method-Override': 'PATCH' },
        body: JSON.stringify({ status: 'rejected', remarks })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { updateRow(_rejectId, 'rejected'); showToast('Reservation rejected.', true); }
        else { showToast(data.error || 'Error', false); setRowBusy(_rejectId, false); }
    })
    .catch(() => { showToast('Network error', false); setRowBusy(_rejectId, false); });
};

// ─── In-place row update ───
function updateRow(id, status) {
    const badge = document.getElementById(`booking-badge-${id}`);
    const actions = document.getElementById(`booking-actions-${id}`);
    const row = document.getElementById(`booking-row-${id}`);
    if (badge) {
        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        badge.className = 'badge ' + (status === 'approved' ? 'badge-success' : 'badge-error');
    }
    if (actions) actions.innerHTML = '<span class="text-muted">—</span>';
    // Remove checkbox
    const cb = row?.querySelector('.booking-check');
    if (cb) cb.closest('td').innerHTML = '';
    updateBulkBar();
}
function setRowBusy(id, busy) {
    const btns = document.querySelectorAll(`#booking-actions-${id} button`);
    btns.forEach(b => b.disabled = busy);
}

// ─── Bulk select ───
function updateBulkBar() {
    const checked = document.querySelectorAll('.booking-check:checked');
    const bar = document.getElementById('bulk-bar-bookings');
    const cnt = document.getElementById('bulk-count-bookings');
    bar.style.display = checked.length ? 'flex' : 'none';
    cnt.textContent = `${checked.length} selected`;
}
document.getElementById('select-all-bookings').addEventListener('change', function() {
    document.querySelectorAll('.booking-check').forEach(c => c.checked = this.checked);
    updateBulkBar();
});
document.querySelectorAll('.booking-check').forEach(c => c.addEventListener('change', updateBulkBar));

// ─── Bulk approve ───
document.getElementById('bulk-approve-bookings-btn').addEventListener('click', function() {
    const ids = [...document.querySelectorAll('.booking-check:checked')].map(c => parseInt(c.value));
    if (!ids.length) return;
    this.disabled = true;
    fetch('/facility-reservations/bulk-approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ ids })
    })
    .then(r => r.json())
    .then(data => {
        ids.forEach(id => updateRow(id, 'approved'));
        showToast(data.message, !data.failures?.length);
        document.getElementById('select-all-bookings').checked = false;
        updateBulkBar();
        this.disabled = false;
    })
    .catch(() => { showToast('Network error', false); this.disabled = false; });
});

// ─── Run Facility Automation ───
function runFacilityAutomation(btn) {
    if (!confirm('Run deterministic facility booking automation on all pending requests?')) {
        return;
    }
    const origHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span>⏳ Processing...</span>';

    fetch('/facility-reservations/auto-resolve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        showToast(data.message, data.success);
        setTimeout(() => window.location.reload(), 1200);
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = origHtml;
        showToast('Network error while running automation', false);
    });
}
</script>
