<?php
/**
 * My Events
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$pageTitle = 'My Events';
$db = getDB();
$userId = $_SESSION['user_id'];

try {
    $stmt = $db->prepare("
        SELECT e.*, er.registered_at, er.has_attended 
        FROM event_registrations er
        JOIN events e ON er.event_id = e.id
        WHERE er.user_id = :uid AND e.deleted_at IS NULL
        ORDER BY e.event_date ASC, e.time_start ASC
    ");
    $stmt->execute([':uid' => $userId]);
    $myEvents = $stmt->fetchAll();
    
    // Split into upcoming and past
    $upcoming = [];
    $past = [];
    $now = date('Y-m-d');
    
    foreach ($myEvents as $e) {
        if ($e['status'] === 'completed' || $e['event_date'] < $now) {
            $past[] = $e;
        } else {
            $upcoming[] = $e;
        }
    }
    
} catch (PDOException $e) {
    setFlash('error', 'Error loading events.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Events', 'url' => '/modules/events/list.php'],
    ['label' => 'My Events']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">My Registered Events</h1>
        <p class="page-subtitle">Track events you've joined and your attendance history.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/events/list.php" class="btn btn-primary">Browse Events</a>
</div>

<div class="grid" style="grid-template-columns: 1fr; gap: var(--spacing-xl);">
    
    <div class="animate-fade-in-up" style="animation-delay: 100ms;">
        <h2 style="margin-bottom: var(--spacing-md); font-size: 1.25rem;">Upcoming Events</h2>
        <?php if (empty($upcoming)): ?>
            <div class="card"><div class="card-body text-center text-muted">You have no upcoming events.</div></div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($upcoming as $e): ?>
                    <div class="card">
                        <div class="card-body">
                            <h3 style="font-size: 1.1rem; margin-bottom: 5px;"><a href="view.php?id=<?php echo $e['id']; ?>"><?php echo sanitize($e['title']); ?></a></h3>
                            <div class="text-muted" style="font-size: 0.85rem; margin-bottom: 10px;">
                                <?php echo formatDate($e['event_date']); ?> &bull; <?php echo formatTime($e['time_start']); ?>
                            </div>
                            <div style="font-size: 0.9rem; margin-bottom: 15px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path></svg>
                                <?php echo sanitize($e['venue'] ?? 'TBA'); ?>
                            </div>
                            <form method="POST" action="register.php" onsubmit="return confirm('Cancel registration?');">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="event_id" value="<?php echo $e['id']; ?>">
                                <input type="hidden" name="action" value="leave">
                                <button type="submit" class="btn btn-ghost btn-sm w-full" style="color: var(--color-error);">Cancel Registration</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="animate-fade-in-up" style="animation-delay: 200ms;">
        <h2 style="margin-bottom: var(--spacing-md); font-size: 1.25rem;">Past Events & Attendance</h2>
        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($past)): ?>
                        <tr><td colspan="4" class="text-center text-muted">No past events found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($past as $e): ?>
                            <tr>
                                <td><a href="view.php?id=<?php echo $e['id']; ?>"><?php echo sanitize($e['title']); ?></a></td>
                                <td><?php echo formatDate($e['event_date']); ?></td>
                                <td><?php echo statusBadge($e['status']); ?></td>
                                <td>
                                    <?php if ($e['has_attended']): ?>
                                        <span class="badge badge-success">Attended</span>
                                    <?php else: ?>
                                        <span class="badge badge-error">Missed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
