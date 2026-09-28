<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Inventory</h1>
        <p class="page-subtitle">Browse available equipment for borrowing.</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="equipment">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search equipment..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=equipment') }}" class="filter-btn {{ empty($categoryFilter) ? 'active' : '' }}">All</a>
                @foreach($categories as $cat)
                    <a href="{{ url('/dashboard?section=equipment&category=' . urlencode($cat)) }}" class="filter-btn {{ ($categoryFilter ?? '') === $cat ? 'active' : '' }}">{{ $cat }}</a>
                @endforeach
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Category</th><th>Available / Total</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($equipment as $item)
                    <tr>
                        <td style="font-weight: 600;">{{ $item->name }}</td>
                        <td><span class="badge badge-general">{{ $item->category }}</span></td>
                        <td>{{ $item->available_quantity }} / {{ $item->quantity }}</td>
                        <td>
                            <span class="badge {{ $item->available_quantity > 0 ? 'badge-success' : 'badge-error' }}">
                                {{ $item->available_quantity > 0 ? 'Available' : 'Unavailable' }}
                            </span>
                        </td>
                        <td><a href="{{ url('/equipment/' . $item->id) }}" class="btn btn-secondary btn-sm">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted" style="padding: 2rem;">No equipment found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $equipment->withQueryString()->links('partials.pagination') }}
    </div>
</div>
