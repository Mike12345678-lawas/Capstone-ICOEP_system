<?php
/**
 * My Reservations
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$pageTitle = 'My Reservations';
$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT r.*, f.name as facility_name 
        FROM facility_reservations r
        JOIN facilities f ON r.facility_id = f.id
        WHERE r.user_id = :uid 
        ORDER BY r.reservation_date DESC, r.time_start DESC
    ");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    $reservations = [];
    setFlash('error', 'Error loading reservations.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Facilities', 'url' => '/modules/facility/list.php'],
    ['label' => 'My Reservations']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">My Reservations</h1>
        <p class="page-subtitle">Track the status of your facility booking requests.</p>
    </div>
    <?php if (hasPermission('facility.reserve')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/facility/reserve.php" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            New Reservation
        </a>
    <?php endif; ?>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Facility</th>
                    <th>Purpose / Event</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: var(--spacing-xl);">
                            <div class="text-muted">You have no facility reservations yet.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reservations as $r): ?>
                        <tr>
                            <td style="font-weight: 500; color: var(--color-text-primary);">
                                <?php echo sanitize($r['facility_name']); ?>
                            </td>
                            <td><?php echo sanitize($r['title']); ?></td>
                            <td>
                                <div><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px; color: var(--color-text-muted);"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg> <?php echo formatDate($r['reservation_date']); ?></div>
                                <div class="text-muted" style="font-size: 0.85em;">
                                    <?php echo formatTime($r['time_start']); ?> - <?php echo formatTime($r['time_end']); ?>
                                </div>
                            </td>
                            <td><?php echo statusBadge($r['status']); ?></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/modules/facility/manage_action.php" style="display:inline;" onsubmit="return confirm('Cancel this reservation?');">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                        <button type="submit" class="btn btn-ghost btn-sm" style="color: var(--color-error);">Cancel</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85em;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
