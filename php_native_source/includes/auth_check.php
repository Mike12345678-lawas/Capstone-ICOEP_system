<?php
/**
 * Authentication & Authorization Middleware
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Require user to be logged in; redirect to login if not
 */
function requireLogin(): void {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
        setFlash('warning', 'Please log in to access this page.');
        redirect('/modules/auth/login.php');
    }
    
    // Check if account is still active
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT status FROM users WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || $user['status'] !== 'active') {
            destroySession();
            setFlash('error', 'Your account has been deactivated. Please contact administration.');
            redirect('/modules/auth/login.php');
        }
    } catch (PDOException $e) {
        error_log("Auth check failed: " . $e->getMessage());
    }
}

/**
 * Require a specific role
 */
function requireRole(string ...$roles): void {
    requireLogin();
    
    $userRole = $_SESSION['role_name'] ?? '';
    if (!in_array($userRole, $roles)) {
        setFlash('error', 'You do not have permission to access this page.');
        redirect('/modules/dashboard/index.php');
    }
}

/**
 * Require a specific permission
 */
function requirePermission(string $permission): void {
    requireLogin();
    
    if (!hasPermission($permission)) {
        setFlash('error', 'You do not have permission to perform this action.');
        redirect('/modules/dashboard/index.php');
    }
}

/**
 * Check if user is logged in (no redirect)
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Validate CSRF on POST requests — call at top of form handlers
 */
function requireCSRF(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!validateCSRFToken()) {
            setFlash('error', 'Invalid security token. Please try again.');
            redirectBack();
        }
    }
}
