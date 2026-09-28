<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Facility Bookings</h1>
        <p class="page-subtitle">Approve or reject facility reservation requests.</p>
    </div>
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
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Requestor</th><th>Facility</th><th>Date</th><th>Purpose</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($reservations as $res)
                    <tr>
                        <td style="font-weight: 600;">{{ $res->user->full_name }}</td>
                        <td>{{ $res->facility->name }}</td>
                        <td>{{ $res->reservation_date->format('M d, Y') }}</td>
                        <td>{{ Str::limit($res->title, 40) }}</td>
                        <td>
                            @php $sb = match($res->status) { 'approved' => 'badge-success', 'rejected','cancelled' => 'badge-error', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($res->status) }}</span>
                        </td>
                        <td>
                            @if($res->status === 'pending')
                                <div class="btn-group">
                                    <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/status') }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/status') }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="rejected">
                                        <button class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No reservations found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $reservations->withQueryString()->links('partials.pagination') }}
    </div>
</div>
