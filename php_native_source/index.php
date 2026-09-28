<?php
/**
 * Entry Point
 * ICOEP - Integrated Campus Operations and Engagement Portal
 * 
 * Redirects to dashboard if logged in, or login page if not.
 */

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/session.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/modules/dashboard/index.php');
} else {
    header('Location: ' . BASE_URL . '/modules/auth/login.php');
}
exit;
