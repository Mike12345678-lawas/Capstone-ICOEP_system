<?php
/**
 * System Audit Log Report
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('report.view');

$pageTitle = 'System Audit Logs';
$db = getDB();

$search = trim($_GET['search'] ?? '');

$where = "1=1";
$params = [];
if ($search) {
    $where .= " AND (a.action LIKE :search OR a.table_name LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)";
    $params[':search'] = "%$search%";
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM audit_logs a JOIN users u ON a.user_id = u.id WHERE $where");
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / ITEMS_PER_PAGE);
    
    $stmt = $db->prepare("
        SELECT a.*, u.first_name, u.last_name, u.email 
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE $where
        ORDER BY a.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $logs = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $logs = [];
    $totalPages = 1;
    setFlash('error', 'Error loading audit logs.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Reports'],
    ['label' => 'Audit Logs']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">System Audit Logs</h1>
        <p class="page-subtitle">Security and compliance tracking (RA 10173).</p>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: var(--spacing-lg);">
            <div class="search-input-wrapper" style="max-width: 400px; width: 100%;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search logs..." value="<?php echo sanitize($search); ?>">
            </div>
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        
        <div class="table-responsive">
            <table class="table" style="font-size: 0.9em;">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Target (Table : ID)</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No logs found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?php echo formatDateTime($log['created_at']); ?></td>
                                <td>
                                    <?php if ($log['first_name']): ?>
                                        <div style="font-weight: 500;"><?php echo sanitize($log['first_name'] . ' ' . $log['last_name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.85em;"><?php echo sanitize($log['email']); ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">System / Unknown</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge badge-general"><?php echo sanitize($log['action']); ?></span></td>
                                <td><?php echo sanitize($log['table_name']); ?> : <?php echo $log['record_id'] ?: 'N/A'; ?></td>
                                <td class="text-muted"><?php echo sanitize($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php echo renderPagination($page, $totalPages, BASE_URL . '/modules/reports/audit_log.php'); ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
