@php
    $isStandalone = request()->is('equipment/returns*');
    $baseUrl = $isStandalone ? url('/equipment/returns') : url('/dashboard');
    $returnDeskUrl = $isStandalone ? url('/equipment/returns') : url('/dashboard?section=equipment-returns');
    $selectedTab = request()->get('tab', 'active');
    if ($selectedRequest && !request()->has('tab')) {
        // If an item is selected and no tab specified, default to active
        $selectedTab = 'active';
    }
@endphp

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Returns Desk</h1>
        <p class="page-subtitle">Inspect physical items, record conditions, restore inventory stock, and track currently borrowed equipment.</p>
    </div>
    <div class="btn-row-mobile" style="display: flex; gap: 0.5rem; align-items: center;">
        <a href="{{ url('/dashboard?section=manage-equipment') }}" class="btn btn-secondary btn-sm">
            &larr; Equipment Requests
        </a>
        <a href="{{ url('/dashboard?section=inventory') }}" class="btn btn-secondary btn-sm">
            Equipment Inventory
        </a>
    </div>
</div>

<style>
    .returns-stats-grid {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1.25rem;
        margin-bottom: 1.75rem;
    }
    .return-stat-card {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 1.25rem 1.25rem;
        border-radius: 12px;
        background: var(--color-bg-card, #ffffff);
        border: 1px solid var(--color-border, #e2e8f0);
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
        position: relative;
        overflow: hidden;
    }
    .return-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
        border-color: var(--color-border-accent, #cbd5e1);
    }
    .return-stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    .return-stat-header .stat-label {
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--color-text-muted, #64748b);
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .return-stat-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .return-stat-value {
        font-size: 1.85rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 0.25rem;
        font-family: var(--font-heading, inherit);
    }
    .return-stat-desc {
        font-size: 0.8rem;
        color: var(--color-text-muted, #64748b);
        line-height: 1.3;
    }
    @media (max-width: 1024px) {
        .returns-stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }
    @media (max-width: 640px) {
        .returns-stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

{{-- Top 5 Stat Counters --}}
<div class="returns-stats-grid animate-fade-in-up" style="animation-delay: 50ms;">
    {{-- Active Borrows --}}
    <div class="return-stat-card" onclick="switchDeskTab('active')">
        <div class="return-stat-header">
            <span class="stat-label">Active Borrows</span>
            <div class="return-stat-icon" style="background: rgba(14, 165, 233, 0.15); color: #0284c7;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
        </div>
        <div>
            <div class="return-stat-value" style="color: #0284c7;">{{ $counters['active_borrows'] ?? 0 }}</div>
            <div class="return-stat-desc">Currently borrowed</div>
        </div>
    </div>

    {{-- Needs Check --}}
    <div class="return-stat-card" onclick="switchDeskTab('needs-check')">
        <div class="return-stat-header">
            <span class="stat-label">Needs Check</span>
            <div class="return-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #d97706;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
        </div>
        <div>
            <div class="return-stat-value" style="color: #d97706;">{{ $counters['needs_check'] ?? 0 }}</div>
            <div class="return-stat-desc">Borrower marked returned</div>
        </div>
    </div>

    {{-- Returning Today --}}
    <div class="return-stat-card" onclick="switchDeskTab('today')">
        <div class="return-stat-header">
            <span class="stat-label">Returning Today</span>
            <div class="return-stat-icon" style="background: rgba(6, 182, 212, 0.15); color: #0891b2;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <polyline points="12 6 12 12 16 14"></polyline>
                </svg>
            </div>
        </div>
        <div>
            <div class="return-stat-value" style="color: #0891b2;">{{ $counters['returning_today'] ?? 0 }}</div>
            <div class="return-stat-desc">Due back today</div>
        </div>
    </div>

    {{-- Overdue --}}
    <div class="return-stat-card" onclick="switchDeskTab('overdue')">
        <div class="return-stat-header">
            <span class="stat-label">Overdue</span>
            <div class="return-stat-icon" style="background: rgba(239, 68, 68, 0.15); color: #dc2626;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                    <line x1="12" y1="9" x2="12" y2="13"></line>
                    <line x1="12" y1="17" x2="12.01" y2="17"></line>
                </svg>
            </div>
        </div>
        <div>
            <div class="return-stat-value" style="color: #dc2626;">{{ $counters['overdue'] ?? 0 }}</div>
            <div class="return-stat-desc">Past expected return date</div>
        </div>
    </div>

    {{-- Total Returned --}}
    <div class="return-stat-card" onclick="switchDeskTab('recent')">
        <div class="return-stat-header">
            <span class="stat-label">Returned Total</span>
            <div class="return-stat-icon" style="background: rgba(16, 185, 129, 0.15); color: #059669;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 6L9 17l-5-5"></path>
                </svg>
            </div>
        </div>
        <div>
            <div class="return-stat-value" style="color: #059669;">{{ $counters['returned_total'] ?? 0 }}</div>
            <div class="return-stat-desc">Completed return history</div>
        </div>
    </div>
</div>

{{-- Find Borrower & Item Box --}}
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-bottom: 1.5rem;">
    <div class="card-body">
        <form method="GET" action="{{ $baseUrl }}" id="search-form">
            @if(!$isStandalone)
                <input type="hidden" name="section" value="equipment-returns">
            @endif
            <label for="find-borrower-input" class="form-label" style="font-weight: 600; font-size: 0.95rem;">
                🔍 Find Borrower / Scan Item
            </label>
            <div class="header-actions-mobile" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                <div class="search-input-wrapper" style="flex: 1; min-width: 220px;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
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
                        <a href="{{ $returnDeskUrl }}" class="btn btn-secondary" style="flex: 1; min-width: 80px;">Clear</a>
                    @endif
                </div>
            </div>
            <div style="margin-top: 0.35rem; font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                💡 Tip: Ready for scanner input. Scanned badges or barcodes will automatically find and load the active borrow.
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
                                    <td data-label="Request #">
                                        <strong>REQ-{{ str_pad($m->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                    </td>
                                    <td data-label="Borrower">
                                        <div style="font-weight: 600;">{{ $m->user->full_name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                            {{ $m->user->employee_id ?: $m->user->email }}
                                            @if($m->user->department)
                                                &bull; {{ $m->user->department->code }}
                                            @endif
                                        </div>
                                    </td>
                                    <td data-label="Equipment">
                                        <div style="font-weight: 600;">{{ $m->equipment->name }}</div>
                                        <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $m->equipment->category }}</div>
                                    </td>
                                    <td data-label="Qty">
                                        {{ $m->quantity - $m->returned_quantity }} of {{ $m->quantity }} due
                                    </td>
                                    <td data-label="Due Date">
                                        {{ $m->expected_return_date ? $m->expected_return_date->format('M d, Y') : '—' }}
                                    </td>
                                    <td data-label="Status">
                                        <x-status-badge :status="$m->status" />
                                    </td>
                                    <td data-label="Action">
                                        <a href="{{ $returnDeskUrl . '?request_id=' . $m->id . ($search ? '&search=' . urlencode($search) : '') }}" class="btn btn-primary btn-sm" style="width: 100%;">
                                            Select & Inspect
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @elseif($search !== '' && $matchingRequests->isEmpty())
            <div style="margin-top: 1rem; padding: 0.75rem 1rem; background: rgba(239, 68, 68, 0.08); border-radius: 8px; color: #dc2626; font-size: 0.875rem;">
                No active borrowed equipment found for "{{ $search }}". Check spelling or search by borrower name or Request #.
            </div>
        @endif
    </div>
</div>

{{-- Selected Return Verification Card --}}
@if($selectedRequest)
@php
    $today = now()->startOfDay();
    $expected = $selectedRequest->expected_return_date ? \Carbon\Carbon::parse($selectedRequest->expected_return_date)->startOfDay() : null;
    $daysLate = ($expected && $today->gt($expected)) ? (int) $expected->diffInDays($today) : 0;
    $remainingUnits = max(1, $selectedRequest->quantity - $selectedRequest->returned_quantity);
    $isAlreadyReturned = ($selectedRequest->status === 'returned');
@endphp

<div class="card animate-fade-in-up" id="verification-card" style="animation-delay: 150ms; margin-bottom: 2rem; border-left: 4px solid var(--color-primary, #0ea5e9);">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h3 class="card-title" style="margin: 0; display: inline-flex; align-items: center; gap: 0.5rem;">
                📦 Return Verification: Request #{{ str_pad($selectedRequest->id, 4, '0', STR_PAD_LEFT) }}
            </h3>
        </div>
        <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
            @if($isAlreadyReturned)
                <span class="badge badge-success" style="font-size: 0.85rem; padding: 0.35rem 0.65rem;">✓ Already Returned</span>
            @elseif($daysLate > 0)
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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; background: var(--color-surface-alt, #f8fafc); padding: 1.25rem; border-radius: 8px;">
            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Borrower</span>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-primary, #0f172a);">
                    {{ $selectedRequest->user->full_name }}
                </div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                    ID: {{ $selectedRequest->user->employee_id ?: '—' }} &bull; {{ $selectedRequest->user->role->name ?? 'User' }}
                </div>
                @if($selectedRequest->user->department)
                    <div style="font-size: 0.75rem; color: var(--color-brand-600, #0284c7); margin-top: 0.2rem; font-weight: 500;">
                        {{ $selectedRequest->user->department->name }} ({{ $selectedRequest->user->department->code }})
                    </div>
                @endif
            </div>

            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Item Borrowed</span>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-primary, #0f172a);">
                    {{ $selectedRequest->equipment->name }}
                </div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                    Category: {{ $selectedRequest->equipment->category }}
                </div>
                <div style="font-size: 0.75rem; color: var(--color-text-muted, #64748b); margin-top: 0.2rem;">
                    Current In-Stock: {{ $selectedRequest->equipment->available_quantity }} / {{ $selectedRequest->equipment->quantity }}
                </div>
            </div>

            <div>
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Units</span>
                <div style="font-weight: 600; font-size: 1rem; color: var(--color-text-primary, #0f172a);">
                    @if($isAlreadyReturned)
                        {{ $selectedRequest->quantity }} unit(s) returned
                    @else
                        {{ $remainingUnits }} unit(s) due
                    @endif
                </div>
                <div style="font-size: 0.8rem; color: var(--color-text-muted, #64748b);">
                    Total Borrowed: {{ $selectedRequest->quantity }} | Returned: {{ $selectedRequest->returned_quantity }}
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
                @if($selectedRequest->returned_at)
                    <div style="font-size: 0.85rem; color: #059669; font-weight: 600;">
                        Returned: {{ $selectedRequest->returned_at->format('M d, Y h:i A') }}
                    </div>
                @endif
            </div>

            <div style="grid-column: 1 / -1;">
                <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600;">Purpose</span>
                <div style="font-size: 0.9rem; margin-top: 0.25rem;">
                    {{ $selectedRequest->purpose ?: 'Not specified' }}
                </div>
            </div>

            @if($selectedRequest->return_photo_url)
                <div style="grid-column: 1 / -1; border-top: 1px solid var(--color-border, #e2e8f0); padding-top: 0.75rem;">
                    <span class="text-muted" style="font-size: 0.75rem; text-transform: uppercase; font-weight: 600; display: block; margin-bottom: 0.5rem;">
                        Borrower Photo Proof of Return
                    </span>
                    <div style="display: flex; gap: 1rem; align-items: flex-start; flex-wrap: wrap;">
                        <a href="{{ $selectedRequest->return_photo_url }}" target="_blank" rel="noopener" title="Open full size">
                            <img src="{{ $selectedRequest->return_photo_url }}" alt="Return photo submitted by borrower"
                                 style="max-width: 320px; max-height: 220px; border-radius: 8px; border: 1px solid #cbd5e1; object-fit: cover;">
                        </a>
                        <div style="font-size: 0.85rem; color: var(--color-text-muted); max-width: 300px;">
                            Uploaded by {{ $selectedRequest->user->full_name }} at {{ $selectedRequest->return_requested_at?->format('M d, Y h:i A') }}.
                            <div style="margin-top: 0.5rem;">
                                <a href="{{ $selectedRequest->return_photo_url }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">
                                    View Full Size ↗
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        @if($isAlreadyReturned)
            <div style="padding: 1rem 1.25rem; background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; border-radius: 8px; color: #065f46;">
                <div style="font-weight: 700; font-size: 1rem;">✓ Equipment Return Already Processed</div>
                <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                    This equipment loan was marked returned on <strong>{{ $selectedRequest->returned_at?->format('M d, Y h:i A') }}</strong>.
                    @if($selectedRequest->confirmedBy)
                        Confirmed by staff: <strong>{{ $selectedRequest->confirmedBy->full_name }}</strong>.
                    @endif
                    Condition recorded: <strong>{{ ucfirst($selectedRequest->return_condition ?? 'Good') }}</strong>.
                    @if($selectedRequest->return_notes)
                        <br><em>Notes: {{ $selectedRequest->return_notes }}</em>
                    @endif
                </div>
                <div style="margin-top: 0.75rem;">
                    <a href="{{ $returnDeskUrl }}" class="btn btn-secondary btn-sm">Close Verification</a>
                </div>
            </div>
        @else
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

                    <a href="{{ $returnDeskUrl }}" class="btn btn-ghost" style="color: var(--color-text-muted); min-height: 44px; display: inline-flex; align-items: center; justify-content: center;">
                        Cancel
                    </a>
                </div>
            </form>
        @endif
    </div>
</div>
@endif

{{-- Returns & Borrow Management Card with Tabs --}}
<div class="card animate-fade-in-up" style="animation-delay: 200ms;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem;">
        <div>
            <h3 class="card-title" style="margin: 0;">Equipment Returns &amp; Borrow Tracking</h3>
            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0.2rem 0 0 0;">
                All items currently borrowed, overdue items, borrower check requests, and recent return logs.
            </p>
        </div>
        {{-- Interactive Tab Filter Buttons --}}
        <div class="scope-segmented-control" role="group" aria-label="Returns Desk Tabs">
            <button type="button"
                    class="filter-btn scope-pill tab-btn {{ $selectedTab === 'active' ? 'active' : '' }}"
                    id="tab-btn-active"
                    onclick="switchDeskTab('active')">
                <span>📦</span> Active Borrows ({{ $activeRequests->count() }})
            </button>
            <button type="button"
                    class="filter-btn scope-pill tab-btn {{ $selectedTab === 'needs-check' ? 'active' : '' }}"
                    id="tab-btn-needs-check"
                    onclick="switchDeskTab('needs-check')">
                <span>🔔</span> Needs Check ({{ $needsCheckRequests->count() }})
            </button>
            <button type="button"
                    class="filter-btn scope-pill tab-btn {{ $selectedTab === 'overdue' ? 'active' : '' }}"
                    id="tab-btn-overdue"
                    onclick="switchDeskTab('overdue')">
                <span>⚠️</span> Overdue ({{ $overdueRequests->count() }})
            </button>
            <button type="button"
                    class="filter-btn scope-pill tab-btn {{ $selectedTab === 'today' ? 'active' : '' }}"
                    id="tab-btn-today"
                    onclick="switchDeskTab('today')">
                <span>⏰</span> Due Today ({{ $returningTodayRequests->count() }})
            </button>
            <button type="button"
                    class="filter-btn scope-pill tab-btn {{ $selectedTab === 'recent' ? 'active' : '' }}"
                    id="tab-btn-recent"
                    onclick="switchDeskTab('recent')">
                <span>✓</span> Recently Returned ({{ $recentReturnedRequests->count() }})
            </button>
        </div>
    </div>

    <div class="card-body" style="padding: 0;">
        {{-- ── TAB 1: ALL ACTIVE LOANS ── --}}
        <div class="tab-pane" id="pane-active" style="display: {{ $selectedTab === 'active' ? 'block' : 'none' }};">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Qty Due</th>
                            <th>Date Borrowed</th>
                            <th>Expected Return</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeRequests as $req)
                            @php
                                $today = now()->startOfDay();
                                $exp = $req->expected_return_date ? \Carbon\Carbon::parse($req->expected_return_date)->startOfDay() : null;
                                $isOverdue = $exp && $today->gt($exp);
                                $isDueToday = $exp && $today->equalTo($exp);
                                $daysOverdue = $isOverdue ? (int) $exp->diffInDays($today) : 0;
                                $unitsRemaining = $req->quantity - $req->returned_quantity;
                            @endphp
                            <tr id="req-row-{{ $req->id }}" style="{{ $selectedRequest && $selectedRequest->id === $req->id ? 'background: rgba(14, 165, 233, 0.06);' : '' }}">
                                <td data-label="Request #">
                                    <strong>REQ-{{ str_pad($req->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td data-label="Borrower">
                                    <div style="font-weight: 600;">{{ $req->user->full_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                        {{ $req->user->employee_id ?: $req->user->email }}
                                        @if($req->user->department)
                                            &bull; {{ $req->user->department->code }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Equipment">
                                    <div style="font-weight: 600;">{{ $req->equipment->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $req->equipment->category }}</div>
                                </td>
                                <td data-label="Qty Due">
                                    <span style="font-weight: 600;">{{ $unitsRemaining }} unit(s)</span>
                                    @if($req->returned_quantity > 0)
                                        <div style="font-size: 0.7rem; color: #0284c7;">({{ $req->returned_quantity }} returned)</div>
                                    @endif
                                </td>
                                <td data-label="Date Borrowed">
                                    {{ $req->date_needed ? $req->date_needed->format('M d, Y') : '—' }}
                                </td>
                                <td data-label="Expected Return">
                                    <div style="font-weight: 500;">
                                        {{ $req->expected_return_date ? $req->expected_return_date->format('M d, Y') : '—' }}
                                    </div>
                                    @if($isOverdue)
                                        <span class="badge badge-error" style="font-size: 0.7rem; margin-top: 0.2rem;">{{ $daysOverdue }}d Overdue</span>
                                    @elseif($isDueToday)
                                        <span class="badge" style="background: rgba(6, 182, 212, 0.15); color: #0891b2; font-size: 0.7rem; margin-top: 0.2rem;">Due Today</span>
                                    @endif
                                </td>
                                <td data-label="Status">
                                    <x-status-badge :status="$req->status" />
                                    @if($req->return_requested_at)
                                        <div style="margin-top: 0.2rem;">
                                            <span class="badge badge-warning" style="font-size: 0.7rem;">Marked Returned</span>
                                        </div>
                                    @endif
                                </td>
                                <td data-label="Action">
                                    <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                        <a href="{{ $returnDeskUrl . '?request_id=' . $req->id }}" class="btn btn-primary btn-sm" style="font-size: 0.75rem; white-space: nowrap;">
                                            Process Return
                                        </a>
                                        @if($isOverdue)
                                            <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/send-reminder') }}" style="margin: 0;">
                                                @csrf
                                                <button type="submit" class="btn btn-warning btn-sm" style="font-size: 0.75rem;" title="Send overdue reminder email/alert">
                                                    Remind
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                                    <div style="font-size: 1.1rem; font-weight: 500; margin-bottom: 0.25rem;">No active equipment borrows right now.</div>
                                    <div style="font-size: 0.85rem;">When equipment requests are approved or borrowed, they will appear here ready for return.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── TAB 2: NEEDS CHECK ── --}}
        <div class="tab-pane" id="pane-needs-check" style="display: {{ $selectedTab === 'needs-check' ? 'block' : 'none' }};">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Reported Time</th>
                            <th>Photo Proof</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($needsCheckRequests as $nc)
                            <tr>
                                <td data-label="Borrower">
                                    <div style="font-weight: 600;">{{ $nc->user->full_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                        {{ $nc->user->employee_id ?: $nc->user->email }}
                                        @if($nc->user->department)
                                            &bull; {{ $nc->user->department->code }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Equipment">
                                    <div style="font-weight: 600;">{{ $nc->equipment->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $nc->equipment->category }}</div>
                                </td>
                                <td data-label="Qty">
                                    {{ $nc->quantity - $nc->returned_quantity }} of {{ $nc->quantity }}
                                </td>
                                <td data-label="Reported Time">
                                    <span style="font-size: 0.85rem; font-weight: 500;">
                                        {{ $nc->return_requested_at ? $nc->return_requested_at->diffForHumans() : '—' }}
                                    </span>
                                </td>
                                <td data-label="Photo Proof">
                                    @if($nc->return_photo_url)
                                        <a href="{{ $nc->return_photo_url }}" target="_blank" rel="noopener" title="View uploaded return proof">
                                            <img src="{{ $nc->return_photo_url }}" alt="Proof" style="width: 44px; height: 44px; border-radius: 6px; object-fit: cover; border: 1px solid #cbd5e1;">
                                        </a>
                                    @else
                                        <span class="text-muted" style="font-size: 0.8rem;">None</span>
                                    @endif
                                </td>
                                <td data-label="Action">
                                    <a href="{{ $returnDeskUrl . '?request_id=' . $nc->id }}" class="btn btn-primary btn-sm" style="font-size: 0.75rem; width: 100%;">
                                        Inspect & Confirm
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                                    <div style="font-size: 1.1rem; font-weight: 500; margin-bottom: 0.25rem;">No pending borrower return notifications.</div>
                                    <div style="font-size: 0.85rem;">When borrowers mark equipment returned with a photo, they will appear here for staff verification.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── TAB 3: OVERDUE ── --}}
        <div class="tab-pane" id="pane-overdue" style="display: {{ $selectedTab === 'overdue' ? 'block' : 'none' }};">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Due Date</th>
                            <th>Days Late</th>
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
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                        {{ $od->user->employee_id ?: $od->user->email }}
                                        @if($od->user->department)
                                            &bull; {{ $od->user->department->code }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Equipment">
                                    <div style="font-weight: 600;">{{ $od->equipment->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $od->equipment->category }}</div>
                                </td>
                                <td data-label="Qty">{{ $od->quantity - $od->returned_quantity }}</td>
                                <td data-label="Due Date">{{ $od->expected_return_date ? $od->expected_return_date->format('M d, Y') : '—' }}</td>
                                <td data-label="Days Late">
                                    <span class="badge badge-error" style="font-size: 0.8rem; font-weight: 600;">
                                        {{ $daysOverdue }} day(s) late
                                    </span>
                                </td>
                                <td data-label="Action">
                                    <div class="btn-group" style="display: flex; gap: 0.35rem; align-items: center; width: 100%;">
                                        <a href="{{ $returnDeskUrl . '?request_id=' . $od->id }}" class="btn btn-primary btn-sm" style="font-size: 0.75rem;">
                                            Process Return
                                        </a>
                                        <form method="POST" action="{{ url('/equipment-requests/' . $od->id . '/send-reminder') }}" style="margin: 0; flex: 1;">
                                            @csrf
                                            <button type="submit" class="btn btn-warning btn-sm" style="font-size: 0.75rem; width: 100%;" title="Send overdue notice">
                                                Remind
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                                    <div style="font-size: 1.1rem; font-weight: 500; color: #059669; margin-bottom: 0.25rem;">✓ No overdue equipment!</div>
                                    <div style="font-size: 0.85rem;">All currently borrowed items are within their scheduled return dates.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── TAB 4: DUE TODAY ── --}}
        <div class="tab-pane" id="pane-today" style="display: {{ $selectedTab === 'today' ? 'block' : 'none' }};">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Borrow Date</th>
                            <th>Due Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($returningTodayRequests as $td)
                            <tr>
                                <td data-label="Borrower">
                                    <div style="font-weight: 600;">{{ $td->user->full_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                        {{ $td->user->employee_id ?: $td->user->email }}
                                        @if($td->user->department)
                                            &bull; {{ $td->user->department->code }}
                                        @endif
                                    </div>
                                </td>
                                <td data-label="Equipment">
                                    <div style="font-weight: 600;">{{ $td->equipment->name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $td->equipment->category }}</div>
                                </td>
                                <td data-label="Qty">{{ $td->quantity - $td->returned_quantity }}</td>
                                <td data-label="Borrow Date">{{ $td->date_needed ? $td->date_needed->format('M d, Y') : '—' }}</td>
                                <td data-label="Due Time">
                                    <span class="badge" style="background: rgba(6, 182, 212, 0.15); color: #0891b2; font-size: 0.75rem;">
                                        End of Day Today
                                    </span>
                                </td>
                                <td data-label="Action">
                                    <a href="{{ $returnDeskUrl . '?request_id=' . $td->id }}" class="btn btn-primary btn-sm" style="font-size: 0.75rem; width: 100%;">
                                        Process Return
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                                    <div style="font-size: 1.1rem; font-weight: 500; margin-bottom: 0.25rem;">No items scheduled to return today.</div>
                                    <div style="font-size: 0.85rem;">Check the "All Active Loans" tab to view items due on future dates.</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ── TAB 5: RECENTLY RETURNED ── --}}
        <div class="tab-pane" id="pane-recent" style="display: {{ $selectedTab === 'recent' ? 'block' : 'none' }};">
            <div class="table-responsive">
                <table class="data-table mobile-cards">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Borrower</th>
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Returned At</th>
                            <th>Condition</th>
                            <th>Inspected By</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentReturnedRequests as $rr)
                            <tr>
                                <td data-label="Request #">
                                    <strong>REQ-{{ str_pad($rr->id, 4, '0', STR_PAD_LEFT) }}</strong>
                                </td>
                                <td data-label="Borrower">
                                    <div style="font-weight: 600;">{{ $rr->user->full_name }}</div>
                                    <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                        {{ $rr->user->employee_id ?: $rr->user->email }}
                                    </div>
                                </td>
                                <td data-label="Equipment">{{ $rr->equipment->name }}</td>
                                <td data-label="Qty">{{ $rr->returned_quantity ?: $rr->quantity }}</td>
                                <td data-label="Returned At">
                                    <div style="font-size: 0.85rem; font-weight: 500;">
                                        {{ $rr->returned_at ? $rr->returned_at->format('M d, Y h:i A') : ($rr->actual_return_date ? $rr->actual_return_date->format('M d, Y') : '—') }}
                                    </div>
                                </td>
                                <td data-label="Condition">
                                    @php
                                        $cond = strtolower($rr->return_condition ?? 'good');
                                    @endphp
                                    @if($cond === 'good')
                                        <span class="badge badge-success" style="font-size: 0.75rem;">✓ Good</span>
                                    @elseif($cond === 'damaged')
                                        <span class="badge badge-error" style="font-size: 0.75rem;">⚠️ Damaged</span>
                                    @elseif($cond === 'missing')
                                        <span class="badge badge-warning" style="font-size: 0.75rem;">❌ Missing Parts</span>
                                    @else
                                        <span class="badge badge-general" style="font-size: 0.75rem;">{{ ucfirst($cond) }}</span>
                                    @endif
                                </td>
                                <td data-label="Inspected By">
                                    {{ $rr->confirmedBy->full_name ?? 'Staff' }}
                                </td>
                                <td data-label="Notes">
                                    <span style="font-size: 0.8rem; color: var(--color-text-muted);">
                                        {{ $rr->return_notes ?: '—' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                                    <div style="font-size: 1.1rem; font-weight: 500; margin-bottom: 0.25rem;">No returned equipment history yet.</div>
                                    <div style="font-size: 0.85rem;">Confirmed returns will appear here with condition records and staff inspection notes.</div>
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
    // Focus find borrower input if not scrolling to verification card
    document.addEventListener('DOMContentLoaded', function () {
        const verifyCard = document.getElementById('verification-card');
        if (verifyCard) {
            verifyCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
            const input = document.getElementById('find-borrower-input');
            if (input) {
                input.focus();
                input.select();
            }
        }
    });

    // Tab Switcher
    function switchDeskTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');

        const activeBtn = document.getElementById('tab-btn-' + tabId);
        const activePane = document.getElementById('pane-' + tabId);

        if (activeBtn) activeBtn.classList.add('active');
        if (activePane) activePane.style.display = 'block';
    }

    // Condition Box Interactive Styling
    function updateConditionStyle(radio) {
        document.querySelectorAll('.condition-badge-box').forEach(box => {
            box.style.border = '2px solid var(--color-border, #e2e8f0)';
            box.style.background = 'transparent';
        });

        const selectedBox = document.getElementById('cond-box-' + radio.value);
        if (selectedBox) {
            if (radio.value === 'good') {
                selectedBox.style.border = '2px solid #10b981';
                selectedBox.style.background = 'rgba(16, 185, 129, 0.08)';
            } else if (radio.value === 'damaged') {
                selectedBox.style.border = '2px solid #dc2626';
                selectedBox.style.background = 'rgba(239, 68, 68, 0.08)';
            } else if (radio.value === 'missing') {
                selectedBox.style.border = '2px solid #d97706';
                selectedBox.style.background = 'rgba(245, 158, 11, 0.08)';
            }
        }
    }

    // Partial Mode Toggle
    let isPartialMode = false;
    function togglePartialMode() {
        isPartialMode = !isPartialMode;
        const container = document.getElementById('partial-qty-container');
        const returnTypeInput = document.getElementById('return_type_input');
        const btnToggle = document.getElementById('btn-toggle-partial');
        const btnFull = document.getElementById('btn-full-return');

        if (isPartialMode) {
            if (container) container.style.display = 'block';
            if (returnTypeInput) returnTypeInput.value = 'partial';
            if (btnToggle) btnToggle.textContent = 'Switch to Full Return';
            if (btnFull) btnFull.textContent = '✓ Confirm Partial Return';
        } else {
            if (container) container.style.display = 'none';
            if (returnTypeInput) returnTypeInput.value = 'full';
            if (btnToggle) btnToggle.textContent = 'Partial Return...';
            if (btnFull) {
                const totalDue = document.getElementById('quantity_returned')?.max || '';
                btnFull.textContent = `✓ Confirm Return (${totalDue} unit(s))`;
            }
        }
    }

    // Submit Return Confirmation
    function submitReturn(mode) {
        const form = document.getElementById('confirm-return-form');
        if (!form) return;

        const returnTypeInput = document.getElementById('return_type_input');
        if (returnTypeInput) {
            returnTypeInput.value = isPartialMode ? 'partial' : mode;
        }

        const conditionRadio = form.querySelector('input[name="condition"]:checked');
        const condition = conditionRadio ? conditionRadio.value : 'good';

        let confirmMsg = 'Confirm this return in ' + condition.toUpperCase() + ' condition?';
        if (condition !== 'good') {
            confirmMsg = '⚠️ Warning: Item is marked ' + condition.toUpperCase() + '. It will NOT be restored to available inventory and will be marked for maintenance.\n\nProceed?';
        }

        if (confirm(confirmMsg)) {
            const btn = document.getElementById('btn-full-return');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Processing Return...';
            }
            form.submit();
        }
    }
</script>
@endpush
