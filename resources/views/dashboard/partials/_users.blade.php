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
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search users..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/dashboard?section=users') }}" class="filter-btn {{ empty($roleFilter) && empty($statusFilter) ? 'active' : '' }}">All</a>
                @foreach($roles as $r)
                    <a href="{{ url('/dashboard?section=users&role=' . $r->id) }}" class="filter-btn {{ ($roleFilter ?? '') == $r->id ? 'active' : '' }}">{{ $r->name }}</a>
                @endforeach
                <a href="{{ url('/dashboard?section=users&status=inactive_auto') }}" class="filter-btn {{ ($statusFilter ?? '') === 'inactive_auto' ? 'active' : '' }}" title="Accounts auto-deactivated due to inactivity">
                    ⚡ Auto-Deactivated
                </a>
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
                            @if(request('search') || request('role') || request('status'))
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
