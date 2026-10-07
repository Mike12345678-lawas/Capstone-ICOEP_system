@php
    $currentScope = $scope ?? 'my';
@endphp
<div class="page-header animate-fade-in-up">
    <div class="page-header-title-wrapper">
        <h1 class="page-title">Borrowed Equipment</h1>
        <p class="page-subtitle">Track borrowed equipment, return items with a photo, and view returned equipment history.</p>
    </div>
    <div class="header-actions header-actions-mobile">
        <div class="scope-segmented-control" role="group" aria-label="Borrowed equipment scope filter">
            <a href="{{ url('/dashboard?section=borrowed-equipment&scope=my') }}" class="filter-btn scope-pill {{ $currentScope === 'my' ? 'active' : '' }}">
                <span>👤</span> My Borrowed
            </a>
            <a href="{{ url('/dashboard?section=borrowed-equipment&scope=all') }}" class="filter-btn scope-pill {{ $currentScope === 'all' ? 'active' : '' }}">
                <span>🌐</span> All Campus Borrowed
            </a>
        </div>
        <div class="btn-row-mobile">
            <a href="{{ url('/dashboard?section=history&tab=equipment') }}" class="btn btn-secondary btn-sm">History</a>
            <a href="{{ url('/dashboard?section=equipment') }}" class="btn btn-primary btn-sm">+ Borrow Equipment</a>
        </div>
    </div>
</div>

{{-- ─── Current borrowed equipment & requests ─── --}}
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .5rem;">
        <h3 class="card-title">
            {{ $currentScope === 'all' ? 'All Campus Borrowed Equipment & Active Requests' : 'My Borrowed Equipment' }}
        </h3>
        <span class="badge badge-general">{{ $myEquipmentList->total() }} record(s)</span>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1.25rem;">
            <input type="hidden" name="section" value="borrowed-equipment">
            <input type="hidden" name="scope" value="{{ $currentScope }}">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by equipment, purpose, borrower..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=borrowed-equipment&scope=' . $currentScope) }}" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All Active</a>
                <a href="{{ url('/dashboard?section=borrowed-equipment&scope=' . $currentScope . '&status=borrowed') }}" class="filter-btn {{ ($statusFilter ?? '') === 'borrowed' ? 'active' : '' }}">Borrowed / Approved</a>
                <a href="{{ url('/dashboard?section=borrowed-equipment&scope=' . $currentScope . '&status=pending') }}" class="filter-btn {{ ($statusFilter ?? '') === 'pending' ? 'active' : '' }}">Pending</a>
                <a href="{{ url('/dashboard?section=borrowed-equipment&scope=' . $currentScope . '&status=rejected') }}" class="filter-btn {{ ($statusFilter ?? '') === 'rejected' ? 'active' : '' }}">Rejected</a>
            </div>
        </form>

        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr>
                        @if($currentScope === 'all')
                            <th>Borrower</th>
                        @endif
                        <th>Equipment</th>
                        <th>Qty</th>
                        <th>Purpose</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($myEquipmentList as $req)
                    @php
                        $isOverdue = ($req->status === 'borrowed' && $req->expected_return_date && $req->expected_return_date < now()->startOfDay());
                        $isMine = ($req->user_id === auth()->id());
                        $canManage = $isMine || auth()->user()->hasPermission('equipment.approve');
                    @endphp
                    <tr>
                        @if($currentScope === 'all')
                            <td data-label="Borrower">
                                <div style="font-weight: 600; color: var(--color-text-primary);">{{ $req->user->full_name }}</div>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted);">
                                    {{ $req->user->role->name ?? 'User' }} &bull; {{ $req->user->employee_id }}
                                </div>
                            </td>
                        @endif
                        <td data-label="Equipment">
                            <div style="font-weight: 600; color: var(--color-text-primary);">{{ $req->equipment->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-text-muted);">
                                <span class="badge badge-general" style="font-size: 0.7rem;">{{ $req->equipment->category }}</span>
                            </div>
                        </td>
                        <td data-label="Qty">
                            <div style="font-weight: 600;">{{ $req->quantity }} unit(s)</div>
                            @if($req->returned_quantity > 0 && $req->returned_quantity < $req->quantity)
                                <div style="font-size: 0.75rem; color: #0284c7;">{{ $req->returned_quantity }} confirmed back</div>
                            @endif
                        </td>
                        <td data-label="Purpose">
                            <div style="font-size: 0.875rem;">{{ $req->purpose }}</div>
                            @if($req->remarks)
                                <div style="font-size: 0.75rem; color: var(--color-text-muted); margin-top: 0.25rem;"><em>Note: {{ $req->remarks }}</em></div>
                            @endif
                        </td>
                        <td data-label="Schedule">
                            <div style="font-size: 0.85rem;">
                                <span class="text-muted">Needed:</span> {{ $req->date_needed ? $req->date_needed->format('M d, Y') : '—' }}
                            </div>
                            <div style="font-size: 0.85rem; font-weight: {{ $isOverdue ? '600' : 'normal' }}; color: {{ $isOverdue ? '#dc2626' : 'inherit' }};">
                                <span class="text-muted">Due:</span> {{ $req->expected_return_date ? $req->expected_return_date->format('M d, Y') : '—' }}
                                @if($isOverdue)
                                    <span class="badge badge-error" style="font-size: 0.65rem; margin-left: 0.25rem;">Overdue</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="Status">
                            <x-status-badge :status="$req->status" />
                            @if(in_array($req->status, ['borrowed', 'approved']) && $req->return_requested_at)
                                <div style="margin-top: 0.25rem;">
                                    <span class="badge badge-warning" style="font-size: 0.7rem;">Waiting for admin check</span>
                                </div>
                                @if($req->return_photo_url)
                                    <a href="{{ $req->return_photo_url }}" target="_blank" rel="noopener" title="View return photo" style="display: inline-block; margin-top: .35rem;">
                                        <img src="{{ $req->return_photo_url }}" alt="Return photo" class="photo-thumb">
                                    </a>
                                @endif
                            @endif
                        </td>
                        <td data-label="Action" class="card-actions-cell">
                            <div style="display: flex; gap: 0.35rem; align-items: center; flex-wrap: wrap;">
                                @if($req->status === 'pending')
                                    @if($isMine)
                                        <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/cancel') }}" style="margin:0;">
                                            @csrf @method('PATCH')
                                            <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.75rem; color: var(--color-error);"
                                                onclick="return confirm('Cancel this equipment borrow request?')">Cancel</button>
                                        </form>
                                    @endif
                                    @if(auth()->user()->hasPermission('equipment.approve'))
                                        <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/status') }}" style="margin:0;">
                                            @csrf @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" class="btn btn-success btn-sm" style="font-size: 0.75rem;">Approve</button>
                                        </form>
                                    @endif
                                @elseif(in_array($req->status, ['borrowed', 'approved']))
                                    @if(is_null($req->return_requested_at))
                                        @if($canManage)
                                            <button type="button" class="btn btn-primary btn-sm" style="font-size: 0.75rem;"
                                                id="return-btn-{{ $req->id }}"
                                                onclick="openReturnModal({{ $req->id }}, @js($req->equipment->name), {{ $req->quantity - $req->returned_quantity }})">
                                                Return Equipment
                                            </button>
                                        @endif
                                    @else
                                        @if($isMine)
                                            <form method="POST" action="{{ url('/equipment-requests/' . $req->id . '/cancel-return-request') }}" style="margin:0;">
                                                @csrf
                                                <button type="submit" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;" title="Cancel return check notification">Cancel Return</button>
                                            </form>
                                        @endif
                                        @if(auth()->user()->hasPermission('equipment.approve'))
                                            <a href="{{ url('/equipment/returns') }}" class="btn btn-success btn-sm" style="font-size: 0.75rem;">Check &amp; Confirm</a>
                                        @endif
                                    @endif
                                @endif
                                <a href="{{ url('/equipment/' . $req->equipment_id) }}" class="btn btn-secondary btn-sm" style="font-size: 0.75rem;">View</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $currentScope === 'all' ? 7 : 6 }}" class="text-center text-muted" style="padding: 2.5rem 1rem;">
                            <div style="font-size: 1rem; font-weight: 500; margin-bottom: 0.5rem;">
                                {{ $search || $statusFilter ? 'No equipment requests found matching your criteria.' : ($currentScope === 'all' ? 'No equipment is currently borrowed across campus.' : 'You have no borrowed equipment right now.') }}
                            </div>
                            @if($search || $statusFilter)
                                <a href="{{ url('/dashboard?section=borrowed-equipment&scope=' . $currentScope) }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                            @else
                                <a href="{{ url('/dashboard?section=equipment') }}" class="btn btn-primary btn-sm">Browse Equipment</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $myEquipmentList->withQueryString()->links('partials.pagination') }}
    </div>
</div>

{{-- ─── Returned equipment ─── --}}
<div class="card animate-fade-in-up" style="animation-delay: 200ms; margin-top: 1.5rem;" id="returned-equipment">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
        <h3 class="card-title">{{ $currentScope === 'all' ? 'All Returned Equipment' : 'My Returned Equipment' }}</h3>
        <span class="badge badge-success">{{ $returnedEquipment->total() }} returned</span>
    </div>
    <div class="card-body">
        <div class="table-responsive manage-table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr>
                        @if($currentScope === 'all')
                            <th>Borrower</th>
                        @endif
                        <th>Equipment</th>
                        <th>Qty</th>
                        <th>Borrowed</th>
                        <th>Returned</th>
                        <th>Condition</th>
                        <th>Photo Proof</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($returnedEquipment as $ret)
                    <tr>
                        @if($currentScope === 'all')
                            <td data-label="Borrower">
                                <div style="font-weight: 600;">{{ $ret->user->full_name }}</div>
                                <div style="font-size: 0.75rem; color: var(--color-text-muted);">{{ $ret->user->role->name ?? 'User' }} &bull; {{ $ret->user->employee_id }}</div>
                            </td>
                        @endif
                        <td data-label="Equipment">
                            <div style="font-weight: 600;">{{ $ret->equipment->name }}</div>
                            <div style="font-size: 0.8rem; color: var(--color-text-muted);">{{ $ret->purpose }}</div>
                        </td>
                        <td data-label="Qty">{{ $ret->quantity }} unit(s)</td>
                        <td data-label="Borrowed" style="font-size: .85rem;">
                            {{ $ret->date_needed?->format('M d, Y') ?? '—' }}<br>
                            <span class="text-muted">Due {{ $ret->expected_return_date?->format('M d, Y') ?? '—' }}</span>
                        </td>
                        <td data-label="Returned" style="font-size: .85rem;">
                            {{ $ret->returned_at?->format('M d, Y g:i A') ?? ($ret->actual_return_date?->format('M d, Y') ?? '—') }}
                            @if($ret->days_late > 0)
                                <div style="color: #dc2626; font-size: .75rem;">{{ $ret->days_late }} day(s) late</div>
                            @endif
                            @if($ret->confirmedBy)
                                <div class="text-muted" style="font-size: .75rem;">Checked by {{ $ret->confirmedBy->full_name }}</div>
                            @endif
                        </td>
                        <td data-label="Condition">
                            @if($ret->return_condition)
                                <span class="badge {{ $ret->return_condition === 'good' ? 'badge-success' : 'badge-error' }}">{{ ucfirst($ret->return_condition) }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="Photo Proof">
                            @if($ret->return_photo_url)
                                <a href="{{ $ret->return_photo_url }}" target="_blank" rel="noopener" title="View return photo">
                                    <img src="{{ $ret->return_photo_url }}" alt="Return photo" class="photo-thumb">
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $currentScope === 'all' ? 7 : 6 }}" class="text-center text-muted" style="padding: 2rem 1rem;">
                            {{ $currentScope === 'all' ? 'No returned equipment recorded yet across campus.' : 'No returned equipment yet.' }}
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $returnedEquipment->withQueryString()->links('partials.pagination') }}
    </div>
</div>

@include('dashboard.partials._return_modal')
