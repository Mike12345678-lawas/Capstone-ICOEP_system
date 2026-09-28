<?php
/**
 * Manage Facility Reservations (Admin/Staff)
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('facility.manage');

$pageTitle = 'Manage Reservations';
$db = getDB();

// Fetch reservations
$statusFilter = trim($_GET['status'] ?? 'pending');

$where = "1=1";
$params = [];
if ($statusFilter !== 'all') {
    $where .= " AND r.status = :status";
    $params[':status'] = $statusFilter;
}

try {
    $stmt = $db->prepare("
        SELECT r.*, f.name as facility_name, u.first_name, u.last_name, u.email 
        FROM facility_reservations r
        JOIN facilities f ON r.facility_id = f.id
        JOIN users u ON r.user_id = u.id
        WHERE $where
        ORDER BY r.reservation_date ASC, r.time_start ASC
    ");
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    $reservations = [];
    setFlash('error', 'Error loading reservations.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Facilities', 'url' => '/modules/facility/list.php'],
    ['label' => 'Manage Reservations']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Reservations</h1>
        <p class="page-subtitle">Review and process facility booking requests.</p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <div class="search-bar">
            <div class="filter-group">
                <a href="?status=pending" class="filter-btn <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?status=approved" class="filter-btn <?php echo $statusFilter === 'approved' ? 'active' : ''; ?>">Approved</a>
                <a href="?status=rejected" class="filter-btn <?php echo $statusFilter === 'rejected' ? 'active' : ''; ?>">Rejected</a>
                <a href="?status=all" class="filter-btn <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All Requests</a>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Requester</th>
                        <th>Facility & Details</th>
                        <th>Schedule</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reservations)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: var(--spacing-xl);">
                                <div class="text-muted">No reservations found matching this status.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($reservations as $r): ?>
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
                                    <div style="font-weight: 500;"><?php echo sanitize($r['facility_name']); ?></div>
                                    <div class="text-muted" style="font-size: 0.85em;" title="<?php echo sanitize($r['description']); ?>">
                                        <?php echo sanitize($r['title']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="white-space: nowrap;"><?php echo formatDate($r['reservation_date']); ?></div>
                                    <div class="text-muted" style="font-size: 0.85em; white-space: nowrap;">
                                        <?php echo formatTime($r['time_start']); ?> - <?php echo formatTime($r['time_end']); ?>
                                    </div>
                                </td>
                                <td><?php echo statusBadge($r['status']); ?></td>
                                <td>
                                    <?php if ($r['status'] === 'pending'): ?>
                                        <div class="btn-group">
                                            <form method="POST" action="<?php echo BASE_URL; ?>/modules/facility/manage_action.php" style="display:inline;">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm" title="Approve">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                </button>
                                            </form>
                                            
                                            <button type="button" class="btn btn-danger btn-sm" title="Reject" data-modal-target="rejectModal_<?php echo $r['id']; ?>">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                                            </button>
                                        </div>
                                        
                                        <!-- Reject Modal -->
                                        <div id="rejectModal_<?php echo $r['id']; ?>" class="modal-overlay">
                                            <div class="modal-container" style="max-width: 400px;">
                                                <div class="modal-header">
                                                    <h3 class="modal-title">Reject Reservation</h3>
                                                    <button class="modal-close">&times;</button>
                                                </div>
                                                <form method="POST" action="<?php echo BASE_URL; ?>/modules/facility/manage_action.php">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="action" value="reject">
                                                    <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                    <div class="modal-body">
                                                        <div class="form-group">
                                                            <label class="form-label">Reason for Rejection *</label>
                                                            <textarea name="remarks" class="form-control" rows="3" required placeholder="e.g. Facility is undergoing maintenance..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary modal-close">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Confirm Reject</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.85em;">Processed</span>
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
