<?php
/**
 * Notifications API
 * ICOEP - Integrated Campus Operations and Engagement Portal
 * 
 * Handles AJAX requests for the notification bell.
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Very basic auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// CSRF Check
$headers = getallheaders();
$csrfToken = $headers['X-CSRF-TOKEN'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid token']);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' && ($_GET['action'] ?? '') === 'get_unread') {
    try {
        // Get count
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0");
        $stmt->execute([':uid' => $userId]);
        $unreadCount = (int) $stmt->fetchColumn();
        
        // Get latest 5
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE user_id = :uid 
            ORDER BY created_at DESC LIMIT 5
        ");
        $stmt->execute([':uid' => $userId]);
        $notifications = $stmt->fetchAll();
        
        // Format time ago
        require_once __DIR__ . '/../includes/functions.php';
        foreach ($notifications as &$n) {
            $n['time_ago'] = timeAgo($n['created_at']);
        }
        
        echo json_encode([
            'unread_count' => $unreadCount,
            'notifications' => $notifications
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error']);
    }
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (($data['action'] ?? '') === 'mark_all_read') {
        try {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :uid AND is_read = 0");
            $stmt->execute([':uid' => $userId]);
            
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error']);
        }
    }
}
