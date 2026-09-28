<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Facilities</h1>
        <p class="page-subtitle">Browse and reserve campus venues and rooms.</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="facilities">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search facilities..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=facilities') }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</a>
                <a href="{{ url('/dashboard?section=facilities&status=available') }}" class="filter-btn {{ ($statusFilter ?? '') === 'available' ? 'active' : '' }}">Available</a>
                <a href="{{ url('/dashboard?section=facilities&status=maintenance') }}" class="filter-btn {{ ($statusFilter ?? '') === 'maintenance' ? 'active' : '' }}">Maintenance</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Location</th><th>Capacity</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($facilities as $f)
                    <tr>
                        <td style="font-weight: 600;">{{ $f->name }}</td>
                        <td>{{ $f->location }}</td>
                        <td>{{ $f->capacity }}</td>
                        <td>
                            @php $sb = match($f->status) { 'available' => 'badge-success', 'maintenance' => 'badge-warning', default => 'badge-error' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($f->status) }}</span>
                        </td>
                        <td><a href="{{ url('/facilities/' . $f->id) }}" class="btn btn-secondary btn-sm">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No facilities found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $facilities->withQueryString()->links('partials.pagination') }}
    </div>
</div>
