<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Interactive Calendar</h1>
        <p class="page-subtitle">View facility reservations and campus events at a glance.</p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <div id="calendar" style="min-height: 600px;"></div>
    </div>
</div>

@push('styles')
<!-- Note: FullCalendar 6+ doesn't require a separate CSS file for basic styling, but it injects styles. We override some here for our theme -->
<style>
    /* Premium tweaks for FullCalendar */
    :root {
        --fc-border-color: var(--color-border);
        --fc-daygrid-event-dot-width: 8px;
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: var(--color-bg-alt);
        --fc-today-bg-color: rgba(59, 130, 246, 0.05);
    }
    
    .fc-theme-standard .fc-scrollgrid,
    .fc-theme-standard td, .fc-theme-standard th {
        border-color: var(--color-border);
    }
    
    .fc-col-header-cell-cushion { 
        padding: 0.75rem !important; 
        font-weight: 600; 
        color: var(--color-text-dark); 
    }
    
    .fc-daygrid-day-number { 
        padding: 0.5rem !important; 
        color: var(--color-text); 
        font-weight: 500; 
    }
    
    .fc-event { 
        border: none !important; 
        border-radius: 4px; 
        padding: 2px 4px; 
        box-shadow: 0 1px 2px rgba(0,0,0,0.05); 
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    
    .fc-event:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }

    .fc-button-primary { 
        background-color: var(--color-brand-600) !important; 
        border-color: var(--color-brand-600) !important; 
        border-radius: 6px !important;
        text-transform: capitalize !important;
        font-family: var(--font-primary) !important;
        font-weight: 500 !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .fc-button-primary:hover { 
        background-color: var(--color-brand-700) !important; 
    }
    .fc-button-primary:disabled { 
        background-color: var(--color-brand-300) !important; 
        border-color: var(--color-brand-300) !important;
    }
    .fc-toolbar-title { 
        font-family: var(--font-secondary); 
        font-weight: 700; 
        color: var(--color-text-dark); 
    }
    
    /* Dark mode support */
    html.dark .fc-col-header-cell-cushion,
    html.dark .fc-daygrid-day-number,
    html.dark .fc-toolbar-title,
    html.dark .fc-list-event-title,
    html.dark .fc-list-event-time {
        color: #f3f4f6 !important;
    }
    
    html.dark .fc-theme-standard .fc-scrollgrid,
    html.dark .fc-theme-standard td, 
    html.dark .fc-theme-standard th,
    html.dark .fc-theme-standard .fc-list {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    html.dark .fc-list-day-cushion {
        background-color: rgba(255, 255, 255, 0.05) !important;
        color: #f3f4f6 !important;
    }
    
    html.dark .fc-list-event:hover td {
        background-color: rgba(255, 255, 255, 0.05) !important;
    }

    /* Responsive Toolbar for Mobile */
    @media (max-width: 768px) {
        .fc-header-toolbar {
            flex-direction: column !important;
            gap: 1rem;
        }
        .fc-toolbar-chunk {
            display: flex;
            justify-content: center;
            width: 100%;
        }
        .fc-toolbar-title {
            font-size: 1.25rem !important;
        }
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.9/index.global.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        var isMobile = window.innerWidth < 768;

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: isMobile ? 'listMonth' : 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: isMobile ? 'dayGridMonth,listMonth' : 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: '/api/calendar/events',
            eventDidMount: function(info) {
                if(info.event.extendedProps.description) {
                    info.el.title = info.event.extendedProps.description;
                }
            },
            height: 'auto',
            navLinks: true, 
            nowIndicator: true,
            dayMaxEvents: true,
        });

        calendar.render();
    });
</script>
@endpush
