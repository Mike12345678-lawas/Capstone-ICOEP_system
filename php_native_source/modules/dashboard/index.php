<?php
/**
 * Dashboard Overview
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$pageTitle = 'Dashboard';
$roleName = $_SESSION['role_name'] ?? 'User';
$userId = $_SESSION['user_id'];
$db = getDB();

// Gather role-specific statistics
$stats = [];
$recentActivity = [];
$upcomingEvents = [];
$announcements = [];

try {
    // 1. Fetch latest announcements (up to 3)
    $stmt = $db->prepare("
        SELECT a.*, u.first_name, u.last_name 
        FROM announcements a
        JOIN users u ON a.posted_by = u.id
        WHERE a.deleted_at IS NULL 
        AND (a.expires_at IS NULL OR a.expires_at > NOW())
        AND (
            a.audience = 'all' 
            OR (a.audience = 'students' AND :role = 'Student')
            OR (a.audience = 'faculty' AND :role = 'Faculty')
            OR (a.audience = 'staff' AND :role = 'Staff')
            OR (a.audience = 'administrators' AND :role = 'Administrator')
        )
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT 3
    ");
    $stmt->execute([':role' => $roleName]);
    $announcements = $stmt->fetchAll();

    // 2. Fetch upcoming events (up to 4)
    $stmt = $db->prepare("
        SELECT * FROM events 
        WHERE status = 'upcoming' AND event_date >= CURDATE() AND deleted_at IS NULL
        ORDER BY event_date ASC, time_start ASC
        LIMIT 4
    ");
    $stmt->execute();
    $upcomingEvents = $stmt->fetchAll();

    // 3. Admin & Staff specific stats
    if (in_array($roleName, ['Administrator', 'Staff'])) {
        // Pending Facilities
        $stmt = $db->query("SELECT COUNT(*) FROM facility_reservations WHERE status = 'pending'");
        $stats['pending_facilities'] = $stmt->fetchColumn();
        
        // Pending Equipment
        $stmt = $db->query("SELECT COUNT(*) FROM equipment_requests WHERE status = 'pending'");
        $stats['pending_equipment'] = $stmt->fetchColumn();
        
        if ($roleName === 'Administrator') {
            // Total Active Users
            $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active' AND deleted_at IS NULL");
            $stats['total_users'] = $stmt->fetchColumn();
            
            // Upcoming Events Count
            $stmt = $db->query("SELECT COUNT(*) FROM events WHERE status = 'upcoming' AND deleted_at IS NULL");
            $stats['upcoming_events_count'] = $stmt->fetchColumn();
        }
    } 
    // 4. User specific stats (Faculty, Student)
    else {
        // My Active Reservations
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM facility_reservations 
            WHERE user_id = :uid AND status IN ('pending', 'approved') AND reservation_date >= CURDATE()
        ");
        $stmt->execute([':uid' => $userId]);
        $stats['my_reservations'] = $stmt->fetchColumn();
        
        // My Borrowed Equipment
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM equipment_requests 
            WHERE user_id = :uid AND status = 'borrowed'
        ");
        $stmt->execute([':uid' => $userId]);
        $stats['my_equipment'] = $stmt->fetchColumn();
        
        // My Registered Events
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM event_registrations er
            JOIN events e ON er.event_id = e.id
            WHERE er.user_id = :uid AND e.status = 'upcoming' AND e.deleted_at IS NULL
        ");
        $stmt->execute([':uid' => $userId]);
        $stats['my_events'] = $stmt->fetchColumn();
    }
    
    // Fetch Calendar Events (Next 30 days)
    $calendarEvents = [];
    $stmt = $db->query("
        SELECT id, title, event_date as date, event_type as type 
        FROM events 
        WHERE event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) 
        AND deleted_at IS NULL
    ");
    while ($row = $stmt->fetch()) {
        $calendarEvents[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'date' => $row['date'],
            'type' => $row['type']
        ];
    }
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
    setFlash('error', 'Could not load dashboard data.');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="dashboard-welcome animate-fade-in-up">
    <h1>Welcome, <span><?php echo sanitize($_SESSION['user_name']); ?></span>!</h1>
    <p>Here's what's happening on campus today.</p>
</div>

<!-- Quick Actions -->
<div class="quick-actions animate-fade-in-up stagger-children">
    <a href="<?php echo BASE_URL; ?>/modules/facility/reserve.php" class="quick-action-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
        Book Facility
    </a>
    <a href="<?php echo BASE_URL; ?>/modules/equipment/borrow.php" class="quick-action-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
        Borrow Equipment
    </a>
    <a href="<?php echo BASE_URL; ?>/modules/events/list.php" class="quick-action-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        Browse Events
    </a>
    <?php if (hasPermission('announcement.create')): ?>
    <a href="<?php echo BASE_URL; ?>/modules/announcements/create.php" class="quick-action-btn">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        Post Announcement
    </a>
    <?php endif; ?>
</div>

<!-- Stats Grid -->
<div class="grid-stats dashboard-section animate-fade-in-up stagger-children">
    <?php if ($roleName === 'Administrator' || $roleName === 'Staff'): ?>
        
        <div class="stat-card">
            <div class="stat-icon gold">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div class="stat-value" data-counter="<?php echo $stats['pending_facilities'] ?? 0; ?>">0</div>
            <div class="stat-label">Pending Facility Requests</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon gold">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
            </div>
            <div class="stat-value" data-counter="<?php echo $stats['pending_equipment'] ?? 0; ?>">0</div>
            <div class="stat-label">Pending Equipment Requests</div>
        </div>

        <?php if ($roleName === 'Administrator'): ?>
            <div class="stat-card">
                <div class="stat-icon blue">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <div class="stat-value" data-counter="<?php echo $stats['total_users'] ?? 0; ?>">0</div>
                <div class="stat-label">Active Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon green">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <div class="stat-value" data-counter="<?php echo $stats['upcoming_events_count'] ?? 0; ?>">0</div>
                <div class="stat-label">Upcoming Events</div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        
        <div class="stat-card">
            <div class="stat-icon blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
            </div>
            <div class="stat-value" data-counter="<?php echo $stats['my_reservations'] ?? 0; ?>">0</div>
            <div class="stat-label">My Active Reservations</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon purple">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
            </div>
            <div class="stat-value" data-counter="<?php echo $stats['my_equipment'] ?? 0; ?>">0</div>
            <div class="stat-label">My Borrowed Equipment</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            </div>
            <div class="stat-value" data-counter="<?php echo $stats['my_events'] ?? 0; ?>">0</div>
            <div class="stat-label">My Upcoming Events</div>
        </div>
        
    <?php endif; ?>
</div>

<div class="dashboard-grid animate-fade-in-up">
    <!-- Main Column -->
    <div class="dashboard-main">
        <!-- Announcements -->
        <div class="card dashboard-section">
            <div class="card-header">
                <h2 class="card-title">Campus Announcements</h2>
                <a href="<?php echo BASE_URL; ?>/modules/announcements/list.php" class="section-link">View All &rarr;</a>
            </div>
            <div class="card-body">
                <?php if (empty($announcements)): ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <p>No announcements at this time.</p>
                    </div>
                <?php else: ?>
                    <div class="announcement-feed">
                        <?php foreach ($announcements as $a): ?>
                            <a href="<?php echo BASE_URL; ?>/modules/announcements/view.php?id=<?php echo $a['id']; ?>" class="announcement-item">
                                <div class="announcement-priority <?php echo sanitize($a['priority']); ?>" title="<?php echo ucfirst(sanitize($a['priority'])); ?> Priority"></div>
                                <div class="announcement-content">
                                    <h3 class="announcement-title-text">
                                        <?php if ($a['is_pinned']): ?>
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-gold); margin-right: 4px; vertical-align: middle;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        <?php endif; ?>
                                        <?php echo sanitize($a['title']); ?>
                                    </h3>
                                    <div class="announcement-excerpt">
                                        <?php echo mb_strimwidth(strip_tags($a['content']), 0, 100, '...'); ?>
                                    </div>
                                    <div class="announcement-meta">
                                        <span><?php echo timeAgo($a['created_at']); ?></span>
                                        <span>•</span>
                                        <span>By <?php echo sanitize($a['first_name'] . ' ' . $a['last_name']); ?></span>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Calendar Widget -->
        <div class="card dashboard-section">
            <div class="card-header">
                <h2 class="card-title">Event Calendar</h2>
            </div>
            <div class="card-body">
                <div id="dashboardCalendar"></div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar Column -->
    <div class="dashboard-sidebar">
        <!-- Upcoming Events -->
        <div class="card dashboard-section">
            <div class="card-header">
                <h2 class="card-title">Upcoming Events</h2>
                <a href="<?php echo BASE_URL; ?>/modules/events/list.php" class="section-link">More &rarr;</a>
            </div>
            <div class="card-body">
                <?php if (empty($upcomingEvents)): ?>
                    <div class="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <p>No upcoming events scheduled.</p>
                    </div>
                <?php else: ?>
                    <div class="event-mini-list">
                        <?php foreach ($upcomingEvents as $e): ?>
                            <a href="<?php echo BASE_URL; ?>/modules/events/view.php?id=<?php echo $e['id']; ?>" class="event-mini-card">
                                <div class="event-mini-date">
                                    <span class="day"><?php echo date('d', strtotime($e['event_date'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($e['event_date'])); ?></span>
                                </div>
                                <div class="event-mini-info">
                                    <h4 class="event-mini-title" title="<?php echo sanitize($e['title']); ?>"><?php echo sanitize($e['title']); ?></h4>
                                    <div class="event-mini-detail">
                                        <?php echo formatTime($e['time_start']); ?> 
                                        <?php if ($e['venue']) echo ' • ' . sanitize($e['venue']); ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $extraJS = 'calendar.js'; ?>
<script>
    // Initialize Calendar
    document.addEventListener('DOMContentLoaded', () => {
        const events = <?php echo json_encode($calendarEvents); ?>;
        
        if (typeof ICOEPCalendar !== 'undefined') {
            new ICOEPCalendar('dashboardCalendar', {
                events: events,
                onDateClick: (date) => {
                    window.location.href = `<?php echo BASE_URL; ?>/modules/events/list.php?date=${date}`;
                },
                onEventClick: (eventId) => {
                    window.location.href = `<?php echo BASE_URL; ?>/modules/events/view.php?id=${eventId}`;
                }
            });
        }
    });
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
