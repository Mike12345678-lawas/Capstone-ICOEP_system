<?php
/**
 * Reserve Facility
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('facility.reserve');

$pageTitle = 'Reserve Facility';
$db = getDB();

// Get selected facility if passed
$selectedFacilityId = isset($_GET['facility_id']) ? (int)$_GET['facility_id'] : 0;

// Fetch available facilities for dropdown
try {
    $stmt = $db->query("SELECT id, name, capacity FROM facilities WHERE status = 'available' ORDER BY name ASC");
    $facilities = $stmt->fetchAll();
} catch (PDOException $e) {
    $facilities = [];
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $facility_id = (int)($_POST['facility_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $reservation_date = trim($_POST['reservation_date'] ?? '');
    $time_start = trim($_POST['time_start'] ?? '');
    $time_end = trim($_POST['time_end'] ?? '');
    
    $errors = [];
    
    if (!$facility_id) $errors[] = "Please select a facility.";
    if (empty($title)) $errors[] = "Purpose/Event Title is required.";
    if (empty($reservation_date) || !validateDate($reservation_date)) $errors[] = "A valid reservation date is required.";
    if (empty($time_start)) $errors[] = "Start time is required.";
    if (empty($time_end)) $errors[] = "End time is required.";
    if ($reservation_date < date('Y-m-d')) $errors[] = "Reservation date cannot be in the past.";
    if ($time_start >= $time_end) $errors[] = "End time must be after start time.";
    
    if (empty($errors)) {
        try {
            // Check for schedule conflicts
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM facility_reservations 
                WHERE facility_id = :fid 
                AND reservation_date = :rdate
                AND status IN ('pending', 'approved')
                AND (
                    (time_start < :tend AND time_end > :tstart)
                )
            ");
            $stmt->execute([
                ':fid' => $facility_id,
                ':rdate' => $reservation_date,
                ':tstart' => $time_start,
                ':tend' => $time_end
            ]);
            
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "This facility is already booked (or pending approval) for the selected time slot. Please choose another time.";
            } else {
                // Insert reservation
                $stmt = $db->prepare("
                    INSERT INTO facility_reservations (facility_id, user_id, title, description, reservation_date, time_start, time_end, status)
                    VALUES (:facility_id, :user_id, :title, :description, :reservation_date, :time_start, :time_end, 'pending')
                ");
                
                $stmt->execute([
                    ':facility_id'      => $facility_id,
                    ':user_id'          => $_SESSION['user_id'],
                    ':title'            => $title,
                    ':description'      => $description,
                    ':reservation_date' => $reservation_date,
                    ':time_start'       => $time_start,
                    ':time_end'         => $time_end
                ]);
                
                $reservationId = $db->lastInsertId();
                logAudit('CREATE_RESERVATION', 'facility_reservations', $reservationId);
                
                // Notify admins/staff
                $adminStmt = $db->query("SELECT id FROM users WHERE role_id IN (1, 2) AND status = 'active'");
                while ($admin = $adminStmt->fetch()) {
                    createNotification($admin['id'], 'New Facility Request', "A new reservation request for '{$title}' is pending approval.", 'info', '/modules/facility/manage.php');
                }
                
                setFlash('success', 'Reservation request submitted successfully. It is now pending administrative approval.');
                redirect('/modules/facility/my_reservations.php');
            }
        } catch (PDOException $e) {
            $errors[] = "A system error occurred. Please try again.";
            error_log("Reservation error: " . $e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Facilities', 'url' => '/modules/facility/list.php'],
    ['label' => 'Reserve']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Reserve Facility</h1>
        <p class="page-subtitle">Submit a booking request for campus facilities.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/facility/list.php" class="btn btn-secondary">Cancel</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="form-group">
                <label class="form-label" for="facility_id">Select Facility *</label>
                <select id="facility_id" name="facility_id" class="form-control" required>
                    <option value="">-- Choose a facility --</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?php echo $f['id']; ?>" <?php echo ($selectedFacilityId == $f['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($f['name']) . ' (Capacity: ' . $f['capacity'] . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="title">Purpose / Event Title *</label>
                <input type="text" id="title" name="title" class="form-control" required placeholder="e.g. BSIT Department Meeting" value="<?php echo sanitize($_POST['title'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="reservation_date">Date *</label>
                    <input type="date" id="reservation_date" name="reservation_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo sanitize($_POST['reservation_date'] ?? date('Y-m-d')); ?>">
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
                <label class="form-label" for="description">Additional Details / Requirements</label>
                <textarea id="description" name="description" class="form-control" rows="4" placeholder="Optional. Any specific setup required?"><?php echo sanitize($_POST['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group" style="padding-top: var(--spacing-md);">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Submit Reservation Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
