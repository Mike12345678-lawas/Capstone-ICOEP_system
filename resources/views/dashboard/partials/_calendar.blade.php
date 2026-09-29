<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Interactive Calendar</h1>
        <p class="page-subtitle">View facility reservations and campus events at a glance.</p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <!-- Calendar Legend -->
        <div class="calendar-legend" style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; font-size: 0.875rem; color: var(--color-text);">
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #10b981;"></span> Approved Booking
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #f59e0b;"></span> Pending Booking
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #3b82f6;"></span> Sports Event
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #8b5cf6;"></span> Academic Event
            </div>
            <div style="display: flex; align-items: center; gap: 0.35rem;">
                <span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%; background-color: #6366f1;"></span> Spiritual Event
            </div>
        </div>

        <div id="calendar" style="min-height: 600px;"></div>
    </div>
</div>

<!-- Event Popover Modal -->
<dialog id="eventPopover" class="card shadow-lg" style="padding: 0; border: none; border-radius: 8px; max-width: 400px; width: 100%; top: 50%; left: 50%; transform: translate(-50%, -50%); margin: 0; position: fixed;">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--color-border); padding: 1rem;">
        <h3 id="popoverTitle" style="margin: 0; font-size: 1.1rem; color: var(--color-text-dark);">Event Title</h3>
        <button id="closePopover" style="background: none; border: none; cursor: pointer; color: var(--color-text);">&times;</button>
    </div>
    <div class="card-body" style="padding: 1rem;">
        <p id="popoverTime" style="font-size: 0.9rem; color: var(--color-text); margin-bottom: 0.5rem;"></p>
        <p id="popoverDesc" style="font-size: 0.95rem; color: var(--color-text-dark); margin-bottom: 1rem;"></p>
        <a id="popoverLink" href="#" class="btn btn-primary" style="display: inline-block; text-decoration: none; padding: 0.5rem 1rem; background-color: var(--color-brand-600); color: white; border-radius: 4px; font-size: 0.9rem;">View Details</a>
    </div>
</dialog>

@push('styles')
<!-- Note: FullCalendar 6+ doesn't require a separate CSS file for basic styling, but it injects styles. We override some here for our theme -->
<style>
    /* Premium tweaks for FullCalendar */
    :root {
        --fc-border-color: var(--color-border);
        --fc-daygrid-event-dot-width: 8px;
        --fc-page-bg-color: transparent;
        --fc-neutral-bg-color: var(--color-bg-alt);
        --fc-today-bg-color: rgba(59, 130, 246, 0.1);
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

    /* Highlight Today */
    .fc-day-today {
        background-color: var(--fc-today-bg-color) !important;
        box-shadow: inset 0 0 0 2px var(--color-brand-500);
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

    /* Dot indicator styling for 'more' link */
    .fc-daygrid-more-link {
        display: inline-block;
        background-color: var(--color-bg-alt);
        border: 1px solid var(--color-border);
        border-radius: 12px;
        padding: 2px 8px !important;
        font-size: 0.75rem;
        font-weight: 600;
        color: var(--color-text-dark) !important;
        margin-top: 2px;
        text-align: center;
    }
    .fc-daygrid-more-link::before {
        content: '•';
        color: var(--color-brand-500);
        margin-right: 4px;
        font-size: 1rem;
        line-height: 1;
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

    /* Dialog backdrop */
    dialog::backdrop {
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(2px);
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
        
        var popover = document.getElementById('eventPopover');
        var popoverTitle = document.getElementById('popoverTitle');
        var popoverDesc = document.getElementById('popoverDesc');
        var popoverTime = document.getElementById('popoverTime');
        var popoverLink = document.getElementById('popoverLink');
        var closePopoverBtn = document.getElementById('closePopover');

        closePopoverBtn.addEventListener('click', function() {
            popover.close();
        });
        
        popover.addEventListener('click', function(e) {
            if (e.target === popover) {
                popover.close();
            }
        });

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: isMobile ? 'listMonth' : 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: isMobile ? 'dayGridMonth,listMonth' : 'dayGridMonth,timeGridWeek,timeGridDay,listMonth'
            },
            events: '/api/calendar/events',
            eventClick: function(info) {
                info.jsEvent.preventDefault(); // don't let the browser navigate
                
                popoverTitle.textContent = info.event.title;
                
                // Format time
                var start = info.event.start;
                var end = info.event.end;
                var timeStr = '';
                if(start) {
                    timeStr += start.toLocaleString();
                }
                if(end) {
                    timeStr += ' - ' + end.toLocaleString();
                }
                popoverTime.textContent = timeStr;
                
                popoverDesc.textContent = info.event.extendedProps.description || 'No description provided.';
                
                if(info.event.url) {
                    popoverLink.href = info.event.url;
                    popoverLink.style.display = 'inline-block';
                } else {
                    popoverLink.style.display = 'none';
                }
                
                popover.showModal();
            },
            height: 'auto',
            navLinks: true, 
            nowIndicator: true,
            dayMaxEvents: true,
            dayMaxEventRows: 3, // Show dot indicator if more than 3 events
        });

        calendar.render();
    });
</script>
@endpush
