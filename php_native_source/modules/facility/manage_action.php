<?php
/**
 * Manage Reservation Action Handler
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    
    if (!$id) {
        setFlash('error', 'Invalid request.');
        redirectBack();
    }
    
    $db = getDB();
    
    try {
        // Fetch reservation info
        $stmt = $db->prepare("SELECT * FROM facility_reservations WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            setFlash('error', 'Reservation not found.');
            redirectBack();
        }
        
        $userId = $reservation['user_id'];
        $facilityId = $reservation['facility_id'];
        $title = $reservation['title'];
        
        // Handle User Cancellation
        if ($action === 'cancel') {
            if ($userId != $_SESSION['user_id']) {
                setFlash('error', 'Unauthorized.');
                redirectBack();
            }
            if ($reservation['status'] !== 'pending') {
                setFlash('error', 'Only pending reservations can be cancelled.');
                redirectBack();
            }
            
            $stmt = $db->prepare("UPDATE facility_reservations SET status = 'cancelled' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            logAudit('CANCEL_RESERVATION', 'facility_reservations', $id);
            setFlash('success', 'Reservation cancelled.');
        }
        
        // Handle Admin Approve/Reject
        elseif (in_array($action, ['approve', 'reject'])) {
            requirePermission('facility.manage');
            
            if ($reservation['status'] !== 'pending') {
                setFlash('error', 'This reservation has already been processed.');
                redirectBack();
            }
            
            $status = $action === 'approve' ? 'approved' : 'rejected';
            
            $stmt = $db->prepare("
                UPDATE facility_reservations 
                SET status = :status, remarks = :remarks, approved_by = :admin_id 
                WHERE id = :id
            ");
            $stmt->execute([
                ':status' => $status,
                ':remarks' => $remarks ?: null,
                ':admin_id' => $_SESSION['user_id'],
                ':id' => $id
            ]);
            
            logAudit(strtoupper($action) . '_RESERVATION', 'facility_reservations', $id);
            
            // Notify user
            $msg = $action === 'approve' 
                ? "Your facility reservation for '{$title}' has been APPROVED." 
                : "Your facility reservation for '{$title}' has been REJECTED." . ($remarks ? " Reason: {$remarks}" : "");
                
            createNotification($userId, 'Reservation ' . ucfirst($status), $msg, $action === 'approve' ? 'success' : 'error', '/modules/facility/my_reservations.php');
            
            setFlash('success', "Reservation {$status} successfully.");
        }
        
    } catch (PDOException $e) {
        setFlash('error', 'System error occurred.');
        error_log($e->getMessage());
    }
}

redirectBack();
