<?php
/**
 * Manage Announcements (Admin POST handler)
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    requireLogin();
    
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    
    if (!$id) {
        setFlash('error', 'Invalid request.');
        redirectBack();
    }
    
    try {
        $db = getDB();
        
        if ($action === 'delete') {
            // Verify permission (admin or the creator)
            $stmt = $db->prepare("SELECT posted_by FROM announcements WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $announcement = $stmt->fetch();
            
            if ($announcement && (hasPermission('announcement.manage') || $announcement['posted_by'] == $_SESSION['user_id'])) {
                // Soft delete
                $stmt = $db->prepare("UPDATE announcements SET deleted_at = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
                
                logAudit('DELETE_ANNOUNCEMENT', 'announcements', $id);
                setFlash('success', 'Announcement deleted successfully.');
            } else {
                setFlash('error', 'You do not have permission to delete this announcement.');
            }
        }
        
    } catch (PDOException $e) {
        setFlash('error', 'A system error occurred.');
        error_log($e->getMessage());
    }
}

redirect('/modules/announcements/list.php');
