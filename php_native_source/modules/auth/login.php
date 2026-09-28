<?php
/**
 * Login Page
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

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
        } else {
            try {
                $db = getDB();
                
                // Find user by email
                $stmt = $db->prepare("
                    SELECT u.*, r.name as role_name 
                    FROM users u 
                    JOIN roles r ON u.role_id = r.id 
                    WHERE u.email = :email AND u.deleted_at IS NULL
                ");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $error = 'Invalid email or password.';
                    logAudit('LOGIN_FAILED', 'users', null, null, ['email' => $email, 'reason' => 'User not found']);
                } elseif ($user['status'] === 'locked') {
                    // Check if lockout has expired
                    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                        $minutes = ceil((strtotime($user['locked_until']) - time()) / 60);
                        $error = "Account is locked. Please try again in {$minutes} minute(s).";
                    } else {
                        // Unlock the account
                        $stmt = $db->prepare("UPDATE users SET status = 'active', login_attempts = 0, locked_until = NULL WHERE id = :id");
                        $stmt->execute([':id' => $user['id']]);
                        $user['status'] = 'active';
                        $user['login_attempts'] = 0;
                    }
                }
                
                if (empty($error) && $user && $user['status'] === 'inactive') {
                    $error = 'Your account has been deactivated. Please contact administration.';
                }
                
                if (empty($error) && $user && $user['status'] === 'active') {
                    if (password_verify($password, $user['password'])) {
                        // Successful login
                        regenerateSession();
                        
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role_id'] = $user['role_id'];
                        $_SESSION['role_name'] = $user['role_name'];
                        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                        
                        // Reset login attempts and update last login
                        $stmt = $db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL, last_login = NOW() WHERE id = :id");
                        $stmt->execute([':id' => $user['id']]);
                        
                        logAudit('LOGIN_SUCCESS', 'users', $user['id']);
                        
                        setFlash('success', 'Welcome back, ' . $user['first_name'] . '!');
                        redirect('/modules/dashboard/index.php');
                    } else {
                        // Failed login — increment attempts
                        $attempts = $user['login_attempts'] + 1;
                        
                        if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                            $lockedUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
                            $stmt = $db->prepare("UPDATE users SET login_attempts = :attempts, status = 'locked', locked_until = :locked_until WHERE id = :id");
                            $stmt->execute([':attempts' => $attempts, ':locked_until' => $lockedUntil, ':id' => $user['id']]);
                            $error = 'Too many failed attempts. Account locked for 15 minutes.';
                        } else {
                            $stmt = $db->prepare("UPDATE users SET login_attempts = :attempts WHERE id = :id");
                            $stmt->execute([':attempts' => $attempts, ':id' => $user['id']]);
                            $remaining = MAX_LOGIN_ATTEMPTS - $attempts;
                            $error = "Invalid email or password. {$remaining} attempt(s) remaining.";
                        }
                        
                        logAudit('LOGIN_FAILED', 'users', $user['id'], null, ['reason' => 'Invalid password', 'attempts' => $attempts]);
                    }
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'A system error occurred. Please try again later.';
            }
        }
    }
}

$extraCSS = 'auth.css';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to <?php echo APP_FULL_NAME; ?> — <?php echo INSTITUTION_NAME; ?>">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Sign In — <?php echo APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/auth.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <h1><?php echo APP_NAME; ?></h1>
                <p><?php echo APP_FULL_NAME; ?></p>
                <p class="institution"><?php echo INSTITUTION_NAME; ?></p>
            </div>
            
            <?php if ($error): ?>
                <div class="flash-message flash-error">
                    <span class="flash-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="15" y1="9" x2="9" y2="15"></line>
                            <line x1="9" y1="9" x2="15" y2="15"></line>
                        </svg>
                    </span>
                    <span class="flash-text"><?php echo sanitize($error); ?></span>
                </div>
            <?php endif; ?>
            
            <?php 
            $flash = getFlash();
            if ($flash): ?>
                <div class="flash-message flash-<?php echo sanitize($flash['type']); ?>">
                    <span class="flash-text"><?php echo sanitize($flash['message']); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="you@slmcb.edu.ph" 
                           value="<?php echo sanitize($email ?? ''); ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">
                        Password
                        <a href="<?php echo BASE_URL; ?>/modules/auth/forgot_password.php">Forgot password?</a>
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Enter your password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Sign In
                </button>
            </form>
            
            <div class="auth-footer">
                Don't have an account? <a href="<?php echo BASE_URL; ?>/modules/auth/register.php">Register here</a>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
            } else {
                input.type = 'password';
                btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            }
        }
    </script>
</body>
</html>
