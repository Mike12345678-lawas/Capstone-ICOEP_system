<?php
/**
 * Manage Event & Attendance
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0);
if (!$id) redirect('/modules/events/list.php');

$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM events WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute([':id' => $id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlash('error', 'Event not found.');
        redirect('/modules/events/list.php');
    }
    
    // Check permission: Must be Admin, Staff, or the Event Organizer
    if (!hasPermission('event.manage') && $event['organizer_id'] != $_SESSION['user_id']) {
        setFlash('error', 'You do not have permission to manage this event.');
        redirect('/modules/events/view.php?id=' . $id);
    }
    
    // Handle POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        requireCSRF();
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update_status') {
            $newStatus = $_POST['status'] ?? 'upcoming';
            $stmt = $db->prepare("UPDATE events SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $newStatus, ':id' => $id]);
            logAudit('UPDATE_EVENT_STATUS', 'events', $id);
            setFlash('success', 'Event status updated.');
            redirect('/modules/events/manage.php?id=' . $id);
        }
        
        if ($action === 'mark_attendance') {
            $userId = (int)$_POST['user_id'];
            $attended = isset($_POST['attended']) ? 1 : 0;
            $stmt = $db->prepare("UPDATE event_registrations SET has_attended = :att WHERE event_id = :eid AND user_id = :uid");
            $stmt->execute([':att' => $attended, ':eid' => $id, ':uid' => $userId]);
            setFlash('success', 'Attendance updated.');
            redirect('/modules/events/manage.php?id=' . $id);
        }
    }
    
    $pageTitle = 'Manage: ' . $event['title'];
    
    // Get attendees
    $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email, u.employee_id, er.registered_at, er.has_attended
        FROM event_registrations er
        JOIN users u ON er.user_id = u.id
        WHERE er.event_id = :eid
        ORDER BY u.last_name ASC, u.first_name ASC
    ");
    $stmt->execute([':eid' => $id]);
    $attendees = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlash('error', 'Database error.');
    redirect('/modules/events/list.php');
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Events', 'url' => '/modules/events/list.php'],
    ['label' => 'Manage Event']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Manage Event</h1>
        <p class="page-subtitle"><?php echo sanitize($event['title']); ?></p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/events/view.php?id=<?php echo $id; ?>" class="btn btn-secondary">&larr; Back to Event</a>
</div>

<div class="grid" style="grid-template-columns: 1fr 3fr; gap: var(--spacing-lg);">
    
    <!-- Controls -->
    <div class="animate-fade-in-up" style="animation-delay: 100ms;">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Event Status</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="event_id" value="<?php echo $id; ?>">
                    
                    <div class="form-group">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="upcoming" <?php echo $event['status'] === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="ongoing" <?php echo $event['status'] === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="completed" <?php echo $event['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                </form>
                
                <div style="margin-top: var(--spacing-lg);">
                    <strong>Summary</strong>
                    <ul style="list-style: none; padding: 0; margin-top: 10px;">
                        <li>Total Registered: <?php echo count($attendees); ?></li>
                        <li>Attended: <?php echo count(array_filter($attendees, fn($a) => $a['has_attended'])); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Attendees -->
    <div class="animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="card-title">Attendee List</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>ID / Email</th>
                            <th>Registered At</th>
                            <th>Attendance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendees)): ?>
                            <tr><td colspan="4" class="text-center text-muted">No attendees registered yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($attendees as $a): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?php echo sanitize($a['last_name'] . ', ' . $a['first_name']); ?></td>
                                    <td>
                                        <div><?php echo sanitize($a['employee_id']); ?></div>
                                        <div class="text-muted" style="font-size: 0.85em;"><?php echo sanitize($a['email']); ?></div>
                                    </td>
                                    <td><?php echo formatDateTime($a['registered_at']); ?></td>
                                    <td>
                                        <form method="POST" action="" style="margin: 0;">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="mark_attendance">
                                            <input type="hidden" name="event_id" value="<?php echo $id; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $a['id']; ?>">
                                            <label class="form-check" style="margin: 0;">
                                                <input type="checkbox" name="attended" onchange="this.form.submit()" <?php echo $a['has_attended'] ? 'checked' : ''; ?>>
                                                <span class="form-check-label"><?php echo $a['has_attended'] ? '<span style="color:var(--color-success)">Present</span>' : 'Absent'; ?></span>
                                            </label>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
