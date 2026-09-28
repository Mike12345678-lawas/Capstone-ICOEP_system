<?php
/**
 * Forgot Password Page
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    redirect('/modules/dashboard/index.php');
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken()) {
        $message = 'Invalid security token.';
        $messageType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');
        
        if (empty($email) || !validateEmail($email)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            try {
                $db = getDB();
                $stmt = $db->prepare("SELECT id FROM users WHERE email = :email AND deleted_at IS NULL");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Generate reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour
                    
                    $stmt = $db->prepare("UPDATE users SET password_reset_token = :token, password_reset_expires = :expires WHERE id = :id");
                    $stmt->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);
                    
                    logAudit('PASSWORD_RESET_REQUEST', 'users', $user['id']);
                    
                    // In a real environment, send email here.
                    // For now, display the token for demo purposes.
                    createNotification($user['id'], 'Password Reset Requested', 'A password reset was requested for your account. If this was not you, please contact administration.', 'warning');
                }
                
                // Always show success to prevent email enumeration
                $message = 'If an account with that email exists, password reset instructions have been sent.';
                $messageType = 'success';
                
            } catch (PDOException $e) {
                $message = 'A system error occurred. Please try again.';
                $messageType = 'error';
                error_log("Forgot password error: " . $e->getMessage());
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
    <meta name="description" content="Reset your password — <?php echo APP_NAME; ?>">
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
    <title>Forgot Password — <?php echo APP_NAME; ?></title>
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
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>
                <h1>Reset Password</h1>
                <p>Enter your email to receive reset instructions</p>
            </div>
            
            <?php if ($message): ?>
                <div class="flash-message flash-<?php echo $messageType; ?>">
                    <span class="flash-text"><?php echo sanitize($message); ?></span>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="you@slmcb.edu.ph" required autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg">
                    Send Reset Link
                </button>
            </form>
            
            <div class="auth-footer">
                <a href="<?php echo BASE_URL; ?>/modules/auth/login.php">← Back to Sign In</a>
            </div>
        </div>
    </div>
</body>
</html>
