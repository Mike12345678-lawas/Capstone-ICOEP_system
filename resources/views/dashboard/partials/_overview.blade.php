<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Welcome back, {{ auth()->user()->first_name }}!</h1>
        <p class="page-subtitle">Here's an overview of campus operations and your activity.</p>
    </div>
</div>

@if(isset($needsActionBookings) || isset($needsActionEquipment))
<div class="card animate-fade-in-up" style="animation-delay: 50ms; border-left: 4px solid #f59e0b;">
    <div class="card-header" style="background: rgba(245,158,11,.06);">
        <h3 class="card-title" style="color:#b45309;">⚡ Needs Your Action</h3>
    </div>
    <div class="card-body" style="padding: 0;">
        <div class="grid grid-2" style="gap:0;">
            <div class="action-box-left" style="padding:1rem;">
                <p style="font-weight:700;font-size:.85rem;margin:0 0 .5rem;">Pending Facility Bookings</p>
                @forelse(($needsActionBookings ?? collect()) as $r)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.35rem 0;border-bottom:1px solid var(--color-border,#e2e8f0);font-size:.85rem;">
                    <span>{{ $r->user->full_name }} — <strong>{{ $r->facility->name }}</strong> ({{ $r->reservation_date->format('M d') }})</span>
                    <a href="{{ url('/dashboard?section=manage-bookings&status=pending') }}" class="btn btn-secondary btn-sm" style="font-size:.75rem;padding:.2rem .5rem;">Review</a>
                </div>
                @empty
                <p class="text-muted" style="font-size:.85rem;margin:0;">None pending.</p>
                @endforelse
                @if(($needsActionBookings ?? collect())->count() >= 5)
                <a href="{{ url('/dashboard?section=manage-bookings&status=pending') }}" style="font-size:.8rem;color:var(--color-brand-600);display:inline-block;margin-top:.5rem;">View all →</a>
                @endif
            </div>
            <div class="action-box-right" style="padding:1rem;">
                <p style="font-weight:700;font-size:.85rem;margin:0 0 .5rem;">Pending Equipment Requests</p>
                @forelse(($needsActionEquipment ?? collect()) as $r)
                <div style="display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;padding:.35rem 0;border-bottom:1px solid var(--color-border,#e2e8f0);font-size:.85rem;">
                    <span>{{ $r->user->full_name }} — <strong>{{ $r->equipment->name }}</strong> ×{{ $r->quantity }}</span>
                    <a href="{{ url('/dashboard?section=manage-equipment&status=pending') }}" class="btn btn-secondary btn-sm" style="font-size:.75rem;padding:.2rem .5rem;">Review</a>
                </div>
                @empty
                <p class="text-muted" style="font-size:.85rem;margin:0;">None pending.</p>
                @endforelse
                @if(($needsActionEquipment ?? collect())->count() >= 5)
                <a href="{{ url('/dashboard?section=manage-equipment&status=pending') }}" style="font-size:.8rem;color:var(--color-brand-600);display:inline-block;margin-top:.5rem;">View all →</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endif

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
            <a href="{{ url('/dashboard?section=manage-bookings&status=pending') }}" style="text-decoration:none;color:inherit;">
                <span class="stat-value">{{ $stats['pending_reservations'] }}</span>
                <span class="stat-label">Pending Bookings</span>
            </a>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.1); color: #0ea5e9;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
        </div>
        <div class="stat-content">
            <a href="{{ url('/dashboard?section=manage-equipment&status=pending') }}" style="text-decoration:none;color:inherit;">
                <span class="stat-value">{{ $stats['pending_borrows'] }}</span>
                <span class="stat-label">Pending Equipment</span>
            </a>
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
            <a href="{{ url('/dashboard?section=booked-facilities') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table mobile-cards">
                <thead><tr><th>Facility</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($myReservations as $res)
                    <tr>
                        <td data-label="Facility" style="font-weight: 600;">{{ $res->facility->name }}</td>
                        <td data-label="Date">{{ $res->reservation_date->format('M d, Y') }}</td>
                        <td data-label="Status">
                            <x-status-badge :status="$res->status" />
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            @if($res->status === 'pending')
                                <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/cancel') }}" style="margin:0;">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-secondary btn-sm" style="font-size:.75rem; width: 100%;"
                                        onclick="return confirm('Cancel this reservation?')">Cancel</button>
                                </form>
                            @elseif($res->status === 'approved')
                                <form method="POST" action="{{ url('/facility-reservations/' . $res->id . '/complete') }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-sm" style="font-size:.75rem; width: 100%;"
                                        onclick="return confirm('Free this facility now and mark it open?')">Make Open</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted" style="padding: 2rem 1rem;">
                            <p style="margin-bottom: .5rem;">No reservations yet.</p>
                            <a href="{{ url('/facilities') }}" class="btn btn-secondary btn-sm">Browse Facilities</a>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent Equipment Requests --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">My Recent Equipment Requests</h3>
            <a href="{{ url('/dashboard?section=borrowed-equipment') }}" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="card-body" style="padding: 0;">
            <table class="data-table mobile-cards">
                <thead><tr><th>Equipment</th><th>Qty</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                @forelse($myBorrows as $borrow)
                    <tr>
                        <td data-label="Equipment" style="font-weight: 600;">{{ $borrow->equipment->name }}</td>
                        <td data-label="Qty">{{ $borrow->quantity }} unit(s)</td>
                        <td data-label="Status">
                            <x-status-badge :status="$borrow->status" />
                            @if(in_array($borrow->status, ['borrowed', 'approved']) && $borrow->return_requested_at)
                                <div>
                                    <span class="badge badge-warning" style="font-size:.7rem; margin-top:.25rem; display:inline-block;">Waiting for admin check</span>
                                </div>
                            @endif
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            @if($borrow->status === 'pending')
                                <form method="POST" action="{{ url('/equipment-requests/' . $borrow->id . '/cancel') }}" style="margin:0;">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-secondary btn-sm" style="font-size:.75rem; width: 100%;"
                                        onclick="return confirm('Cancel this request?')">Cancel</button>
                                </form>
                            @elseif(in_array($borrow->status, ['borrowed', 'approved']))
                                @if(is_null($borrow->return_requested_at))
                                    <a href="{{ url('/dashboard?section=borrowed-equipment&return=' . $borrow->id) }}" class="btn btn-primary btn-sm" style="font-size:.75rem; width: 100%;">Return Equipment</a>
                                @else
                                    <form method="POST" action="{{ url('/equipment-requests/' . $borrow->id . '/cancel-return-request') }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm" style="font-size:.75rem; width: 100%;" title="Cancel return check notification">Cancel Check</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted" style="padding: 2rem 1rem;">
                            <p style="margin-bottom: .5rem;">No equipment requests yet.</p>
                            <a href="{{ url('/equipment') }}" class="btn btn-secondary btn-sm">Browse Equipment</a>
                        </td>
                    </tr>
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
            <table class="data-table mobile-cards">
                <thead><tr><th>Event</th><th>Date</th><th>Type</th></tr></thead>
                <tbody>
                @forelse($upcomingEvents as $event)
                    <tr>
                        <td data-label="Event"><a href="{{ url('/events/' . $event->id) }}" style="font-weight: 600; color: var(--color-brand-600);">{{ $event->title }}</a></td>
                        <td data-label="Date">{{ $event->event_date->format('M d, Y') }}</td>
                        <td data-label="Type"><span class="badge badge-general">{{ ucfirst($event->event_type) }}</span></td>
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
            <table class="data-table mobile-cards">
                <thead><tr><th>Title</th><th>Priority</th><th>Date</th></tr></thead>
                <tbody>
                @forelse($latestAnnouncements as $ann)
                    <tr>
                        <td data-label="Title">
                            <a href="{{ url('/announcements/' . $ann->id) }}" style="font-weight: 600; color: var(--color-brand-600);">
                                @if($ann->is_pinned) <span class="badge badge-warning" style="margin-right:4px;">Pinned</span> @endif{{ $ann->title }}
                            </a>
                        </td>
                        <td data-label="Priority">
                            @php $bc = match($ann->priority) { 'urgent' => 'badge-error', 'important' => 'badge-warning', default => 'badge-general' }; @endphp
                            <span class="badge {{ $bc }}">{{ ucfirst($ann->priority) }}</span>
                        </td>
                        <td data-label="Date">{{ $ann->created_at->format('M d') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="text-center text-muted" style="padding: 1.5rem;">No announcements.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
