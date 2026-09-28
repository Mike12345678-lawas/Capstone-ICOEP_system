<?php
/**
 * Announcements List
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$pageTitle = 'Announcements';
$db = getDB();
$roleName = $_SESSION['role_name'] ?? 'User';

// Pagination setup
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Search filter
$search = trim($_GET['search'] ?? '');
$priorityFilter = trim($_GET['priority'] ?? '');

$where = "a.deleted_at IS NULL AND (a.expires_at IS NULL OR a.expires_at > NOW()) AND (
    a.audience = 'all' 
    OR (a.audience = 'students' AND :role = 'Student')
    OR (a.audience = 'faculty' AND :role = 'Faculty')
    OR (a.audience = 'staff' AND :role = 'Staff')
    OR (a.audience = 'administrators' AND :role = 'Administrator')
)";
$params = [':role' => $roleName];

if ($search) {
    $where .= " AND (a.title LIKE :search OR a.content LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($priorityFilter) {
    $where .= " AND a.priority = :priority";
    $params[':priority'] = $priorityFilter;
}

try {
    // Total count for pagination
    $stmt = $db->prepare("SELECT COUNT(*) FROM announcements a WHERE $where");
    $stmt->execute($params);
    $totalItems = $stmt->fetchColumn();
    $totalPages = ceil($totalItems / ITEMS_PER_PAGE);
    
    // Fetch records
    $stmt = $db->prepare("
        SELECT a.*, u.first_name, u.last_name 
        FROM announcements a
        JOIN users u ON a.posted_by = u.id
        WHERE $where
        ORDER BY a.is_pinned DESC, a.created_at DESC
        LIMIT :limit OFFSET :offset
    ");
    
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', ITEMS_PER_PAGE, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $announcements = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $announcements = [];
    $totalItems = 0;
    $totalPages = 1;
    setFlash('error', 'Error loading announcements.');
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Campus Announcements</h1>
        <p class="page-subtitle">Stay updated with the latest news and information from <?php echo INSTITUTION_SHORT; ?>.</p>
    </div>
    
    <?php if (hasPermission('announcement.create')): ?>
        <a href="<?php echo BASE_URL; ?>/modules/announcements/create.php" class="btn btn-primary">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            Post Announcement
        </a>
    <?php endif; ?>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-body">
        <form method="GET" action="" class="search-bar">
            <div class="search-input-wrapper">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" class="search-input" placeholder="Search announcements..." value="<?php echo sanitize($search); ?>">
            </div>
            
            <div class="filter-group">
                <button type="submit" name="priority" value="" class="filter-btn <?php echo empty($priorityFilter) ? 'active' : ''; ?>">All</button>
                <button type="submit" name="priority" value="general" class="filter-btn <?php echo $priorityFilter === 'general' ? 'active' : ''; ?>">General</button>
                <button type="submit" name="priority" value="important" class="filter-btn <?php echo $priorityFilter === 'important' ? 'active' : ''; ?>">Important</button>
                <button type="submit" name="priority" value="urgent" class="filter-btn <?php echo $priorityFilter === 'urgent' ? 'active' : ''; ?>">Urgent</button>
            </div>
        </form>
        
        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <h3>No Announcements Found</h3>
                <p><?php echo $search ? 'Try adjusting your search criteria.' : 'There are no active announcements at this time.'; ?></p>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($announcements as $a): ?>
                    <a href="<?php echo BASE_URL; ?>/modules/announcements/view.php?id=<?php echo $a['id']; ?>" class="item-card">
                        <?php if ($a['image']): ?>
                            <img src="<?php echo BASE_URL . '/uploads/announcements/' . sanitize($a['image']); ?>" alt="Image" class="item-card-image">
                        <?php else: ?>
                            <div class="item-card-image" style="height: 120px; background: var(--gradient-surface);">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="item-card-body">
                            <div class="item-card-meta">
                                <?php echo statusBadge($a['priority']); ?>
                                <?php if ($a['is_pinned']): ?>
                                    <span class="badge badge-general" style="background: rgba(245, 158, 11, 0.15); color: var(--color-gold);">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 2px;"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path></svg>
                                        Pinned
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <h3 class="item-card-title"><?php echo sanitize($a['title']); ?></h3>
                            
                            <div class="item-card-desc">
                                <?php echo mb_strimwidth(strip_tags($a['content']), 0, 120, '...'); ?>
                            </div>
                            
                            <div class="item-card-meta" style="margin-top: auto; margin-bottom: 0;">
                                <span>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                    <?php echo formatDate($a['created_at']); ?>
                                </span>
                                <span style="margin-left: auto;">
                                    <?php echo sanitize($a['first_name'] . ' ' . $a['last_name']); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <?php echo renderPagination($page, $totalPages, BASE_URL . '/modules/announcements/list.php'); ?>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
