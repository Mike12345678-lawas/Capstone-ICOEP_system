<?php
/**
 * Events List
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$pageTitle = 'Campus Events';
$db = getDB();

$search = trim($_GET['search'] ?? '');
$typeFilter = trim($_GET['type'] ?? '');
$dateFilter = trim($_GET['date'] ?? ''); // From dashboard calendar

$where = "e.status IN ('upcoming', 'ongoing') AND e.deleted_at IS NULL";
$params = [];

if ($search) {
    $where .= " AND (e.title LIKE :search OR e.description LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($typeFilter) {
    $where .= " AND e.event_type = :type";
    $params[':type'] = $typeFilter;
}

if ($dateFilter && validateDate($dateFilter)) {
    $where .= " AND e.event_date = :date";
    $params[':date'] = $dateFilter;
}

try {
    // Get user's registered events to show 'Registered' badge
    $stmt = $db->prepare("SELECT event_id FROM event_registrations WHERE user_id = :uid");
    $stmt->execute([':uid' => $_SESSION['user_id']]);
    $userRegistrations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get Events with registration count
    $stmt = $db->prepare("
        SELECT e.*, 
               u.first_name, u.last_name,
               (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as reg_count
        FROM events e
        JOIN users u ON e.organizer_id = u.id
        WHERE $where
        ORDER BY e.event_date ASC, e.time_start ASC
    ");
    $stmt->execute($params);
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    $events = [];
    setFlash('error', 'Error loading events.');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Events']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Events</h1>
        <p class="page-subtitle">Discover and join academic, cultural, and extracurricular events.</p>
    </div>
    <div class="btn-group">
        <a href="<?php echo BASE_URL; ?>/modules/events/my_events.php" class="btn btn-secondary">My Events</a>
        <?php if (hasPermission('event.create')): ?>
            <a href="<?php echo BASE_URL; ?>/modules/events/create.php" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                Create Event
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search events..." value="<?php echo sanitize($search); ?>">
            </div>
            
            <div class="filter-group">
                <button type="submit" name="type" value="" class="filter-btn <?php echo empty($typeFilter) ? 'active' : ''; ?>">All</button>
                <button type="submit" name="type" value="academic" class="filter-btn <?php echo $typeFilter === 'academic' ? 'active' : ''; ?>">Academic</button>
                <button type="submit" name="type" value="cultural" class="filter-btn <?php echo $typeFilter === 'cultural' ? 'active' : ''; ?>">Cultural</button>
                <button type="submit" name="type" value="sports" class="filter-btn <?php echo $typeFilter === 'sports' ? 'active' : ''; ?>">Sports</button>
                <button type="submit" name="type" value="spiritual" class="filter-btn <?php echo $typeFilter === 'spiritual' ? 'active' : ''; ?>">Spiritual</button>
            </div>
            <?php if ($dateFilter): ?>
                <input type="hidden" name="date" value="<?php echo sanitize($dateFilter); ?>">
                <a href="?search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($typeFilter); ?>" class="btn btn-ghost btn-sm" style="margin-left: 10px;">Clear Date Filter</a>
            <?php endif; ?>
        </form>
        
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                <h3>No Events Found</h3>
                <p>There are no upcoming events matching your criteria.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($events as $e): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/events/view.php?id=<?php echo $e['id']; ?>" class="item-card">
                        <?php if ($e['image_banner']): ?>
                            <img src="<?php echo BASE_URL . '/uploads/events/' . sanitize($e['image_banner']); ?>" alt="<?php echo sanitize($e['title']); ?>" class="item-card-image">
                        <?php else: ?>
                            <div class="item-card-image">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-card-body">
                            <div class="flex justify-between items-start" style="margin-bottom: var(--spacing-xs);">
                                <h3 class="item-card-title" style="margin-bottom: 0; font-size: 1.1rem;"><?php echo sanitize($e['title']); ?></h3>
                            </div>
                            
                            <div class="item-card-meta" style="margin-bottom: var(--spacing-sm);">
                                <span class="badge badge-general"><?php echo ucfirst(sanitize($e['event_type'])); ?></span>
                                <?php if (in_array($e['id'], $userRegistrations)): ?>
                                    <span class="badge badge-success">Registered</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="item-card-meta flex-column" style="margin-bottom: var(--spacing-md); font-size: var(--text-sm); gap: 4px;">
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php echo formatDate($e['event_date']); ?> &bull; <?php echo formatTime($e['time_start']); ?>
                                </span>
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                                    <?php echo sanitize($e['venue'] ?? 'TBA'); ?>
                                </span>
                            </div>
                            
                            <div class="item-card-desc" style="-webkit-line-clamp: 2;">
                                <?php echo mb_strimwidth(strip_tags($e['description']), 0, 100, '...'); ?>
                            </div>
                            
                            <div class="item-card-meta" style="margin-top: auto; border-top: 1px solid var(--color-border); padding-top: var(--spacing-sm);">
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                                    <?php echo $e['reg_count']; ?> / <?php echo $e['max_participants'] ?: '&infin;'; ?> joined
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
