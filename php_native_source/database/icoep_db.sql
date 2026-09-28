-- ============================================================
-- ICOEP Database Schema
-- Integrated Campus Operations and Engagement Portal
-- St. Louise de Marillac College of Bogo, Cebu Inc.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+08:00";

DROP DATABASE IF EXISTS `icoep_db`;
CREATE DATABASE `icoep_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `icoep_db`;

-- ============================================================
-- 1. ROLES & PERMISSIONS (RBAC)
-- ============================================================

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`, `name`, `description`) VALUES
(1, 'Administrator', 'Full system access — manages all modules, users, and settings'),
(2, 'Staff', 'Campus staff — manages facility/equipment bookings and announcements'),
(3, 'Faculty', 'Teaching staff — can book facilities/equipment, create events and announcements'),
(4, 'Student', 'Students — can view announcements, register for events, request bookings');

CREATE TABLE `permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL UNIQUE,
  `description` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `permissions` (`name`, `description`) VALUES
-- Dashboard
('dashboard.view', 'View the dashboard'),
-- Facility
('facility.view', 'View facility listings'),
('facility.reserve', 'Create facility reservations'),
('facility.manage', 'Approve/reject/manage all facility reservations'),
-- Equipment
('equipment.view', 'View equipment inventory'),
('equipment.borrow', 'Request equipment borrowing'),
('equipment.manage', 'Approve/reject/manage all equipment requests'),
-- Events
('event.view', 'View events listing'),
('event.register', 'Register for events'),
('event.create', 'Create new events'),
('event.manage', 'Full event management (CRUD, metrics)'),
-- Announcements
('announcement.view', 'View announcements'),
('announcement.create', 'Create announcements'),
('announcement.manage', 'Full announcement management'),
-- Users
('user.view', 'View user profiles'),
('user.create', 'Create new users'),
('user.edit', 'Edit user accounts'),
('user.manage', 'Full user management (CRUD, role assignment)'),
-- Reports
('report.view', 'View all reports'),
('report.facility', 'View facility usage reports'),
('report.events', 'View event engagement reports'),
('report.audit', 'View audit log'),
-- Privacy
('privacy.view', 'View privacy policy');

CREATE TABLE `role_permissions` (
  `role_id` INT UNSIGNED NOT NULL,
  `permission_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Administrator: all permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, `id` FROM `permissions`;

-- Staff: dashboard, facility (view/reserve/manage), equipment (view/borrow/manage), events (view/register/create), announcements (view/create), privacy
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, `id` FROM `permissions` WHERE `name` IN (
  'dashboard.view', 'facility.view', 'facility.reserve', 'facility.manage',
  'equipment.view', 'equipment.borrow', 'equipment.manage',
  'event.view', 'event.register', 'event.create',
  'announcement.view', 'announcement.create', 'privacy.view'
);

-- Faculty: dashboard, facility (view/reserve), equipment (view/borrow), events (view/register/create), announcements (view/create), privacy
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, `id` FROM `permissions` WHERE `name` IN (
  'dashboard.view', 'facility.view', 'facility.reserve',
  'equipment.view', 'equipment.borrow',
  'event.view', 'event.register', 'event.create',
  'announcement.view', 'announcement.create', 'privacy.view'
);

-- Student: dashboard, facility (view/reserve), equipment (view/borrow), events (view/register), announcements (view), privacy
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, `id` FROM `permissions` WHERE `name` IN (
  'dashboard.view', 'facility.view', 'facility.reserve',
  'equipment.view', 'equipment.borrow',
  'event.view', 'event.register',
  'announcement.view', 'privacy.view'
);

-- ============================================================
-- 2. USERS
-- ============================================================

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `employee_id` VARCHAR(50) DEFAULT NULL UNIQUE COMMENT 'School ID / Employee ID',
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash via password_hash()',
  `role_id` INT UNSIGNED NOT NULL DEFAULT 4,
  `department` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive', 'locked') NOT NULL DEFAULT 'active',
  `login_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `password_reset_token` VARCHAR(255) DEFAULT NULL,
  `password_reset_expires` DATETIME DEFAULT NULL,
  `privacy_consent` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'RA 10173 consent flag',
  `privacy_consent_date` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL COMMENT 'Soft delete',
  PRIMARY KEY (`id`),
  INDEX `idx_users_email` (`email`),
  INDEX `idx_users_role` (`role_id`),
  INDEX `idx_users_status` (`status`),
  FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- PRACTICE / TEST ACCOUNTS
-- ============================================================
-- ALL PASSWORDS: Password123!
-- bcrypt hash of "Password123!" generated via password_hash()
-- $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi  (hash of "password")
-- We use a proper hash below for "Password123!"
-- ============================================================

INSERT INTO `users` (`employee_id`, `first_name`, `last_name`, `email`, `password`, `role_id`, `department`, `status`, `privacy_consent`, `privacy_consent_date`) VALUES
-- ADMINISTRATOR ACCOUNT
('ADMIN-001', 'Admin', 'User', 'admin@slmcb.edu.ph', '$2y$10$8DZ8HV6XdotPM6HR7ETTp.WjkPoyBAviruoUrw2eanxnUGVQVBzTm', 1, 'Administration', 'active', 1, NOW()),

-- STAFF ACCOUNT
('STAFF-001', 'Maria', 'Dela Cruz', 'staff@slmcb.edu.ph', '$2y$10$8DZ8HV6XdotPM6HR7ETTp.WjkPoyBAviruoUrw2eanxnUGVQVBzTm', 2, 'General Services', 'active', 1, NOW()),

-- FACULTY ACCOUNT
('FAC-001', 'Juan', 'Santos', 'faculty@slmcb.edu.ph', '$2y$10$8DZ8HV6XdotPM6HR7ETTp.WjkPoyBAviruoUrw2eanxnUGVQVBzTm', 3, 'BSIT Department', 'active', 1, NOW()),

-- STUDENT ACCOUNT
('STU-2025-001', 'Ana', 'Reyes', 'student@slmcb.edu.ph', '$2y$10$8DZ8HV6XdotPM6HR7ETTp.WjkPoyBAviruoUrw2eanxnUGVQVBzTm', 4, 'BSIT', 'active', 1, NOW()),

-- EXTRA STUDENT FOR TESTING
('STU-2025-002', 'Carlos', 'Garcia', 'student2@slmcb.edu.ph', '$2y$10$8DZ8HV6XdotPM6HR7ETTp.WjkPoyBAviruoUrw2eanxnUGVQVBzTm', 4, 'BSBA', 'active', 1, NOW());

-- ============================================================
-- 3. FACILITIES
-- ============================================================

CREATE TABLE `facilities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `capacity` INT UNSIGNED DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('available', 'maintenance', 'unavailable') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `facilities` (`name`, `location`, `description`, `capacity`, `status`) VALUES
('Main Auditorium', 'Building A, Ground Floor', 'Large auditorium suitable for school-wide assemblies, programs, and events.', 500, 'available'),
('AVR (Audio-Visual Room)', 'Building B, 2nd Floor', 'Multi-media equipped room for presentations, film screenings, and seminars.', 80, 'available'),
('Covered Court', 'Campus Grounds', 'Open-air covered court for sports events, PE classes, and outdoor activities.', 300, 'available'),
('Computer Laboratory 1', 'Building C, 3rd Floor', 'Air-conditioned lab with 40 desktop units for IT and computer classes.', 40, 'available'),
('Computer Laboratory 2', 'Building C, 3rd Floor', 'Air-conditioned lab with 35 desktop units for IT and computer classes.', 35, 'available'),
('Conference Room', 'Building A, 2nd Floor', 'Meeting room for faculty meetings, committee sessions, and small gatherings.', 25, 'available'),
('Chapel', 'Building A, Ground Floor', 'School chapel for masses, recollections, and spiritual activities.', 150, 'available'),
('Science Laboratory', 'Building B, 3rd Floor', 'Fully equipped laboratory for science experiments and practical classes.', 40, 'available');

CREATE TABLE `facility_reservations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `facility_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(200) NOT NULL COMMENT 'Purpose/Event name',
  `description` TEXT DEFAULT NULL,
  `reservation_date` DATE NOT NULL,
  `time_start` TIME NOT NULL,
  `time_end` TIME NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_reservation_date` (`reservation_date`),
  INDEX `idx_reservation_status` (`status`),
  INDEX `idx_reservation_facility` (`facility_id`, `reservation_date`),
  FOREIGN KEY (`facility_id`) REFERENCES `facilities`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. EQUIPMENT
-- ============================================================

CREATE TABLE `equipment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `available_quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('available', 'maintenance', 'unavailable') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_equipment_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `equipment` (`name`, `category`, `description`, `quantity`, `available_quantity`) VALUES
('LCD Projector', 'Audio-Visual', 'Epson LCD projector for presentations and screenings.', 5, 5),
('Laptop', 'Computing', 'Dell Inspiron laptop for official use.', 3, 3),
('Portable Speaker', 'Audio-Visual', 'JBL portable Bluetooth speaker for events.', 4, 4),
('Whiteboard', 'Office Supply', 'Magnetic whiteboard (4x3 ft) with markers.', 6, 6),
('Extension Cord (10m)', 'Electrical', 'Heavy-duty 10-meter extension cord.', 10, 10),
('Folding Table', 'Furniture', 'Foldable rectangular table for events.', 20, 20),
('Monoblock Chair', 'Furniture', 'Standard monoblock chair.', 100, 100),
('Microphone (Wireless)', 'Audio-Visual', 'Wireless handheld microphone with receiver.', 4, 4),
('HDMI Cable (3m)', 'Accessories', '3-meter HDMI cable for display connectivity.', 8, 8),
('First Aid Kit', 'Medical', 'Standard first aid kit for events.', 3, 3);

CREATE TABLE `equipment_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `equipment_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `purpose` TEXT NOT NULL,
  `date_needed` DATE NOT NULL,
  `expected_return_date` DATE NOT NULL,
  `actual_return_date` DATE DEFAULT NULL,
  `status` ENUM('pending', 'approved', 'rejected', 'borrowed', 'returned', 'cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` INT UNSIGNED DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_equip_req_status` (`status`),
  FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. EVENTS
-- ============================================================

CREATE TABLE `events` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `event_date` DATE NOT NULL,
  `time_start` TIME NOT NULL,
  `time_end` TIME DEFAULT NULL,
  `venue` VARCHAR(255) DEFAULT NULL,
  `organizer_id` INT UNSIGNED NOT NULL,
  `image_banner` VARCHAR(255) DEFAULT NULL,
  `max_participants` INT UNSIGNED DEFAULT 0,
  `event_type` ENUM('academic', 'cultural', 'sports', 'spiritual', 'other') NOT NULL DEFAULT 'other',
  `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_event_date` (`event_date`),
  INDEX `idx_event_status` (`status`),
  INDEX `idx_event_type` (`event_type`),
  FOREIGN KEY (`organizer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample events for testing
INSERT INTO `events` (`title`, `description`, `event_date`, `time_start`, `time_end`, `venue`, `organizer_id`, `max_participants`, `event_type`, `status`) VALUES
('SLMCB Foundation Day 2026', 'Annual celebration of the founding of St. Louise de Marillac College of Bogo with cultural performances, exhibits, and an awards ceremony.', '2026-09-15', '08:00:00', '17:00:00', 'Main Auditorium', 1, 500, 'cultural', 'upcoming'),
('IT Skills Workshop: Web Development Basics', 'Hands-on workshop covering HTML, CSS, and JavaScript fundamentals for BSIT students.', '2026-09-05', '09:00:00', '12:00:00', 'Computer Laboratory 1', 3, 40, 'academic', 'upcoming'),
('Intramurals 2026', 'Annual campus-wide sports competition featuring basketball, volleyball, and track and field events.', '2026-09-20', '07:00:00', '17:00:00', 'Covered Court', 1, 300, 'sports', 'upcoming'),
('Recollection Day', 'A day of spiritual reflection and prayer for all students led by the Campus Ministry.', '2026-09-10', '08:00:00', '15:00:00', 'Chapel', 1, 150, 'spiritual', 'upcoming');

CREATE TABLE `event_registrations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `has_attended` TINYINT(1) NOT NULL DEFAULT 0,
  `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_event_user` (`event_id`, `user_id`),
  FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ANNOUNCEMENTS
-- ============================================================

CREATE TABLE `announcements` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `priority` ENUM('general', 'important', 'urgent') NOT NULL DEFAULT 'general',
  `audience` ENUM('all', 'students', 'faculty', 'staff', 'administrators') NOT NULL DEFAULT 'all',
  `posted_by` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_pinned` TINYINT(1) NOT NULL DEFAULT 0,
  `expires_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_announcement_priority` (`priority`),
  INDEX `idx_announcement_audience` (`audience`),
  INDEX `idx_announcement_expires` (`expires_at`),
  FOREIGN KEY (`posted_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample announcements for testing
INSERT INTO `announcements` (`title`, `content`, `priority`, `audience`, `posted_by`, `is_pinned`) VALUES
('Welcome to ICOEP!', 'We are excited to launch the Integrated Campus Operations and Engagement Portal (ICOEP) for St. Louise de Marillac College of Bogo. This portal will serve as your one-stop hub for facility reservations, equipment borrowing, event management, and campus announcements. Please explore the system and let us know your feedback!', 'important', 'all', 1, 1),
('Enrollment for SY 2026-2027 Now Open', 'Dear students, enrollment for the upcoming school year is now open. Please visit the Registrar Office for more details and requirements. Online pre-registration is available through the school website.', 'general', 'students', 1, 0),
('Faculty Meeting — September 2026', 'All faculty members are required to attend the general faculty meeting scheduled on September 3, 2026 at 2:00 PM in the Conference Room. Agenda includes curriculum updates and event planning for Foundation Day.', 'important', 'faculty', 1, 0);

-- ============================================================
-- 7. NOTIFICATIONS
-- ============================================================

CREATE TABLE `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'error') NOT NULL DEFAULT 'info',
  `link` VARCHAR(500) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_notif_user_read` (`user_id`, `is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Welcome notifications for test accounts
INSERT INTO `notifications` (`user_id`, `title`, `message`, `type`, `link`) VALUES
(1, 'System Ready', 'The ICOEP system has been initialized. You have full administrative access.', 'success', '/modules/dashboard/index.php'),
(2, 'Welcome to ICOEP', 'Your staff account is ready. You can now manage facility and equipment requests.', 'info', '/modules/dashboard/index.php'),
(3, 'Welcome to ICOEP', 'Your faculty account is ready. You can create events, reserve facilities, and post announcements.', 'info', '/modules/dashboard/index.php'),
(4, 'Welcome to ICOEP', 'Your student account is ready. Explore campus facilities, events, and announcements!', 'success', '/modules/dashboard/index.php'),
(5, 'Welcome to ICOEP', 'Your student account is ready. Explore campus facilities, events, and announcements!', 'success', '/modules/dashboard/index.php');

-- ============================================================
-- 8. AUDIT LOGS (RA 10173 Compliance)
-- ============================================================

CREATE TABLE `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL COMMENT 'e.g. LOGIN, CREATE, UPDATE, DELETE, EXPORT',
  `table_name` VARCHAR(100) DEFAULT NULL,
  `record_id` INT UNSIGNED DEFAULT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_audit_user` (`user_id`),
  INDEX `idx_audit_action` (`action`),
  INDEX `idx_audit_table` (`table_name`),
  INDEX `idx_audit_date` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. PRIVACY CONSENTS (RA 10173)
-- ============================================================

CREATE TABLE `privacy_consents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `consent_type` VARCHAR(100) NOT NULL DEFAULT 'data_processing',
  `consented` TINYINT(1) NOT NULL DEFAULT 0,
  `consent_text` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `consented_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_consent_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
