@extends('layouts.app')

@section('title', $pageTitle . ' — ' . config('icoep.app_name'))

@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">{{ $facility->name }}</h1>
        <p class="page-subtitle">{{ $facility->location }} &bull; Capacity: {{ $facility->capacity }}</p>
    </div>
    <a href="{{ url('/facilities') }}" class="btn btn-secondary">&larr; Back to List</a>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: 1.5rem;">
    <div>
        {{-- Facility Details --}}
        <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
            <div class="card-header">
                <h3 class="card-title">About This Facility</h3>
                @php
                    $statusBadge = match($facility->status) {
                        'available' => 'badge-success',
                        'maintenance' => 'badge-warning',
                        default => 'badge-error',
                    };
                @endphp
                <span class="badge {{ $statusBadge }}">{{ ucfirst($facility->status) }}</span>
            </div>
            <div class="card-body">
                <p style="line-height: 1.8;">{{ $facility->description ?: 'No description available.' }}</p>
            </div>
        </div>

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
                        <table class="table">
                            <thead>
                                <tr><th>Date</th><th>Time</th><th>Title</th><th>Requested By</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach($reservations as $res)
                                <tr>
                                    <td>{{ $res->reservation_date->format('M d, Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($res->time_start)->format('h:i A') }} - {{ \Carbon\Carbon::parse($res->time_end)->format('h:i A') }}</td>
                                    <td>{{ $res->title }}</td>
                                    <td>{{ $res->user->full_name }}</td>
                                    <td><span class="badge badge-{{ $res->status === 'approved' ? 'success' : 'warning' }}">{{ ucfirst($res->status) }}</span></td>
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
                        <input type="text" id="title" name="title" class="form-control" required value="{{ old('title') }}" placeholder="e.g., Department Meeting">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="reservation_date">Date *</label>
                        <input type="date" id="reservation_date" name="reservation_date" class="form-control" required value="{{ old('reservation_date') }}" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="time_start">Start Time *</label>
                            <input type="time" id="time_start" name="time_start" class="form-control" required value="{{ old('time_start') }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="time_end">End Time *</label>
                            <input type="time" id="time_end" name="time_end" class="form-control" required value="{{ old('time_end') }}">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="description">Purpose / Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg">Submit Reservation</button>
                </form>
            @endif
        </div>
    </div>
    @endif
</div>
@endsection
