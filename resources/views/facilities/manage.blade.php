@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Facility Bookings</h1>
        <p class="page-subtitle">Approve, reject, or review facility reservation requests.</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search reservations..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/facilities/manage') }}" class="filter-btn {{ $statusFilter === 'pending' ? 'active' : '' }}">Pending</a>
                <a href="{{ url('/facilities/manage?status=approved') }}" class="filter-btn {{ $statusFilter === 'approved' ? 'active' : '' }}">Approved</a>
                <a href="{{ url('/facilities/manage?status=rejected') }}" class="filter-btn {{ $statusFilter === 'rejected' ? 'active' : '' }}">Rejected</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Facility</th><th>Requested By</th><th>Date & Time</th><th>Title</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($reservations as $res)
                    <tr>
                        <td style="font-weight: 500;">{{ $res->facility->name }}</td>
                        <td>{{ $res->user->full_name }}</td>
                        <td>{{ $res->reservation_date->format('M d, Y') }}<br><span class="text-muted" style="font-size:0.85em;">{{ \Carbon\Carbon::parse($res->time_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($res->time_end)->format('h:i A') }}</span></td>
                        <td>{{ $res->title }}</td>
                        <td>
                            @php $badge = match($res->status) { 'approved' => 'badge-success', 'rejected' => 'badge-error', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($res->status) }}</span>
                        </td>
                        <td>
                            @if($res->status === 'pending')
                            <div class="btn-group">
                                <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/status') }}" style="display:inline;">@csrf @method('PATCH')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit" class="btn btn-primary btn-sm">Approve</button>
                                </form>
                                <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/status') }}" style="display:inline;">@csrf @method('PATCH')
                                    <input type="hidden" name="status" value="rejected">
                                    <button type="submit" class="btn btn-danger btn-sm">Reject</button>
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
@endsection
