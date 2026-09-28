/**
 * ICOEP — Global JavaScript
 * Integrated Campus Operations and Engagement Portal
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
    initDropdowns();
    initFlashMessages();
    initAnimatedCounters();
    initModals();
});

/* ============================================================
   1. SIDEBAR TOGGLE (Mobile)
   ============================================================ */
function initSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (!toggle || !sidebar) return;
    
    toggle.addEventListener('click', () => {
        sidebar.classList.toggle('open');
    });
    
    // Close sidebar on outside click (mobile)
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
}

/* ============================================================
   2. DROPDOWN MENUS (Notifications & User Menu)
   ============================================================ */
function initDropdowns() {
    // Notification dropdown
    const notifBell = document.getElementById('notificationBell');
    const notifDropdown = document.getElementById('notificationDropdown');
    
    if (notifBell && notifDropdown) {
        notifBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notifDropdown.classList.toggle('open');
            // Close user dropdown if open
            document.getElementById('userDropdown')?.classList.remove('open');
        });
    }
    
    // User menu dropdown
    const userTrigger = document.getElementById('userMenuTrigger');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            userDropdown.classList.toggle('open');
            // Close notification dropdown if open
            notifDropdown?.classList.remove('open');
        });
    }
    
    // Close all dropdowns on outside click
    document.addEventListener('click', (e) => {
        if (notifDropdown && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('open');
        }
        if (userDropdown && !userDropdown.contains(e.target)) {
            userDropdown.classList.remove('open');
        }
    });
    
    // Mark all notifications as read
    const markAllBtn = document.getElementById('markAllRead');
    if (markAllBtn) {
        markAllBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(getBaseUrl() + '/modules/notifications_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: JSON.stringify({ action: 'mark_all_read' })
                });
                
                if (response.ok) {
                    document.querySelectorAll('.notif-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    const badge = document.getElementById('notifBadge');
                    if (badge) badge.remove();
                    markAllBtn.remove();
                }
            } catch (err) {
                console.error('Failed to mark notifications as read:', err);
            }
        });
    }
}

/* ============================================================
   3. FLASH MESSAGE AUTO-DISMISS
   ============================================================ */
function initFlashMessages() {
    const flash = document.getElementById('flashMessage');
    if (!flash) return;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        flash.style.opacity = '0';
        flash.style.transform = 'translateY(-10px)';
        setTimeout(() => flash.remove(), 300);
    }, 5000);
}

/* ============================================================
   4. ANIMATED COUNTERS (Dashboard Stats)
   ============================================================ */
function initAnimatedCounters() {
    const counters = document.querySelectorAll('[data-counter]');
    
    counters.forEach(counter => {
        const target = parseInt(counter.getAttribute('data-counter'), 10);
        if (isNaN(target)) return;
        
        const duration = 1200;
        const startTime = performance.now();
        
        function updateCounter(currentTime) {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            // Ease out cubic
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(eased * target);
            
            counter.textContent = current.toLocaleString();
            
            if (progress < 1) {
                requestAnimationFrame(updateCounter);
            }
        }
        
        // Start animation when element is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    requestAnimationFrame(updateCounter);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        observer.observe(counter);
    });
}

/* ============================================================
   5. MODAL SYSTEM
   ============================================================ */
function initModals() {
    // Open modal
    document.querySelectorAll('[data-modal-target]').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            const modalId = trigger.getAttribute('data-modal-target');
            openModal(modalId);
        });
    });
    
    // Close modal
    document.querySelectorAll('.modal-close, [data-modal-close]').forEach(btn => {
        btn.addEventListener('click', () => {
            const overlay = btn.closest('.modal-overlay');
            if (overlay) closeModal(overlay.id);
        });
    });
    
    // Close on overlay click
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay.id);
        });
    });
    
    // Close on Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal-overlay.open').forEach(overlay => {
                closeModal(overlay.id);
            });
        }
    });
}

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('open');
        document.body.style.overflow = '';
    }
}

/* ============================================================
   6. UTILITY FUNCTIONS
   ============================================================ */

function getBaseUrl() {
    return '/ICOEP_system';
}

/**
 * Submit a form via fetch with CSRF token
 */
async function submitForm(url, formData) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken && !formData.has('csrf_token')) {
        formData.append('csrf_token', csrfToken);
    }
    
    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });
        return await response.json();
    } catch (err) {
        console.error('Form submission failed:', err);
        return { success: false, message: 'An error occurred. Please try again.' };
    }
}

/**
 * Confirm action with a modal dialog
 */
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toLocaleString();
}

/**
 * Debounce function for search inputs
 */
function debounce(func, wait = 300) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Password strength checker
 */
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const levels = ['weak', 'weak', 'fair', 'good', 'strong', 'strong'];
    return {
        score: strength,
        level: levels[strength],
        label: levels[strength].charAt(0).toUpperCase() + levels[strength].slice(1)
    };
}
