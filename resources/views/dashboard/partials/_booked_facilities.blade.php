@php
    $currentScope = $scope ?? 'my';
@endphp
<div class="page-header animate-fade-in-up">
    <div class="page-header-title-wrapper">
        <h1 class="page-title">Booked Facilities</h1>
        <p class="page-subtitle">Facility bookings. A facility is <strong>Occupied</strong> during booked time and automatically becomes <strong>Open</strong> once completed or ended.</p>
    </div>
    <div class="header-actions header-actions-mobile">
        <div class="scope-segmented-control" role="group" aria-label="Bookings scope filter">
            <a href="{{ url('/dashboard?section=booked-facilities&scope=my') }}" class="filter-btn scope-pill {{ $currentScope === 'my' ? 'active' : '' }}">
                <span>👤</span> My Bookings
            </a>
            <a href="{{ url('/dashboard?section=booked-facilities&scope=all') }}" class="filter-btn scope-pill {{ $currentScope === 'all' ? 'active' : '' }}">
                <span>🌐</span> All Campus Bookings
            </a>
        </div>
        <div class="btn-row-mobile">
            <a href="{{ url('/dashboard?section=history&tab=facilities') }}" class="btn btn-secondary btn-sm">History</a>
            <a href="{{ url('/dashboard?section=facilities') }}" class="btn btn-primary btn-sm">+ Reserve Facility</a>
        </div>
    </div>
</div>

{{-- ─── Current & upcoming bookings ─── --}}
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem;">
        <h3 class="card-title">
            {{ $currentScope === 'all' ? 'All Campus Active Bookings' : 'My Booked Facilities' }}
        </h3>
        <span class="badge badge-general">{{ $myReservationsList->total() }} record(s)</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1.25rem;">
            <input type="hidden" name="section" value="booked-facilities">
            <input type="hidden" name="scope" value="{{ $currentScope }}">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by facility, event, reserver..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=booked-facilities&scope=' . $currentScope) }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All Active</a>
                <a href="{{ url('/dashboard?section=booked-facilities&scope=' . $currentScope . '&status=approved') }}" class="filter-btn {{ ($statusFilter ?? '') === 'approved' ? 'active' : '' }}">Approved</a>
                <a href="{{ url('/dashboard?section=booked-facilities&scope=' . $currentScope . '&status=pending') }}" class="filter-btn {{ ($statusFilter ?? '') === 'pending' ? 'active' : '' }}">Pending</a>
            </div>
        </form>

        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr>
                        @if($currentScope === 'all')
                            <th>Reserved By</th>
                        @endif
                        <th>Facility</th>
                        <th>Event &amp; Purpose</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($myReservationsList as $res)
                    @php
                        $state = $res->usage_state;
                        $isMine = ($res->user_id === auth()->id());
                        $canManage = $isMine || auth()->user()->hasPermission('facility.approve');
                    @endphp
                    <tr>
                        @if($currentScope === 'all')
                            <td data-label="Reserved By">
                                <div style="font-weight: 600; color: var(--color-text-primary);">{{ $res->user?->full_name ?? 'Deleted User' }}</div>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                    {{ $res->user?->role?->name ?? 'User' }} &bull; {{ $res->user?->employee_id ?? '—' }}
                                </div>
                            </td>
                        @endif
                        <td data-label="Facility">
                            <div style="font-weight: 600; color: var(--color-text-primary);">{{ $res->facility->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-text-muted);">📍 {{ $res->facility->location }}</div>
                        </td>
                        <td data-label="Event &amp; Purpose">
                            <div style="font-weight: 500;">{{ $res->title }}</div>
                            @if($res->description)
                                <div style="font-size: 0.8rem; color: var(--color-text-muted);">{{ Str::limit($res->description, 50) }}</div>
                            @endif
                            @if($res->remarks)
                                <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem;"><em>Note: {{ $res->remarks }}</em></div>
                            @endif
                        </td>
                        <td data-label="Schedule">
                            <div style="font-weight: 500;">📅 {{ $res->reservation_date->format('M d, Y') }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-text-muted);">
                                ⏰ {{ \Carbon\Carbon::parse($res->time_start)->format('g:i A') }} – {{ \Carbon\Carbon::parse($res->time_end)->format('g:i A') }}
                            </div>
                        </td>
                        <td data-label="Status">
                            @if($state === 'occupied')
                                <span class="badge badge-error"><span class="occupancy-dot occupied"></span>Occupied</span>
                                <div class="text-muted" style="font-size: .75rem; margin-top: .25rem;">Opens at {{ $res->ends_at->format('g:i A') }}</div>
                            @elseif($state === 'upcoming')
                                <x-status-badge status="approved" />
                                <div class="text-muted" style="font-size: .75rem; margin-top: .25rem;">Starts {{ $res->starts_at->diffForHumans() }}</div>
                            @else
                                <x-status-badge :status="$res->status" />
                            @endif
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                @if($res->status === 'approved' && $canManage)
                                    <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/complete') }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" style="font-size: 0.75rem;"
                                            onclick="return confirm('Free this facility now and make it open?')">
                                            Make Facility Open
                                        </button>
                                    </form>
                                @elseif($res->status === 'pending')
                                    @if($isMine)
                                        <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/cancel') }}" style="margin:0;">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; color: var(--color-error);"
                                                onclick="return confirm('Cancel this facility reservation?')">Cancel</button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->hasPermission('facility.approve'))
                                        <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/status') }}" style="margin:0;">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-success btn-sm" style="font-size: 0.75rem;">Approve</button>
                                        </form>
                                    @endif
                                @endif
                                <a href="{{ url('/facilities/' . $res->facility_id) }}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">View</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $currentScope === 'all' ? 6 : 5 }}" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                            <div style="font-size: 1rem; font-weight: 500; margin-bottom: 0.5rem;">
                                {{ $search || $statusFilter ? 'No bookings found matching your criteria.' : ($currentScope === 'all' ? 'No active facility bookings across campus.' : 'You have no current facility bookings.') }}
                            </div>
                            @if($search || $statusFilter)
                                <a href="{{ url('/dashboard?section=booked-facilities&scope=' . $currentScope) }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                            @else
                                <a href="{{ url('/dashboard?section=facilities') }}" class="btn btn-primary btn-sm">Browse Facilities</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $myReservationsList->withQueryString()->links('partials.pagination') }}
    </div>
</div>

{{-- ─── Completed bookings (facility is open again) ─── --}}
<div class="card animate-fade-in-up" style="animation-delay: 200ms; margin-top: 1.5rem;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h3 class="card-title">{{ $currentScope === 'all' ? 'All Completed Campus Bookings' : 'My Completed Bookings' }}</h3>
        <span class="badge badge-success">{{ $completedReservations->total() }}</span>
    </div>
    <div class="card-body">
        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr>
                        @if($currentScope === 'all')
                            <th>Reserved By</th>
                        @endif
                        <th>Facility</th>
                        <th>Event</th>
                        <th>Schedule</th>
                        <th>Facility Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($completedReservations as $res)
                    <tr>
                        @if($currentScope === 'all')
                            <td data-label="Reserved By">
                                <div style="font-weight: 600;">{{ $res->user?->full_name ?? 'Deleted User' }}</div>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $res->user?->role?->name ?? 'User' }} &bull; {{ $res->user?->employee_id ?? '—' }}</div>
                            </td>
                        @endif
                        <td data-label="Facility">
                            <div style="font-weight: 600;">{{ $res->facility->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-text-muted);">📍 {{ $res->facility->location }}</div>
                        </td>
                        <td data-label="Event">{{ $res->title }}</td>
                        <td data-label="Schedule" style="font-size: .85rem;">
                            {{ $res->reservation_date->format('M d, Y') }}<br>
                            <span class="text-muted">{{ \Carbon\Carbon::parse($res->time_start)->format('g:i A') }} – {{ \Carbon\Carbon::parse($res->time_end)->format('g:i A') }}</span>
                        </td>
                        <td data-label="Facility Status">
                            <span class="badge badge-success"><span class="occupancy-dot"></span>Open</span>
                            <div class="text-muted" style="font-size: .75rem; margin-top: .25rem;">Freed / Ended {{ $res->ends_at->diffForHumans() }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $currentScope === 'all' ? 5 : 4 }}" class="text-center text-muted" style="padding: 2rem 1rem;">
                            {{ $currentScope === 'all' ? 'No completed bookings recorded yet.' : 'No completed bookings yet.' }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $completedReservations->withQueryString()->links('partials.pagination') }}
    </div>
</div>
