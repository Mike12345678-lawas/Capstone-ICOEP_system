@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">System Audit Logs</h1>
        <p class="page-subtitle">Complete record of all system actions for compliance and security.</p>
    </div>
    <button onclick="window.print()" class="btn btn-secondary">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        Print Log
    </button>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: 1.5rem;">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by action, table, or user..." value="{{ $search }}">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
        </form>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Timestamp</th><th>User</th><th>Action</th><th>Table</th><th>Record ID</th><th>IP Address</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="text-muted" style="font-size: 0.85em; white-space: nowrap;">{{ $log->created_at->format('M d, Y h:i:s A') }}</td>
                        <td style="font-weight: 500;">{{ $log->user ? $log->user->full_name : 'System' }}</td>
                        <td><span class="badge badge-general">{{ $log->action }}</span></td>
                        <td>{{ $log->table_name ?: '—' }}</td>
                        <td>{{ $log->record_id ?: '—' }}</td>
                        <td class="text-muted" style="font-size: 0.85em;">{{ $log->ip_address ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted" style="padding: 2rem;">No audit logs found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $logs->withQueryString()->links('partials.pagination') }}
    </div>
</div>
@endsection
