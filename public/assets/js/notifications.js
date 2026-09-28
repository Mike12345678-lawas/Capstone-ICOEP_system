/**
 * ICOEP — Notification System
 * Polls for new notifications and updates the bell badge.
 */

class NotificationManager {
    constructor(options = {}) {
        this.pollInterval = options.pollInterval || 30000; // 30 seconds
        this.baseUrl = '/ICOEP_system';
        this.timer = null;
        
        this.init();
    }
    
    init() {
        this.startPolling();
    }
    
    startPolling() {
        this.timer = setInterval(() => this.fetchNotifications(), this.pollInterval);
    }
    
    stopPolling() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
    
    async fetchNotifications() {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(this.baseUrl + '/modules/notifications_api.php?action=get_unread', {
                headers: {
                    'X-CSRF-TOKEN': csrfToken || ''
                }
            });
            
            if (!response.ok) return;
            
            const data = await response.json();
            this.updateBadge(data.unread_count || 0);
            
            if (data.notifications && data.notifications.length > 0) {
                this.updateDropdown(data.notifications);
            }
        } catch (err) {
            // Silently fail — notifications are non-critical
        }
    }
    
    updateBadge(count) {
        let badge = document.getElementById('notifBadge');
        const bell = document.getElementById('notificationBell');
        
        if (!bell) return;
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                badge.id = 'notifBadge';
                bell.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else if (badge) {
            badge.remove();
        }
    }
    
    updateDropdown(notifications) {
        const list = document.getElementById('notifList');
        if (!list) return;
        
        const typeIcons = {
            info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
            success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>',
            warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line></svg>',
            error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line></svg>'
        };
        
        if (notifications.length === 0) {
            list.innerHTML = '<div class="notif-empty"><p>No notifications yet</p></div>';
            return;
        }
        
        list.innerHTML = notifications.map(notif => `
            <a href="${notif.link ? this.baseUrl + this.escapeHtml(notif.link) : '#'}" 
               class="notif-item ${!notif.is_read ? 'unread' : ''}"
               data-id="${notif.id}">
                <div class="notif-icon notif-${this.escapeHtml(notif.type)}">
                    ${typeIcons[notif.type] || typeIcons.info}
                </div>
                <div class="notif-content">
                    <p class="notif-title">${this.escapeHtml(notif.title)}</p>
                    <p class="notif-message">${this.escapeHtml(notif.message)}</p>
                    <span class="notif-time">${this.escapeHtml(notif.time_ago)}</span>
                </div>
            </a>
        `).join('');
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    window.notificationManager = new NotificationManager();
});
