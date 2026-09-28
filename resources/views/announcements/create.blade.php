@extends('layouts.app')
@section('title', $pageTitle . ' — ' . config('icoep.app_name'))
@section('content')
<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Post Announcement</h1>
        <p class="page-subtitle">Create a new broadcast message for the campus community.</p>
    </div>
    <a href="{{ url('/announcements') }}" class="btn btn-secondary">Cancel</a>
</div>
<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="{{ url('/announcements') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="title">Announcement Title *</label>
                <input type="text" id="title" name="title" class="form-control" required autofocus value="{{ old('title') }}">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="general" {{ old('priority') === 'general' ? 'selected' : '' }}>General</option>
                        <option value="important" {{ old('priority') === 'important' ? 'selected' : '' }}>Important</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="audience">Target Audience</label>
                    <select id="audience" name="audience" class="form-control">
                        <option value="all">All Members</option>
                        <option value="students">Students Only</option>
                        <option value="faculty">Faculty Only</option>
                        <option value="staff">Staff Only</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="content">Message Content *</label>
                <textarea id="content" name="content" class="form-control" rows="8" required placeholder="Write your announcement here...">{{ old('content') }}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="expires_at">Expiration Date (Optional)</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" class="form-control" value="{{ old('expires_at') }}">
                    <div class="form-hint">Announcement will be hidden automatically after this date.</div>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 15px;">
                    <label class="form-check">
                        <input type="checkbox" name="is_pinned" value="1" {{ old('is_pinned') ? 'checked' : '' }}>
                        <span class="form-check-label">Pin to top of the list</span>
                    </label>
                </div>
            </div>
            <hr style="border: 0; border-top: 1px solid var(--color-surface-200); margin: 2rem 0;">
            <button type="submit" class="btn btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                Post Announcement
            </button>
        </form>
    </div>
</div>
@endsection
