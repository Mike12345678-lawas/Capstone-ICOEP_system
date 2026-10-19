<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Events</h1>
        <p class="page-subtitle">Discover and register for upcoming campus events.</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->hasPermission('event.create'))
            <a href="{{ url('/events/create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Event
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
        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr><th>Title</th><th>Date</th><th>Venue</th><th>Type</th><th>Audience</th><th>Registered</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td data-label="Title" style="font-weight: 600;">{{ $event->title }}</td>
                        <td data-label="Date">{{ $event->event_date->format('M d, Y') }}</td>
                        <td data-label="Venue">{{ $event->venue ?: '—' }}</td>
                        <td data-label="Type"><span class="badge badge-general">{{ ucfirst($event->event_type) }}</span></td>
                        <td data-label="Audience">
                            @if($event->target_audience === 'faculty')
                                <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.3); font-weight: 600;">Faculty</span>
                            @elseif($event->target_audience === 'staff')
                                <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3); font-weight: 600;">Staff</span>
                            @elseif($event->target_audience === 'student')
                                <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.3); font-weight: 600;">Students</span>
                            @elseif(in_array($event->target_audience, ['bsit', 'bshm', 'bsba-fm', 'bsed', 'beed']))
                                <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #047857; border: 1px solid rgba(16, 185, 129, 0.3); font-weight: 600;">{{ $event->target_audience_label }}</span>
                            @else
                                <span class="badge badge-general">All Campus</span>
                            @endif
                        </td>
                        <td data-label="Registered">{{ $event->registrations_count }}{{ $event->max_participants > 0 ? '/' . $event->max_participants : '' }}</td>
                        <td data-label="Status">
                            <x-status-badge :status="$event->status" />
                        </td>
                        <td data-label="Action" class="card-actions-cell"><a href="{{ url('/events/' . $event->id) }}" class="btn btn-secondary btn-sm" style="width: 100%;">View Details</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 2rem 1rem;">
                            <p style="margin-bottom: .5rem;">No events found.</p>
                            @if(request('search') || request('status'))
                                <a href="{{ url('/dashboard?section=events') }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                            @else
                                <a href="{{ url('/events/create') }}" class="btn btn-primary btn-sm">Create Event</a>
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
