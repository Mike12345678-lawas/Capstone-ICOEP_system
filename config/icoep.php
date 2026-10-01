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

    // ─── Rule-Based Deterministic Auto-Approval ─────────────────────────
    // Enabled by default for all roles with role-based priority & preemption.
    // When enabled, requests resolve deterministically (approve/reject/pending).

    'auto_approve' => [
        'facility' => [
            'enabled'                  => env('AUTO_APPROVE_FACILITY', true),
            // Roles whose requests may be auto-approved
            'allowed_roles'            => ['Administrator', 'Faculty', 'Staff', 'Student'],
            // Operating window — requests outside this window stay pending
            'operating_start'          => '07:00', // 7 AM
            'operating_end'            => '20:00', // 8 PM
            // Maximum allowed duration in hours (0 = no limit)
            'max_duration_hours'       => 8,
            // Roles that can be preempted by higher priority tiers
            'preemptible_roles'        => ['Student'],
            // Minimum hours before booking start required to permit preemption
            'preempt_min_hours_before' => 24,
            // Maximum suggestions to return on rejection/preemption
            'max_suggestions'          => 5,
        ],

        'equipment' => [
            'enabled'                  => env('AUTO_APPROVE_EQUIPMENT', true),
            // Roles whose requests may be auto-approved
            'allowed_roles'            => ['Administrator', 'Faculty', 'Staff', 'Student'],
            // Minimum units that must remain available after approving
            'min_stock_buffer'         => 0,
            // Roles that can be preempted by higher priority tiers
            'preemptible_roles'        => ['Student'],
            // Maximum suggestions to return on rejection/preemption
            'max_suggestions'          => 5,
        ],
    ],
];
