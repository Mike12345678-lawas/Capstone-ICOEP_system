import './bootstrap';

// ─── Dark Mode Toggle ───────────────────────────────────────────────────────
const setupDarkMode = () => {
    const themeToggle = document.getElementById('themeToggle');
    const sunIcon = themeToggle?.querySelector('.sun-icon');
    const moonIcon = themeToggle?.querySelector('.moon-icon');

    // Check initial state (default to light if no preference is saved)
    if (localStorage.theme === 'dark') {
        document.documentElement.classList.add('dark');
        if (sunIcon) sunIcon.style.display = 'none';
        if (moonIcon) moonIcon.style.display = 'block';
    } else {
        document.documentElement.classList.remove('dark');
        if (sunIcon) sunIcon.style.display = 'block';
        if (moonIcon) moonIcon.style.display = 'none';
    }

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.theme = isDark ? 'dark' : 'light';

            if (isDark) {
                if (sunIcon) sunIcon.style.display = 'none';
                if (moonIcon) moonIcon.style.display = 'block';
            } else {
                if (sunIcon) sunIcon.style.display = 'block';
                if (moonIcon) moonIcon.style.display = 'none';
            }
        });
    }
};
setupDarkMode();

// ─── Password Visibility Toggle ──────────────────────────────────────────
window.togglePassword = function (inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPassword = input.type === 'password';
    input.type = isPassword ? 'text' : 'password';
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    btn.title = isPassword ? 'Hide password' : 'Show password';
    btn.innerHTML = isPassword
        ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="23" x2="23" y2="23"></line></svg>'
        : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
};

// ─── Sidebar Toggle ───────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');

    const sidebarOverlay = document.createElement('div');
    sidebarOverlay.className = 'sidebar-overlay';
    document.body.appendChild(sidebarOverlay);

    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            if (window.innerWidth < 1024) {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('open');
            } else {
                sidebar.classList.toggle('collapsed');
                if (mainContent) {
                    mainContent.style.marginLeft = sidebar.classList.contains('collapsed') ? '0' : '16rem';
                }
            }
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('open');
        });
    }

    // ─── Sidebar Accordions ───────────────────────────────────────────────────
    document.querySelectorAll('.toggle-section').forEach(btn => {
        btn.addEventListener('click', () => {
            const isExpanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', !isExpanded);
            const content = btn.nextElementSibling;
            if (content && content.classList.contains('nav-section-content')) {
                content.setAttribute('aria-hidden', isExpanded);
            }
        });
    });

    // ─── User Dropdown ────────────────────────────────────────────────────────
    const userTrigger = document.getElementById('userMenuTrigger');
    const userDropdown = document.getElementById('userDropdown');

    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
            // Close notification dropdown
            document.getElementById('notificationDropdown')?.classList.remove('open');
        });
    }

    // ─── Notification Dropdown ────────────────────────────────────────────────
    const notifBell = document.getElementById('notificationBell');
    const notifDropdown = document.getElementById('notificationDropdown');

    if (notifBell && notifDropdown) {
        notifBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('open');
            // Close user dropdown
            userDropdown?.classList.remove('open');
        });
    }

    // ─── Notification actions: mark read, delete one, clear all ─────────────
    const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;
    const notifList = document.getElementById('notifList');
    const notifEmpty = document.getElementById('notifEmpty');
    const notifActions = document.getElementById('notifActions');

    const notifRequest = (url, method) => fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrf(),
            'Accept': 'application/json',
            'Content-Type': 'application/json',
        },
    }).then(r => {
        if (!r.ok) throw new Error('Request failed');
        return r.json();
    });

    const updateBadge = (count) => {
        let badge = document.getElementById('notifBadge');
        if (count > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge';
                badge.id = 'notifBadge';
                notifBell?.appendChild(badge);
            }
            badge.textContent = count > 99 ? '99+' : count;
        } else {
            badge?.remove();
            document.getElementById('markAllRead')?.remove();
        }
    };

    const refreshEmptyState = () => {
        const hasItems = !!notifList?.querySelector('.notif-item');
        if (notifEmpty) notifEmpty.style.display = hasItems ? 'none' : '';
        if (notifActions) notifActions.style.display = hasItems ? '' : 'none';
    };

    // Mark all as read
    document.getElementById('markAllRead')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        notifRequest('/api/notifications/mark-read', 'POST').then(() => {
            updateBadge(0);
            document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
        });
    });

    // Delete a single notification (event delegation)
    notifList?.addEventListener('click', (e) => {
        const btn = e.target.closest('.notif-delete-single');
        if (!btn) return;
        e.preventDefault();
        e.stopPropagation();

        const item = btn.closest('.notif-item');
        btn.disabled = true;
        item.style.maxHeight = item.offsetHeight + 'px';

        notifRequest('/api/notifications/' + btn.dataset.id, 'DELETE')
            .then(data => {
                item.classList.add('deleting');
                setTimeout(() => {
                    item.remove();
                    refreshEmptyState();
                    // Older notifications exist beyond the 5 shown — reload to reveal them
                    if (!notifList.querySelector('.notif-item') && data.total_count > 0) {
                        window.location.reload();
                    }
                }, 250);
                updateBadge(data.unread_count);
            })
            .catch(() => { btn.disabled = false; });
    });

    // Clear all notifications
    document.getElementById('deleteAllNotifs')?.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (!confirm('Delete all of your notifications? This cannot be undone.')) return;

        notifRequest('/api/notifications', 'DELETE').then(() => {
            notifList?.querySelectorAll('.notif-item').forEach(el => el.remove());
            updateBadge(0);
            refreshEmptyState();
        });
    });

    // ─── Close dropdowns on outside click ─────────────────────────────────────
    document.addEventListener('click', (e) => {
        if (userDropdown && !document.getElementById('userMenuWrapper')?.contains(e.target)) {
            userDropdown.classList.remove('open');
        }
        if (notifDropdown && !document.getElementById('notificationWrapper')?.contains(e.target)) {
            notifDropdown.classList.remove('open');
        }
    });

    // ─── Auto-dismiss flash messages ──────────────────────────────────────────
    // Flashes with suggested alternatives stay until the user clicks close.
    document.querySelectorAll('.flash-message').forEach(flash => {
        if (flash.dataset.persist === 'true' || flash.querySelector('.suggestion-chips')) return;
        setTimeout(() => {
            flash.style.transition = 'opacity .3s, transform .3s';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-8px)';
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    });
});
