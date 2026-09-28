<?php
/**
 * View Event
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) redirect('/modules/events/list.php');

$db = getDB();
$userId = $_SESSION['user_id'];

try {
    // Fetch Event
    $stmt = $db->prepare("
        SELECT e.*, 
               u.first_name, u.last_name,
               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as reg_count
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        WHERE e.id = :id AND e.deleted_at IS NULL
    ");
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlash('error', 'Event not found.');
        redirect('/modules/events/list.php');
    }
    
    $pageTitle = $event['title'];
    
    // Check if user is registered
    $stmt = $db->prepare("SELECT * FROM event_registrations WHERE event_id = :eid AND user_id = :uid");
    $stmt->execute([':eid' => $id, ':uid' => $userId]);
    $registration = $stmt->fetch();
    $isRegistered = (bool)$registration;
    
    $isFull = ($event['max_participants'] > 0 && $event['reg_count'] >= $event['max_participants']);
    $isUpcoming = $event['status'] === 'upcoming' || $event['status'] === 'ongoing';
    
} catch (PDOException $e) {
    setFlash('error', 'Database error.');
    redirect('/modules/events/list.php');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Events', 'url' => '/modules/events/list.php'],
    ['label' => 'View Event']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title"><?php echo sanitize($event['title']); ?></h1>
        <p class="page-subtitle">Organized by <?php echo sanitize($event['first_name'] . ' ' . $event['last_name']); ?></p>
    </div>
    <div class="btn-group">
        <?php if (hasPermission('event.manage') || (hasPermission('event.create') && $event['organizer_id'] == $userId)): ?>
            <a href="<?php echo BASE_URL; ?>/modules/events/manage.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">
                Manage Event
            </a>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/modules/events/list.php" class="btn btn-secondary">&larr; Back</a>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: var(--spacing-xl);">
    
    <!-- Main Content -->
    <div class="animate-fade-in-up" style="animation-delay: 100ms;">
        <div class="card">
            <?php if ($event['image_banner']): ?>
                <img src="<?php echo BASE_URL . '/uploads/events/' . sanitize($event['image_banner']); ?>" alt="Banner" style="width: 100%; max-height: 300px; object-fit: cover; border-bottom: 1px solid var(--color-border);">
            <?php endif; ?>
            <div class="card-body">
                <div class="flex gap-sm" style="margin-bottom: var(--spacing-lg);">
                    <span class="badge badge-general"><?php echo ucfirst(sanitize($event['event_type'])); ?></span>
                    <?php echo statusBadge($event['status']); ?>
                </div>
                
                <div style="font-size: var(--text-base); line-height: 1.8; color: var(--color-text-primary);">
                    <?php echo nl2br(sanitize($event['description'])); ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="card dashboard-section">
            <div class="card-header">
                <h3 class="card-title">Event Details</h3>
            </div>
            <div class="card-body">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: var(--spacing-md);">
                    <li style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-accent-primary);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <div>
                            <strong>Date</strong>
                            <div class="text-muted"><?php echo formatDate($event['event_date']); ?></div>
                        </div>
                    </li>
                    <li style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-accent-primary);"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <div>
                            <strong>Time</strong>
                            <div class="text-muted"><?php echo formatTime($event['time_start']); ?> - <?php echo formatTime($event['time_end']); ?></div>
                        </div>
                    </li>
                    <li style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-accent-primary);"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        <div>
                            <strong>Venue</strong>
                            <div class="text-muted"><?php echo sanitize($event['venue'] ?? 'TBA'); ?></div>
                        </div>
                    </li>
                    <li style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: var(--color-accent-primary);"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <div>
                            <strong>Capacity</strong>
                            <div class="text-muted"><?php echo $event['reg_count']; ?> / <?php echo $event['max_participants'] ?: 'Unlimited'; ?></div>
                        </div>
                    </li>
                </ul>
            </div>
            
            <div class="card-footer" style="padding-top: var(--spacing-lg);">
                <?php if ($isRegistered): ?>
                    <div class="text-center" style="margin-bottom: var(--spacing-sm);">
                        <span style="color: var(--color-success); font-weight: 500;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            You are registered
                        </span>
                        <?php if ($registration['has_attended']): ?>
                            <div style="font-size: 0.85em; color: var(--color-text-muted);">Attendance recorded.</div>
                        <?php endif; ?>
                    </div>
                    <?php if ($isUpcoming && !$registration['has_attended']): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/modules/events/register.php" onsubmit="return confirm('Are you sure you want to cancel your registration?');">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <input type="hidden" name="action" value="leave">
                            <button type="submit" class="btn btn-ghost w-full" style="color: var(--color-error);">Cancel Registration</button>
                        </form>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <?php if (!$isUpcoming): ?>
                        <button class="btn btn-secondary w-full" disabled>Event has ended</button>
                    <?php elseif ($isFull): ?>
                        <button class="btn btn-secondary w-full" disabled>Event is Full</button>
                    <?php else: ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/modules/events/register.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <input type="hidden" name="action" value="join">
                            <button type="submit" class="btn btn-primary w-full">Join Event</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
