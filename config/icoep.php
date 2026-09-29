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

    // ─── Optional Rule-Based Auto-Approval ───────────────────────────────
    // Set 'enabled' to true to activate. When false, all new requests remain
    // 'pending' and require manual admin action (default / recommended for go-live).

    'auto_approve' => [
        'facility' => [
            'enabled'          => env('AUTO_APPROVE_FACILITY', false),
            // Roles whose requests may be auto-approved (case-sensitive role names)
            'allowed_roles'    => ['Administrator', 'Faculty', 'Staff'],
            // Operating window — requests outside this window stay pending
            'operating_start'  => '07:00', // 7 AM
            'operating_end'    => '20:00', // 8 PM
            // Maximum allowed duration in hours (0 = no limit)
            'max_duration_hours' => 8,
        ],

        'equipment' => [
            'enabled'          => env('AUTO_APPROVE_EQUIPMENT', false),
            // Roles whose requests may be auto-approved
            'allowed_roles'    => ['Administrator', 'Faculty', 'Staff'],
            // Minimum units that must remain available after approving
            'min_stock_buffer' => 0,
        ],
    ],
];
