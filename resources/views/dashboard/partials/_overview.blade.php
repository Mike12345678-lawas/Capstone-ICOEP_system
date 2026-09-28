<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Welcome back, {{ auth()->user()->first_name }}!</h1>
        <p class="page-subtitle">Here's an overview of campus operations and your activity.</p>
    </div>
</div>

@if(isset($stats))
<div class="grid grid-4 animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['total_users'] }}</span>
            <span class="stat-label">Total Users</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['pending_reservations'] }}</span>
            <span class="stat-label">Pending Bookings</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['pending_borrows'] }}</span>
            <span class="stat-label">Pending Equipment</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(6, 70, 99, 0.1); color: #0369a1;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
        </div>
        <div class="stat-content">
            <span class="stat-value">{{ $stats['upcoming_events'] }}</span>
            <span class="stat-label">Active Events</span>
        </div>
    </div>
</div>
@endif

<div class="grid grid-2 animate-fade-in-up" style="animation-delay: 200ms;">
    {{-- Recent Reservations --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Recent Reservations</h3>
            <a href="{{ url('/dashboard?section=facilities') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead><tr><th>Facility</th><th>Date</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($myReservations as $res)
                    <tr>
                        <td style="font-weight: 600;">{{ $res->facility->name }}</td>
                        <td>{{ $res->reservation_date->format('M d, Y') }}</td>
                        <td>
                            @php $sb = match($res->status) { 'approved' => 'badge-success', 'rejected','cancelled' => 'badge-error', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($res->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted" style="padding: 1.5rem;">No reservations yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Equipment Requests --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Recent Equipment Requests</h3>
            <a href="{{ url('/dashboard?section=equipment') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead><tr><th>Equipment</th><th>Qty</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($myBorrows as $borrow)
                    <tr>
                        <td style="font-weight: 600;">{{ $borrow->equipment->name }}</td>
                        <td>{{ $borrow->quantity }}</td>
                        <td>
                            @php $sb = match($borrow->status) { 'approved','returned' => 'badge-success', 'rejected','cancelled' => 'badge-error', 'borrowed' => 'badge-general', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($borrow->status) }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted" style="padding: 1.5rem;">No equipment requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="grid grid-2 animate-fade-in-up" style="animation-delay: 300ms;">
    {{-- Upcoming Events --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upcoming Events</h3>
            <a href="{{ url('/dashboard?section=events') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead><tr><th>Event</th><th>Date</th><th>Type</th></tr></thead>
                <tbody>
                @forelse($upcomingEvents as $event)
                    <tr>
                        <td><a href="{{ url('/events/' . $event->id) }}" style="font-weight: 600; color: var(--color-brand-600);">{{ $event->title }}</a></td>
                        <td>{{ $event->event_date->format('M d, Y') }}</td>
                        <td><span class="badge badge-general">{{ ucfirst($event->event_type) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted" style="padding: 1.5rem;">No upcoming events.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Latest Announcements --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Latest Announcements</h3>
            <a href="{{ url('/dashboard?section=announcements') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table">
                <thead><tr><th>Title</th><th>Priority</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($latestAnnouncements as $ann)
                    <tr>
                        <td>
                            <a href="{{ url('/announcements/' . $ann->id) }}" style="font-weight: 600; color: var(--color-brand-600);">
                                @if($ann->is_pinned) <span class="badge badge-warning" style="margin-right:4px;">Pinned</span> @endif{{ $ann->title }}
                            </a>
                        </td>
                        <td>
                            @php $bc = match($ann->priority) { 'urgent' => 'badge-error', 'important' => 'badge-warning', default => 'badge-general' }; @endphp
                            <span class="badge {{ $bc }}">{{ ucfirst($ann->priority) }}</span>
                        </td>
                        <td>{{ $ann->created_at->format('M d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted" style="padding: 1.5rem;">No announcements.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
