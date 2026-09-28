<?php
/**
 * Manage Equipment Request Action Handler
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
        // Fetch request info
        $stmt = $db->prepare("SELECT r.*, e.name as equipment_name FROM equipment_requests r JOIN equipment e ON r.equipment_id = e.id WHERE r.id = :id");
        $stmt->execute([':id' => $id]);
        $req = $stmt->fetch();
        
        if (!$req) {
            setFlash('error', 'Request not found.');
            redirectBack();
        }
        
        $userId = $req['user_id'];
        $equipmentId = $req['equipment_id'];
        $qty = (int)$req['quantity'];
        $title = $req['equipment_name'];
        
        // Handle User Cancellation
        if ($action === 'cancel') {
            if ($userId != $_SESSION['user_id']) {
                setFlash('error', 'Unauthorized.');
                redirectBack();
            }
            if ($req['status'] !== 'pending') {
                setFlash('error', 'Only pending requests can be cancelled.');
                redirectBack();
            }
            
            $stmt = $db->prepare("UPDATE equipment_requests SET status = 'cancelled' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            logAudit('CANCEL_EQUIPMENT_REQUEST', 'equipment_requests', $id);
            setFlash('success', 'Request cancelled.');
        }
        
        // Handle Admin Actions (Approve, Reject, Release, Return)
        elseif (in_array($action, ['approve', 'reject', 'release', 'return'])) {
            requirePermission('equipment.manage');
            
            $db->beginTransaction();
            
            // Re-fetch with lock for stock deduction/addition
            $stmt = $db->prepare("SELECT available_quantity FROM equipment WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $equipmentId]);
            $eq = $stmt->fetch();
            
            if ($action === 'approve') {
                if ($req['status'] !== 'pending') throw new Exception("Invalid status transition.");
                
                // Deduct stock upon approval
                if ($eq['available_quantity'] < $qty) {
                    throw new Exception("Not enough stock available to approve this request.");
                }
                
                $stmt = $db->prepare("UPDATE equipment SET available_quantity = available_quantity - :qty WHERE id = :id");
                $stmt->execute([':qty' => $qty, ':id' => $equipmentId]);
                
                $stmt = $db->prepare("UPDATE equipment_requests SET status = 'approved', approved_by = :admin_id WHERE id = :id");
                $stmt->execute([':admin_id' => $_SESSION['user_id'], ':id' => $id]);
                
                createNotification($userId, 'Equipment Request Approved', "Your request for {$qty}x {$title} has been approved. Please pick it up.", 'success', '/modules/equipment/my_requests.php');
                $msg = "Request approved successfully.";
            }
            
            elseif ($action === 'reject') {
                if ($req['status'] !== 'pending') throw new Exception("Invalid status transition.");
                
                $stmt = $db->prepare("UPDATE equipment_requests SET status = 'rejected', remarks = :remarks, approved_by = :admin_id WHERE id = :id");
                $stmt->execute([':remarks' => $remarks, ':admin_id' => $_SESSION['user_id'], ':id' => $id]);
                
                createNotification($userId, 'Equipment Request Rejected', "Your request for {$qty}x {$title} was rejected.", 'error', '/modules/equipment/my_requests.php');
                $msg = "Request rejected.";
            }
            
            elseif ($action === 'release') {
                if ($req['status'] !== 'approved') throw new Exception("Can only release approved items.");
                
                $stmt = $db->prepare("UPDATE equipment_requests SET status = 'borrowed' WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $msg = "Equipment marked as borrowed.";
            }
            
            elseif ($action === 'return') {
                if ($req['status'] !== 'borrowed') throw new Exception("Can only return borrowed items.");
                
                // Add stock back
                $stmt = $db->prepare("UPDATE equipment SET available_quantity = available_quantity + :qty WHERE id = :id");
                $stmt->execute([':qty' => $qty, ':id' => $equipmentId]);
                
                $stmt = $db->prepare("UPDATE equipment_requests SET status = 'returned', actual_return_date = NOW() WHERE id = :id");
                $stmt->execute([':id' => $id]);
                $msg = "Equipment marked as returned and stock restored.";
            }
            
            logAudit(strtoupper($action) . '_EQUIPMENT', 'equipment_requests', $id);
            $db->commit();
            setFlash('success', $msg);
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('error', $e->getMessage());
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('error', 'System error occurred.');
        error_log($e->getMessage());
    }
}

redirectBack();
