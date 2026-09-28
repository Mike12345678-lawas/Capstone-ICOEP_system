<?php
/**
 * Logout Handler
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../config/session.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Log the logout action before destroying session
if (isset($_SESSION['user_id'])) {
    logAudit('LOGOUT', 'users', $_SESSION['user_id']);
}

destroySession();

// Start a new session for flash message
session_start();
setFlash('info', 'You have been signed out successfully.');
redirect('/modules/auth/login.php');
