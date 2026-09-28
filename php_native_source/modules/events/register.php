<?php
/**
 * Register for Event Action
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $eventId = (int)($_POST['event_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if (!$eventId) {
        setFlash('error', 'Invalid event.');
        redirectBack();
    }
    
    $db = getDB();
    
    try {
        $db->beginTransaction();
        
        // Fetch event with lock to safely check capacity
        $stmt = $db->prepare("
            SELECT e.*, (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as reg_count 
            FROM events e 
            WHERE id = :id FOR UPDATE
        ");
        $stmt->execute([':id' => $eventId]);
        $event = $stmt->fetch();
        
        if (!$event || $event['deleted_at'] || !in_array($event['status'], ['upcoming', 'ongoing'])) {
            throw new Exception("Event is not available for registration.");
        }
        
        // Check current registration
        $stmt = $db->prepare("SELECT * FROM event_registrations WHERE event_id = :eid AND user_id = :uid");
        $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
        $isRegistered = $stmt->fetch();
        
        if ($action === 'join') {
            if ($isRegistered) throw new Exception("You are already registered.");
            
            // Check capacity
            if ($event['max_participants'] > 0 && $event['reg_count'] >= $event['max_participants']) {
                throw new Exception("Event is already full.");
            }
            
            $stmt = $db->prepare("INSERT INTO event_registrations (event_id, user_id) VALUES (:eid, :uid)");
            $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
            
            logAudit('REGISTER_EVENT', 'event_registrations', $eventId);
            setFlash('success', 'Successfully registered for event!');
            
        } elseif ($action === 'leave') {
            if (!$isRegistered) throw new Exception("You are not registered.");
            if ($isRegistered['has_attended']) throw new Exception("Cannot cancel after attending.");
            
            $stmt = $db->prepare("DELETE FROM event_registrations WHERE event_id = :eid AND user_id = :uid");
            $stmt->execute([':eid' => $eventId, ':uid' => $userId]);
            
            logAudit('LEAVE_EVENT', 'event_registrations', $eventId);
            setFlash('success', 'Registration cancelled.');
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('error', $e->getMessage());
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        setFlash('error', 'A system error occurred.');
    }
}

redirect('/modules/events/view.php?id=' . $eventId);
