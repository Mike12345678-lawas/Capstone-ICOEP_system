<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Inventory</h1>
        <p class="page-subtitle">Browse available equipment for borrowing.</p>
    </div>
    <div>
        <a href="{{ url('/dashboard?section=borrowed-equipment') }}" class="btn btn-primary btn-sm">
            📦 My Borrowed Equipment
        </a>
    </div>
</div>

@if(isset($myActiveEquipment) && $myActiveEquipment->isNotEmpty())
<div class="card animate-fade-in-up" style="animation-delay: 50ms; margin-bottom: 1rem; border-left: 4px solid var(--color-brand-500, #0ea5e9); background: rgba(14, 165, 233, 0.04);">
    <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; padding: 0.85rem 1.25rem; flex-wrap: wrap; gap: .75rem;">
        <div>
            <strong>📦 You have {{ $myActiveEquipment->count() }} active equipment request(s) / borrowed item(s):</strong>
            <span class="text-muted" style="margin-left: .5rem; font-size: .85rem;">
                {{ $myActiveEquipment->take(3)->map(fn($r) => $r->equipment->name . ' (' . ucfirst($r->status) . ')')->join(', ') }}
                @if($myActiveEquipment->count() > 3) and more... @endif
            </span>
        </div>
        <a href="{{ url('/dashboard?section=borrowed-equipment') }}" class="btn btn-primary btn-sm" style="font-size: .8rem;">
            Manage &amp; Return Equipment &rarr;
        </a>
    </div>
</div>
@endif
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
        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr><th>Name</th><th>Category</th><th>Unit</th><th>Available / Total</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                @forelse($equipment as $item)
                    <tr>
                        <td data-label="Name" style="font-weight: 600;">{{ $item->name }}</td>
                        <td data-label="Category"><span class="badge badge-general">{{ $item->category }}</span></td>
                        <td data-label="Unit"><span class="badge badge-general">{{ $item->custodianUnit?->name ?? 'Unassigned' }}</span></td>
                        <td data-label="Available / Total">{{ $item->available_quantity }} / {{ $item->quantity }}</td>
                        <td data-label="Status">
                            <span class="badge {{ $item->available_quantity > 0 ? 'badge-success' : 'badge-error' }}">
                                {{ $item->available_quantity > 0 ? 'Available' : 'Unavailable' }}
                            </span>
                        </td>
                        <td data-label="Action" class="card-actions-cell"><a href="{{ url('/equipment/' . $item->id) }}" class="btn btn-secondary btn-sm" style="width: 100%;">View Details</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No equipment found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $equipment->withQueryString()->links('partials.pagination') }}
    </div>
</div>
