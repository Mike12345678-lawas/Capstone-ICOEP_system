<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\EquipmentReturnController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FacilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PrivacyConsentController;
use Illuminate\Support\Facades\Route;

// ──────────────────────────────────────────────────
// Guest Routes (Auth)
// ──────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect('/login'));
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Privacy Policy & Consent
Route::get('/privacy/policy', function () {
    return view('privacy.policy', [
        'pageTitle' => 'Data Privacy Policy',
        'breadcrumb' => [
            ['label' => 'Dashboard', 'url' => '/dashboard'],
            ['label' => 'Privacy Policy'],
        ],
    ]);
})->name('privacy.policy')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/privacy/consent', [PrivacyConsentController::class, 'showConsentForm'])->name('privacy.consent');
    Route::post('/privacy/consent', [PrivacyConsentController::class, 'accept'])->name('privacy.consent.accept');
});

// User Guide & System Manual
Route::get('/user-guide', function () {
    return view('guide.index', [
        'pageTitle' => 'User Guide & System Manual',
        'breadcrumb' => [
            ['label' => 'Dashboard', 'url' => '/dashboard'],
            ['label' => 'User Guide'],
        ],
    ]);
})->middleware('auth');

// About Us / Institutional Profile
Route::get('/about-us', function () {
    return view('about.index', [
        'pageTitle' => 'About Us',
        'breadcrumb' => [
            ['label' => 'Dashboard', 'url' => '/dashboard'],
            ['label' => 'About Us'],
        ],
    ]);
})->middleware('auth');
Route::get('/about', fn() => redirect('/about-us'));

// Direct / fallback route for uploaded storage files (facilities, return photos, equipment)
Route::get('/storage/{path}', function ($path) {
    $basePath = realpath(storage_path('app/public'));
    $targetPath = realpath(storage_path('app/public/' . $path));
    if (!$targetPath || !str_starts_with($targetPath, $basePath) || !is_file($targetPath)) {
        abort(404);
    }
    return response()->file($targetPath);
})->where('path', '.*');

// ──────────────────────────────────────────────────
// Authenticated Routes — Unified Dashboard
// ──────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ─── UNIFIED DASHBOARD (Single page, all sections) ───
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // ─── Redirect legacy index routes to the unified dashboard ───
    Route::get('/facilities', fn() => redirect('/dashboard?section=facilities'));
    Route::get('/equipment', fn() => redirect('/dashboard?section=equipment'));
    Route::get('/borrowed-equipment', fn() => redirect('/dashboard?section=borrowed-equipment'));
    Route::get('/booked-facilities', fn() => redirect('/dashboard?section=booked-facilities'));
    Route::get('/my-equipment', fn() => redirect('/dashboard?section=borrowed-equipment'));
    Route::get('/my-bookings', fn() => redirect('/dashboard?section=booked-facilities'));
    Route::get('/history', fn() => redirect('/dashboard?section=history'));
    Route::get('/events', fn() => redirect('/dashboard?section=events'));
    Route::get('/announcements', fn() => redirect('/dashboard?section=announcements'));
    Route::get('/users', fn() => redirect('/dashboard?section=users'));
    Route::get('/backups', fn() => redirect('/dashboard?section=backups'));
    Route::get('/facilities/manage', fn() => redirect('/dashboard?section=manage-facilities'));
    Route::get('/equipment/manage', fn() => redirect('/dashboard?section=manage-equipment'));
    Route::get('/reports/facility-usage', fn() => redirect('/dashboard?section=reports&report=facility-usage'));
    Route::get('/reports/equipment-usage', fn() => redirect('/dashboard?section=reports&report=equipment-usage'));
    Route::get('/reports/event-engagement', fn() => redirect('/dashboard?section=reports&report=event-engagement'));
    Route::get('/reports/audit-log', fn() => redirect('/dashboard?section=reports&report=audit-log'));

    // ─── Detail/Show routes (still separate pages) ───
    Route::get('/facilities/create', [FacilityController::class, 'create'])->middleware('permission:facility.manage,facility.approve');
    Route::post('/facilities', [FacilityController::class, 'store'])->middleware('permission:facility.manage,facility.approve');
    Route::get('/facilities/{facility}/edit', [FacilityController::class, 'edit'])->middleware('permission:facility.manage,facility.approve');
    Route::put('/facilities/{facility}', [FacilityController::class, 'update'])->middleware('permission:facility.manage,facility.approve');
    Route::delete('/facilities/{facility}', [FacilityController::class, 'destroy'])->middleware('permission:facility.manage,facility.approve');
    Route::get('/facilities/{facility}', [FacilityController::class, 'show'])->middleware('permission:facility.view');
    Route::get('/equipment/create', [EquipmentController::class, 'create'])->middleware('permission:equipment.manage,inventory.manage_own');
    Route::get('/equipment/returns', [EquipmentReturnController::class, 'index'])->middleware('permission:equipment.return');
    Route::get('/equipment/{equipment}', [EquipmentController::class, 'show'])->middleware('permission:equipment.view');
    Route::get('/events/my', [EventController::class, 'myEvents'])->middleware('permission:event.view');
    Route::get('/events/create', [EventController::class, 'create'])->middleware('permission:event.create');
    Route::get('/events/{event}', [EventController::class, 'show'])->middleware('permission:event.view');
    Route::get('/events/{event}/manage', [EventController::class, 'manage'])->middleware('permission:event.manage');
    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->middleware('permission:announcement.create');
    Route::get('/announcements/{announcement}', [AnnouncementController::class, 'show'])->middleware('permission:announcement.view');

    // ─── User detail routes ───
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware('permission:user.create');
    Route::get('/users/{user}/edit', [UserController::class, 'edit']);

    // ─── Action routes (POST/PUT/PATCH/DELETE) ───
    Route::post('/facilities/{facility}/reserve', [FacilityController::class, 'reserve'])->middleware('permission:facility.reserve');
    Route::patch('/facility-reservations/{reservation}/status', [FacilityController::class, 'updateStatus'])->middleware('permission:facility.approve');
    Route::post('/facility-reservations/bulk-approve', [FacilityController::class, 'bulkApprove'])->middleware('permission:facility.approve');
    Route::post('/facility-reservations/auto-resolve', [FacilityController::class, 'autoResolveAll'])->middleware('permission:facility.approve');
    Route::patch('/facility-reservations/{reservation}/cancel', [FacilityController::class, 'cancelOwn']);
    Route::post('/facility-reservations/{reservation}/complete', [FacilityController::class, 'complete']);
    Route::patch('/facility-reservations/{reservation}/complete', [FacilityController::class, 'complete']);

    Route::post('/equipment/{equipment}/borrow', [EquipmentController::class, 'borrow'])->middleware('permission:equipment.borrow');
    Route::patch('/equipment-requests/{equipmentRequest}/status', [EquipmentController::class, 'updateStatus'])->middleware('permission:equipment.approve');
    Route::post('/equipment-requests/bulk-approve', [EquipmentController::class, 'bulkApprove'])->middleware('permission:equipment.approve');
    Route::patch('/equipment-requests/{equipmentRequest}/cancel', [EquipmentController::class, 'cancelOwn']);

    // ─── Equipment Pickup & Returns ───
    Route::post('/equipment-requests/{equipmentRequest}/pickup', [EquipmentController::class, 'pickup'])->middleware('permission:equipment.issue');
    Route::patch('/equipment-requests/{equipmentRequest}/pickup', [EquipmentController::class, 'pickup'])->middleware('permission:equipment.issue');
    Route::post('/equipment-requests/{equipmentRequest}/return-request', [EquipmentReturnController::class, 'requestReturn']);
    Route::post('/equipment-requests/{equipmentRequest}/cancel-return-request', [EquipmentReturnController::class, 'cancelReturnRequest']);
    Route::delete('/equipment-requests/{equipmentRequest}/return-request', [EquipmentReturnController::class, 'cancelReturnRequest']);
    Route::post('/equipment-requests/{equipmentRequest}/confirm-return', [EquipmentReturnController::class, 'confirmReturn'])->middleware('permission:equipment.return');
    Route::post('/equipment-requests/{equipmentRequest}/send-reminder', [EquipmentReturnController::class, 'sendReminder'])->middleware('permission:equipment.return');

    // ─── Equipment Inventory CRUD (Admin / Custodians) ───
    Route::post('/equipment', [EquipmentController::class, 'store'])->middleware('permission:equipment.manage,inventory.manage_own');
    Route::get('/equipment/{equipment}/edit', [EquipmentController::class, 'edit'])->middleware('permission:equipment.manage,inventory.manage_own');
    Route::put('/equipment/{equipment}', [EquipmentController::class, 'update'])->middleware('permission:equipment.manage,inventory.manage_own');
    Route::delete('/equipment/{equipment}', [EquipmentController::class, 'destroy'])->middleware('permission:equipment.manage,inventory.manage_own');

    Route::post('/events', [EventController::class, 'store'])->middleware('permission:event.create');
    Route::post('/events/{event}/register', [EventController::class, 'register'])->middleware('permission:event.view');
    Route::delete('/events/{event}/register', [EventController::class, 'cancelRegistration'])->middleware('permission:event.view');
    Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->middleware('permission:event.manage');
    Route::patch('/events/{event}/approve', [EventController::class, 'approve'])->middleware('permission:event.manage');
    Route::patch('/events/{event}/reject', [EventController::class, 'reject'])->middleware('permission:event.manage');
    Route::post('/events/{event}/attendance', [EventController::class, 'markAttendance'])->middleware('permission:event.manage');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->middleware('permission:event.manage');

    // ─── Event Scanner & QR Attendance ───
    Route::get('/events/{event}/scanner', [EventController::class, 'scanner'])->middleware('permission:event.manage');
    Route::post('/events/{event}/scan-qr', [EventController::class, 'scanQr'])->middleware('permission:event.manage');
    Route::post('/events/{event}/manual-checkin', [EventController::class, 'manualCheckin'])->middleware('permission:event.manage');

    Route::post('/announcements', [AnnouncementController::class, 'store'])->middleware('permission:announcement.create');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->middleware('permission:announcement.delete');

    Route::post('/users', [UserController::class, 'store'])->middleware('permission:user.create');
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:user.delete');

    // ─── Notifications API ───
    Route::get('/api/notifications', [NotificationController::class, 'getUnread']);
    Route::post('/api/notifications/mark-read', [NotificationController::class, 'markAllRead']);
    Route::delete('/api/notifications', [NotificationController::class, 'destroyAll']);
    Route::delete('/api/notifications/{notification}', [NotificationController::class, 'destroy']);

    // ─── Calendar API ───
    Route::get('/api/calendar/events', [CalendarController::class, 'getEvents']);

    // ─── Data Backup (Admin) ───
    Route::post('/backups', [BackupController::class, 'create']);
    Route::post('/backups/auto-toggle', [BackupController::class, 'toggleAuto'])->name('backups.toggle-auto');
    Route::get('/backups/{filename}/download', [BackupController::class, 'download']);
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy']);
});

// ─── Public Storage Fallback (serves uploaded files if symlink is unavailable) ───
Route::get('/storage/{path}', function (string $path) {
    $fullPath = storage_path('app/public/' . $path);
    if (!file_exists($fullPath) || !is_file($fullPath)) {
        abort(404);
    }
    return response()->file($fullPath, [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('path', '.*');

