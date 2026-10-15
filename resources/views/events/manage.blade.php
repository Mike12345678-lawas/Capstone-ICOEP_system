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
        <a href="{{ url('/events/' . $event->id . '/scanner') }}" class="btn btn-primary">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 4px;">
                <rect x="3" y="3" width="7" height="7"></rect>
                <rect x="14" y="3" width="7" height="7"></rect>
                <rect x="14" y="14" width="7" height="7"></rect>
                <rect x="3" y="14" width="7" height="7"></rect>
            </svg>
            QR Scanner
        </a>
    </div>
</div>

{{-- Status Control --}}
<div class="card animate-fade-in-up" style="animation-delay: 100ms; margin-bottom: 1.5rem;">
    <div class="card-header"><h3 class="card-title">Event Status</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ url('/events/' . $event->id . '/status') }}" class="status-control-group">
            @csrf @method('PATCH')
            <select name="status" class="form-control" style="min-width: 160px; max-width: 260px;">
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
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
        <h3 class="card-title" style="margin: 0;">Registered Participants ({{ $event->registrations->count() }})</h3>
    </div>
    <div class="card-body">
        @if($event->registrations->isEmpty())
            <div class="empty-state" style="padding: 2.5rem 1rem; text-align: center;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin: 0 auto 0.75rem auto; color: var(--color-surface-400);">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <p class="text-muted" style="margin: 0; font-size: 0.95rem;">No participants have registered for this event yet.</p>
            </div>
        @else
            @php
                $totalCount = $event->registrations->count();
                $attendedCount = $event->registrations->where('has_attended', true)->count();
                $absentCount = $totalCount - $attendedCount;
            @endphp

            {{-- Participant Management Toolbar --}}
            <div class="participant-management-toolbar">
                <div class="participant-search-wrapper">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" id="participant-search" class="participant-search-input" placeholder="Search participant or email..." autocomplete="off">
                </div>

                <div class="participant-toolbar-actions">
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-select-all" title="Mark all registered participants as attended">
                        Mark All Attended
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-clear-all" title="Mark all registered participants as absent">
                        Mark All Absent
                    </button>

                    <div class="view-mode-toggle" id="view-mode-toggle" role="group" aria-label="View mode">
                        <button type="button" class="view-mode-btn" id="view-cards-btn" title="Stacked Cards View (Best for Mobile)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="7" rx="1"></rect>
                                <rect x="3" y="14" width="18" height="7" rx="1"></rect>
                            </svg>
                            Cards
                        </button>
                        <button type="button" class="view-mode-btn" id="view-table-btn" title="Full Table View (Scroll sideways on small screens)">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="9" y1="21" x2="9" y2="9"></line>
                            </svg>
                            Table
                        </button>
                    </div>
                </div>
            </div>

            {{-- Stats Pills --}}
            <div class="participant-stats-bar">
                <span class="stat-pill stat-pill-total" id="stat-total">Total: {{ $totalCount }}</span>
                <span class="stat-pill stat-pill-attended" id="stat-attended">Attended: {{ $attendedCount }}</span>
                <span class="stat-pill stat-pill-absent" id="stat-absent">Absent: {{ $absentCount }}</span>
            </div>

            {{-- Horizontal Scroll Hint Banner (shows when table mode is active) --}}
            <div class="table-scroll-hint" id="table-scroll-hint">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
                <span>Swipe left / right to scroll through table columns</span>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="9 18 15 12 9 6"></polyline>
                </svg>
            </div>

            {{-- Main Attendance Form --}}
            <form method="POST" action="{{ url('/events/' . $event->id . '/attendance') }}" id="attendance-form">
                @csrf
                <div class="table-scroll-wrapper participant-table-scroll is-cards-view" id="participant-table-wrapper">
                    <table class="data-table mobile-cards participant-table" id="participants-table">
                        <thead>
                            <tr>
                                <th style="min-width: 200px;">Participant</th>
                                <th style="min-width: 220px;">Email</th>
                                <th style="min-width: 170px;">Registered At</th>
                                <th style="min-width: 140px; text-align: center;">Attendance</th>
                            </tr>
                        </thead>
                        <tbody id="participants-tbody">
                        @foreach($event->registrations as $reg)
                            <tr class="participant-row" data-name="{{ strtolower($reg->user->full_name) }}" data-email="{{ strtolower($reg->user->email) }}">
                                <td data-label="Participant">
                                    <div class="participant-user-info">
                                        <div class="user-avatar-initials">{{ $reg->user->initials }}</div>
                                        <div>
                                            <div class="participant-name">{{ $reg->user->full_name }}</div>
                                            <div class="participant-role text-muted">
                                                {{ $reg->user->role?->name ?? 'User' }}
                                                @if($reg->user->department)
                                                    &bull; {{ $reg->user->department->name }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="Email">
                                    <a href="mailto:{{ $reg->user->email }}" class="email-text">{{ $reg->user->email }}</a>
                                </td>
                                <td data-label="Registered">
                                    <span class="text-surface-700">{{ $reg->registered_at->format('M d, Y h:i A') }}</span>
                                </td>
                                <td data-label="Attendance">
                                    <label class="attendance-toggle-label" for="att-{{ $reg->id }}" title="Toggle attendance for {{ $reg->user->full_name }}">
                                        <input type="checkbox" id="att-{{ $reg->id }}" name="user_ids[]" value="{{ $reg->user_id }}"
                                            class="attendance-checkbox" {{ $reg->has_attended ? 'checked' : '' }}
                                            onchange="updateAttendanceBadge(this)">
                                        <span class="attendance-badge-state {{ $reg->has_attended ? 'is-attended' : 'is-absent' }}" id="badge-{{ $reg->id }}">
                                            <span class="attendance-status-icon">{{ $reg->has_attended ? '✓' : '✕' }}</span>
                                            <span class="attendance-status-text">{{ $reg->has_attended ? 'Attended' : 'Absent' }}</span>
                                        </span>
                                    </label>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>

                    <div class="participant-empty-search" id="empty-search-msg">
                        No participants match your search query.
                    </div>
                </div>

                {{-- Action / Submit Bar --}}
                <div class="participant-submit-bar">
                    <span class="text-muted text-sm" id="attended-summary-text">
                        <strong id="live-attended-count">{{ $attendedCount }}</strong> of {{ $totalCount }} participant(s) marked as attended.
                    </span>
                    <button type="submit" class="btn btn-primary" id="save-attendance-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; vertical-align: middle; margin-right: 6px;">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        Save Attendance
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const table = document.getElementById('participants-table');
    const wrapper = document.getElementById('participant-table-wrapper');
    const scrollHint = document.getElementById('table-scroll-hint');
    const cardsBtn = document.getElementById('view-cards-btn');
    const tableBtn = document.getElementById('view-table-btn');
    const searchInput = document.getElementById('participant-search');
    const btnSelectAll = document.getElementById('btn-select-all');
    const btnClearAll = document.getElementById('btn-clear-all');
    const rows = document.querySelectorAll('.participant-row');
    const emptyMsg = document.getElementById('empty-search-msg');

    if (!table) return;

    // ─── View Mode Switcher (Cards vs Table) ───
    function setViewMode(mode) {
        if (mode === 'table') {
            table.classList.add('force-table-view');
            table.classList.remove('force-cards-view');
            wrapper.classList.remove('is-cards-view');
            wrapper.classList.add('is-table-view');
            if (cardsBtn) cardsBtn.classList.remove('active');
            if (tableBtn) tableBtn.classList.add('active');
            if (scrollHint) {
                // Show hint if content overflows wrapper
                if (window.innerWidth < 768 || wrapper.scrollWidth > wrapper.clientWidth) {
                    scrollHint.classList.add('show');
                } else {
                    scrollHint.classList.remove('show');
                }
            }
        } else {
            // Cards mode
            table.classList.remove('force-table-view');
            if (window.innerWidth >= 768 && window.innerWidth <= 1024) {
                table.classList.add('force-cards-view');
            } else {
                table.classList.remove('force-cards-view');
            }
            wrapper.classList.add('is-cards-view');
            wrapper.classList.remove('is-table-view');
            if (cardsBtn) cardsBtn.classList.add('active');
            if (tableBtn) tableBtn.classList.remove('active');
            if (scrollHint) scrollHint.classList.remove('show');
        }
        try {
            localStorage.setItem('event_manage_view_mode', mode);
        } catch (e) {}
    }

    // Default mode: On mobile screens (<768px), default to cards. On tablets/desktops, table view.
    let savedMode = null;
    try {
        savedMode = localStorage.getItem('event_manage_view_mode');
    } catch (e) {}

    if (savedMode === 'cards' || savedMode === 'table') {
        setViewMode(savedMode);
    } else {
        if (window.innerWidth < 768) {
            setViewMode('cards');
        } else {
            setViewMode('table');
        }
    }

    if (cardsBtn) {
        cardsBtn.addEventListener('click', function () {
            setViewMode('cards');
        });
    }

    if (tableBtn) {
        tableBtn.addEventListener('click', function () {
            setViewMode('table');
        });
    }

    // Responsive window resize handler
    window.addEventListener('resize', function () {
        const isTable = table.classList.contains('force-table-view');
        if (isTable && scrollHint) {
            if (window.innerWidth < 768 || wrapper.scrollWidth > wrapper.clientWidth) {
                scrollHint.classList.add('show');
            } else {
                scrollHint.classList.remove('show');
            }
        }
    });

    // ─── Real-Time Search Filtering ───
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            rows.forEach(function (row) {
                const name = row.getAttribute('data-name') || '';
                const email = row.getAttribute('data-email') || '';
                const match = !query || name.includes(query) || email.includes(query);

                if (match) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (emptyMsg) {
                emptyMsg.style.display = visibleCount === 0 ? 'block' : 'none';
            }
        });
    }

    // ─── Bulk Select / Clear All ───
    if (btnSelectAll) {
        btnSelectAll.addEventListener('click', function () {
            const checkboxes = table.querySelectorAll('.attendance-checkbox');
            checkboxes.forEach(function (cb) {
                // Only select visible rows if filtered
                const row = cb.closest('.participant-row');
                if (!row || row.style.display !== 'none') {
                    cb.checked = true;
                    updateAttendanceBadge(cb, false);
                }
            });
            recalcStats();
        });
    }

    if (btnClearAll) {
        btnClearAll.addEventListener('click', function () {
            const checkboxes = table.querySelectorAll('.attendance-checkbox');
            checkboxes.forEach(function (cb) {
                const row = cb.closest('.participant-row');
                if (!row || row.style.display !== 'none') {
                    cb.checked = false;
                    updateAttendanceBadge(cb, false);
                }
            });
            recalcStats();
        });
    }
});

// ─── Attendance Badge & Stats Sync ───
function updateAttendanceBadge(checkbox, triggerRecalc = true) {
    const label = checkbox.closest('.attendance-toggle-label');
    if (!label) return;

    const badge = label.querySelector('.attendance-badge-state');
    if (!badge) return;

    const icon = badge.querySelector('.attendance-status-icon');
    const text = badge.querySelector('.attendance-status-text');

    if (checkbox.checked) {
        badge.classList.remove('is-absent');
        badge.classList.add('is-attended');
        if (icon) icon.textContent = '✓';
        if (text) text.textContent = 'Attended';
    } else {
        badge.classList.remove('is-attended');
        badge.classList.add('is-absent');
        if (icon) icon.textContent = '✕';
        if (text) text.textContent = 'Absent';
    }

    if (triggerRecalc) {
        recalcStats();
    }
}

function recalcStats() {
    const checkboxes = document.querySelectorAll('.attendance-checkbox');
    const total = checkboxes.length;
    let attended = 0;

    checkboxes.forEach(function (cb) {
        if (cb.checked) attended++;
    });

    const absent = total - attended;

    const statTotal = document.getElementById('stat-total');
    const statAttended = document.getElementById('stat-attended');
    const statAbsent = document.getElementById('stat-absent');
    const liveAttended = document.getElementById('live-attended-count');

    if (statTotal) statTotal.textContent = 'Total: ' + total;
    if (statAttended) statAttended.textContent = 'Attended: ' + attended;
    if (statAbsent) statAbsent.textContent = 'Absent: ' + absent;
    if (liveAttended) liveAttended.textContent = attended;
}
</script>
@endsection
