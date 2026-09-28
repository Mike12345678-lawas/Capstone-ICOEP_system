@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $announcement->title }}</h1>
        <p class="page-subtitle">Posted by {{ $announcement->author->full_name }} on {{ $announcement->created_at->format('F d, Y \a\t h:i A') }}</p>
    </div>
    <div class="btn-group">
        @if(auth()->user()->hasPermission('announcement.manage') || (auth()->user()->hasPermission('announcement.create') && $announcement->posted_by == auth()->id()))
            <form method="POST" action="{{ url('/announcements/' . $announcement->id) }}" onsubmit="return confirm('Delete this announcement?')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        @endif
        <a href="{{ url('/announcements') }}" class="btn btn-secondary">&larr; Back to List</a>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-header">
        <div class="flex gap-sm">
            @php $badge = match($announcement->priority) { 'urgent' => 'badge-error', 'important' => 'badge-warning', default => 'badge-general' }; @endphp
            <span class="badge {{ $badge }}">{{ ucfirst($announcement->priority) }}</span>
            <span class="badge badge-general">Audience: {{ ucfirst($announcement->audience) }}</span>
            @if($announcement->is_pinned)
                <span class="badge badge-general" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">Pinned</span>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="announcement-full-content" style="font-size: var(--text-base); line-height: 1.8;">
            {!! nl2br(e($announcement->content)) !!}
        </div>
    </div>
    @if($announcement->expires_at)
    <div class="card-footer">
        <div class="text-muted" style="font-size: .75rem;">Valid until: {{ $announcement->expires_at->format('F d, Y \a\t h:i A') }}</div>
    </div>
    @endif
</div>
@endsection
