<?php

return [
    'app_name'          => env('APP_NAME', 'ICOEP'),
    'app_full_name'     => env('APP_FULL_NAME', 'Integrated Campus Operations and Engagement Portal'),
    'app_version'       => env('APP_VERSION', '1.0.0'),
    'institution_name'  => env('INSTITUTION_NAME', 'St. Louise de Marillac College of Bogo'),
    'institution_short' => env('INSTITUTION_SHORT', 'SLMCB'),
    'items_per_page'    => 15,
    'max_login_attempts'=> 5,
    'lockout_minutes'   => 30,
    'block_borrowing_after_days_overdue' => env('BLOCK_BORROWING_AFTER_DAYS_OVERDUE', 3),

    // ─── Rule-Based Deterministic Auto-Approval ─────────────────────────
    // Enabled by default for facilities ONLY with role-based priority & preemption.
    // Note: Equipment requests require manual admin approval (auto-approval is facility-only).

    'auto_approve' => [
        'facility' => [
            'enabled'                  => env('AUTO_APPROVE_FACILITY', true),
            // Roles whose requests may be auto-approved
            'allowed_roles'            => ['Administrator', 'Faculty', 'Staff', 'Student'],
            // Operating window — requests outside this window stay pending
            'operating_start'          => env('AUTO_APPROVE_FACILITY_OP_START', '06:00'),
            'operating_end'            => env('AUTO_APPROVE_FACILITY_OP_END', '23:00'),
            // Maximum allowed duration in hours (0 = no limit)
            'max_duration_hours'       => env('AUTO_APPROVE_FACILITY_MAX_HOURS', 0),
            // Roles that can be preempted by higher priority tiers
            'preemptible_roles'        => ['Student'],
            // Minimum hours before booking start required to permit preemption
            'preempt_min_hours_before' => 24,
            // Maximum suggestions to return on rejection/preemption
            'max_suggestions'          => 5,
        ],

        'equipment' => [
            // Equipment auto-approval is disabled. Auto-approval is facilities only.
            'enabled'                  => false,
            'max_suggestions'          => 5,
        ],
    ],

    // ─── Automated Account Inactivity Deactivation ──────────────────────
    'inactivity' => [
        'days'                       => env('INACTIVITY_DAYS', 14),
        'exempt_roles'               => ['Administrator'],
        'protect_borrowed_equipment' => true,
        'protect_upcoming_bookings'  => true,
        'allow_self_reactivation'    => env('INACTIVITY_ALLOW_SELF_REACTIVATION', false),
        'warn_days_before'           => env('INACTIVITY_WARN_DAYS_BEFORE', 2),
        'activity_throttle_minutes'  => 10,
    ],
];

