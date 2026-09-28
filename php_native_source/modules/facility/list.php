<?php
/**
 * Facilities List
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('facility.view');

$pageTitle = 'Campus Facilities';
$db = getDB();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (name LIKE :search OR location LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($statusFilter) {
    $where .= " AND status = :status";
    $params[':status'] = $statusFilter;
}

try {
    $stmt = $db->prepare("SELECT * FROM facilities WHERE $where ORDER BY name ASC");
    $stmt->execute($params);
    $facilities = $stmt->fetchAll();
} catch (PDOException $e) {
    $facilities = [];
    setFlash('error', 'Error loading facilities.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Facilities']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Facilities</h1>
        <p class="page-subtitle">Browse and reserve venues for your activities and events.</p>
    </div>
    <?php if (hasPermission('facility.reserve')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/facility/my_reservations.php" class="btn btn-secondary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            My Reservations
        </a>
    <?php endif; ?>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search facilities by name or location..." value="<?php echo sanitize($search); ?>">
            </div>
            
            <div class="filter-group">
                <button type="submit" name="status" value="" class="filter-btn <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</button>
                <button type="submit" name="status" value="available" class="filter-btn <?php echo $statusFilter === 'available' ? 'active' : ''; ?>">Available</button>
                <button type="submit" name="status" value="maintenance" class="filter-btn <?php echo $statusFilter === 'maintenance' ? 'active' : ''; ?>">Under Maintenance</button>
            </div>
        </form>
        
        <?php if (empty($facilities)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                <h3>No Facilities Found</h3>
                <p>Try adjusting your search criteria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($facilities as $f): ?>
                    <div class="item-card">
                        <?php if ($f['image']): ?>
                            <img src="<?php echo BASE_URL . '/uploads/facilities/' . sanitize($f['image']); ?>" alt="<?php echo sanitize($f['name']); ?>" class="item-card-image">
                        <?php else: ?>
                            <div class="item-card-image">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-card-body">
                            <div class="flex justify-between items-center" style="margin-bottom: var(--spacing-sm);">
                                <h3 class="item-card-title" style="margin-bottom: 0;"><?php echo sanitize($f['name']); ?></h3>
                                <?php echo statusBadge($f['status']); ?>
                            </div>
                            
                            <div class="item-card-meta">
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <?php echo sanitize($f['location']); ?>
                                </span>
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    Capacity: <?php echo (int)$f['capacity']; ?>
                                </span>
                            </div>
                            
                            <p class="item-card-desc"><?php echo sanitize($f['description']); ?></p>
                            
                            <div class="item-card-actions">
                                <?php if ($f['status'] === 'available' && hasPermission('facility.reserve')): ?>
                                    <a href="<?php echo BASE_URL; ?>/modules/facility/reserve.php?facility_id=<?php echo $f['id']; ?>" class="btn btn-primary w-full">
                                        Reserve Now
                                    </a>
                                <?php else: ?>
                                    <button class="btn btn-secondary w-full" disabled>Unavailable</button>
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
