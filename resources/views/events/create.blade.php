@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Create Event</h1>
        <p class="page-subtitle">Schedule a new campus event for students and staff.</p>
    </div>
    <a href="{{ url('/dashboard?section=events') }}" class="btn btn-secondary">Cancel</a>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="{{ url('/events') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="title">Event Title *</label>
                <input type="text" id="title" name="title" class="form-control @error('title') error @enderror" required value="{{ old('title') }}" autofocus>
                @error('title') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            {{-- ─── MANDATORY: Event Targets/Objectives ─── --}}
            <div class="form-group">
                <label class="form-label" for="objectives">Event Targets / Objectives *</label>
                <textarea id="objectives" name="objectives" class="form-control @error('objectives') error @enderror" rows="4"
                          placeholder="Define the key goals and expected outcomes of this event..." required>{{ old('objectives') }}</textarea>
                <div class="form-hint">Required — minimum 10 characters. Clearly state what this event aims to achieve.</div>
                @error('objectives') <span class="form-error">{{ $message }}</span> @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="event_type">Event Type *</label>
                    <select id="event_type" name="event_type" class="form-control" required>
                        <option value="academic" {{ old('event_type') === 'academic' ? 'selected' : '' }}>Academic</option>
                        <option value="cultural" {{ old('event_type') === 'cultural' ? 'selected' : '' }}>Cultural</option>
                        <option value="sports" {{ old('event_type') === 'sports' ? 'selected' : '' }}>Sports</option>
                        <option value="spiritual" {{ old('event_type') === 'spiritual' ? 'selected' : '' }}>Spiritual</option>
                        <option value="other" {{ old('event_type') === 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="event_date">Event Date *</label>
                    <input type="date" id="event_date" name="event_date" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ old('event_date') }}">
                </div>
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
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" class="form-control" value="{{ old('venue') }}" placeholder="e.g., Main Auditorium">
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_participants">Max Participants</label>
                    <input type="number" id="max_participants" name="max_participants" class="form-control" min="0" value="{{ old('max_participants', 0) }}">
                    <div class="form-hint">Set 0 for unlimited.</div>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="6" placeholder="Describe the event details...">{{ old('description') }}</textarea>
            </div>
            <hr style="border: 0; border-top: 1px solid var(--color-surface-200); margin: 2rem 0;">
            <button type="submit" class="btn btn-primary btn-lg">Create Event</button>
        </form>
    </div>
</div>
@endsection
