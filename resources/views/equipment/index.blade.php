@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Equipment Inventory</h1>
        <p class="page-subtitle">Browse available equipment for borrowing.</p>
    </div>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search equipment..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <button type="submit" name="category" value="" class="filter-btn {{ empty($categoryFilter) ? 'active' : '' }}">All</button>
                @foreach($categories as $cat)
                    <button type="submit" name="category" value="{{ $cat }}" class="filter-btn {{ $categoryFilter === $cat ? 'active' : '' }}">{{ $cat }}</button>
                @endforeach
            </div>
        </form>
        @if($equipment->isEmpty())
            <div class="empty-state"><h3>No Equipment Found</h3><p>{{ $search ? 'Try adjusting your search.' : 'No equipment items available.' }}</p></div>
        @else
            <div class="grid grid-3">
                @foreach($equipment as $item)
                    <a href="{{ url('/equipment/' . $item->id) }}" class="item-card">
                        <div class="item-card-image" style="height: 100px; background: var(--gradient-surface); display: flex; align-items: center; justify-content: center;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-meta">
                                <span class="badge {{ $item->available_quantity > 0 ? 'badge-success' : 'badge-error' }}">{{ $item->available_quantity > 0 ? 'Available' : 'Unavailable' }}</span>
                                <span class="badge badge-general">{{ $item->category }}</span>
                            </div>
                            <h3 class="item-card-title">{{ $item->name }}</h3>
                            <div class="item-card-desc">{{ Str::limit($item->description, 80) }}</div>
                            <div class="item-card-meta" style="margin-top: auto; margin-bottom: 0;">
                                <span>Available: {{ $item->available_quantity }}/{{ $item->quantity }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
            {{ $equipment->withQueryString()->links('partials.pagination') }}
        @endif
    </div>
</div>
@endsection
