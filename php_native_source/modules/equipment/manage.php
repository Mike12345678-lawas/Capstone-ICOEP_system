<?php
/**
 * Manage Equipment Requests (Admin/Staff)
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('equipment.manage');

$pageTitle = 'Manage Equipment Requests';
$db = getDB();

$statusFilter = trim($_GET['status'] ?? 'pending');

$where = "1=1";
$params = [];
if ($statusFilter !== 'all') {
    $where .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

try {
    $stmt = $db->prepare("
        SELECT r.*, e.name as equipment_name, u.first_name, u.last_name, u.email 
        FROM equipment_requests r
        JOIN equipment e ON r.equipment_id = e.id
        JOIN users u ON r.user_id = u.id
        WHERE $where
        ORDER BY r.created_at DESC
    ");
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $requests = [];
    setFlash('error', 'Error loading requests.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Equipment', 'url' => '/modules/equipment/list.php'],
    ['label' => 'Manage Requests']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Equipment Requests</h1>
        <p class="page-subtitle">Review, approve, and track equipment borrowing logistics.</p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <div class="search-bar">
            <div class="filter-group">
                <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=approved" class="filter-btn <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">Approved (To Pick Up)</a>
                <a href="?status=borrowed" class="filter-btn <?php echo $statusFilter === 'borrowed' ? 'active' : ''; ?>">Currently Borrowed</a>
                <a href="?status=returned" class="filter-btn <?php echo $statusFilter === 'returned' ? 'active' : ''; ?>">Returned</a>
                <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Requester</th>
                        <th>Equipment & Qty</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: var(--spacing-xl);">
                                <div class="text-muted">No requests found matching this status.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500; color: var(--color-text-primary);">
                                        <?php echo sanitize($r['first_name'] . ' ' . $r['last_name']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.85em;">
                                        <?php echo sanitize($r['email']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo sanitize($r['equipment_name']); ?> <span class="badge badge-general">x<?php echo $r['quantity']; ?></span></div>
                                    <div class="text-muted" style="font-size: 0.85em;" title="<?php echo sanitize($r['purpose']); ?>">
                                        <?php echo mb_strimwidth(sanitize($r['purpose']), 0, 30, '...'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="white-space: nowrap;"><span class="text-muted" style="font-size: 0.85em;">Need:</span> <?php echo formatDate($r['date_needed']); ?></div>
                                    <div style="white-space: nowrap;"><span class="text-muted" style="font-size: 0.85em;">Return:</span> <?php echo formatDate($r['expected_return_date']); ?></div>
                                </td>
                                <td>
                                    <?php 
                                        $s = $r['status'];
                                        if ($s === 'pending') echo '<span class="badge badge-warning">Pending</span>';
                                        elseif ($s === 'approved') echo '<span class="badge badge-success">Approved</span>';
                                        elseif ($s === 'borrowed') echo '<span class="badge badge-info">Borrowed</span>';
                                        elseif ($s === 'returned') echo '<span class="badge badge-general">Returned</span>';
                                        else echo '<span class="badge badge-error">'.ucfirst($s).'</span>';
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/modules/equipment/manage_action.php" style="display:inline;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" title="Approve Request">Approve</button>
                                            </form>
                                            
                                            <form method="POST" action="<?php echo BASE_URL; ?>/modules/equipment/manage_action.php" style="display:inline;" onsubmit="return confirm('Reject this request?');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" title="Reject">Reject</button>
                                            </form>
                                            
                                        <?php elseif ($r['status'] === 'approved'): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/modules/equipment/manage_action.php" style="display:inline;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="release">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-info btn-sm" style="background: var(--color-info); color: white;">Mark as Borrowed</button>
                                            </form>
                                            
                                        <?php elseif ($r['status'] === 'borrowed'): ?>
                                            <form method="POST" action="<?php echo BASE_URL; ?>/modules/equipment/manage_action.php" style="display:inline;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="return">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" style="background: var(--color-success); color: white;">Mark as Returned</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size: 0.85em;">-</span>
                                        <?php endif; ?>
                                    </div>
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
