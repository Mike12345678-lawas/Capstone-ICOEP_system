@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Equipment Requests</h1>
        <p class="page-subtitle">Approve, reject, and track equipment borrowing lifecycle.</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search requests..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/equipment/manage') }}" class="filter-btn {{ $statusFilter === 'pending' ? 'active' : '' }}">Pending</a>
                <a href="{{ url('/equipment/manage?status=approved') }}" class="filter-btn {{ $statusFilter === 'approved' ? 'active' : '' }}">Approved</a>
                <a href="{{ url('/equipment/manage?status=borrowed') }}" class="filter-btn {{ $statusFilter === 'borrowed' ? 'active' : '' }}">Borrowed</a>
                <a href="{{ url('/equipment/manage?status=returned') }}" class="filter-btn {{ $statusFilter === 'returned' ? 'active' : '' }}">Returned</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Equipment</th><th>Borrower</th><th>Qty</th><th>Date Needed</th><th>Return By</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td style="font-weight: 500;">{{ $req->equipment->name }}</td>
                        <td>{{ $req->user->full_name }}</td>
                        <td>{{ $req->quantity }}</td>
                        <td>{{ $req->date_needed->format('M d, Y') }}</td>
                        <td>{{ $req->expected_return_date->format('M d, Y') }}</td>
                        <td>
                            @php $badge = match($req->status) { 'approved','returned' => 'badge-success', 'rejected','cancelled' => 'badge-error', 'borrowed' => 'badge-general', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($req->status) }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                @if($req->status === 'pending')
                                    <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}" style="display:inline;">@csrf @method('PATCH')<input type="hidden" name="status" value="approved"><button type="submit" class="btn btn-primary btn-sm">Approve</button></form>
                                    <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}" style="display:inline;">@csrf @method('PATCH')<input type="hidden" name="status" value="rejected"><button type="submit" class="btn btn-danger btn-sm">Reject</button></form>
                                @elseif($req->status === 'approved')
                                    <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}" style="display:inline;">@csrf @method('PATCH')<input type="hidden" name="status" value="borrowed"><button type="submit" class="btn btn-secondary btn-sm">Mark Borrowed</button></form>
                                @elseif($req->status === 'borrowed')
                                    <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}" style="display:inline;">@csrf @method('PATCH')<input type="hidden" name="status" value="returned"><button type="submit" class="btn btn-primary btn-sm">Mark Returned</button></form>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 2rem;">No requests found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $requests->withQueryString()->links('partials.pagination') }}
    </div>
</div>
@endsection
