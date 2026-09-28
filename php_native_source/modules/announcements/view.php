<?php
/**
 * View Announcement
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    redirect('/modules/announcements/list.php');
}

$db = getDB();
$roleName = $_SESSION['role_name'] ?? 'User';

try {
    // Fetch announcement and check audience visibility
    $stmt = $db->prepare("
        SELECT a.*, u.first_name, u.last_name 
        FROM announcements a
        JOIN users u ON a.posted_by = u.id
        WHERE a.id = :id AND a.deleted_at IS NULL
    ");
    $stmt->execute([':id' => $id]);
    $announcement = $stmt->fetch();
    
    if (!$announcement) {
        setFlash('error', 'Announcement not found or has been deleted.');
        redirect('/modules/announcements/list.php');
    }
    
    // Check audience permission
    $audience = $announcement['audience'];
    $allowed = ($audience === 'all') || 
               ($audience === 'students' && $roleName === 'Student') ||
               ($audience === 'faculty' && $roleName === 'Faculty') ||
               ($audience === 'staff' && $roleName === 'Staff') ||
               ($audience === 'administrators' && $roleName === 'Administrator') ||
               hasPermission('announcement.manage'); // Admins can view all
               
    if (!$allowed) {
        setFlash('error', 'You do not have permission to view this announcement.');
        redirect('/modules/announcements/list.php');
    }
    
    $pageTitle = $announcement['title'];
    
} catch (PDOException $e) {
    setFlash('error', 'Database error.');
    redirect('/modules/announcements/list.php');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Announcements', 'url' => '/modules/announcements/list.php'],
    ['label' => 'View Announcement']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title"><?php echo sanitize($announcement['title']); ?></h1>
        <p class="page-subtitle">
            Posted by <?php echo sanitize($announcement['first_name'] . ' ' . $announcement['last_name']); ?> on <?php echo formatDateTime($announcement['created_at']); ?>
        </p>
    </div>
    <div class="btn-group">
        <?php if (hasPermission('announcement.manage') || (hasPermission('announcement.create') && $announcement['posted_by'] == $_SESSION['user_id'])): ?>
            <button class="btn btn-danger" onclick="confirmAction('Are you sure you want to delete this announcement?', () => document.getElementById('deleteForm').submit())">
                Delete
            </button>
            <form id="deleteForm" action="<?php echo BASE_URL; ?>/modules/announcements/manage.php" method="POST" style="display: none;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $announcement['id']; ?>">
            </form>
        <?php endif; ?>
        <a href="<?php echo BASE_URL; ?>/modules/announcements/list.php" class="btn btn-secondary">
            &larr; Back to List
        </a>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms;">
    <div class="card-header">
        <div class="flex gap-sm">
            <?php echo statusBadge($announcement['priority']); ?>
            <span class="badge badge-general">Audience: <?php echo ucfirst(sanitize($announcement['audience'])); ?></span>
            <?php if ($announcement['is_pinned']): ?>
                <span class="badge badge-general" style="background: rgba(245, 158, 11, 0.15); color: var(--color-gold);">Pinned</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <?php if ($announcement['image']): ?>
            <div style="margin-bottom: var(--spacing-xl); text-align: center;">
                <img src="<?php echo BASE_URL . '/uploads/announcements/' . sanitize($announcement['image']); ?>" alt="Announcement Image" style="max-height: 400px; border-radius: var(--radius-md);">
            </div>
        <?php endif; ?>
        
        <div class="announcement-full-content" style="font-size: var(--text-base); line-height: 1.8;">
            <!-- Outputting nl2br for simple formatting. In a real app with TinyMCE, you'd output raw HTML if sanitized -->
            <?php echo nl2br(sanitize($announcement['content'])); ?>
        </div>
    </div>
    <?php if ($announcement['expires_at']): ?>
    <div class="card-footer">
        <div class="text-muted" style="font-size: var(--text-xs);">
            Valid until: <?php echo formatDateTime($announcement['expires_at']); ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
