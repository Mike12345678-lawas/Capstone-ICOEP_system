@extends('layouts.app')

@section('title', $pageTitle . ' — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Facilities</h1>
        <p class="page-subtitle">Browse and reserve campus venues and rooms.</p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search facilities..." value="{{ $search }}">
            </div>
            <div class="filter-group">
                <button type="submit" name="status" value="" class="filter-btn {{ empty($statusFilter) ? 'active' : '' }}">All</button>
                <button type="submit" name="status" value="available" class="filter-btn {{ $statusFilter === 'available' ? 'active' : '' }}">Available</button>
                <button type="submit" name="status" value="maintenance" class="filter-btn {{ $statusFilter === 'maintenance' ? 'active' : '' }}">Maintenance</button>
            </div>
        </form>

        @if($facilities->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                <h3>No Facilities Found</h3>
                <p>{{ $search ? 'Try adjusting your search criteria.' : 'No facilities have been added yet.' }}</p>
            </div>
        @else
            <div class="grid grid-3">
                @foreach($facilities as $facility)
                    <a href="{{ url('/facilities/' . $facility->id) }}" class="item-card">
                        <div class="item-card-image" style="height: 120px; background: var(--gradient-surface); display: flex; align-items: center; justify-content: center;">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path></svg>
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-meta">
                                @php
                                    $statusBadge = match($facility->status) {
                                        'available' => 'badge-success',
                                        'maintenance' => 'badge-warning',
                                        default => 'badge-error',
                                    };
                                @endphp
                                <span class="badge {{ $statusBadge }}">{{ ucfirst($facility->status) }}</span>
                            </div>
                            <h3 class="item-card-title">{{ $facility->name }}</h3>
                            <div class="item-card-desc">{{ Str::limit($facility->description, 80) }}</div>
                            <div class="item-card-meta" style="margin-top: auto; margin-bottom: 0;">
                                <span>📍 {{ $facility->location }}</span>
                                <span style="margin-left: auto;">👥 {{ $facility->capacity }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            {{ $facilities->withQueryString()->links('partials.pagination') }}
        @endif
    </div>
</div>
@endsection
