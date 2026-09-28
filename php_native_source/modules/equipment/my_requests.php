<?php
/**
 * My Equipment Requests
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$pageTitle = 'My Equipment Requests';
$db = getDB();

try {
    $stmt = $db->prepare("
        SELECT r.*, e.name as equipment_name, e.image 
        FROM equipment_requests r
        JOIN equipment e ON r.equipment_id = e.id
        WHERE r.user_id = :uid 
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
    setFlash('error', 'Error loading requests.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Equipment', 'url' => '/modules/equipment/list.php'],
    ['label' => 'My Requests']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">My Equipment Requests</h1>
        <p class="page-subtitle">Track the status of your borrowed equipment.</p>
    </div>
    <?php if (hasPermission('equipment.borrow')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/equipment/list.php" class="btn btn-primary">
            New Request
        </a>
    <?php endif; ?>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Purpose</th>
                    <th>Schedule</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: var(--spacing-xl);">
                            <div class="text-muted">You have no equipment requests yet.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 500; color: var(--color-text-primary);">
                                    <?php echo sanitize($r['equipment_name']); ?> 
                                    <span class="badge badge-general" style="margin-left: 4px;">x<?php echo $r['quantity']; ?></span>
                                </div>
                            </td>
                            <td><?php echo sanitize($r['purpose']); ?></td>
                            <td>
                                <div><span class="text-muted">Needed:</span> <?php echo formatDate($r['date_needed']); ?></div>
                                <div style="font-size: 0.85em;"><span class="text-muted">Return:</span> <?php echo formatDate($r['expected_return_date']); ?></div>
                            </td>
                            <td>
                                <?php 
                                    // Custom status badges for equipment
                                    $s = $r['status'];
                                    if ($s === 'pending') echo '<span class="badge badge-warning">Pending</span>';
                                    elseif ($s === 'approved') echo '<span class="badge badge-success">Approved (Ready)</span>';
                                    elseif ($s === 'borrowed') echo '<span class="badge badge-info">Borrowed (In Possession)</span>';
                                    elseif ($s === 'returned') echo '<span class="badge badge-general">Returned</span>';
                                    elseif ($s === 'rejected' || $s === 'cancelled') echo '<span class="badge badge-error">'.ucfirst($s).'</span>';
                                    else echo sanitize($s);
                                ?>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                    <form method="POST" action="<?php echo BASE_URL; ?>/modules/equipment/manage_action.php" style="display:inline;" onsubmit="return confirm('Cancel this request?');">
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
