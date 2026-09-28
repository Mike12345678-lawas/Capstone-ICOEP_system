<?php
/**
 * Create User
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();
requirePermission('user.create');

$pageTitle = 'Create User';
$db = getDB();

try {
    $stmt = $db->query("SELECT id, name FROM roles ORDER BY id ASC");
    $roles = $stmt->fetchAll();
} catch (PDOException $e) {
    $roles = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $employee_id = trim($_POST['employee_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 4);
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    if (empty($first_name)) $errors[] = "First name is required.";
    if (empty($last_name)) $errors[] = "Last name is required.";
    if (empty($email) || !validateEmail($email)) $errors[] = "Valid email is required.";
    if (empty($password) || strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    
    if (empty($errors)) {
        try {
            // Check email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) $errors[] = "Email is already in use.";
            
            if (empty($errors)) {
                $stmt = $db->prepare("
                    INSERT INTO users (employee_id, first_name, last_name, email, password, role_id, department, status, privacy_consent, privacy_consent_date)
                    VALUES (:eid, :fname, :lname, :email, :pass, :role, :dept, :status, 1, NOW())
                ");
                
                $stmt->execute([
                    ':eid' => $employee_id ?: null,
                    ':fname' => $first_name,
                    ':lname' => $last_name,
                    ':email' => $email,
                    ':pass' => password_hash($password, PASSWORD_DEFAULT),
                    ':role' => $role_id,
                    ':dept' => $department,
                    ':status' => $status
                ]);
                
                $newId = $db->lastInsertId();
                logAudit('CREATE_USER', 'users', $newId);
                
                setFlash('success', 'User created successfully.');
                redirect('/modules/users/list.php');
            }
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
    ['label' => 'User Management', 'url' => '/modules/users/list.php'],
    ['label' => 'Create User']
];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title">Create User</h1>
        <p class="page-subtitle">Add a new user to the system manually.</p>
    </div>
    <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-secondary">Cancel</a>
</div>

<div class="card animate-fade-in-up" style="max-width: 800px;">
    <div class="card-body">
        <form method="POST" action="">
            <?php echo csrfField(); ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" class="form-control" required value="<?php echo sanitize($_POST['first_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" class="form-control" required value="<?php echo sanitize($_POST['last_name'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="email">Email Address *</label>
                <input type="email" id="email" name="email" class="form-control" required value="<?php echo sanitize($_POST['email'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="employee_id">School / Employee ID</label>
                    <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?php echo sanitize($_POST['employee_id'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="department">Department</label>
                    <input type="text" id="department" name="department" class="form-control" value="<?php echo sanitize($_POST['department'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="role_id">Role *</label>
                    <select id="role_id" name="role_id" class="form-control" required>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?php echo $r['id']; ?>" <?php echo (($_POST['role_id'] ?? 4) == $r['id']) ? 'selected' : ''; ?>><?php echo sanitize($r['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Account Status *</label>
                    <select id="status" name="status" class="form-control" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="password">Initial Password *</label>
                <input type="text" id="password" name="password" class="form-control" required minlength="8" placeholder="Must be at least 8 characters">
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg mt-4">Create User</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
