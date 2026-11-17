<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Events</h1>
        <p class="page-subtitle">Discover and register for upcoming campus events.</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->hasRole('Administrator'))
            <a href="{{ url('/events/create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Event
            </a>
        @elseif(auth()->user()->hasPermission('event.create'))
            <a href="{{ url('/events/create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Request Event
            </a>
        @endif
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="events">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search events..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=events') }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</a>
                @if(auth()->user()->hasRole('Administrator') || auth()->user()->hasPermission('event.create'))
                    <a href="{{ url('/dashboard?section=events&status=pending') }}" class="filter-btn {{ ($statusFilter ?? '') === 'pending' ? 'active' : '' }}">
                        Pending
                        @if(($pendingEventsCount ?? 0) > 0)
                            <span class="badge" style="background: #f59e0b; color: #ffffff; margin-left: 4px; font-size: 0.7rem; padding: 1px 6px; border-radius: 9999px;">{{ $pendingEventsCount }}</span>
                        @endif
                    </a>
                @endif
                <a href="{{ url('/dashboard?section=events&status=upcoming') }}" class="filter-btn {{ ($statusFilter ?? '') === 'upcoming' ? 'active' : '' }}">Upcoming</a>
                <a href="{{ url('/dashboard?section=events&status=ongoing') }}" class="filter-btn {{ ($statusFilter ?? '') === 'ongoing' ? 'active' : '' }}">Ongoing</a>
                <a href="{{ url('/dashboard?section=events&status=completed') }}" class="filter-btn {{ ($statusFilter ?? '') === 'completed' ? 'active' : '' }}">Completed</a>
            </div>
            @if(auth()->user()->hasRole('Administrator'))
            <div class="filter-group" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.375rem;">
                <a href="{{ url('/dashboard?section=events' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ empty($audienceFilter) ? 'active' : '' }}">All Audiences</a>
                <a href="{{ url('/dashboard?section=events&audience=faculty' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'faculty' ? 'active' : '' }}">Faculty</a>
                <a href="{{ url('/dashboard?section=events&audience=staff' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'staff' ? 'active' : '' }}">Staff</a>
                <a href="{{ url('/dashboard?section=events&audience=student' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'student' ? 'active' : '' }}">Students</a>
                <a href="{{ url('/dashboard?section=events&audience=bsit' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'bsit' ? 'active' : '' }}">BSIT</a>
                <a href="{{ url('/dashboard?section=events&audience=bshm' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'bshm' ? 'active' : '' }}">BSHM</a>
                <a href="{{ url('/dashboard?section=events&audience=bsba-fm' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'bsba-fm' ? 'active' : '' }}">BSBA-FM</a>
                <a href="{{ url('/dashboard?section=events&audience=bsed' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'bsed' ? 'active' : '' }}">BSED</a>
                <a href="{{ url('/dashboard?section=events&audience=beed' . ($statusFilter ? '&status=' . $statusFilter : '')) }}" class="filter-btn {{ ($audienceFilter ?? '') === 'beed' ? 'active' : '' }}">BEED</a>
            </div>
            @endif
        </form>

        @if(($statusFilter ?? '') === 'pending' && (auth()->user()->hasRole('Administrator') || auth()->user()->hasPermission('event.manage')))
        <div style="background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 8px; padding: 0.75rem 1rem; margin-bottom: 1rem; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.5rem;">
            <div style="display: flex; align-items: center; gap: 0.5rem; color: #92400e; font-size: 0.875rem;">
                <span style="font-size: 1.15rem;">⏳</span>
                <span><strong>Event Requests Awaiting Review:</strong> Faculty and staff event submissions pending administrative approval. You can approve or reject directly from the table, or click View for full details.</span>
            </div>
            <span class="badge" style="background: #f59e0b; color: #ffffff; font-weight: 700; padding: 3px 8px; border-radius: 9999px;">{{ $pendingEventsCount ?? $events->total() }} Pending</span>
        </div>
        @endif

        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr><th>Title</th><th>Date</th><th>Venue</th><th>Type</th><th>Audience</th><th>Registered</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td data-label="Title" style="font-weight: 600;">
                            <div>{{ $event->title }}</div>
                            @if($event->status === 'pending' && $event->organizer)
                                <div style="font-size: 0.75rem; color: var(--color-surface-600); font-weight: 400; margin-top: 2px;">
                                    Requested by: <strong>{{ $event->organizer->full_name }}</strong>
                                </div>
                            @endif
                        </td>
                        <td data-label="Date">{{ $event->event_date->format('M d, Y') }}</td>
                        <td data-label="Venue">{{ $event->venue ?: '—' }}</td>
                        <td data-label="Type"><span class="badge badge-general">{{ ucfirst($event->event_type) }}</span></td>
                        <td data-label="Audience">
                            @php $auds = $event->getTargetAudiences(); @endphp
                            @if(in_array('all', $auds, true) || empty($auds))
                                <span class="badge badge-general">All Campus</span>
                            @else
                                <div style="display: flex; flex-wrap: wrap; gap: 0.25rem;">
                                    @foreach($auds as $aud)
                                        @if($aud === 'faculty')
                                            <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.3); font-weight: 600;">Faculty</span>
                                        @elseif($aud === 'staff')
                                            <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3); font-weight: 600;">Staff</span>
                                        @elseif($aud === 'student')
                                            <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.3); font-weight: 600;">Students</span>
                                        @elseif(in_array($aud, ['bsit', 'bshm', 'bsba-fm', 'bsed', 'beed']))
                                            <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #047857; border: 1px solid rgba(16, 185, 129, 0.3); font-weight: 600;">{{ \App\Models\Event::AUDIENCES[$aud] ?? strtoupper($aud) }}</span>
                                        @else
                                            <span class="badge badge-general">{{ ucfirst($aud) }}</span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td data-label="Registered">{{ $event->registrations_count }}{{ $event->max_participants > 0 ? '/' . $event->max_participants : '' }}</td>
                        <td data-label="Status">
                            <x-status-badge :status="$event->status" />
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            @php
                                $canDelete = auth()->check() && (auth()->user()->hasRole('Administrator') || ($event->organizer_id === auth()->id() && in_array($event->status, ['pending', 'rejected'], true)));
                            @endphp
                            @if(($event->status === 'pending') && (auth()->user()->hasRole('Administrator') || auth()->user()->hasPermission('event.manage')))
                                <div style="display: flex; gap: 0.35rem; align-items: center; justify-content: flex-end; flex-wrap: wrap;">
                                    <form method="POST" action="{{ url('/events/' . $event->id . '/approve') }}" style="display: inline; margin: 0;" onsubmit="return confirm('Approve event request: {{ addslashes($event->title) }}?');">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-primary btn-sm" style="background: #10b981; border-color: #10b981; padding: 0.3rem 0.65rem; font-size: 0.75rem;" title="Approve event request">
                                            ✓ Approve
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: #ef4444; padding: 0.3rem 0.65rem; font-size: 0.75rem;" title="Reject event request" onclick="openDashboardEventRejectModal({{ $event->id }}, '{{ addslashes($event->title) }}')">
                                        ✕ Reject
                                    </button>
                                    @if($canDelete)
                                        <form method="POST" action="{{ url('/events/' . $event->id) }}" style="display: inline; margin: 0;" onsubmit="return confirm('Delete event: {{ addslashes($event->title) }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: #ef4444; padding: 0.3rem 0.65rem; font-size: 0.75rem;" title="Delete event">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ url('/events/' . $event->id) }}" class="btn btn-secondary btn-sm" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;">
                                        View
                                    </a>
                                </div>
                            @else
                                <div style="display: flex; gap: 0.35rem; align-items: center; justify-content: flex-end; flex-wrap: wrap;">
                                    @if($canDelete)
                                        <form method="POST" action="{{ url('/events/' . $event->id) }}" style="display: inline; margin: 0;" onsubmit="return confirm('Delete event: {{ addslashes($event->title) }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-secondary btn-sm" style="color: #ef4444; border-color: #ef4444; padding: 0.3rem 0.65rem; font-size: 0.75rem;" title="Delete event">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                    <a href="{{ url('/events/' . $event->id) }}" class="btn btn-secondary btn-sm" style="{{ $canDelete ? 'padding: 0.3rem 0.65rem; font-size: 0.75rem;' : 'width: 100%;' }}">View Details</a>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 2rem 1rem;">
                            <p style="margin-bottom: .5rem;">No events found.</p>
                            @if(request('search') || request('status'))
                                <a href="{{ url('/dashboard?section=events') }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                            @elseif(auth()->user()->hasRole('Administrator'))
                                <a href="{{ url('/events/create') }}" class="btn btn-primary btn-sm">Create Event</a>
                            @elseif(auth()->user()->hasPermission('event.create'))
                                <a href="{{ url('/events/create') }}" class="btn btn-primary btn-sm">Request Event</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $events->withQueryString()->links('partials.pagination') }}
    </div>
</div>

@if(auth()->user()->hasRole('Administrator') || auth()->user()->hasPermission('event.manage'))
<div id="dashboardEventRejectModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div style="background: #ffffff; border-radius: 12px; max-width: 480px; width: 100%; padding: 1.5rem; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2);">
        <h3 style="margin-top: 0; font-size: 1.15rem; color: #dc2626;">Reject Event Request</h3>
        <p style="color: var(--color-surface-600); font-size: 0.9rem; margin-bottom: 1rem;">
            Are you sure you want to reject <strong id="rejectModalEventTitle">this event</strong>? You may optionally provide a reason for the organizer.
        </p>
        <form id="dashboardEventRejectForm" method="POST" action="">
            @csrf @method('PATCH')
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label class="form-label" for="dashboard_reject_remarks">Reason / Remarks (Optional)</label>
                <textarea name="remarks" id="dashboard_reject_remarks" class="form-control" rows="3" placeholder="Explain why this event request cannot be approved..."></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="closeDashboardEventRejectModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDashboardEventRejectModal(eventId, eventTitle) {
    var modal = document.getElementById('dashboardEventRejectModal');
    var form = document.getElementById('dashboardEventRejectForm');
    var titleEl = document.getElementById('rejectModalEventTitle');
    var textarea = document.getElementById('dashboard_reject_remarks');
    
    if (form) {
        form.action = '{{ url("/events") }}/' + eventId + '/reject';
    }
    if (titleEl) {
        titleEl.textContent = '"' + eventTitle + '"';
    }
    if (textarea) {
        textarea.value = '';
    }
    if (modal) {
        modal.style.display = 'flex';
    }
}

function closeDashboardEventRejectModal() {
    var modal = document.getElementById('dashboardEventRejectModal');
    if (modal) {
        modal.style.display = 'none';
    }
}
</script>
@endif
