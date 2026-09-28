<?php
/**
 * Helper Functions
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../config/database.php';

// ============================================================
// INPUT SANITIZATION
// ============================================================

/**
 * Sanitize string input for safe HTML output
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function validateEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate date format (Y-m-d)
 */
function validateDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// ============================================================
// FLASH MESSAGES
// ============================================================

/**
 * Set a flash message in the session
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

/**
 * Get and clear the flash message
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message HTML
 */
function renderFlash(): string {
    $flash = getFlash();
    if (!$flash) return '';
    
    $icons = [
        'success' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
        'error' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
        'warning' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
        'info' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
    ];
    
    $icon = $icons[$flash['type']] ?? $icons['info'];
    $type = sanitize($flash['type']);
    $message = sanitize($flash['message']);
    
    return <<<HTML
    <div class="flash-message flash-{$type}" id="flashMessage">
        <span class="flash-icon">{$icon}</span>
        <span class="flash-text">{$message}</span>
        <button class="flash-close" onclick="this.parentElement.remove()">&times;</button>
    </div>
    HTML;
}

// ============================================================
// REDIRECT
// ============================================================

/**
 * Redirect to a URL
 */
function redirect(string $path): void {
    header("Location: " . BASE_URL . $path);
    exit;
}

/**
 * Redirect back to the previous page
 */
function redirectBack(): void {
    $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . '/modules/dashboard/index.php';
    header("Location: " . $referer);
    exit;
}

// ============================================================
// AUDIT LOGGING (RA 10173 Compliance)
// ============================================================

/**
 * Log an action to the audit trail
 */
function logAudit(string $action, ?string $tableName = null, ?int $recordId = null, $oldValues = null, $newValues = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, old_values, new_values, ip_address, user_agent) 
            VALUES (:user_id, :action, :table_name, :record_id, :old_values, :new_values, :ip_address, :user_agent)
        ");
        
        $stmt->execute([
            ':user_id'    => $_SESSION['user_id'] ?? null,
            ':action'     => $action,
            ':table_name' => $tableName,
            ':record_id'  => $recordId,
            ':old_values' => $oldValues ? json_encode($oldValues) : null,
            ':new_values' => $newValues ? json_encode($newValues) : null,
            ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            ':user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 500),
        ]);
    } catch (PDOException $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

// ============================================================
// NOTIFICATION HELPERS
// ============================================================

/**
 * Create an in-app notification for a user
 */
function createNotification(int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, link) 
            VALUES (:user_id, :title, :message, :type, :link)
        ");
        $stmt->execute([
            ':user_id' => $userId,
            ':title'   => $title,
            ':message' => $message,
            ':type'    => $type,
            ':link'    => $link,
        ]);
    } catch (PDOException $e) {
        error_log("Notification creation failed: " . $e->getMessage());
    }
}

/**
 * Get unread notification count for a user
 */
function getUnreadNotificationCount(int $userId): int {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :user_id AND is_read = 0");
        $stmt->execute([':user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

/**
 * Get recent notifications for a user
 */
function getRecentNotifications(int $userId, int $limit = 10): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :user_id 
            ORDER BY created_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// ============================================================
// DATE/TIME HELPERS
// ============================================================

/**
 * Format date for display
 */
function formatDate(string $date, string $format = 'M d, Y'): string {
    return date($format, strtotime($date));
}

/**
 * Format datetime for display
 */
function formatDateTime(string $datetime, string $format = 'M d, Y h:i A'): string {
    return date($format, strtotime($datetime));
}

/**
 * Format time for display
 */
function formatTime(string $time, string $format = 'h:i A'): string {
    return date($format, strtotime($time));
}

/**
 * Get relative time (e.g., "2 hours ago")
 */
function timeAgo(string $datetime): string {
    $now = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);
    
    if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
    if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
    if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}

// ============================================================
// USER HELPERS
// ============================================================

/**
 * Get current logged-in user data
 */
function currentUser(): ?array {
    if (!isset($_SESSION['user_id'])) return null;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            WHERE u.id = :id AND u.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        return $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        return null;
    }
}

/**
 * Get user's full name
 */
function getUserFullName(?array $user = null): string {
    if (!$user) $user = currentUser();
    if (!$user) return 'Unknown User';
    return $user['first_name'] . ' ' . $user['last_name'];
}

/**
 * Get user's initials for avatar
 */
function getUserInitials(?array $user = null): string {
    if (!$user) $user = currentUser();
    if (!$user) return '?';
    return strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
}

/**
 * Check if current user has a specific role
 */
function hasRole(string $roleName): bool {
    return ($_SESSION['role_name'] ?? '') === $roleName;
}

/**
 * Check if current user has a specific permission
 */
function hasPermission(string $permission): bool {
    if (!isset($_SESSION['user_id'])) return false;
    
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = :role_id AND p.name = :permission
        ");
        $stmt->execute([
            ':role_id'    => $_SESSION['role_id'] ?? 0,
            ':permission' => $permission,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// ============================================================
// PAGINATION HELPER
// ============================================================

/**
 * Generate pagination HTML
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" class="pagination-btn">&laquo; Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . $baseUrl . '?page=1" class="pagination-btn">1</a>';
        if ($start > 2) $html .= '<span class="pagination-dots">...</span>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="pagination-btn' . $active . '">' . $i . '</a>';
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) $html .= '<span class="pagination-dots">...</span>';
        $html .= '<a href="' . $baseUrl . '?page=' . $totalPages . '" class="pagination-btn">' . $totalPages . '</a>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . $baseUrl . '?page=' . ($currentPage + 1) . '" class="pagination-btn">Next &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

// ============================================================
// STATUS BADGE HELPER
// ============================================================

/**
 * Render a status badge
 */
function statusBadge(string $status): string {
    $status = strtolower($status);
    $labels = [
        'pending'   => 'Pending',
        'approved'  => 'Approved',
        'rejected'  => 'Rejected',
        'cancelled' => 'Cancelled',
        'borrowed'  => 'Borrowed',
        'returned'  => 'Returned',
        'overdue'   => 'Overdue',
        'upcoming'  => 'Upcoming',
        'ongoing'   => 'Ongoing',
        'completed' => 'Completed',
        'active'    => 'Active',
        'inactive'  => 'Inactive',
        'locked'    => 'Locked',
        'available'    => 'Available',
        'maintenance'  => 'Maintenance',
        'unavailable'  => 'Unavailable',
        'general'   => 'General',
        'important' => 'Important',
        'urgent'    => 'Urgent',
    ];
    
    $label = $labels[$status] ?? ucfirst($status);
    return '<span class="badge badge-' . sanitize($status) . '">' . $label . '</span>';
}
