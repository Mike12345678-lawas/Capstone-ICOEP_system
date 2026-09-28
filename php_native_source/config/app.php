<?php
/**
 * Application Configuration
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

// Application Info
define('APP_NAME', 'ICOEP');
define('APP_FULL_NAME', 'Integrated Campus Operations and Engagement Portal');
define('APP_VERSION', '1.0.0');
define('INSTITUTION_NAME', 'St. Louise de Marillac College of Bogo, Cebu Inc.');
define('INSTITUTION_SHORT', 'SLMCB');

// Base URL (adjust if deployed to a different path)
define('BASE_URL', '/ICOEP_system');

// Timezone
date_default_timezone_set('Asia/Manila');

// Upload paths
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Login security
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes in seconds

// Pagination
define('ITEMS_PER_PAGE', 12);
