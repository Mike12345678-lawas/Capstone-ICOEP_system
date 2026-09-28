<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Equipment Requests</h1>
        <p class="page-subtitle">Approve or reject equipment borrowing requests.</p>
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
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Requestor</th><th>Equipment</th><th>Qty</th><th>Date Needed</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($equipmentRequests as $req)
                    <tr>
                        <td style="font-weight: 600;">{{ $req->user->full_name }}</td>
                        <td>{{ $req->equipment->name }}</td>
                        <td>{{ $req->quantity }}</td>
                        <td>{{ $req->date_needed->format('M d, Y') }}</td>
                        <td>
                            @php $sb = match($req->status) { 'approved','returned' => 'badge-success', 'rejected','cancelled' => 'badge-error', 'borrowed' => 'badge-general', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td>
                            @if($req->status === 'pending')
                                <div class="btn-group">
                                    <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}">
                                        @csrf @method('PATCH')
                                        <input type="hidden" name="status" value="approved">
                                        <button class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}">
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
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No equipment requests found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $equipmentRequests->withQueryString()->links('partials.pagination') }}
    </div>
</div>
