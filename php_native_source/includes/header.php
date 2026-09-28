<?php
/**
 * Header Include
 * ICOEP - Integrated Campus Operations and Engagement Portal
 * 
 * Shared HTML head, top navbar, and opening body markup.
 * Include at the top of every authenticated page.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/functions.php';

$currentUser = currentUser();
$unreadCount = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;
$notifications = $currentUser ? getRecentNotifications($currentUser['id'], 5) : [];
$pageTitle = isset($pageTitle) ? $pageTitle . ' — ' . APP_NAME : APP_FULL_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo APP_FULL_NAME; ?> for <?php echo INSTITUTION_NAME; ?>. Centralized campus operations, facility reservations, equipment logistics, and school-wide event management.">
    <meta name="author" content="<?php echo INSTITUTION_SHORT; ?>">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    
    <title><?php echo sanitize($pageTitle); ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <?php if (isset($extraCSS)): ?>
        <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/<?php echo $extraCSS; ?>">
    <?php endif; ?>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="navbar-left">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="12" x2="21" y2="12"></line>
                            <line x1="3" y1="6" x2="21" y2="6"></line>
                            <line x1="3" y1="18" x2="21" y2="18"></line>
                        </svg>
                    </button>
                    <div class="breadcrumb">
                        <?php if (isset($breadcrumb)): ?>
                            <?php foreach ($breadcrumb as $i => $crumb): ?>
                                <?php if ($i > 0): ?><span class="breadcrumb-sep">/</span><?php endif; ?>
                                <?php if (isset($crumb['url'])): ?>
                                    <a href="<?php echo BASE_URL . $crumb['url']; ?>" class="breadcrumb-link"><?php echo sanitize($crumb['label']); ?></a>
                                <?php else: ?>
                                    <span class="breadcrumb-current"><?php echo sanitize($crumb['label']); ?></span>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="navbar-right">
                    <!-- Notification Bell -->
                    <div class="notification-wrapper" id="notificationWrapper">
                        <button class="notification-bell" id="notificationBell" aria-label="Notifications">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <?php if ($unreadCount > 0): ?>
                                <span class="notification-badge" id="notifBadge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
                            <?php endif; ?>
                        </button>
                        
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notif-header">
                                <h4>Notifications</h4>
                                <?php if ($unreadCount > 0): ?>
                                    <a href="#" id="markAllRead" class="notif-mark-all">Mark all read</a>
                                <?php endif; ?>
                            </div>
                            <div class="notif-list" id="notifList">
                                <?php if (empty($notifications)): ?>
                                    <div class="notif-empty">
                                        <p>No notifications yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <a href="<?php echo $notif['link'] ? BASE_URL . sanitize($notif['link']) : '#'; ?>" 
                                           class="notif-item <?php echo !$notif['is_read'] ? 'unread' : ''; ?>"
                                           data-id="<?php echo $notif['id']; ?>">
                                            <div class="notif-icon notif-<?php echo sanitize($notif['type']); ?>">
                                                <?php if ($notif['type'] === 'success'): ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                <?php elseif ($notif['type'] === 'warning'): ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line></svg>
                                                <?php elseif ($notif['type'] === 'error'): ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line></svg>
                                                <?php else: ?>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                                                <?php endif; ?>
                                            </div>
                                            <div class="notif-content">
                                                <p class="notif-title"><?php echo sanitize($notif['title']); ?></p>
                                                <p class="notif-message"><?php echo sanitize($notif['message']); ?></p>
                                                <span class="notif-time"><?php echo timeAgo($notif['created_at']); ?></span>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="user-menu-wrapper" id="userMenuWrapper">
                        <button class="user-menu-trigger" id="userMenuTrigger">
                            <div class="user-avatar">
                                <?php echo getUserInitials($currentUser); ?>
                            </div>
                            <div class="user-info-mini">
                                <span class="user-name-mini"><?php echo sanitize(getUserFullName($currentUser)); ?></span>
                                <span class="user-role-mini"><?php echo sanitize($currentUser['role_name'] ?? 'User'); ?></span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </button>
                        
                        <div class="user-dropdown" id="userDropdown">
                            <div class="user-dropdown-header">
                                <div class="user-avatar large">
                                    <?php echo getUserInitials($currentUser); ?>
                                </div>
                                <div>
                                    <p class="user-dropdown-name"><?php echo sanitize(getUserFullName($currentUser)); ?></p>
                                    <p class="user-dropdown-email"><?php echo sanitize($currentUser['email'] ?? ''); ?></p>
                                </div>
                            </div>
                            <div class="user-dropdown-divider"></div>
                            <a href="<?php echo BASE_URL; ?>/modules/users/view.php?id=<?php echo $_SESSION['user_id'] ?? 0; ?>" class="user-dropdown-item">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                My Profile
                            </a>
                            <a href="<?php echo BASE_URL; ?>/privacy/policy.php" class="user-dropdown-item">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                                Privacy Policy
                            </a>
                            <div class="user-dropdown-divider"></div>
                            <a href="<?php echo BASE_URL; ?>/modules/auth/logout.php" class="user-dropdown-item logout">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                Sign Out
                            </a>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <main class="page-content">
                <?php echo renderFlash(); ?>
