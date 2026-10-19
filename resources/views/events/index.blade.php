@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Events</h1>
        <p class="page-subtitle">Discover and register for upcoming campus events.</p>
    </div>
    <div class="header-actions-mobile">
        <div class="btn-row-mobile">
            @if(auth()->user()->hasPermission('event.view'))
                <a href="{{ url('/events/my') }}" class="btn btn-secondary">My Events</a>
            @endif
            @if(auth()->user()->hasPermission('event.create'))
                <a href="{{ url('/events/create') }}" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Create Event
                </a>
            @endif
        </div>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search events..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <button type="submit" name="status" value="" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</button>
                <button type="submit" name="status" value="upcoming" class="filter-btn {{ $statusFilter === 'upcoming' ? 'active' : '' }}">Upcoming</button>
                <button type="submit" name="status" value="ongoing" class="filter-btn {{ $statusFilter === 'ongoing' ? 'active' : '' }}">Ongoing</button>
                <button type="submit" name="status" value="completed" class="filter-btn {{ $statusFilter === 'completed' ? 'active' : '' }}">Completed</button>
            </div>
        </form>
        @if($events->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
                <h3>No Events Found</h3>
                <p>{{ $search ? 'Try adjusting your search.' : 'No events scheduled at this time.' }}</p>
            </div>
        @else
            <div class="grid grid-3">
                @foreach($events as $event)
                    <a href="{{ url('/events/' . $event->id) }}" class="item-card">
                        <div class="item-card-image" style="height: 120px; background: var(--gradient-surface); display: flex; align-items: center; justify-content: center;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-meta">
                                @php
                                    $statusBadge = match($event->status) { 'upcoming' => 'badge-general', 'ongoing' => 'badge-success', 'completed' => 'badge-warning', 'cancelled' => 'badge-error', default => 'badge-general' };
                                    $typeBadge = match($event->event_type) { 'academic' => 'badge-general', 'sports' => 'badge-warning', 'cultural' => 'badge-success', 'spiritual' => 'badge-general', default => 'badge-general' };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst($event->status) }}</span>
                                <span class="badge {{ $typeBadge }}">{{ ucfirst($event->event_type) }}</span>
                                @if($event->target_audience === 'faculty')
                                    <span class="badge" style="background: rgba(139, 92, 246, 0.15); color: #7c3aed; border: 1px solid rgba(139, 92, 246, 0.3);">Faculty Only</span>
                                @elseif($event->target_audience === 'staff')
                                    <span class="badge" style="background: rgba(245, 158, 11, 0.15); color: #b45309; border: 1px solid rgba(245, 158, 11, 0.3);">Staff Only</span>
                                @elseif($event->target_audience === 'student')
                                    <span class="badge" style="background: rgba(59, 130, 246, 0.15); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.3);">Students Only</span>
                                @elseif(in_array($event->target_audience, ['bsit', 'bshm', 'bsba-fm', 'bsed', 'beed']))
                                    <span class="badge" style="background: rgba(16, 185, 129, 0.15); color: #047857; border: 1px solid rgba(16, 185, 129, 0.3);">{{ $event->target_audience_label }}</span>
                                @else
                                    <span class="badge badge-general">All Campus</span>
                                @endif
                            </div>
                            <h3 class="item-card-title">{{ $event->title }}</h3>
                            <div class="item-card-desc">{{ Str::limit($event->description, 100) }}</div>
                            <div class="item-card-meta" style="margin-top: auto; margin-bottom: 0;">
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line></svg>
                                    {{ $event->event_date->format('M d, Y') }}
                                </span>
                                <span style="margin-left: auto;">
                                    👥 {{ $event->registrations_count }}{{ $event->max_participants > 0 ? '/' . $event->max_participants : '' }}
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            {{ $events->withQueryString()->links('partials.pagination') }}
        @endif
    </div>
</div>
@endsection
