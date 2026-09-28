<?php
/**
 * Edit User
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../includes/auth_check.php';
requireLogin();

// User can edit their own profile, or Admin can edit any
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isSelf = ($id === $_SESSION['user_id']);

if (!$isSelf) {
    requirePermission('user.edit');
}

$db = getDB();

try {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlash('error', 'User not found.');
        redirect('/modules/dashboard/index.php');
    }
    
    $stmt = $db->query("SELECT id, name FROM roles ORDER BY id ASC");
    $roles = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlash('error', 'Database error.');
    redirect('/modules/dashboard/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCSRF();
    $action = $_POST['action'] ?? 'update';
    
    if ($action === 'update') {
        $first_name = trim($_POST['first_name'] ?? '');
        $last_name = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        // Admins can change role and status
        $role_id = hasPermission('user.edit') && !$isSelf ? (int)($_POST['role_id'] ?? $user['role_id']) : $user['role_id'];
        $status = hasPermission('user.edit') && !$isSelf ? ($_POST['status'] ?? $user['status']) : $user['status'];
        
        // Admin resetting password or User changing own password
        $password = $_POST['password'] ?? '';
        
        $errors = [];
        if (empty($first_name)) $errors[] = "First name is required.";
        if (empty($last_name)) $errors[] = "Last name is required.";
        if (empty($email) || !validateEmail($email)) $errors[] = "Valid email is required.";
        
        if (empty($errors)) {
            try {
                // Check email uniqueness
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id AND deleted_at IS NULL");
                $stmt->execute([':email' => $email, ':id' => $id]);
                if ($stmt->fetch()) $errors[] = "Email is already in use by another account.";
                
                if (empty($errors)) {
                    $sql = "UPDATE users SET first_name = :fname, last_name = :lname, email = :email, 
                            employee_id = :eid, department = :dept, phone = :phone, 
                            role_id = :role, status = :status";
                    $params = [
                        ':fname' => $first_name, ':lname' => $last_name, ':email' => $email,
                        ':eid' => $employee_id, ':dept' => $department, ':phone' => $phone,
                        ':role' => $role_id, ':status' => $status, ':id' => $id
                    ];
                    
                    if (!empty($password)) {
                        $sql .= ", password = :pass";
                        $params[':pass'] = password_hash($password, PASSWORD_DEFAULT);
                    }
                    
                    // Unlock account if status changed to active
                    if ($status === 'active' && $user['status'] === 'locked') {
                        $sql .= ", login_attempts = 0, locked_until = NULL";
                    }
                    
                    $sql .= " WHERE id = :id";
                    
                    $stmt = $db->prepare($sql);
                    $stmt->execute($params);
                    
                    logAudit('UPDATE_USER', 'users', $id);
                    setFlash('success', 'Profile updated successfully.');
                    
                    // Refresh session if editing self
                    if ($isSelf) {
                        $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    }
                    
                    redirect('/modules/users/edit.php?id=' . $id);
                }
            } catch (PDOException $e) {
                $errors[] = "Database error occurred.";
            }
        }
        if (!empty($errors)) setFlash('error', implode('<br>', $errors));
        
    } elseif ($action === 'delete' && hasPermission('user.edit') && !$isSelf) {
        // Soft delete
        try {
            $stmt = $db->prepare("UPDATE users SET deleted_at = NOW(), status = 'inactive' WHERE id = :id");
            $stmt->execute([':id' => $id]);
            logAudit('DELETE_USER', 'users', $id);
            setFlash('success', 'User deleted.');
            redirect('/modules/users/list.php');
        } catch (PDOException $e) {
            setFlash('error', 'Database error.');
        }
    }
}

$pageTitle = $isSelf ? 'My Profile' : 'Edit User';
$breadcrumb = [
    ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php']
];
if (!$isSelf && hasPermission('user.view')) {
    $breadcrumb[] = ['label' => 'User Management', 'url' => '/modules/users/list.php'];
}
$breadcrumb[] = ['label' => $pageTitle];

include __DIR__ . '/../../includes/header.php';
?>

<div class="page-header animate-fade-in-up">
    <div>
        <h1 class="page-title"><?php echo $pageTitle; ?></h1>
        <p class="page-subtitle"><?php echo sanitize($user['first_name'] . ' ' . $user['last_name']); ?></p>
    </div>
    <div class="btn-group">
        <?php if (!$isSelf && hasPermission('user.edit')): ?>
            <form method="POST" action="" onsubmit="return confirm('Are you sure you want to deactivate/delete this user?');" style="margin: 0;">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="btn btn-danger">Delete User</button>
            </form>
        <?php endif; ?>
        <?php if (!$isSelf): ?>
            <a href="<?php echo BASE_URL; ?>/modules/users/list.php" class="btn btn-secondary">&larr; Back to List</a>
        <?php endif; ?>
    </div>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap: var(--spacing-lg);">
    
    <div class="card animate-fade-in-up" style="animation-delay: 100ms;">
        <div class="card-header"><h3 class="card-title">Profile Information</h3></div>
        <div class="card-body">
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" required value="<?php echo sanitize($user['first_name']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" required value="<?php echo sanitize($user['last_name']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo sanitize($user['email']); ?>">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="employee_id">School / Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" value="<?php echo sanitize($user['employee_id']); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="text" id="phone" name="phone" class="form-control" value="<?php echo sanitize($user['phone']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="department">Department / Course</label>
                    <input type="text" id="department" name="department" class="form-control" value="<?php echo sanitize($user['department']); ?>">
                </div>
                
                <hr style="margin: var(--spacing-xl) 0; border: 0; border-top: 1px solid var(--color-border);">
                
                <?php if (hasPermission('user.edit') && !$isSelf): ?>
                    <h3 class="card-title" style="margin-bottom: var(--spacing-md);">Administrative Controls</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="role_id">Role *</label>
                            <select id="role_id" name="role_id" class="form-control" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>" <?php echo $user['role_id'] == $r['id'] ? 'selected' : ''; ?>><?php echo sanitize($r['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Account Status *</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="locked" <?php echo $user['status'] === 'locked' ? 'selected' : ''; ?>>Locked</option>
                            </select>
                            <?php if ($user['status'] === 'locked'): ?>
                                <div class="form-hint" style="color: var(--color-error);">User is locked out until <?php echo formatDateTime($user['locked_until']); ?>. Set to Active to unlock.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label class="form-label" for="password"><?php echo $isSelf ? 'Change Password' : 'Reset Password'; ?></label>
                    <input type="text" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password" minlength="8">
                    <?php if ($isSelf): ?>
                        <div class="form-hint">Enter a new password here if you wish to change it.</div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary mt-4">Save Changes</button>
            </form>
        </div>
    </div>
    
    <!-- Info sidebar -->
    <div class="animate-fade-in-up" style="animation-delay: 200ms;">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Account Details</h3></div>
            <div class="card-body">
                <div style="margin-bottom: var(--spacing-md);">
                    <strong>Current Role</strong><br>
                    <?php 
                        $roleName = '';
                        foreach($roles as $r) { if($r['id'] == $user['role_id']) $roleName = $r['name']; }
                        echo sanitize($roleName); 
                    ?>
                </div>
                <div style="margin-bottom: var(--spacing-md);">
                    <strong>Status</strong><br>
                    <?php echo statusBadge($user['status']); ?>
                </div>
                <div style="margin-bottom: var(--spacing-md);">
                    <strong>Last Login</strong><br>
                    <?php echo $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'; ?>
                </div>
                <div>
                    <strong>Data Privacy Consent</strong><br>
                    <?php echo $user['privacy_consent'] ? 'Consented on ' . formatDate($user['privacy_consent_date']) : 'Not yet consented'; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
