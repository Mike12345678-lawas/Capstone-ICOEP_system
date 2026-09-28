<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage system users, roles, and account status.</p>
    </div>
    @if(auth()->user()->hasPermission('user.create'))
        <a href="{{ url('/users/create') }}" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Create User
        </a>
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
                <a href="{{ url('/dashboard?section=users') }}" class="filter-btn {{ empty($roleFilter) ? 'active' : '' }}">All</a>
                @foreach($roles as $r)
                    <a href="{{ url('/dashboard?section=users&role=' . $r->id) }}" class="filter-btn {{ ($roleFilter ?? '') == $r->id ? 'active' : '' }}">{{ $r->name }}</a>
                @endforeach
            </div>
        </form>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr><th>Name</th><th>ID</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td style="font-weight: 600;">{{ $u->full_name }}</td>
                        <td><code>{{ $u->employee_id ?: '—' }}</code></td>
                        <td>{{ $u->email }}</td>
                        <td><span class="badge badge-general">{{ $u->role->name }}</span></td>
                        <td>{{ $u->department?->name ?: '—' }}</td>
                        <td>
                            @php $sb = match($u->status) { 'active' => 'badge-success', 'locked' => 'badge-error', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $sb }}">{{ ucfirst($u->status) }}</span>
                        </td>
                        <td>
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
                    <tr><td colspan="7" class="text-center text-muted" style="padding: 2rem;">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->withQueryString()->links('partials.pagination') }}
    </div>
</div>
