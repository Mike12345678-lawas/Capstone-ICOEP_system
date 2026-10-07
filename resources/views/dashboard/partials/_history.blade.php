@php
    $isEquip = $historyTab === 'equipment';
    $base    = '/dashboard?section=history&tab=' . $historyTab;
    $statusOptions = $isEquip
        ? ['pending' => 'Pending', 'approved' => 'Approved', 'borrowed' => 'Borrowed', 'returned' => 'Returned', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled']
        : ['upcoming' => 'Upcoming', 'completed' => 'Completed', 'pending' => 'Pending', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled'];
    $hasFilters = $search || $statusFilter || $dateFrom || $dateTo;
@endphp

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">History</h1>
        <p class="page-subtitle">
            Complete history of {{ $isEquip ? 'borrowed equipment' : 'booked facilities' }} for <strong>{{ ($historyScope ?? 'all') === 'my' ? 'your account' : 'all users' }}</strong>.
        </p>
    </div>
</div>

<div class="section-tabs animate-fade-in-up" role="tablist">
    <a href="{{ url('/dashboard?section=history&tab=equipment' . (($historyScope ?? '') === 'my' ? '&scope=my' : '')) }}" class="section-tab {{ $isEquip ? 'active' : '' }}" role="tab" id="history-tab-equipment">📦 Borrowed Equipment</a>
    <a href="{{ url('/dashboard?section=history&tab=facilities' . (($historyScope ?? '') === 'my' ? '&scope=my' : '')) }}" class="section-tab {{ !$isEquip ? 'active' : '' }}" role="tab" id="history-tab-facilities">🏢 Booked Facilities</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="history-filter-form">
            <input type="hidden" name="section" value="history">
            <input type="hidden" name="tab" value="{{ $historyTab }}">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" id="history-search" class="search-input"
                       placeholder="Search by user, {{ $isEquip ? 'equipment or purpose' : 'facility or event title' }}..."
                       value="{{ $search }}">
            </div>
            <div class="filter-field">
                <label class="form-label" for="history-scope" style="font-size: .75rem;">View</label>
                <select name="scope" id="history-scope" class="form-control" onchange="this.form.submit()">
                    <option value="all" @selected(($historyScope ?? 'all') === 'all')>All Users</option>
                    <option value="my" @selected(($historyScope ?? 'all') === 'my')>My History</option>
                </select>
            </div>
            <div class="filter-field">
                <label class="form-label" for="history-status" style="font-size: .75rem;">Status</label>
                <select name="status" id="history-status" class="form-control">
                    <option value="">All statuses</option>
                    @foreach($statusOptions as $val => $label)
                        <option value="{{ $val }}" @selected($statusFilter === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="filter-field">
                <label class="form-label" for="history-from" style="font-size: .75rem;">From</label>
                <input type="date" name="from" id="history-from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="filter-field">
                <label class="form-label" for="history-to" style="font-size: .75rem;">To</label>
                <input type="date" name="to" id="history-to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary btn-sm" id="history-filter-btn">Filter</button>
                @if($hasFilters)
                    <a href="{{ url($base) }}" class="btn btn-secondary btn-sm">Clear</a>
                @endif
            </div>
        </form>

        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    @if($isEquip)
                        <tr>
                            @if($canViewAll)<th>User</th>@endif
                            <th>Equipment</th>
                            <th>Qty</th>
                            <th>Borrow Period</th>
                            <th>Returned</th>
                            <th>Status</th>
                            <th>Photo</th>
                        </tr>
                    @else
                        <tr>
                            @if($canViewAll)<th>User</th>@endif
                            <th>Facility</th>
                            <th>Event</th>
                            <th>Schedule</th>
                            <th>Status</th>
                        </tr>
                    @endif
                </thead>
                <tbody>
                @forelse($historyItems as $item)
                    <tr>
                        @if($canViewAll)
                            <td data-label="User">
                                <div style="font-weight: 600;">{{ $item->user->full_name ?? 'Unknown user' }}</div>
                                <div class="text-muted" style="font-size: .75rem;">{{ $item->user->employee_id ?? $item->user->email ?? '' }}</div>
                            </td>
                        @endif

                        @if($isEquip)
                            <td data-label="Equipment">
                                <div style="font-weight: 600;">{{ $item->equipment->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size: .75rem;">{{ Str::limit($item->purpose, 40) }}</div>
                            </td>
                            <td data-label="Qty">{{ $item->quantity }}</td>
                            <td data-label="Borrow Period" style="font-size: .85rem;">
                                {{ $item->date_needed?->format('M d, Y') ?? '—' }}
                                <span class="text-muted">→</span>
                                {{ $item->expected_return_date?->format('M d, Y') ?? '—' }}
                            </td>
                            <td data-label="Returned" style="font-size: .85rem;">
                                @if($item->status === 'returned')
                                    {{ $item->returned_at?->format('M d, Y') ?? $item->actual_return_date?->format('M d, Y') }}
                                    @if($item->return_condition)
                                        <div><span class="badge {{ $item->return_condition === 'good' ? 'badge-success' : 'badge-error' }}" style="font-size: .65rem;">{{ ucfirst($item->return_condition) }}</span></div>
                                    @endif
                                    @if($item->days_late > 0)
                                        <div style="color: #dc2626; font-size: .75rem;">{{ $item->days_late }}d late</div>
                                    @endif
                                @elseif($item->status === 'borrowed' && $item->return_requested_at)
                                    <span class="badge badge-warning" style="font-size: .65rem;">Waiting for check</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td data-label="Status"><x-status-badge :status="$item->status" /></td>
                            <td data-label="Photo">
                                @if($item->return_photo_url)
                                    <a href="{{ $item->return_photo_url }}" target="_blank" rel="noopener" title="View return photo">
                                        <img src="{{ $item->return_photo_url }}" alt="Return photo" class="photo-thumb">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @else
                            @php $state = $item->usage_state; @endphp
                            <td data-label="Facility">
                                <div style="font-weight: 600;">{{ $item->facility->name ?? '—' }}</div>
                                <div class="text-muted" style="font-size: .75rem;">📍 {{ $item->facility->location ?? '' }}</div>
                            </td>
                            <td data-label="Event">{{ $item->title }}</td>
                            <td data-label="Schedule" style="font-size: .85rem;">
                                {{ $item->reservation_date->format('M d, Y') }}<br>
                                <span class="text-muted">{{ \Carbon\Carbon::parse($item->time_start)->format('g:i A') }} – {{ \Carbon\Carbon::parse($item->time_end)->format('g:i A') }}</span>
                            </td>
                            <td data-label="Status">
                                @if($state === 'occupied')
                                    <span class="badge badge-error"><span class="occupancy-dot occupied"></span>Occupied</span>
                                @else
                                    <x-status-badge :status="$state" />
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($isEquip ? 6 : 4) + ($canViewAll ? 1 : 0) }}" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                            {{ $hasFilters ? 'No records match your filters.' : 'No history yet.' }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $historyItems->withQueryString()->links('partials.pagination') }}
    </div>
</div>
