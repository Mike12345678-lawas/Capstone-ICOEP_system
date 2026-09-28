@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
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
        <form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search users..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <a href="{{ url('/users') }}" class="filter-btn {{ empty($roleFilter) && empty($statusFilter) ? 'active' : '' }}">All</a>
                @foreach($roles as $r)
                    <a href="{{ url('/users?role=' . $r->id) }}" class="filter-btn {{ $roleFilter == $r->id ? 'active' : '' }}">{{ $r->name }}</a>
                @endforeach
            </div>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse($users as $u)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: .625rem;">
                                <div class="user-avatar small">{{ $u->initials }}</div>
                                <div>
                                    <div style="font-weight: 600;">{{ $u->full_name }}</div>
                                    <div class="text-muted" style="font-size: .75rem;">{{ $u->employee_id ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $u->email }}</td>
                        <td><span class="badge badge-general">{{ $u->role->name }}</span></td>
                        <td>{{ $u->department?->name ?: '—' }}</td>
                        <td>
                            @php $badge = match($u->status) { 'active' => 'badge-success', 'locked' => 'badge-error', default => 'badge-warning' }; @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($u->status) }}</span>
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
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No users found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $users->withQueryString()->links('partials.pagination') }}
    </div>
</div>
@endsection
