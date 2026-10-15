<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage system users, roles, and account status.</p>
    </div>
    @if(auth()->user()->hasPermission('user.create'))
        <div class="header-actions-mobile">
            <a href="{{ url('/users/create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create User
            </a>
        </div>
    @endif
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="{{ url('/dashboard') }}" class="search-bar" style="margin-bottom: 1rem;">
            <input type="hidden" name="section" value="users">
            @if(!empty($roleFilter))
                <input type="hidden" name="role" value="{{ $roleFilter }}">
            @endif
            @if(!empty($statusFilter))
                <input type="hidden" name="status" value="{{ $statusFilter }}">
            @endif

            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search users..." value="{{ $search }}">
            </div>

            <div class="search-select-wrapper" style="flex: 0 1 260px; min-width: 200px; max-width: 100%;">
                <select name="department" class="form-control form-select" onchange="this.form.submit()" style="height: 44px; font-size: .875rem;">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        @php
                            $deptLabel = $dept->code && !str_starts_with($dept->name, $dept->code)
                                ? "{$dept->code} – {$dept->name}"
                                : $dept->name;
                        @endphp
                        <option value="{{ $dept->id }}" {{ (string)($departmentFilter ?? '') === (string)$dept->id ? 'selected' : '' }}>
                            {{ $deptLabel }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                @php
                    $allQuery = array_merge(request()->query(), ['section' => 'users']);
                    unset($allQuery['role'], $allQuery['status']);
                    if (empty($allQuery['search'])) unset($allQuery['search']);
                    if (empty($allQuery['department'])) unset($allQuery['department']);

                    $hasActiveFilters = !empty($roleFilter) || !empty($statusFilter) || !empty($departmentFilter) || !empty($search);
                @endphp
                <a href="{{ url('/dashboard?' . http_build_query($allQuery)) }}" class="filter-btn {{ empty($roleFilter) && empty($statusFilter) && empty($departmentFilter) ? 'active' : '' }}">All</a>
                @foreach($roles as $r)
                    @php
                        $roleQuery = array_merge(request()->query(), ['section' => 'users', 'role' => $r->id]);
                        unset($roleQuery['status']);
                        if (empty($roleQuery['search'])) unset($roleQuery['search']);
                        if (empty($roleQuery['department'])) unset($roleQuery['department']);
                    @endphp
                    <a href="{{ url('/dashboard?' . http_build_query($roleQuery)) }}" class="filter-btn {{ ($roleFilter ?? '') == $r->id ? 'active' : '' }}">{{ $r->name }}</a>
                @endforeach
                @php
                    $statusQuery = array_merge(request()->query(), ['section' => 'users', 'status' => 'inactive_auto']);
                    unset($statusQuery['role']);
                    if (empty($statusQuery['search'])) unset($statusQuery['search']);
                    if (empty($statusQuery['department'])) unset($statusQuery['department']);
                @endphp
                <a href="{{ url('/dashboard?' . http_build_query($statusQuery)) }}" class="filter-btn {{ ($statusFilter ?? '') === 'inactive_auto' ? 'active' : '' }}" title="Accounts auto-deactivated due to inactivity">
                    ⚡ Auto-Deactivated
                </a>

                @if($hasActiveFilters)
                    <a href="{{ url('/dashboard?section=users') }}" class="clear-filters-link" style="font-size: .8125rem; color: var(--color-text-muted, #94a3b8); text-decoration: underline; align-self: center; margin-left: .25rem; white-space: nowrap;">
                        Clear filters
                    </a>
                @endif
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table mobile-cards">
                <thead>
                    <tr><th>Name</th><th>ID</th><th>Email</th><th>Role</th><th>Department</th><th>Last Activity</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td data-label="Name" style="font-weight: 600;">{{ $u->full_name }}</td>
                        <td data-label="ID"><code>{{ $u->employee_id ?: '—' }}</code></td>
                        <td data-label="Email">{{ $u->email }}</td>
                        <td data-label="Role"><span class="badge badge-general">{{ $u->role->name }}</span></td>
                        <td data-label="Department">{{ $u->department?->name ?: '—' }}</td>
                        <td data-label="Last Activity">
                            @if($u->last_activity_at)
                                <span title="{{ $u->last_activity_at->format('M d, Y h:i A') }}">{{ $u->last_activity_at->diffForHumans() }}</span>
                            @elseif($u->last_login)
                                <span title="{{ $u->last_login->format('M d, Y h:i A') }}">{{ $u->last_login->diffForHumans() }} (login)</span>
                            @else
                                <span class="text-muted">Never</span>
                            @endif
                        </td>
                        <td data-label="Status">
                            <div style="display: flex; flex-direction: column; gap: 4px; align-items: flex-start;">
                                <x-status-badge :status="$u->status" />
                                @if($u->status === 'inactive')
                                    @if($u->deactivation_reason === 'inactivity')
                                        <span class="badge badge-error" style="font-size: .65rem; padding: 1px 6px;" title="Auto-deactivated after {{ config('icoep.inactivity.days', 14) }} days of inactivity">
                                            Auto: no access for {{ config('icoep.inactivity.days', 14) }} days
                                        </span>
                                    @elseif($u->deactivation_reason === 'admin')
                                        <span class="text-muted" style="font-size: .75rem;">By admin</span>
                                    @endif
                                @endif
                            </div>
                        </td>
                        <td data-label="Actions">
                            <div class="btn-group">
                                <a href="{{ url('/users/' . $u->id . '/edit') }}" class="btn btn-secondary btn-sm">Edit</a>
                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ url('/users/' . $u->id) }}" onsubmit="return confirm('Delete this user?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted" style="padding: 2rem 1rem;">
                            <p style="margin-bottom: .5rem;">No users found.</p>
                            @if(request('search') || request('role') || request('status') || request('department'))
                                <a href="{{ url('/dashboard?section=users') }}" class="btn btn-secondary btn-sm">Clear Filters</a>
                            @else
                                <a href="{{ url('/users/create') }}" class="btn btn-primary btn-sm">Create User</a>
                            @endif
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->withQueryString()->links('partials.pagination') }}
    </div>
</div>
