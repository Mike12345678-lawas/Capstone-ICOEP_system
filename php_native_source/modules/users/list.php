<?php
/**
 * User Management List
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('user.view');

$pageTitle = 'User Management';
$db = getDB();

$search = trim($_GET['search'] ?? '');
$roleFilter = (int)($_GET['role'] ?? 0);
$statusFilter = trim($_GET['status'] ?? '');

$where = "u.deleted_at IS NULL";
$params = [];

if ($search) {
    $where .= " AND (u.first_name LIKE :search OR u.last_name LIKE :search OR u.email LIKE :search OR u.employee_id LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($roleFilter) {
    $where .= " AND u.role_id = :role";
    $params[':role'] = $roleFilter;
}

if ($statusFilter) {
    $where .= " AND u.status = :status";
    $params[':status'] = $statusFilter;
}

// Pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

try {
    $stmt = $db->query("SELECT id, name FROM roles ORDER BY id ASC");
    $roles = $stmt->fetchAll();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM users u WHERE $where");
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / ITEMS_PER_PAGE);
    
    $stmt = $db->prepare("
        SELECT u.*, r.name as role_name 
        FROM users u 
        JOIN roles r ON u.role_id = r.id 
        WHERE $where 
        ORDER BY u.last_name ASC, u.first_name ASC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $users = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $users = [];
    $roles = [];
    $totalPages = 1;
    setFlash('error', 'Error loading users.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'User Management']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">User Management</h1>
        <p class="page-subtitle">Manage campus accounts, roles, and access.</p>
    </div>
    <?php if (hasPermission('user.create')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/users/create.php" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
            Add User
        </a>
    <?php endif; ?>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar" style="margin-bottom: var(--spacing-lg);">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search by name, email, or ID..." value="<?php echo sanitize($search); ?>">
            </div>
            
            <div class="filter-group">
                <select name="role" class="form-control" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo $roleFilter == $r['id'] ? 'selected' : ''; ?>><?php echo sanitize($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select name="status" class="form-control" style="width: auto;" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="locked" <?php echo $statusFilter === 'locked' ? 'selected' : ''; ?>>Locked</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-secondary">Filter</button>
        </form>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>ID / Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="text-center text-muted" style="padding: var(--spacing-xl);">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 500; color: var(--color-text-primary);">
                                        <?php echo sanitize($u['last_name'] . ', ' . $u['first_name']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size: 0.85em;"><?php echo sanitize($u['department'] ?? ''); ?></div>
                                </td>
                                <td>
                                    <div><?php echo sanitize($u['employee_id']); ?></div>
                                    <div class="text-muted" style="font-size: 0.85em;"><a href="mailto:<?php echo sanitize($u['email']); ?>"><?php echo sanitize($u['email']); ?></a></div>
                                </td>
                                <td><span class="badge badge-general"><?php echo sanitize($u['role_name']); ?></span></td>
                                <td><?php echo statusBadge($u['status']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <?php if (hasPermission('user.edit')): ?>
                                            <a href="<?php echo BASE_URL; ?>/modules/users/edit.php?id=<?php echo $u['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php echo renderPagination($page, $totalPages, BASE_URL . '/modules/users/list.php'); ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
