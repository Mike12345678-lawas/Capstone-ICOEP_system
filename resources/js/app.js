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

    // Mark all notifications as read
    const markAllBtn = document.getElementById('markAllRead');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', (e) => {
            e.preventDefault();
            fetch('/api/notifications/mark-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Content-Type': 'application/json',
                },
            }).then(() => {
                document.getElementById('notifBadge')?.remove();
                document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
                markAllBtn.remove();
            });
        });
    }

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
    document.querySelectorAll('.flash-message').forEach(flash => {
        setTimeout(() => {
            flash.style.transition = 'opacity .3s, transform .3s';
            flash.style.opacity = '0';
            flash.style.transform = 'translateY(-8px)';
            setTimeout(() => flash.remove(), 300);
        }, 5000);
    });
});
