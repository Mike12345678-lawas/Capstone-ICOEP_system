<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Inventory</h1>
        <p class="page-subtitle">Manage equipment items — add, edit, and track inventory status.</p>
    </div>
    <a href="{{ url('/equipment/create') }}" class="btn btn-primary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add Equipment
    </a>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="inventory">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search equipment..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=inventory') }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</a>
                <a href="{{ url('/dashboard?section=inventory&status=available') }}" class="filter-btn {{ ($statusFilter ?? '') === 'available' ? 'active' : '' }}">Available</a>
                <a href="{{ url('/dashboard?section=inventory&status=maintenance') }}" class="filter-btn {{ ($statusFilter ?? '') === 'maintenance' ? 'active' : '' }}">Maintenance</a>
                <a href="{{ url('/dashboard?section=inventory&status=retired') }}" class="filter-btn {{ ($statusFilter ?? '') === 'retired' ? 'active' : '' }}">Retired</a>
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>Category</th><th>Total Qty</th><th>Available</th><th>Borrowed</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($inventoryItems as $item)
                    @php $borrowed = $item->quantity - $item->available_quantity; @endphp
                    <tr>
                        <td style="font-weight: 600;">{{ $item->name }}</td>
                        <td><span class="badge badge-general">{{ $item->category }}</span></td>
                        <td>{{ $item->quantity }}</td>
                        <td>
                            <span style="font-weight: 600; color: {{ $item->available_quantity > 0 ? '#10b981' : '#ef4444' }};">
                                {{ $item->available_quantity }}
                            </span>
                        </td>
                        <td>{{ $borrowed > 0 ? $borrowed : '—' }}</td>
                        <td>
                            @php $sb = match($item->status) { 'available' => 'badge-success', 'maintenance' => 'badge-warning', 'retired' => 'badge-error', default => 'badge-general' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($item->status) }}</span>
                        </td>
                        <td>
                            <div class="btn-group">
                                <a href="{{ url('/equipment/' . $item->id . '/edit') }}" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ url('/equipment/' . $item->id) }}" onsubmit="return confirm('Delete this equipment item?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 2rem;">No equipment items found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $inventoryItems->withQueryString()->links('partials.pagination') }}
    </div>
</div>
