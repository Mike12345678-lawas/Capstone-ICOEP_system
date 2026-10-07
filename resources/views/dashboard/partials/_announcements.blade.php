<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Announcements</h1>
        <p class="page-subtitle">Stay updated with the latest news and information.</p>
    </div>
    @if(auth()->user()->hasPermission('announcement.create'))
        <div class="header-actions-mobile">
            <a href="{{ url('/announcements/create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Post Announcement
            </a>
        </div>
    @endif
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="announcements">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search announcements..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=announcements') }}" class="filter-btn {{ empty($priorityFilter) ? 'active' : '' }}">All</a>
                <a href="{{ url('/dashboard?section=announcements&priority=general') }}" class="filter-btn {{ ($priorityFilter ?? '') === 'general' ? 'active' : '' }}">General</a>
                <a href="{{ url('/dashboard?section=announcements&priority=important') }}" class="filter-btn {{ ($priorityFilter ?? '') === 'important' ? 'active' : '' }}">Important</a>
                <a href="{{ url('/dashboard?section=announcements&priority=urgent') }}" class="filter-btn {{ ($priorityFilter ?? '') === 'urgent' ? 'active' : '' }}">Urgent</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr><th>Title</th><th>Priority</th><th>Author</th><th>Date</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($announcements as $a)
                    <tr>
                        <td data-label="Title">
                            @if($a->is_pinned)<span style="color: #0ea5e9;">📌</span> @endif
                            <span style="font-weight: 600;">{{ $a->title }}</span>
                        </td>
                        <td data-label="Priority">
                            @php $bc = match($a->priority) { 'urgent' => 'badge-error', 'important' => 'badge-warning', default => 'badge-general' }; @endphp
                            <span class="badge {{ $bc }}">{{ ucfirst($a->priority) }}</span>
                        </td>
                        <td data-label="Author">{{ $a->author->full_name ?? '—' }}</td>
                        <td data-label="Date">{{ $a->created_at->format('M d, Y') }}</td>
                        <td data-label="Action"><a href="{{ url('/announcements/' . $a->id) }}" class="btn btn-secondary btn-sm" style="width: 100%;">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No announcements found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $announcements->withQueryString()->links('partials.pagination') }}
    </div>
</div>
