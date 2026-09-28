/**
 * ICOEP — Interactive Calendar
 * Displays events and facility reservations in a monthly view.
 */

class ICOEPCalendar {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;
        
        this.currentDate = new Date();
        this.currentMonth = this.currentDate.getMonth();
        this.currentYear = this.currentDate.getFullYear();
        this.events = options.events || [];
        this.onDateClick = options.onDateClick || null;
        this.onEventClick = options.onEventClick || null;
        
        this.render();
    }
    
    render() {
        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        const firstDay = new Date(this.currentYear, this.currentMonth, 1).getDay();
        const daysInMonth = new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        const today = new Date();
        
        let html = `
            <div class="calendar">
                <div class="calendar-header">
                    <button class="btn btn-ghost btn-icon calendar-nav" id="calPrev">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    </button>
                    <h3 class="calendar-title">${monthNames[this.currentMonth]} ${this.currentYear}</h3>
                    <button class="btn btn-ghost btn-icon calendar-nav" id="calNext">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-days-header">
                        ${dayNames.map(d => `<div class="calendar-day-name">${d}</div>`).join('')}
                    </div>
                    <div class="calendar-days">
        `;
        
        // Empty cells before first day
        for (let i = 0; i < firstDay; i++) {
            html += `<div class="calendar-day empty"></div>`;
        }
        
        // Days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const isToday = (day === today.getDate() && this.currentMonth === today.getMonth() && this.currentYear === today.getFullYear());
            const dayEvents = this.events.filter(e => e.date === dateStr);
            
            let dayClass = 'calendar-day';
            if (isToday) dayClass += ' today';
            if (dayEvents.length > 0) dayClass += ' has-events';
            
            html += `
                <div class="${dayClass}" data-date="${dateStr}">
                    <span class="calendar-day-number">${day}</span>
                    ${dayEvents.slice(0, 2).map(e => `
                        <div class="calendar-event calendar-event-${e.type || 'default'}" data-event-id="${e.id}" title="${this.escapeHtml(e.title)}">
                            ${this.escapeHtml(e.title)}
                        </div>
                    `).join('')}
                    ${dayEvents.length > 2 ? `<div class="calendar-more">+${dayEvents.length - 2} more</div>` : ''}
                </div>
            `;
        }
        
        html += `
                    </div>
                </div>
            </div>
        `;
        
        this.container.innerHTML = html;
        this.attachEvents();
    }
    
    attachEvents() {
        document.getElementById('calPrev')?.addEventListener('click', () => {
            this.currentMonth--;
            if (this.currentMonth < 0) {
                this.currentMonth = 11;
                this.currentYear--;
            }
            this.render();
        });
        
        document.getElementById('calNext')?.addEventListener('click', () => {
            this.currentMonth++;
            if (this.currentMonth > 11) {
                this.currentMonth = 0;
                this.currentYear++;
            }
            this.render();
        });
        
        // Day click
        this.container.querySelectorAll('.calendar-day:not(.empty)').forEach(day => {
            day.addEventListener('click', (e) => {
                if (e.target.classList.contains('calendar-event')) return;
                const date = day.getAttribute('data-date');
                if (this.onDateClick) this.onDateClick(date);
            });
        });
        
        // Event click
        this.container.querySelectorAll('.calendar-event').forEach(event => {
            event.addEventListener('click', (e) => {
                e.stopPropagation();
                const eventId = event.getAttribute('data-event-id');
                if (this.onEventClick) this.onEventClick(eventId);
            });
        });
    }
    
    setEvents(events) {
        this.events = events;
        this.render();
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Calendar-specific styles (injected)
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .calendar {
            background: var(--color-bg-card);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            overflow: hidden;
        }
        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--spacing-lg);
            border-bottom: 1px solid var(--color-border);
        }
        .calendar-title {
            font-family: var(--font-heading);
            font-size: var(--text-lg);
            font-weight: 700;
        }
        .calendar-grid {
            padding: var(--spacing-md);
        }
        .calendar-days-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            margin-bottom: var(--spacing-sm);
        }
        .calendar-day-name {
            text-align: center;
            font-size: var(--text-xs);
            font-weight: 600;
            color: var(--color-text-muted);
            padding: var(--spacing-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
        }
        .calendar-day {
            min-height: 80px;
            padding: 6px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: background var(--transition-fast);
        }
        .calendar-day:hover:not(.empty) {
            background: rgba(59, 130, 246, 0.06);
        }
        .calendar-day.empty {
            cursor: default;
        }
        .calendar-day.today .calendar-day-number {
            background: var(--gradient-primary);
            color: white;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .calendar-day-number {
            font-size: var(--text-sm);
            font-weight: 500;
            color: var(--color-text-secondary);
            margin-bottom: 4px;
            display: inline-flex;
        }
        .calendar-event {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 3px;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
            font-weight: 500;
        }
        .calendar-event-default { background: var(--color-info-soft); color: var(--color-info); }
        .calendar-event-academic { background: rgba(99, 102, 241, 0.15); color: var(--color-accent-secondary); }
        .calendar-event-cultural { background: rgba(245, 158, 11, 0.15); color: var(--color-gold); }
        .calendar-event-sports { background: var(--color-success-soft); color: var(--color-success); }
        .calendar-event-spiritual { background: rgba(139, 92, 246, 0.15); color: var(--color-accent-tertiary); }
        .calendar-event-reservation { background: rgba(236, 72, 153, 0.15); color: #ec4899; }
        .calendar-more {
            font-size: 10px;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 2px 6px;
        }
        .calendar-more:hover {
            color: var(--color-accent-primary);
        }
        @media (max-width: 768px) {
            .calendar-day {
                min-height: 50px;
                padding: 4px;
            }
            .calendar-event {
                display: none;
            }
            .calendar-day.has-events .calendar-day-number::after {
                content: '';
                display: block;
                width: 5px;
                height: 5px;
                border-radius: 50%;
                background: var(--color-accent-primary);
                margin: 2px auto 0;
            }
        }
    `;
    document.head.appendChild(style);
})();
