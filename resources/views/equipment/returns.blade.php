@extends('layouts.app')

@section('title', 'Equipment Returns Desk — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Returns Desk</h1>
        <p class="page-subtitle">Inspect physical items, record conditions, and confirm equipment returns.</p>
    </div>
    <div>
        <a href="{{ url('/dashboard?section=manage-equipment') }}" class="btn btn-secondary btn-sm">
            &larr; Equipment Requests
        </a>
    </div>
</div>

{{-- Top Counters --}}
<div class="stats-grid animate-fade-in-up" style="animation-delay: 50ms; margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.15); color: #0284c7;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Returning Today</span>
            <span class="stat-value">{{ $counters['returning_today'] }}</span>
            <span class="stat-desc">Due back before end of day</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Overdue</span>
            <span class="stat-value" style="color: #dc2626;">{{ $counters['overdue'] }}</span>
            <span class="stat-desc">Passed expected return date</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        </div>
        <div class="stat-content">
            <span class="stat-label">Needs Check</span>
            <span class="stat-value" style="color: #d97706;">{{ $counters['needs_check'] }}</span>
            <span class="stat-desc">Borrower marked returned</span>
        </div>
    </div>
</div>

{{-- Find Borrower & Item Box --}}
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-bottom: 1.5rem;">
    <div class="card-body">
        <form method="GET" action="{{ url('/equipment/returns') }}" id="search-form">
            <label for="find-borrower-input" class="form-label" style="font-weight: 600; font-size: 0.95rem;">
                🔍 Find Borrower / Scan Item
            </label>
            <div class="header-actions-mobile" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <div class="search-input-wrapper" style="flex: 1; min-width: 220px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text"
                           id="find-borrower-input"
                           name="search"
                           class="search-input"
                           autofocus
                           autocomplete="off"
                           placeholder="Scan QR / barcode / badge or type borrower name, email, employee ID, or request #..."
                           value="{{ $search }}">
                </div>
                <div class="btn-row-mobile" style="display: flex; gap: 0.5rem;">
                    <button type="submit" class="btn btn-primary" style="flex: 1; min-width: 80px;">Find</button>
                    @if($search || request('request_id'))
                        <a href="{{ url('/equipment/returns') }}" class="btn btn-secondary" style="flex: 1; min-width: 80px;">Clear</a>
                    @endif
                </div>
            </div>
            <div style="margin-top: 0.35rem; font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                💡 Tip: Ready for scanner input. Scanned badges or QR codes will automatically submit and load the active borrow.
            </div>
        </form>

        {{-- Multiple Matches List --}}
        @if($matchingRequests->count() > 1 && !$selectedRequest)
            <div style="margin-top: 1.25rem; border-top: 1px solid var(--color-border, #e2e8f0); padding-top: 1rem;">
                <h4 style="margin-bottom: 0.75rem; font-size: 0.95rem; font-weight: 600;">
                    Found {{ $matchingRequests->count() }} active borrows for "{{ $search }}":
                </h4>
                <div class="table-responsive">
                    <table class="data-table mobile-cards">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Borrower</th>
                                <th>Equipment</th>
                                <th>Qty</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($matchingRequests as $m)
                                <tr>
                                    <td data-label="Request #"><strong>#{{ $m->id }}</strong></td>
                                    <td data-label="Borrower">{{ $m->user->full_name }} ({{ $m->user->employee_id ?: $m->user->email }})</td>
                                    <td data-label="Equipment">{{ $m->equipment->name }}</td>
                                    <td data-label="Qty">{{ $m->quantity }}</td>
                                    <td data-label="Due Date">{{ $m->expected_return_date ? $m->expected_return_date->format('M d, Y') : '—' }}</td>
                                    <td data-label="Status">
                                        @if($m->return_requested_at)
                                            <span class="badge badge-warning">Waiting Check</span>
                                        @elseif($m->expected_return_date && $m->expected_return_date < now()->startOfDay())
                                            <span class="badge badge-error">Overdue</span>
                                        @else
                                            <span class="badge badge-info">Borrowed</span>
                                        @endif
                                    </td>
                                    <td data-label="Action">
                                        <a href="{{ url('/equipment/returns?request_id=' . $m->id . ($search ? '&search=' . urlencode($search) : '')) }}" class="btn btn-primary btn-sm" style="width: 100%;">
                                            Select &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($search !== '' && $matchingRequests->isEmpty() && !$selectedRequest)
            <div style="margin-top: 1rem; padding: 1rem; background: var(--color-surface-alt, #f8fafc); border-radius: 8px; text-align: center;" class="text-muted">
                No active borrowed equipment matched "{{ $search }}".
            </div>
        @endif
    </div>
</div>

{{-- Selected Return Card --}}
@if($selectedRequest)
@php
    $today = now()->startOfDay();
    $expected = $selectedRequest->expected_return_date ? \Carbon\Carbon::parse($selectedRequest->expected_return_date)->startOfDay() : null;
    $daysLate = ($expected && $today->gt($expected)) ? (int) $expected->diffInDays($today) : 0;
    $remainingUnits = $selectedRequest->quantity - $selectedRequest->returned_quantity;
@endphp

<div class="card animate-fade-in-up" style="animation-delay: 150ms; margin-bottom: 2rem; border-left: 4px solid var(--color-primary, #0ea5e9);">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h3 class="card-title" style="margin: 0; display: inline-flex; align-items: center; gap: 0.5rem;">
                📦 Return Verification: Request #{{ $selectedRequest->id }}
            </h3>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            @if($daysLate > 0)
                <span class="badge badge-error" style="font-size: 0.85rem; padding: 0.35rem 0.65rem;">⚠️ {{ $daysLate }} Day(s) Late</span>
            @else
                <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.35rem 0.65rem;">✓ On Time</span>
            @endif

            @if($selectedRequest->return_requested_at)
                <span class="badge badge-warning" style="font-size: 0.85rem; padding: 0.35rem 0.65rem;">
                    Borrower Reported Return ({{ $selectedRequest->return_requested_at->format('M d, h:i A') }})
                </span>
            @endif
        </div>
    </div>

    <div class="card-body">
        {{-- Request details grid --}}
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background: var(--color-surface-alt, #f8fafc); padding: 1rem; border-radius: 8px;">
            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Borrower</span>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-primary, #0f172a);">
                    {{ $selectedRequest->user->full_name }}
                </div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                    ID: {{ $selectedRequest->user->employee_id ?: '—' }} &bull; {{ $selectedRequest->user->role->name ?? 'User' }}
                </div>
            </div>

            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Item Borrowed</span>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-primary, #0f172a);">
                    {{ $selectedRequest->equipment->name }}
                </div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                    Category: {{ $selectedRequest->equipment->category }}
                </div>
            </div>

            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Units</span>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-primary, #0f172a);">
                    {{ $remainingUnits }} unit(s) due
                </div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                    Total: {{ $selectedRequest->quantity }} | Returned: {{ $selectedRequest->returned_quantity }}
                </div>
            </div>

            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Dates & Schedule</span>
                <div style="font-size: 0.85rem; font-weight: 500;">
                    Borrowed: {{ $selectedRequest->date_needed ? $selectedRequest->date_needed->format('M d, Y') : '—' }}
                </div>
                <div style="font-size: 0.85rem; font-weight: 600; color: {{ $daysLate > 0 ? '#dc2626' : 'inherit' }};">
                    Due: {{ $selectedRequest->expected_return_date ? $selectedRequest->expected_return_date->format('M d, Y') : '—' }}
                </div>
            </div>

            <div style="grid-column: 1 / -1;">
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Purpose</span>
                <div style="font-size: 0.9rem;">
                    {{ $selectedRequest->purpose }}
                </div>
            </div>

            <div style="grid-column: 1 / -1;">
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Borrower's Return Photo</span>
                @if($selectedRequest->return_photo_url)
                    <div style="margin-top: .4rem;">
                        <a href="{{ $selectedRequest->return_photo_url }}" target="_blank" rel="noopener" title="Open full size">
                            <img src="{{ $selectedRequest->return_photo_url }}" alt="Return photo submitted by borrower"
                                 style="max-width: 320px; max-height: 240px; border-radius: 8px; border: 1px solid #e2e8f0; object-fit: cover;">
                        </a>
                    </div>
                @else
                    <div style="font-size: 0.85rem; color: var(--color-text-muted, #64748b);">No photo submitted yet.</div>
                @endif
            </div>
        </div>

        {{-- Return Confirmation Form --}}
        <form method="POST" action="{{ url('/equipment-requests/' . $selectedRequest->id . '/confirm-return') }}" id="confirm-return-form">
            @csrf

            <div style="margin-bottom: 1.25rem;">
                <label class="form-label" style="font-weight: 600; display: block; margin-bottom: 0.5rem;">
                    Physical Item Condition *
                </label>
                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
                    <label class="condition-btn-label" style="cursor: pointer; flex: 1; min-width: 140px;">
                        <input type="radio" name="condition" value="good" checked style="display: none;" onchange="updateConditionStyle(this)">
                        <div class="condition-badge-box selected" id="cond-box-good" style="padding: 0.75rem; border: 2px solid #10b981; border-radius: 8px; text-align: center; background: rgba(16, 185, 129, 0.08);">
                            <div style="font-weight: 700; color: #059669;">✓ Good / Normal</div>
                            <div style="font-size: 0.75rem; color: #065f46;">Full working condition. Restores stock.</div>
                        </div>
                    </label>

                    <label class="condition-btn-label" style="cursor: pointer; flex: 1; min-width: 140px;">
                        <input type="radio" name="condition" value="damaged" style="display: none;" onchange="updateConditionStyle(this)">
                        <div class="condition-badge-box" id="cond-box-damaged" style="padding: 0.75rem; border: 2px solid var(--color-border, #e2e8f0); border-radius: 8px; text-align: center;">
                            <div style="font-weight: 700; color: #dc2626;">⚠️ Damaged</div>
                            <div style="font-size: 0.75rem; color: #991b1b;">Needs repair. Placed in maintenance.</div>
                        </div>
                    </label>

                    <label class="condition-btn-label" style="cursor: pointer; flex: 1; min-width: 140px;">
                        <input type="radio" name="condition" value="missing" style="display: none;" onchange="updateConditionStyle(this)">
                        <div class="condition-badge-box" id="cond-box-missing" style="padding: 0.75rem; border: 2px solid var(--color-border, #e2e8f0); border-radius: 8px; text-align: center;">
                            <div style="font-weight: 700; color: #d97706;">❌ Missing Parts</div>
                            <div style="font-size: 0.75rem; color: #92400e;">Incomplete. Placed in maintenance.</div>
                        </div>
                    </label>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <label for="return_notes" class="form-label" style="font-weight: 600;">Optional Inspection Notes</label>
                <textarea id="return_notes" name="return_notes" class="form-control" rows="2" placeholder="e.g., Cleaned, tested power cable, minor cosmetic scratches on handle..."></textarea>
            </div>

            {{-- Hidden partial return inputs --}}
            <input type="hidden" name="return_type" id="return_type_input" value="full">

            <div id="partial-qty-container" style="display: none; margin-bottom: 1.25rem; padding: 1rem; background: var(--color-surface-alt, #f8fafc); border-radius: 8px; border: 1px dashed var(--color-primary, #0ea5e9);">
                <label for="quantity_returned" class="form-label" style="font-weight: 600;">
                    Partial Units Returned (1 to {{ $remainingUnits }}) *
                </label>
                <div style="display: flex; gap: 0.5rem; align-items: center; max-width: 300px;">
                    <input type="number" id="quantity_returned" name="quantity_returned" class="form-control" min="1" max="{{ $remainingUnits }}" value="{{ $remainingUnits }}">
                    <span style="font-size: 0.85rem; color: var(--color-text-muted);">of {{ $remainingUnits }} due</span>
                </div>
            </div>

            {{-- Action buttons --}}
            <div class="return-actions-group" style="display: flex; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
                <button type="button" class="btn btn-success" id="btn-full-return" onclick="submitReturn('full')" style="font-size: 1rem; padding: 0.65rem 1.5rem; font-weight: 600; min-height: 44px;">
                    ✓ Confirm Return ({{ $remainingUnits }} unit(s))
                </button>

                @if($remainingUnits > 1)
                    <button type="button" class="btn btn-secondary" id="btn-toggle-partial" onclick="togglePartialMode()" style="font-size: 0.9rem; min-height: 44px;">
                        Partial Return...
                    </button>
                @endif

                <a href="{{ url('/equipment/returns') }}" class="btn btn-ghost" style="color: var(--color-text-muted); min-height: 44px; display: inline-flex; align-items: center; justify-content: center;">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Lower Section: Needs Check & Overdue lists --}}
<div class="grid grid-2 animate-fade-in-up" style="animation-delay: 200ms; gap: 1.5rem;">
    {{-- Needs Check List --}}
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #d97706;">🔔</span> Borrower Return Requests
                <span class="badge badge-warning" style="font-size: 0.75rem;">{{ $needsCheckRequests->count() }}</span>
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Reported</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($needsCheckRequests as $nc)
                            <tr>
                                <td data-label="Borrower">
                                    <div style="font-weight: 600;">{{ $nc->user->full_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $nc->user->employee_id ?: $nc->user->email }}</div>
                                </td>
                                <td data-label="Equipment">
                                    <div style="display: flex; align-items: center; gap: .5rem;">
                                        @if($nc->return_photo_url)
                                            <a href="{{ $nc->return_photo_url }}" target="_blank" rel="noopener" title="View return photo">
                                                <img src="{{ $nc->return_photo_url }}" alt="Return photo" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                            </a>
                                        @endif
                                        <span>{{ $nc->equipment->name }}</span>
                                    </div>
                                </td>
                                <td data-label="Qty">{{ $nc->quantity }}</td>
                                <td data-label="Reported">
                                    <span style="font-size: 0.8rem;">{{ $nc->return_requested_at ? $nc->return_requested_at->diffForHumans() : '—' }}</span>
                                </td>
                                <td data-label="Action">
                                    <a href="{{ url('/equipment/returns?request_id=' . $nc->id) }}" class="btn btn-primary btn-sm" style="font-size: 0.75rem; width: 100%;">
                                        Inspect & Confirm
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                                    No pending borrower return notifications.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Overdue List --}}
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
            <h3 class="card-title" style="display: flex; align-items: center; gap: 0.5rem;">
                <span style="color: #dc2626;">⚠️</span> Overdue Equipment
                <span class="badge badge-error" style="font-size: 0.75rem;">{{ $overdueRequests->count() }}</span>
            </h3>
        </div>
        <div class="card-body" style="padding: 0;">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Due Date</th>
                            <th>Late</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($overdueRequests as $od)
                            @php
                                $daysOverdue = max(1, (int) \Carbon\Carbon::parse($od->expected_return_date)->startOfDay()->diffInDays(now()->startOfDay()));
                            @endphp
                            <tr>
                                <td data-label="Borrower">
                                    <div style="font-weight: 600;">{{ $od->user->full_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $od->user->employee_id ?: $od->user->email }}</div>
                                </td>
                                <td data-label="Equipment">{{ $od->equipment->name }}</td>
                                <td data-label="Due Date">{{ $od->expected_return_date ? $od->expected_return_date->format('M d, Y') : '—' }}</td>
                                <td data-label="Late">
                                    <span class="badge badge-error" style="font-size: 0.75rem;">{{ $daysOverdue }}d late</span>
                                </td>
                                <td data-label="Action">
                                    <div class="btn-group" style="display: flex; gap: 0.35rem; align-items: center; width: 100%;">
                                        <a href="{{ url('/equipment/returns?request_id=' . $od->id) }}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;" title="Select for return">
                                            Return
                                        </a>
                                        <form method="POST" action="{{ url('/equipment-requests/' . $od->id . '/send-reminder') }}" style="margin: 0; flex: 1;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm" style="font-size: 0.75rem; width: 100%;" title="Send overdue reminder to borrower">
                                                Remind
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted" style="padding: 2rem;">
                                    ✓ No overdue equipment currently.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Keep find borrower box focused
    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('find-borrower-input');
        if (input && !document.querySelector('.is-focused-manually')) {
            input.focus();
            // Automatically select text so next scan replaces previous query
            input.select();
        }
    });

    function updateConditionStyle(radio) {
        document.querySelectorAll('.condition-badge-box').forEach(box => {
            box.style.border = '2px solid var(--color-border, #e2e8f0)';
            box.style.background = 'transparent';
        });

        if (radio.value === 'good') {
            const b = document.getElementById('cond-box-good');
            b.style.border = '2px solid #10b981';
            b.style.background = 'rgba(16, 185, 129, 0.08)';
        } else if (radio.value === 'damaged') {
            const b = document.getElementById('cond-box-damaged');
            b.style.border = '2px solid #dc2626';
            b.style.background = 'rgba(239, 68, 68, 0.08)';
        } else if (radio.value === 'missing') {
            const b = document.getElementById('cond-box-missing');
            b.style.border = '2px solid #d97706';
            b.style.background = 'rgba(245, 158, 11, 0.08)';
        }
    }

    let isPartialMode = false;
    function togglePartialMode() {
        isPartialMode = !isPartialMode;
        const container = document.getElementById('partial-qty-container');
        const typeInput = document.getElementById('return_type_input');
        const fullBtn = document.getElementById('btn-full-return');
        const toggleBtn = document.getElementById('btn-toggle-partial');

        if (isPartialMode) {
            container.style.display = 'block';
            typeInput.value = 'partial';
            fullBtn.innerText = '✓ Confirm Partial Return';
            toggleBtn.innerText = 'Switch to Full Return';
            document.getElementById('quantity_returned').focus();
        } else {
            container.style.display = 'none';
            typeInput.value = 'full';
            fullBtn.innerText = '✓ Confirm Return';
            toggleBtn.innerText = 'Partial Return...';
        }
    }

    function submitReturn(type) {
        if (type === 'full') {
            if (isPartialMode) {
                document.getElementById('return_type_input').value = 'partial';
            } else {
                document.getElementById('return_type_input').value = 'full';
            }
        }
        document.getElementById('confirm-return-form').submit();
    }
</script>
@endpush
@endsection
