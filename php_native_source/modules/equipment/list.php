<?php
/**
 * Equipment List
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('equipment.view');

$pageTitle = 'Campus Equipment';
$db = getDB();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE :search OR category LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

try {
    $stmt = $db->prepare("SELECT * FROM equipment WHERE $where ORDER BY name ASC");
    $stmt->execute($params);
    $equipment = $stmt->fetchAll();
} catch (PDOException $e) {
    $equipment = [];
    setFlash('error', 'Error loading equipment.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Equipment']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Equipment</h1>
        <p class="page-subtitle">Browse and request equipment for your academic and extracurricular needs.</p>
    </div>
    <?php if (hasPermission('equipment.borrow')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/equipment/my_requests.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            My Requests
        </a>
    <?php endif; ?>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search equipment by name or category..." value="<?php echo sanitize($search); ?>">
            </div>
            
            <div class="filter-group">
                <button type="submit" name="status" value="" class="filter-btn <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</button>
                <button type="submit" name="status" value="available" class="filter-btn <?php echo $statusFilter === 'available' ? 'active' : ''; ?>">Available</button>
                <button type="submit" name="status" value="maintenance" class="filter-btn <?php echo $statusFilter === 'maintenance' ? 'active' : ''; ?>">In Maintenance</button>
            </div>
        </form>
        
        <?php if (empty($equipment)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                <h3>No Equipment Found</h3>
                <p>Try adjusting your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-4">
                <?php foreach ($equipment as $eq): ?>
                    <div class="item-card">
                        <div class="item-card-image" style="height: 140px; padding: var(--spacing-sm);">
                            <?php if ($eq['image']): ?>
                                <img src="<?php echo BASE_URL . '/uploads/equipment/' . sanitize($eq['image']); ?>" alt="<?php echo sanitize($eq['name']); ?>" style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path></svg>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-card-body">
                            <div class="flex justify-between items-start" style="margin-bottom: var(--spacing-xs);">
                                <h3 class="item-card-title" style="margin-bottom: 0; font-size: 1rem;"><?php echo sanitize($eq['name']); ?></h3>
                            </div>
                            
                            <div class="item-card-meta" style="margin-bottom: var(--spacing-sm);">
                                <span class="badge badge-general"><?php echo sanitize($eq['category']); ?></span>
                                <?php echo statusBadge($eq['status']); ?>
                            </div>
                            
                            <div class="item-card-meta" style="margin-bottom: var(--spacing-md); font-size: var(--text-sm);">
                                <span style="display: flex; justify-content: space-between; width: 100%;">
                                    <span>Total Qty: <?php echo (int)$eq['quantity']; ?></span>
                                    <span style="font-weight: 600; color: <?php echo $eq['available_quantity'] > 0 ? 'var(--color-success)' : 'var(--color-error)'; ?>">
                                        Available: <?php echo (int)$eq['available_quantity']; ?>
                                    </span>
                                </span>
                            </div>
                            
                            <div class="item-card-desc" style="font-size: var(--text-xs); -webkit-line-clamp: 2;">
                                <?php echo sanitize($eq['description']); ?>
                            </div>
                            
                            <div class="item-card-actions" style="margin-top: auto;">
                                <?php if ($eq['status'] === 'available' && $eq['available_quantity'] > 0 && hasPermission('equipment.borrow')): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/equipment/borrow.php?equipment_id=<?php echo $eq['id']; ?>" class="btn btn-primary w-full btn-sm">
                                        Request to Borrow
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-full btn-sm" disabled>
                                        <?php echo $eq['available_quantity'] == 0 ? 'Out of Stock' : 'Unavailable'; ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
