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
                <div class="form-group" style="position: relative;">
                    <label class="form-label" for="audienceDropdownBtn">Target Audience *</label>
                    <div class="custom-multiselect" id="audienceMultiselect" style="position: relative;">
                        <button type="button" class="form-control" id="audienceDropdownBtn" style="display: flex; align-items: center; justify-content: space-between; text-align: left; background: var(--color-surface-50); cursor: pointer; user-select: none;">
                            <span id="audienceDisplaySummary" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 0.9rem; color: var(--color-surface-800); font-weight: 500;">All Campus (Everyone)</span>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink: 0; margin-left: 0.5rem; transition: transform 0.2s;" id="audienceDropdownArrow"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>

                        <div class="custom-multiselect-menu" id="audienceDropdownMenu" style="display: none; position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: #ffffff; border: 1px solid var(--color-surface-300, #cbd5e1); border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15), 0 8px 10px -6px rgba(0,0,0,0.1); z-index: 100; max-height: 280px; overflow-y: auto; padding: 0.5rem;">
                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--color-surface-400); letter-spacing: 0.05em; padding: 0.25rem 0.5rem; margin-top: 0.25rem;">
                                Campus-Wide & Roles
                            </div>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="all" class="audience-checkbox audience-all" {{ (is_array(old('target_audience')) ? in_array('all', old('target_audience')) : old('target_audience', 'all') === 'all') ? 'checked' : '' }}>
                                <span style="font-weight: 600;">All Campus (Everyone)</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="faculty" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('faculty', old('target_audience')) ? 'checked' : '' }}>
                                <span>Faculty Only</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="staff" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('staff', old('target_audience')) ? 'checked' : '' }}>
                                <span>Staff (Non-Teaching) Only</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="student" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('student', old('target_audience')) ? 'checked' : '' }}>
                                <span>Students Only</span>
                            </label>

                            <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; color: var(--color-surface-400); letter-spacing: 0.05em; padding: 0.5rem 0.5rem 0.25rem 0.5rem; border-top: 1px solid var(--color-surface-200); margin-top: 0.25rem;">
                                Academic Programs / Departments
                            </div>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="bsit" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('bsit', old('target_audience')) ? 'checked' : '' }}>
                                <span>BSIT Only</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="bshm" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('bshm', old('target_audience')) ? 'checked' : '' }}>
                                <span>BSHM Only</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="bsba-fm" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('bsba-fm', old('target_audience')) ? 'checked' : '' }}>
                                <span>BSBA-FM Only</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="bsed" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('bsed', old('target_audience')) ? 'checked' : '' }}>
                                <span>BSED Only</span>
                            </label>
                            <label class="multiselect-option" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.4rem 0.5rem; border-radius: 6px; cursor: pointer; font-size: 0.875rem;">
                                <input type="checkbox" name="target_audience[]" value="beed" class="audience-checkbox" {{ is_array(old('target_audience')) && in_array('beed', old('target_audience')) ? 'checked' : '' }}>
                                <span>BEED Only</span>
                            </label>
                        </div>
                    </div>
                    <div class="form-hint">Select one or multiple audiences (e.g. BSIT and BSHM).</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="event_date">Event Date *</label>
                    <input type="date" id="event_date" name="event_date" class="form-control" required min="{{ date('Y-m-d') }}" value="{{ old('event_date') }}">
                </div>
                <div class="form-group">
                    <label class="form-label" for="venue">Venue</label>
                    <input type="text" id="venue" name="venue" class="form-control" value="{{ old('venue') }}" placeholder="e.g., Main Auditorium">
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
            <div class="form-group">
                <label class="form-label" for="max_participants">Max Participants</label>
                <input type="number" id="max_participants" name="max_participants" class="form-control" min="0" value="{{ old('max_participants', 0) }}">
                <div class="form-hint">Set 0 for unlimited.</div>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('audienceDropdownBtn');
    const menu = document.getElementById('audienceDropdownMenu');
    const arrow = document.getElementById('audienceDropdownArrow');
    const summary = document.getElementById('audienceDisplaySummary');
    const allCheckbox = document.querySelector('.audience-all');
    const checkboxes = document.querySelectorAll('.audience-checkbox');

    if (!btn || !menu || !summary) return;

    function updateSummary() {
        const checked = Array.from(checkboxes).filter(cb => cb.checked);
        if (checked.length === 0) {
            allCheckbox.checked = true;
            summary.textContent = 'All Campus (Everyone)';
            return;
        }

        const isAll = allCheckbox && allCheckbox.checked;
        if (isAll) {
            summary.textContent = 'All Campus (Everyone)';
            return;
        }

        const labels = checked.map(cb => cb.parentElement.textContent.trim());
        summary.textContent = labels.join(', ');
    }

    btn.addEventListener('click', function (e) {
        e.stopPropagation();
        const isOpen = menu.style.display === 'block';
        menu.style.display = isOpen ? 'none' : 'block';
        if (arrow) arrow.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
    });

    document.addEventListener('click', function (e) {
        if (!menu.contains(e.target) && !btn.contains(e.target)) {
            menu.style.display = 'none';
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        }
    });

    checkboxes.forEach(function (cb) {
        cb.addEventListener('change', function () {
            if (cb.value === 'all' && cb.checked) {
                checkboxes.forEach(other => {
                    if (other.value !== 'all') other.checked = false;
                });
            } else if (cb.value !== 'all' && cb.checked) {
                if (allCheckbox) allCheckbox.checked = false;
            }
            updateSummary();
        });
    });

    updateSummary();
});
</script>
@endpush
@endsection
