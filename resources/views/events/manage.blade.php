@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage: {{ $event->title }}</h1>
        <p class="page-subtitle">{{ $event->event_date->format('F d, Y') }} &bull; {{ $event->registrations->count() }} registered</p>
    </div>
    <div class="btn-group">
        <a href="{{ url('/events/' . $event->id) }}" class="btn btn-secondary">&larr; Back to Event</a>
    </div>
</div>

{{-- Status Control --}}
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-bottom: 1.5rem;">
    <div class="card-header"><h3 class="card-title">Event Status</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ url('/events/' . $event->id . '/status') }}" class="flex gap-sm" style="align-items: center;">
            @csrf @method('PATCH')
            <select name="status" class="form-control" style="width: auto;">
                @foreach(['upcoming', 'ongoing', 'completed', 'cancelled'] as $s)
                    <option value="{{ $s }}" {{ $event->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-primary">Update Status</button>
        </form>
    </div>
</div>

{{-- Attendance / Registrant List --}}
<div class="card animate-fade-in-up" style="animation-delay: 200ms;">
    <div class="card-header"><h3 class="card-title">Registered Participants ({{ $event->registrations->count() }})</h3></div>
    <div class="card-body">
        @if($event->registrations->isEmpty())
            <div class="empty-state" style="padding: 1.5rem;"><p class="text-muted">No one has registered yet.</p></div>
        @else
            <form method="POST" action="{{ url('/events/' . $event->id . '/attendance') }}">
                @csrf
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Name</th><th>Email</th><th>Registered At</th><th style="text-align: center;">Attended</th></tr></thead>
                        <tbody>
                        @foreach($event->registrations as $reg)
                            <tr>
                                <td style="font-weight: 500;">{{ $reg->user->full_name }}</td>
                                <td class="text-muted">{{ $reg->user->email }}</td>
                                <td>{{ $reg->registered_at->format('M d, Y h:i A') }}</td>
                                <td style="text-align: center;">
                                    <input type="checkbox" name="user_ids[]" value="{{ $reg->user_id }}" {{ $reg->has_attended ? 'checked' : '' }}>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">Save Attendance</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
