<?php
/**
 * Registration Page
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    redirect('/modules/dashboard/index.php');
}

$errors = [];
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $old = [
            'employee_id' => trim($_POST['employee_id'] ?? ''),
            'first_name'  => trim($_POST['first_name'] ?? ''),
            'last_name'   => trim($_POST['last_name'] ?? ''),
            'email'       => trim($_POST['email'] ?? ''),
            'department'  => trim($_POST['department'] ?? ''),
            'phone'       => trim($_POST['phone'] ?? ''),
            'role_id'     => (int)($_POST['role_id'] ?? 4),
        ];
        
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $privacyConsent = isset($_POST['privacy_consent']);
        
        // Validation
        if (empty($old['first_name'])) $errors[] = 'First name is required.';
        if (empty($old['last_name'])) $errors[] = 'Last name is required.';
        if (empty($old['email'])) {
            $errors[] = 'Email is required.';
        } elseif (!validateEmail($old['email'])) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain uppercase, lowercase, and a number.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        }
        if (!$privacyConsent) {
            $errors[] = 'You must agree to the Data Privacy Policy (RA 10173) to register.';
        }
        
        // Only allow Student role for self-registration
        if (!in_array($old['role_id'], [4])) {
            $old['role_id'] = 4; // Default to Student
        }
        
        // Check if email already exists
        if (empty($errors)) {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL");
                $stmt->execute([':email' => $old['email']]);
                
                if ($stmt->fetch()) {
                    $errors[] = 'An account with this email already exists.';
                }
            } catch (PDOException $e) {
                $errors[] = 'A system error occurred. Please try again.';
                error_log("Registration check error: " . $e->getMessage());
            }
        }
        
        // Create user
        if (empty($errors)) {
            try {
                $db = getDB();
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                $stmt = $db->prepare("
                    INSERT INTO users (employee_id, first_name, last_name, email, password, role_id, department, phone, status, privacy_consent, privacy_consent_date) 
                    VALUES (:employee_id, :first_name, :last_name, :email, :password, :role_id, :department, :phone, 'active', 1, NOW())
                ");
                
                $stmt->execute([
                    ':employee_id' => $old['employee_id'] ?: null,
                    ':first_name'  => $old['first_name'],
                    ':last_name'   => $old['last_name'],
                    ':email'       => $old['email'],
                    ':password'    => $hashedPassword,
                    ':role_id'     => $old['role_id'],
                    ':department'  => $old['department'] ?: null,
                    ':phone'       => $old['phone'] ?: null,
                ]);
                
                $userId = $db->lastInsertId();
                
                // Record privacy consent
                $stmt = $db->prepare("
                    INSERT INTO privacy_consents (user_id, consent_type, consented, consent_text, ip_address, consented_at) 
                    VALUES (:user_id, 'data_processing', 1, 'Agreed to ICOEP Data Privacy Policy under RA 10173', :ip, NOW())
                ");
                $stmt->execute([':user_id' => $userId, ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
                
                logAudit('REGISTER', 'users', $userId);
                
                // Create welcome notification
                createNotification($userId, 'Welcome to ICOEP!', 'Your account has been created successfully. Explore campus facilities, events, and announcements.', 'success', '/modules/dashboard/index.php');
                
                setFlash('success', 'Account created successfully! Please sign in.');
                redirect('/modules/auth/login.php');
                
            } catch (PDOException $e) {
                $errors[] = 'Registration failed. Please try again.';
                error_log("Registration error: " . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Create your account on <?php echo APP_FULL_NAME; ?>">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Register — <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/auth.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card" style="max-width: 520px;">
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                </div>
                <h1>Create Account</h1>
                <p>Join the <?php echo INSTITUTION_SHORT; ?> campus portal</p>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="flash-message flash-error">
                    <span class="flash-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </span>
                    <span class="flash-text">
                        <?php echo sanitize($errors[0]); ?>
                        <?php if (count($errors) > 1): ?>
                            <br><small>(+<?php echo count($errors) - 1; ?> more issue<?php echo count($errors) - 1 > 1 ? 's' : ''; ?>)</small>
                        <?php endif; ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <?php echo csrfField(); ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="first_name">First Name *</label>
                        <input type="text" id="first_name" name="first_name" class="form-control" 
                               value="<?php echo sanitize($old['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="last_name">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" class="form-control" 
                               value="<?php echo sanitize($old['last_name'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address *</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="you@slmcb.edu.ph"
                           value="<?php echo sanitize($old['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="employee_id">School / Employee ID</label>
                        <input type="text" id="employee_id" name="employee_id" class="form-control" 
                               placeholder="e.g., STU-2025-001"
                               value="<?php echo sanitize($old['employee_id'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="department">Department</label>
                        <input type="text" id="department" name="department" class="form-control" 
                               placeholder="e.g., BSIT"
                               value="<?php echo sanitize($old['department'] ?? ''); ?>">
                    </div>
                </div>
                
                <input type="hidden" name="role_id" value="4">
                
                <div class="form-group">
                    <label class="form-label" for="password">Password *</label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Min. 8 characters" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="password-strength-bar" id="str1"></div>
                        <div class="password-strength-bar" id="str2"></div>
                        <div class="password-strength-bar" id="str3"></div>
                        <div class="password-strength-bar" id="str4"></div>
                    </div>
                    <span class="password-strength-text" id="strengthText"></span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm Password *</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Repeat your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                
                <div class="privacy-consent">
                    <label class="form-check">
                        <input type="checkbox" name="privacy_consent" id="privacy_consent" required>
                        <span class="form-check-label">
                            I have read and agree to the <a href="<?php echo BASE_URL; ?>/privacy/policy.php" target="_blank">Data Privacy Policy</a> 
                            in compliance with the <strong>Philippine Data Privacy Act of 2012 (RA 10173)</strong>. 
                            I consent to the collection and processing of my personal information for the purposes of this portal.
                        </span>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Create Account
                </button>
            </form>
            
            <div class="auth-footer">
                Already have an account? <a href="<?php echo BASE_URL; ?>/modules/auth/login.php">Sign in</a>
            </div>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') { input.type = 'text'; } else { input.type = 'password'; }
        }
        
        // Password strength meter
        document.getElementById('password')?.addEventListener('input', function() {
            const result = checkPasswordStrength(this.value);
            const bars = ['str1', 'str2', 'str3', 'str4'];
            const levels = ['weak', 'fair', 'good', 'strong'];
            
            bars.forEach((bar, i) => {
                const el = document.getElementById(bar);
                el.className = 'password-strength-bar';
                if (i < result.score && result.score > 0) {
                    el.classList.add(result.level);
                }
            });
            
            const text = document.getElementById('strengthText');
            if (this.value.length > 0) {
                text.textContent = 'Strength: ' + result.label;
                text.style.color = result.level === 'strong' ? 'var(--color-success)' : 
                                   result.level === 'good' ? 'var(--color-gold)' :
                                   result.level === 'fair' ? 'var(--color-warning)' : 'var(--color-error)';
            } else {
                text.textContent = '';
            }
        });
    </script>
</body>
</html>
