<?php
/**
 * Data Privacy Policy (RA 10173 Compliance)
 * ICOEP - Integrated Campus Operations and Engagement Portal
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Data Privacy Policy';

// If logged in, include standard layout, otherwise use a minimal layout
$isLoggedIn = isLoggedIn();

if ($isLoggedIn) {
    $breadcrumb = [
        ['label' => 'Dashboard', 'url' => '/modules/dashboard/index.php'],
        ['label' => 'Privacy Policy']
    ];
    include __DIR__ . '/../includes/header.php';
} else {
    // Minimal layout for unauthenticated users (e.g. clicking from login/register page)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Data Privacy Policy for <?php echo APP_FULL_NAME; ?>">
    <title>Privacy Policy &mdash; <?php echo APP_NAME; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body style="background: var(--color-bg-primary); padding: var(--spacing-xl);">
    <div style="max-width: 800px; margin: 0 auto;">
        <div style="margin-bottom: var(--spacing-lg);">
            <a href="<?php echo BASE_URL; ?>/modules/auth/login.php" class="btn btn-secondary">&larr; Back to Login</a>
        </div>
<?php } ?>

<div class="card animate-fade-in-up">
    <div class="card-header" style="border-bottom: 1px solid var(--color-border); margin-bottom: var(--spacing-xl); padding-bottom: var(--spacing-md);">
        <div>
            <h1 class="page-title">Data Privacy Policy</h1>
            <p class="page-subtitle text-muted">In compliance with the Philippine Data Privacy Act of 2012 (Republic Act No. 10173)</p>
        </div>
    </div>
    
    <div class="card-body" style="font-size: var(--text-sm); line-height: 1.8; color: var(--color-text-secondary);">
        
        <p style="margin-bottom: var(--spacing-md);">
            <strong><?php echo INSTITUTION_NAME; ?></strong> ("we", "us", "our") respects your privacy and recognizes the need for appropriate measures to protect and manage your personal data entrusted to us. This Privacy Policy outlines how we collect, use, store, and protect your personal data in relation to your use of the <strong><?php echo APP_FULL_NAME; ?> (ICOEP)</strong>.
        </p>
        
        <h3 style="color: var(--color-text-primary); margin-top: var(--spacing-xl); margin-bottom: var(--spacing-sm);">1. Information We Collect</h3>
        <p style="margin-bottom: var(--spacing-md);">
            When you register and use the ICOEP system, we collect the following personal information:
        </p>
        <ul style="margin-left: var(--spacing-xl); margin-bottom: var(--spacing-md);">
            <li>Full Name</li>
            <li>Institutional Email Address</li>
            <li>School / Employee ID Number</li>
            <li>Department / Affiliation</li>
            <li>Contact Number (Optional)</li>
            <li>System Activity Logs (e.g., login times, reservations made, events attended)</li>
            <li>IP Address and browser information for security audit purposes.</li>
        </ul>

        <h3 style="color: var(--color-text-primary); margin-top: var(--spacing-xl); margin-bottom: var(--spacing-sm);">2. Purpose of Data Collection</h3>
        <p style="margin-bottom: var(--spacing-md);">
            Your data is strictly collected and processed for the following legitimate institutional purposes:
        </p>
        <ul style="margin-left: var(--spacing-xl); margin-bottom: var(--spacing-md);">
            <li>Authenticating your identity to access the portal.</li>
            <li>Managing facility reservations and equipment borrowing logistics.</li>
            <li>Tracking registration and attendance for school-wide events.</li>
            <li>Sending critical campus announcements and notifications.</li>
            <li>Generating aggregated statistical reports on campus engagement (anonymized where possible).</li>
            <li>Ensuring system security and preventing unauthorized access.</li>
        </ul>

        <h3 style="color: var(--color-text-primary); margin-top: var(--spacing-xl); margin-bottom: var(--spacing-sm);">3. Data Protection and Security</h3>
        <p style="margin-bottom: var(--spacing-md);">
            We implement strict organizational, physical, and technical security measures to protect your personal data against accidental loss, unauthorized access, alteration, and disclosure. These include:
        </p>
        <ul style="margin-left: var(--spacing-xl); margin-bottom: var(--spacing-md);">
            <li><strong>Encryption:</strong> User passwords are securely hashed using modern cryptographic algorithms.</li>
            <li><strong>Access Control:</strong> Data access is strictly limited based on Role-Based Access Control (RBAC). Only authorized administrators and staff can view specific records.</li>
            <li><strong>Audit Trails:</strong> The system maintains an immutable audit log of all data-modifying actions to ensure accountability.</li>
        </ul>

        <h3 style="color: var(--color-text-primary); margin-top: var(--spacing-xl); margin-bottom: var(--spacing-sm);">4. Data Retention</h3>
        <p style="margin-bottom: var(--spacing-md);">
            Your personal data will be retained only for as long as necessary to fulfill the purposes outlined above, or as required by institutional policies and relevant laws. Once your affiliation with the institution ends, your account will be deactivated, and your data will be securely archived or deleted in accordance with our retention policy.
        </p>

        <h3 style="color: var(--color-text-primary); margin-top: var(--spacing-xl); margin-bottom: var(--spacing-sm);">5. Your Rights as a Data Subject</h3>
        <p style="margin-bottom: var(--spacing-md);">
            Under the Data Privacy Act of 2012 (RA 10173), you have the right to:
        </p>
        <ul style="margin-left: var(--spacing-xl); margin-bottom: var(--spacing-md);">
            <li><strong>Be informed</strong> about how your data is processed.</li>
            <li><strong>Access</strong> your personal data stored in the system.</li>
            <li><strong>Rectify or correct</strong> any inaccurate data via your profile page.</li>
            <li><strong>Object</strong> to the processing of your data, or request its <strong>erasure/blocking</strong>, subject to institutional requirements for active students and staff.</li>
            <li><strong>Data Portability</strong>.</li>
        </ul>

        <h3 style="color: var(--color-text-primary); margin-top: var(--spacing-xl); margin-bottom: var(--spacing-sm);">6. Contact Us</h3>
        <p style="margin-bottom: var(--spacing-md);">
            If you have any questions, concerns, or requests regarding this Privacy Policy or your personal data, please contact the Data Protection Officer (DPO) of <?php echo INSTITUTION_SHORT; ?> at the Administration Office.
        </p>
        
        <div style="margin-top: var(--spacing-2xl); padding: var(--spacing-md); background: rgba(59, 130, 246, 0.05); border-radius: var(--radius-md); text-align: center; font-size: var(--text-xs);">
            Last updated: <?php echo date('F j, Y'); ?>
        </div>
    </div>
</div>

<?php 
if ($isLoggedIn) {
    include __DIR__ . '/../includes/footer.php';
} else {
?>
    </div>
</body>
</html>
<?php } ?>
