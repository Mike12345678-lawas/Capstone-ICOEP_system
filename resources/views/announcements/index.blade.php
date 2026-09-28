@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Announcements</h1>
        <p class="page-subtitle">Stay updated with the latest news and information from {{ config('icoep.institution_short') }}.</p>
    </div>
    @if(auth()->user()->hasPermission('announcement.create'))
        <a href="{{ url('/announcements/create') }}" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Post Announcement
        </a>
    @endif
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search announcements..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <button type="submit" name="priority" value="" class="filter-btn {{ empty($priorityFilter) ? 'active' : '' }}">All</button>
                <button type="submit" name="priority" value="general" class="filter-btn {{ $priorityFilter === 'general' ? 'active' : '' }}">General</button>
                <button type="submit" name="priority" value="important" class="filter-btn {{ $priorityFilter === 'important' ? 'active' : '' }}">Important</button>
                <button type="submit" name="priority" value="urgent" class="filter-btn {{ $priorityFilter === 'urgent' ? 'active' : '' }}">Urgent</button>
            </div>
        </form>
        @if($announcements->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <h3>No Announcements Found</h3>
                <p>{{ $search ? 'Try adjusting your search criteria.' : 'There are no active announcements at this time.' }}</p>
            </div>
        @else
            <div class="grid grid-3">
                @foreach($announcements as $a)
                    <a href="{{ url('/announcements/' . $a->id) }}" class="item-card">
                        <div class="item-card-image" style="height: 120px; background: var(--gradient-surface); display: flex; align-items: center; justify-content: center;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-meta">
                                @php $badge = match($a->priority) { 'urgent' => 'badge-error', 'important' => 'badge-warning', default => 'badge-general' }; @endphp
                                <span class="badge {{ $badge }}">{{ ucfirst($a->priority) }}</span>
                                @if($a->is_pinned)
                                    <span class="badge badge-general" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">📌 Pinned</span>
                                @endif
                            </div>
                            <h3 class="item-card-title">{{ $a->title }}</h3>
                            <div class="item-card-desc">{{ Str::limit(strip_tags($a->content), 120) }}</div>
                            <div class="item-card-meta" style="margin-top: auto; margin-bottom: 0;">
                                <span>{{ $a->created_at->format('M d, Y') }}</span>
                                <span style="margin-left: auto;">{{ $a->author->full_name ?? '' }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            {{ $announcements->withQueryString()->links('partials.pagination') }}
        @endif
    </div>
</div>
@endsection
