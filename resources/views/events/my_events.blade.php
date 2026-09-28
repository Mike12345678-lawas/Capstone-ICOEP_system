@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">My Events</h1>
        <p class="page-subtitle">Events you have registered for.</p>
    </div>
    <a href="{{ url('/events') }}" class="btn btn-secondary">&larr; Browse Events</a>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        @if($registrations->isEmpty())
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect></svg>
                <h3>No Registrations Yet</h3>
                <p>You haven't registered for any events. <a href="{{ url('/events') }}">Browse events</a> to get started.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Event</th><th>Date</th><th>Venue</th><th>Status</th><th>Attended</th></tr></thead>
                    <tbody>
                    @foreach($registrations as $reg)
                        <tr>
                            <td><a href="{{ url('/events/' . $reg->event->id) }}" style="font-weight: 500; color: var(--color-brand-600);">{{ $reg->event->title }}</a></td>
                            <td>{{ $reg->event->event_date->format('M d, Y') }}</td>
                            <td>{{ $reg->event->venue ?: '—' }}</td>
                            <td>
                                @php $badge = match($reg->event->status) { 'upcoming' => 'badge-general', 'ongoing' => 'badge-success', 'completed' => 'badge-warning', default => 'badge-error' }; @endphp
                                <span class="badge {{ $badge }}">{{ ucfirst($reg->event->status) }}</span>
                            </td>
                            <td>{!! $reg->has_attended ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-general">No</span>' !!}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            {{ $registrations->links('partials.pagination') }}
        @endif
    </div>
</div>
@endsection
