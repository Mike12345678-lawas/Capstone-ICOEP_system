<?php
/**
 * Create Event
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requirePermission('event.create');

$pageTitle = 'Create Event';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_type = trim($_POST['event_type'] ?? 'academic');
    $event_date = trim($_POST['event_date'] ?? '');
    $time_start = trim($_POST['time_start'] ?? '');
    $time_end = trim($_POST['time_end'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $max_participants = (int)($_POST['max_participants'] ?? 0);
    
    $errors = [];
    if (empty($title)) $errors[] = "Event title is required.";
    if (empty($description)) $errors[] = "Description is required.";
    if (empty($event_date) || !validateDate($event_date)) $errors[] = "Valid date is required.";
    if (empty($time_start)) $errors[] = "Start time is required.";
    if (empty($time_end)) $errors[] = "End time is required.";
    if ($time_start >= $time_end) $errors[] = "End time must be after start time.";
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO events (title, description, event_type, event_date, time_start, time_end, venue, max_participants, organizer_id, status)
                VALUES (:title, :description, :type, :edate, :tstart, :tend, :venue, :maxp, :org, 'upcoming')
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':type' => $event_type,
                ':edate' => $event_date,
                ':tstart' => $time_start,
                ':tend' => $time_end,
                ':venue' => $venue ?: null,
                ':maxp' => $max_participants,
                ':org' => $_SESSION['user_id']
            ]);
            
            $eventId = $db->lastInsertId();
            logAudit('CREATE_EVENT', 'events', $eventId);
            
            setFlash('success', 'Event created successfully.');
            redirect('/modules/events/view.php?id=' . $eventId);
            
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
    ['label' => 'Events', 'url' => '/modules/events/list.php'],
    ['label' => 'Create Event']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Create Event</h1>
        <p class="page-subtitle">Organize a new campus activity.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/events/list.php" class="btn btn-secondary">Cancel</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label class="form-label" for="title">Event Title *</label>
                <input type="text" id="title" name="title" class="form-control" required value="<?php echo sanitize($_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="event_type">Event Type *</label>
                    <select id="event_type" name="event_type" class="form-control" required>
                        <option value="academic">Academic</option>
                        <option value="cultural">Cultural</option>
                        <option value="sports">Sports</option>
                        <option value="spiritual">Spiritual</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="max_participants">Max Participants</label>
                    <input type="number" id="max_participants" name="max_participants" class="form-control" min="0" placeholder="0 = Unlimited" value="<?php echo sanitize($_POST['max_participants'] ?? 0); ?>">
                    <div class="form-hint">Leave as 0 for unlimited capacity.</div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="event_date">Date *</label>
                    <input type="date" id="event_date" name="event_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo sanitize($_POST['event_date'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="time_start">Start Time *</label>
                    <input type="time" id="time_start" name="time_start" class="form-control" required value="<?php echo sanitize($_POST['time_start'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="time_end">End Time *</label>
                    <input type="time" id="time_end" name="time_end" class="form-control" required value="<?php echo sanitize($_POST['time_end'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="venue">Venue / Location</label>
                <input type="text" id="venue" name="venue" class="form-control" placeholder="e.g. Main Auditorium" value="<?php echo sanitize($_POST['venue'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label" for="description">Event Description *</label>
                <textarea id="description" name="description" class="form-control" rows="6" required><?php echo sanitize($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group" style="padding-top: var(--spacing-md);">
                <button type="submit" class="btn btn-primary btn-lg">
                    Create Event
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
