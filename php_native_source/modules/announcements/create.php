<?php
/**
 * Create Announcement
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('announcement.create');

$pageTitle = 'Post Announcement';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = $_POST['priority'] ?? 'general';
    $audience = $_POST['audience'] ?? 'all';
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    
    // Expires at parsing
    $expires_at = null;
    if (!empty($_POST['expires_at'])) {
        $expires_at = date('Y-m-d H:i:s', strtotime($_POST['expires_at']));
    }
    
    // Simple validation
    $errors = [];
    if (empty($title)) $errors[] = "Title is required.";
    if (empty($content)) $errors[] = "Content is required.";
    
    if (empty($errors)) {
        try {
            $db = getDB();
            
            // Image upload handling (stub for now)
            $imageName = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                // In a real app, implement secure upload validation here
                // $imageName = 'upload_' . time() . '.jpg';
            }
            
            $stmt = $db->prepare("
                INSERT INTO announcements (title, content, priority, audience, posted_by, image, is_pinned, expires_at) 
                VALUES (:title, :content, :priority, :audience, :posted_by, :image, :is_pinned, :expires_at)
            ");
            
            $stmt->execute([
                ':title'      => $title,
                ':content'    => $content,
                ':priority'   => $priority,
                ':audience'   => $audience,
                ':posted_by'  => $_SESSION['user_id'],
                ':image'      => $imageName,
                ':is_pinned'  => $is_pinned,
                ':expires_at' => $expires_at
            ]);
            
            $announcementId = $db->lastInsertId();
            logAudit('CREATE_ANNOUNCEMENT', 'announcements', $announcementId);
            
            setFlash('success', 'Announcement posted successfully.');
            redirect('/modules/announcements/list.php');
            
        } catch (PDOException $e) {
            $errors[] = "Database error occurred.";
            error_log($e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Announcements', 'url' => '/modules/announcements/list.php'],
    ['label' => 'Post Announcement']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Post Announcement</h1>
        <p class="page-subtitle">Create a new broadcast message for the campus community.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/announcements/list.php" class="btn btn-secondary">Cancel</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label class="form-label" for="title">Announcement Title *</label>
                <input type="text" id="title" name="title" class="form-control" required autofocus>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="priority">Priority</label>
                    <select id="priority" name="priority" class="form-control">
                        <option value="general">General</option>
                        <option value="important">Important</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="audience">Target Audience</label>
                    <select id="audience" name="audience" class="form-control">
                        <option value="all">All Members</option>
                        <option value="students">Students Only</option>
                        <option value="faculty">Faculty Only</option>
                        <option value="staff">Staff Only</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="content">Message Content *</label>
                <textarea id="content" name="content" class="form-control" rows="8" required placeholder="Write your announcement here..."></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="expires_at">Expiration Date (Optional)</label>
                    <input type="datetime-local" id="expires_at" name="expires_at" class="form-control">
                    <div class="form-hint">Announcement will be hidden automatically after this date.</div>
                </div>
                
                <div class="form-group" style="display: flex; align-items: flex-end; padding-bottom: 15px;">
                    <label class="form-check">
                        <input type="checkbox" name="is_pinned" id="is_pinned" value="1">
                        <span class="form-check-label">Pin to top of the list</span>
                    </label>
                </div>
            </div>
            
            <hr style="border: 0; border-top: 1px solid var(--color-border); margin: var(--spacing-xl) 0;">
            
            <div class="flex justify-between items-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                    Post Announcement
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
