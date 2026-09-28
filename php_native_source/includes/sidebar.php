<?php
/**
 * Sidebar Navigation
 * ICOEP - Integrated Campus Operations and Engagement Portal
 * 
 * Role-aware sidebar with module links and icons.
 */

$currentPath = $_SERVER['PHP_SELF'] ?? '';
$roleName = $_SESSION['role_name'] ?? '';

/**
 * Check if a nav link is active
 */
function isActive(string $path): string {
    global $currentPath;
    return strpos($currentPath, $path) !== false ? 'active' : '';
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo BASE_URL; ?>/modules/dashboard/index.php" class="sidebar-brand">
            <div class="brand-icon">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="brand-text">
                <span class="brand-name"><?php echo APP_NAME; ?></span>
                <span class="brand-sub"><?php echo INSTITUTION_SHORT; ?></span>
            </div>
        </a>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">Main</span>
            
            <a href="<?php echo BASE_URL; ?>/modules/dashboard/index.php" class="nav-link <?php echo isActive('/dashboard/'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"></rect>
                    <rect x="14" y="3" width="7" height="7"></rect>
                    <rect x="14" y="14" width="7" height="7"></rect>
                    <rect x="3" y="14" width="7" height="7"></rect>
                </svg>
                <span>Dashboard</span>
            </a>
        </div>
        
        <div class="nav-section">
            <span class="nav-section-title">Operations</span>
            
            <a href="<?php echo BASE_URL; ?>/modules/facility/list.php" class="nav-link <?php echo isActive('/facility/'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
                <span>Facilities</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/modules/equipment/list.php" class="nav-link <?php echo isActive('/equipment/'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path>
                </svg>
                <span>Equipment</span>
            </a>
        </div>
        
        <div class="nav-section">
            <span class="nav-section-title">Engagement</span>
            
            <a href="<?php echo BASE_URL; ?>/modules/events/list.php" class="nav-link <?php echo isActive('/events/'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <span>Events</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/modules/announcements/list.php" class="nav-link <?php echo isActive('/announcements/'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <span>Announcements</span>
            </a>
        </div>
        
        <?php if ($roleName === 'Administrator' || $roleName === 'Staff'): ?>
        <div class="nav-section">
            <span class="nav-section-title">Management</span>
            
            <?php if ($roleName === 'Administrator'): ?>
            <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="nav-link <?php echo isActive('/users/'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
                <span>Users</span>
            </a>
            <?php endif; ?>
            
            <a href="<?php echo BASE_URL; ?>/modules/facility/manage.php" class="nav-link <?php echo isActive('/facility/manage'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                </svg>
                <span>Manage Bookings</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/modules/equipment/manage.php" class="nav-link <?php echo isActive('/equipment/manage'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
                <span>Manage Equipment</span>
            </a>
        </div>
        <?php endif; ?>
        
        <?php if ($roleName === 'Administrator'): ?>
        <div class="nav-section">
            <span class="nav-section-title">Reports</span>
            
            <a href="<?php echo BASE_URL; ?>/modules/reports/facility_usage.php" class="nav-link <?php echo isActive('/reports/facility'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="20" x2="18" y2="10"></line>
                    <line x1="12" y1="20" x2="12" y2="4"></line>
                    <line x1="6" y1="20" x2="6" y2="14"></line>
                </svg>
                <span>Facility Reports</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/modules/reports/event_engagement.php" class="nav-link <?php echo isActive('/reports/event'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"></polyline>
                    <polyline points="17 6 23 6 23 12"></polyline>
                </svg>
                <span>Event Reports</span>
            </a>
            
            <a href="<?php echo BASE_URL; ?>/modules/reports/audit_log.php" class="nav-link <?php echo isActive('/reports/audit'); ?>">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span>Audit Log</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="user-avatar small">
                <?php echo getUserInitials(); ?>
            </div>
            <div class="sidebar-user-info">
                <span class="sidebar-user-name"><?php echo sanitize(getUserFullName()); ?></span>
                <span class="sidebar-user-role"><?php echo sanitize($roleName); ?></span>
            </div>
        </div>
    </div>
</aside>
