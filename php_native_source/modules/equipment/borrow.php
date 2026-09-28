<?php
/**
 * Borrow Equipment
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('equipment.borrow');

$pageTitle = 'Request Equipment';
$db = getDB();

$selectedEqId = isset($_GET['equipment_id']) ? (int)$_GET['equipment_id'] : 0;

try {
    $stmt = $db->query("SELECT id, name, available_quantity FROM equipment WHERE status = 'available' AND available_quantity > 0 ORDER BY name ASC");
    $equipments = $stmt->fetchAll();
} catch (PDOException $e) {
    $equipments = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $equipment_id = (int)($_POST['equipment_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    $purpose = trim($_POST['purpose'] ?? '');
    $date_needed = trim($_POST['date_needed'] ?? '');
    $expected_return_date = trim($_POST['expected_return_date'] ?? '');
    
    $errors = [];
    
    if (!$equipment_id) $errors[] = "Please select equipment.";
    if ($quantity < 1) $errors[] = "Quantity must be at least 1.";
    if (empty($purpose)) $errors[] = "Purpose is required.";
    if (empty($date_needed) || !validateDate($date_needed)) $errors[] = "Valid date needed is required.";
    if (empty($expected_return_date) || !validateDate($expected_return_date)) $errors[] = "Valid expected return date is required.";
    
    if ($date_needed < date('Y-m-d')) $errors[] = "Date needed cannot be in the past.";
    if ($expected_return_date < $date_needed) $errors[] = "Expected return date cannot be before the date needed.";
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Re-verify availability
            $stmt = $db->prepare("SELECT available_quantity, name FROM equipment WHERE id = :id FOR UPDATE");
            $stmt->execute([':id' => $equipment_id]);
            $eq = $stmt->fetch();
            
            if (!$eq || $eq['available_quantity'] < $quantity) {
                $errors[] = "Sorry, only {$eq['available_quantity']} units of '{$eq['name']}' are currently available.";
                $db->rollBack();
            } else {
                // Insert request
                $stmt = $db->prepare("
                    INSERT INTO equipment_requests (equipment_id, user_id, quantity, purpose, date_needed, expected_return_date, status)
                    VALUES (:eid, :uid, :qty, :purpose, :dneeded, :ereturn, 'pending')
                ");
                $stmt->execute([
                    ':eid' => $equipment_id,
                    ':uid' => $_SESSION['user_id'],
                    ':qty' => $quantity,
                    ':purpose' => $purpose,
                    ':dneeded' => $date_needed,
                    ':ereturn' => $expected_return_date
                ]);
                
                $requestId = $db->lastInsertId();
                
                // We do NOT deduct quantity on pending. Quantity is deducted on 'approved' status.
                
                logAudit('CREATE_EQUIPMENT_REQUEST', 'equipment_requests', $requestId);
                $db->commit();
                
                // Notify admins
                $adminStmt = $db->query("SELECT id FROM users WHERE role_id IN (1, 2) AND status = 'active'");
                while ($admin = $adminStmt->fetch()) {
                    createNotification($admin['id'], 'New Equipment Request', "A request for {$quantity}x {$eq['name']} is pending approval.", 'info', '/modules/equipment/manage.php');
                }
                
                setFlash('success', 'Equipment request submitted successfully and is pending approval.');
                redirect('/modules/equipment/my_requests.php');
            }
        } catch (PDOException $e) {
            $db->rollBack();
            $errors[] = "A system error occurred. Please try again.";
            error_log($e->getMessage());
        }
    }
    
    if (!empty($errors)) {
        setFlash('error', implode('<br>', $errors));
    }
}

$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
    ['label' => 'Equipment', 'url' => '/modules/equipment/list.php'],
    ['label' => 'Request']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Request Equipment</h1>
        <p class="page-subtitle">Submit a request to borrow campus equipment.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/equipment/list.php" class="btn btn-secondary">Cancel</a>
</div>

<div class="card animate-fade-in-up" style="animation-delay: 100ms; max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group" style="flex: 2;">
                    <label class="form-label" for="equipment_id">Select Equipment *</label>
                    <select id="equipment_id" name="equipment_id" class="form-control" required>
                        <option value="">-- Choose Equipment --</option>
                        <?php foreach ($equipments as $eq): ?>
                            <option value="<?php echo $eq['id']; ?>" <?php echo ($selectedEqId == $eq['id']) ? 'selected' : ''; ?>>
                                <?php echo sanitize($eq['name']) . ' (Available: ' . $eq['available_quantity'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="quantity">Quantity *</label>
                    <input type="number" id="quantity" name="quantity" class="form-control" min="1" value="<?php echo (int)($_POST['quantity'] ?? 1); ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="purpose">Purpose *</label>
                <input type="text" id="purpose" name="purpose" class="form-control" required placeholder="e.g. For filming a project" value="<?php echo sanitize($_POST['purpose'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="date_needed">Date Needed *</label>
                    <input type="date" id="date_needed" name="date_needed" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo sanitize($_POST['date_needed'] ?? date('Y-m-d')); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="expected_return_date">Expected Return Date *</label>
                    <input type="date" id="expected_return_date" name="expected_return_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo sanitize($_POST['expected_return_date'] ?? date('Y-m-d')); ?>">
                </div>
            </div>
            
            <div class="form-group" style="padding-top: var(--spacing-md);">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
