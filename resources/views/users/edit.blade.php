@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $isSelf ? 'My Profile' : 'Edit User: ' . $user->full_name }}</h1>
        <p class="page-subtitle">{{ $isSelf ? 'Update your personal information and password.' : 'Manage user account details.' }}</p>
    </div>
    @if(!$isSelf && auth()->user()->hasPermission('user.view'))
        <a href="{{ url('/users') }}" class="btn btn-secondary">&larr; Back to Users</a>
    @endif
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="{{ url('/users/' . $user->id) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required value="{{ old('first_name', $user->first_name) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required value="{{ old('last_name', $user->last_name) }}">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" required value="{{ old('email', $user->email) }}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="employee_id">Employee/Student ID</label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" value="{{ old('employee_id', $user->employee_id) }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department_id">Department</label>
                    <select id="department_id" name="department_id" class="form-control form-select">
                        <option value="">— Select Department —</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="phone">Phone Number</label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}">
            </div>

            @if(!$isSelf && auth()->user()->hasPermission('user.edit'))
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="role_id">Role</label>
                    <select id="role_id" name="role_id" class="form-control form-select">
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Status</label>
                    <select id="status" name="status" class="form-control form-select">
                        <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="locked" {{ old('status', $user->status) === 'locked' ? 'selected' : '' }}>Locked</option>
                    </select>
                </div>
            </div>
            @endif

            <div style="border-top: 1px solid var(--color-surface-200); margin: 1.5rem 0;"></div>
            <div class="form-group">
                <label class="form-label" for="password">New Password</label>
                <input type="password" id="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
                <span class="form-hint">Only fill this if you want to change the password.</span>
            </div>
            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm New Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" placeholder="Re-enter new password">
            </div>
            <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
        </form>
    </div>
</div>

@if(!$isSelf)
<div class="card animate-fade-in-up" style="animation-delay: 200ms; max-width: 800px; margin-top: 1.5rem;">
    <div class="card-header"><h3 class="card-title">Account Info</h3></div>
    <div class="card-body">
        <div class="grid grid-3">
            <div>
                <div class="text-muted" style="font-size: .75rem; margin-bottom: 4px;">Last Login</div>
                <div style="font-weight: 600;">{{ $user->last_login ? $user->last_login->format('M d, Y h:i A') : 'Never' }}</div>
            </div>
            <div>
                <div class="text-muted" style="font-size: .75rem; margin-bottom: 4px;">Login Attempts</div>
                <div style="font-weight: 600;">{{ $user->login_attempts }}</div>
            </div>
            <div>
                <div class="text-muted" style="font-size: .75rem; margin-bottom: 4px;">Joined</div>
                <div style="font-weight: 600;">{{ $user->created_at->format('M d, Y') }}</div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
