@php
    $currentSection = request()->get('section', 'overview');
    $roleName = auth()->user()->role->name ?? '';
@endphp
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ url('/dashboard') }}" class="sidebar-brand">
            <div class="brand-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-name">{{ config('icoep.app_name') }}</span>
                <span class="brand-sub">{{ config('icoep.institution_short') }}</span>
            </div>
        </a>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">
                <span>Main</span>
            </div>
            <div class="nav-section-content">
                <a href="{{ url('/dashboard') }}" class="nav-link {{ $currentSection === 'overview' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ url('/dashboard?section=calendar') }}" class="nav-link {{ $currentSection === 'calendar' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Calendar</span>
                </a>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">
                <span>Operations</span>
            </div>
            <div class="nav-section-content">
                <a href="{{ url('/dashboard?section=facilities') }}" class="nav-link {{ $currentSection === 'facilities' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                    <span>Facilities</span>
                </a>

                <a href="{{ url('/dashboard?section=equipment') }}" class="nav-link {{ $currentSection === 'equipment' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                    </svg>
                    <span>Equipment</span>
                </a>
            </div>
        </div>

        <div class="nav-section">
            <div class="nav-section-title">
                <span>Engagement</span>
            </div>
            <div class="nav-section-content">
                <a href="{{ url('/dashboard?section=events') }}" class="nav-link {{ $currentSection === 'events' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span>Events</span>
                </a>

                <a href="{{ url('/dashboard?section=announcements') }}" class="nav-link {{ $currentSection === 'announcements' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <span>Announcements</span>
                </a>
            </div>
        </div>

        @if($roleName === 'Administrator' || $roleName === 'Staff')
        <div class="nav-section">
            <div class="nav-section-title">
                <span>Management</span>
            </div>
            <div class="nav-section-content">
                @if($roleName === 'Administrator')
                <a href="{{ url('/dashboard?section=users') }}" class="nav-link {{ $currentSection === 'users' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                    <span>Users</span>
                </a>
                @endif

                <a href="{{ url('/dashboard?section=manage-bookings') }}" class="nav-link {{ $currentSection === 'manage-bookings' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                    <span>Manage Bookings</span>
                </a>

                <a href="{{ url('/dashboard?section=manage-equipment') }}" class="nav-link {{ $currentSection === 'manage-equipment' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                    </svg>
                    <span>Manage Equipment</span>
                </a>

                <a href="{{ url('/dashboard?section=inventory') }}" class="nav-link {{ $currentSection === 'inventory' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line>
                        <line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line>
                    </svg>
                    <span>Inventory</span>
                </a>
            </div>
        </div>
        @endif

        @if($roleName === 'Administrator')
        <div class="nav-section">
            <div class="nav-section-title">
                <span>Reports</span>
            </div>
            <div class="nav-section-content">
                <a href="{{ url('/dashboard?section=reports&report=facility-usage') }}" class="nav-link {{ $currentSection === 'reports' ? 'active' : '' }}">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    <span>Reports &amp; Analytics</span>
                </a>
            </div>
        </div>
        @endif
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar small">
                {{ auth()->user()->initials }}
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name">{{ auth()->user()->full_name }}</span>
                <span class="sidebar-user-role">{{ $roleName }}</span>
            </div>
        </div>
    </div>
</aside>
