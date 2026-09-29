{{-- ═══ REJECT MODAL (equipment) ═══ --}}
<div id="eq-reject-modal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center;">
    <div style="background:var(--color-surface,#fff);border-radius:12px;padding:1.5rem;width:min(420px,92vw);box-shadow:0 8px 32px rgba(0,0,0,.18);">
        <h3 style="margin:0 0 1rem;font-size:1.05rem;">Reject Equipment Request</h3>
        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Reason</label>
        <select id="eq-reject-reason" style="width:100%;padding:.5rem .75rem;border:1px solid var(--color-border,#e2e8f0);border-radius:8px;margin-bottom:.75rem;font-size:.9rem;">
            <option value="Facility/equipment unavailable">Equipment unavailable</option>
            <option value="Insufficient stock">Insufficient stock</option>
            <option value="Incomplete details">Incomplete details</option>
            <option value="Conflicts with another booking">Conflicts with another booking</option>
            <option value="Other">Other</option>
        </select>
        <label style="font-size:.85rem;font-weight:600;display:block;margin-bottom:.35rem;">Additional note (optional)</label>
        <textarea id="eq-reject-note" rows="2" style="width:100%;padding:.5rem .75rem;border:1px solid var(--color-border,#e2e8f0);border-radius:8px;font-size:.9rem;resize:vertical;" placeholder="Add a note…"></textarea>
        <div style="display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem;">
            <button id="eq-reject-cancel-btn" class="btn btn-secondary btn-sm">Cancel</button>
            <button id="eq-reject-confirm-btn" class="btn btn-danger btn-sm">Confirm Reject</button>
        </div>
    </div>
</div>

{{-- ═══ TOAST ═══ --}}
<div id="eq-toast" style="display:none;position:fixed;bottom:1.5rem;right:1.5rem;z-index:10000;padding:.75rem 1.25rem;border-radius:8px;font-size:.9rem;font-weight:600;color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.18);"></div>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Requests</h1>
        <p class="page-subtitle">Review and approve or reject equipment borrowing requests.</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="manage-equipment">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by name..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=manage-equipment') }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</a>
                <a href="{{ url('/dashboard?section=manage-equipment&status=pending') }}" class="filter-btn {{ ($statusFilter ?? '') === 'pending' ? 'active' : '' }}">Pending</a>
                <a href="{{ url('/dashboard?section=manage-equipment&status=approved') }}" class="filter-btn {{ ($statusFilter ?? '') === 'approved' ? 'active' : '' }}">Approved</a>
                <a href="{{ url('/dashboard?section=manage-equipment&status=borrowed') }}" class="filter-btn {{ ($statusFilter ?? '') === 'borrowed' ? 'active' : '' }}">Borrowed</a>
            </div>
        </form>

        {{-- Bulk approve bar (Admin only) --}}
        @if(auth()->user()->hasPermission('equipment.approve'))
        <div id="bulk-bar-eq" style="display:none;align-items:center;gap:.75rem;margin-bottom:.75rem;padding:.5rem .75rem;background:var(--color-surface-alt,#f8fafc);border-radius:8px;">
            <span id="bulk-count-eq" style="font-size:.9rem;font-weight:600;"></span>
            <button id="bulk-approve-eq-btn" class="btn btn-success btn-sm">Approve Selected</button>
        </div>
        @endif

        <div class="table-responsive manage-table-responsive">
            <table class="data-table responsive-card-table">
                <thead>
                    <tr>
                        <th style="width:36px;"><input type="checkbox" id="select-all-eq" title="Select all pending"></th>
                        <th>Requestor</th><th>Equipment</th><th>Qty</th><th>Date Needed</th>
                        <th>Stock</th><th>Status</th><th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($equipmentRequests as $req)
                    <tr id="eq-row-{{ $req->id }}">
                        <td data-label="Select">
                            @if(auth()->user()->hasPermission('equipment.approve') && $req->status === 'pending')
                                <input type="checkbox" class="eq-check" value="{{ $req->id }}">
                            @endif
                        </td>
                        <td data-label="Requestor" style="font-weight: 600;">{{ $req->user->full_name }}</td>
                        <td data-label="Equipment">{{ $req->equipment->name }}</td>
                        <td data-label="Quantity">{{ $req->quantity }}</td>
                        <td data-label="Date Needed">{{ $req->date_needed->format('M d, Y') }}</td>
                        <td data-label="Stock">
                            @if($req->status === 'pending' && isset($stockMap[$req->id]))
                                @php $stock = $stockMap[$req->id]; @endphp
                                <span class="badge {{ $stock === 'Stock OK' ? 'badge-success' : 'badge-error' }}" style="font-size:.75rem;">{{ $stock }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td data-label="Status">
                            <x-status-badge :status="$req->status" id="eq-badge-{{ $req->id }}" />
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            @if(auth()->user()->hasPermission('equipment.approve') && $req->status === 'pending')
                                <div class="btn-group" id="eq-actions-{{ $req->id }}">
                                    <button class="btn btn-success btn-sm btn-action-large"
                                        onclick="approveEq({{ $req->id }}, this)">Approve</button>
                                    <button class="btn btn-danger btn-sm btn-action-large"
                                        onclick="openEqRejectModal({{ $req->id }}, this)">Reject</button>
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
                                <p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 500;">No equipment requests found.</p>
                                @if(request('status') || request('search'))
                                    <a href="{{ url('/dashboard?section=manage-equipment') }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                                @else
                                    <a href="{{ url('/equipment') }}" class="btn btn-secondary btn-sm">Browse Equipment</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $equipmentRequests->withQueryString()->links('partials.pagination') }}
    </div>
</div>

<script>
const EQ_CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '{{ csrf_token() }}';

function showEqToast(msg, ok) {
    const t = document.getElementById('eq-toast');
    t.textContent = msg;
    t.style.background = ok ? '#10b981' : '#ef4444';
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 3500);
}

function approveEq(id, btn) {
    setEqBusy(id, true);
    fetch(`/equipment-requests/${id}/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EQ_CSRF, 'Accept': 'application/json',
                   'X-HTTP-Method-Override': 'PATCH' },
        body: JSON.stringify({ status: 'approved' })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { updateEqRow(id, 'approved'); showEqToast('Request approved ✓', true); }
        else { showEqToast(data.error || 'Error', false); setEqBusy(id, false); }
    })
    .catch(() => { showEqToast('Network error', false); setEqBusy(id, false); });
}

let _eqRejectId = null;
function openEqRejectModal(id, btn) {
    _eqRejectId = id;
    document.getElementById('eq-reject-note').value = '';
    document.getElementById('eq-reject-reason').selectedIndex = 0;
    document.getElementById('eq-reject-modal').style.display = 'flex';
}
document.getElementById('eq-reject-cancel-btn').onclick = () => {
    document.getElementById('eq-reject-modal').style.display = 'none';
};
document.getElementById('eq-reject-confirm-btn').onclick = () => {
    const reason  = document.getElementById('eq-reject-reason').value;
    const note    = document.getElementById('eq-reject-note').value.trim();
    const remarks = note ? `${reason}. ${note}` : reason;
    document.getElementById('eq-reject-modal').style.display = 'none';
    setEqBusy(_eqRejectId, true);
    fetch(`/equipment-requests/${_eqRejectId}/status`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EQ_CSRF, 'Accept': 'application/json',
                   'X-HTTP-Method-Override': 'PATCH' },
        body: JSON.stringify({ status: 'rejected', remarks })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { updateEqRow(_eqRejectId, 'rejected'); showEqToast('Request rejected.', true); }
        else { showEqToast(data.error || 'Error', false); setEqBusy(_eqRejectId, false); }
    })
    .catch(() => { showEqToast('Network error', false); setEqBusy(_eqRejectId, false); });
};

function updateEqRow(id, status) {
    const badge   = document.getElementById(`eq-badge-${id}`);
    const actions = document.getElementById(`eq-actions-${id}`);
    const row     = document.getElementById(`eq-row-${id}`);
    if (badge) {
        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        badge.className = 'badge ' + (status === 'approved' ? 'badge-success' : 'badge-error');
    }
    if (actions) actions.innerHTML = '<span class="text-muted">—</span>';
    const cb = row?.querySelector('.eq-check');
    if (cb) cb.closest('td').innerHTML = '';
    updateEqBulkBar();
}
function setEqBusy(id, busy) {
    document.querySelectorAll(`#eq-actions-${id} button`).forEach(b => b.disabled = busy);
}

function updateEqBulkBar() {
    const checked = document.querySelectorAll('.eq-check:checked');
    const bar = document.getElementById('bulk-bar-eq');
    const cnt = document.getElementById('bulk-count-eq');
    bar.style.display = checked.length ? 'flex' : 'none';
    cnt.textContent = `${checked.length} selected`;
}
document.getElementById('select-all-eq').addEventListener('change', function() {
    document.querySelectorAll('.eq-check').forEach(c => c.checked = this.checked);
    updateEqBulkBar();
});
document.querySelectorAll('.eq-check').forEach(c => c.addEventListener('change', updateEqBulkBar));

document.getElementById('bulk-approve-eq-btn').addEventListener('click', function() {
    const ids = [...document.querySelectorAll('.eq-check:checked')].map(c => parseInt(c.value));
    if (!ids.length) return;
    this.disabled = true;
    fetch('/equipment-requests/bulk-approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': EQ_CSRF, 'Accept': 'application/json' },
        body: JSON.stringify({ ids })
    })
    .then(r => r.json())
    .then(data => {
        ids.forEach(id => updateEqRow(id, 'approved'));
        showEqToast(data.message, !data.failures?.length);
        document.getElementById('select-all-eq').checked = false;
        updateEqBulkBar();
        this.disabled = false;
    })
    .catch(() => { showEqToast('Network error', false); this.disabled = false; });
});
</script>
