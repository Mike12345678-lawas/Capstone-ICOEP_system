@extends('layouts.app')

@section('title', $pageTitle . ' — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $facility->name }}</h1>
        <p class="page-subtitle">{{ $facility->location }} &bull; Capacity: {{ $facility->capacity }}</p>
    </div>
    <div class="header-actions-mobile">
        <a href="{{ url('/facilities') }}" class="btn btn-secondary">&larr; Back to List</a>
    </div>
</div>

<div class="grid-sidebar-layout">
    <div>
        {{-- Facility Details --}}
        <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="card-header">
                <h3 class="card-title">About This Facility</h3>
                @php $currentBooking = $facility->status === 'available' ? $facility->currentBooking() : null; @endphp
                <div style="display: flex; gap: .4rem; align-items: center; flex-wrap: wrap;">
                    <x-status-badge :status="$facility->status" />
                    @if($facility->status === 'available')
                        @if($currentBooking)
                            <span class="badge badge-error" title="{{ $currentBooking->title }} — {{ $currentBooking->user->full_name ?? '' }}">
                                <span class="occupancy-dot occupied"></span>Occupied until {{ $currentBooking->ends_at->format('g:i A') }}
                            </span>
                        @else
                            <span class="badge badge-success"><span class="occupancy-dot"></span>Open now</span>
                        @endif
                    @endif
                </div>
            </div>
            <div class="card-body">
                <p style="line-height: 1.8;">{{ $facility->description ?: 'No description available.' }}</p>
            </div>
        </div>

        {{-- User's Own Bookings for this Facility --}}
        @if(isset($myReservations) && $myReservations->isNotEmpty())
        <div class="card animate-fade-in-up" style="animation-delay: 150ms; margin-top: 1.5rem; border-left: 4px solid var(--color-brand-500, #0ea5e9);">
            <div class="card-header">
                <h3 class="card-title">My Bookings for this Facility</h3>
                <a href="{{ url('/dashboard?section=booked-facilities') }}" class="btn btn-secondary btn-sm" style="font-size: .75rem;">View All Bookings</a>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="data-table responsive-card-table">
                    <thead>
                        <tr><th>Title</th><th>Schedule</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    @foreach($myReservations as $mRes)
                        @php $state = $mRes->usage_state; @endphp
                        <tr>
                            <td data-label="Title" style="font-weight: 600;">{{ $mRes->title }}</td>
                            <td data-label="Schedule" style="font-size: .85rem;">
                                <span>📅 {{ $mRes->reservation_date->format('M d, Y') }}</span>
                                <div class="text-muted" style="font-size: .75rem;">⏰ {{ \Carbon\Carbon::parse($mRes->time_start)->format('g:i A') }} – {{ \Carbon\Carbon::parse($mRes->time_end)->format('g:i A') }}</div>
                            </td>
                            <td data-label="Status">
                                @if($state === 'occupied')
                                    <span class="badge badge-error"><span class="occupancy-dot occupied"></span>Occupied</span>
                                @elseif($state === 'upcoming')
                                    <x-status-badge status="approved" />
                                @else
                                    <x-status-badge :status="$mRes->status" />
                                @endif
                            </td>
                            <td data-label="Action" class="card-actions-cell">
                                @if($mRes->status === 'approved')
                                    <form method="POST" action="{{ url('/facility-reservations/' . $mRes->id . '/complete') }}" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" style="font-size: 0.75rem;"
                                            onclick="return confirm('Free this facility now and mark it open?')">
                                            Make Open
                                        </button>
                                    </form>
                                @elseif($mRes->status === 'pending')
                                    <form method="POST" action="{{ url('/facility-reservations/' . $mRes->id . '/cancel') }}" style="margin:0;">
                                        @csrf @method('PATCH')
                                        <button class="btn btn-secondary btn-sm" style="font-size:.75rem; color:var(--color-error);"
                                            onclick="return confirm('Cancel this reservation?')">Cancel</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Upcoming Reservations --}}
        <div class="card animate-fade-in-up" style="animation-delay: 200ms; margin-top: 1.5rem;">
            <div class="card-header">
                <h3 class="card-title">Upcoming Reservations</h3>
            </div>
            <div class="card-body">
                @if($reservations->isEmpty())
                    <div class="empty-state" style="padding: 1.5rem;">
                        <p class="text-muted">No upcoming reservations for this facility.</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table responsive-card-table">
                            <thead>
                                <tr><th>Date</th><th>Time</th><th>Title</th><th>Requested By</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach($reservations as $res)
                                <tr>
                                    <td data-label="Date">{{ $res->reservation_date->format('M d, Y') }}</td>
                                    <td data-label="Time">{{ \Carbon\Carbon::parse($res->time_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($res->time_end)->format('h:i A') }}</td>
                                    <td data-label="Title">{{ $res->title }}</td>
                                    <td data-label="Requested By">{{ $res->user->full_name }}</td>
                                    <td data-label="Status"><x-status-badge :status="$res->status" /></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Reservation Form --}}
    @if(auth()->user()->hasPermission('facility.reserve'))
    <div class="card animate-fade-in-up" style="animation-delay: 300ms;">
        <div class="card-header"><h3 class="card-title">Reserve This Facility</h3></div>
        <div class="card-body">
            @if($facility->status !== 'available')
                <div class="empty-state" style="padding: 1.5rem;">
                    <p class="text-muted">This facility is currently {{ $facility->status }}.</p>
                </div>
            @else
                <form method="POST" action="{{ url('/facilities/' . $facility->id . '/reserve') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label" for="title">Reservation Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required value="{{ old('title', request('title')) }}" placeholder="e.g., Department Meeting">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reservation_date">Date *</label>
                        <input type="date" id="reservation_date" name="reservation_date" class="form-control" required value="{{ old('reservation_date', request('reservation_date', request('date'))) }}" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="time_start">Start Time *</label>
                            <input type="time" id="time_start" name="time_start" class="form-control" required value="{{ old('time_start', request('time_start', request('start'))) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_end">End Time *</label>
                            <input type="time" id="time_end" name="time_end" class="form-control" required value="{{ old('time_end', request('time_end', request('end'))) }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Purpose / Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; justify-content: center;">Submit Reservation</button>
                </form>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
