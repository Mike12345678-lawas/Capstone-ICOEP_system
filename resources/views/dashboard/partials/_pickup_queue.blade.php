<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Pickup Queue</h1>
        <p class="page-subtitle">
            @if(isset($isUnitScoped) && $isUnitScoped && isset($userUnit))
                Approved and borrowed equipment for <strong>{{ $userUnit->name }}</strong> ready for pickup.
            @else
                Approved and borrowed equipment across custodian units ready for pickup.
            @endif
        </p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="pickup-queue">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by borrower, equipment..." value="{{ $search }}">
            </div>
            @if($search)
                <a href="{{ url('/dashboard?section=pickup-queue') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>

        <div class="table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr>
                        <th>Borrower</th>
                        <th>Item</th>
                        <th>Unit</th>
                        <th>Qty</th>
                        <th>Date Needed</th>
                        <th>Expected Return</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($pickupRequests as $req)
                    <tr>
                        <td data-label="Borrower" style="font-weight: 600;">
                            {{ $req->user->full_name }}
                            @if($req->user->employee_id)
                                <span class="text-muted" style="font-size: 0.75rem; display: block;">{{ $req->user->employee_id }}</span>
                            @endif
                        </td>
                        <td data-label="Item" style="font-weight: 600;">
                            <a href="{{ url('/equipment/' . $req->equipment_id) }}" style="color: inherit; text-decoration: underline;">
                                {{ $req->equipment->name }}
                            </a>
                            <span class="text-muted" style="font-size: 0.75rem; display: block;">{{ $req->purpose }}</span>
                        </td>
                        <td data-label="Unit">
                            <span class="badge badge-general">
                                {{ $req->equipment->custodianUnit->name ?? 'Unassigned' }}
                            </span>
                        </td>
                        <td data-label="Qty">{{ $req->quantity }}</td>
                        <td data-label="Date Needed">
                            {{ $req->date_needed ? $req->date_needed->format('M d, Y') : '—' }}
                        </td>
                        <td data-label="Expected Return">
                            {{ $req->expected_return_date ? $req->expected_return_date->format('M d, Y') : '—' }}
                        </td>
                        <td data-label="Status">
                            <x-status-badge :status="$req->status" />
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted" style="padding: 2rem 1rem;">
                            <p style="margin-bottom: 0.5rem;">No items currently waiting in the pickup queue.</p>
                            @if(request('search'))
                                <a href="{{ url('/dashboard?section=pickup-queue') }}" class="btn btn-secondary btn-sm">Clear Search</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $pickupRequests->withQueryString()->links('partials.pagination') }}
    </div>
</div>
