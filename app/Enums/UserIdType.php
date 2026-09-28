<?php

namespace App\Enums;

/**
 * Discriminates between the two ID formats used in the system.
 *
 *  - student  → YY-NNNNN   (e.g. 24-00123)
 *  - employee → NNN         (e.g. 001)
 */
enum UserIdType: string
{
    case Student  = 'student';
    case Employee = 'employee';

    /** Human-readable label */
    public function label(): string
    {
        return match($this) {
            self::Student  => 'Student ID',
            self::Employee => 'Employee Number',
        };
    }

    /** The regex pattern expected for this type */
    public function pattern(): string
    {
        return match($this) {
            self::Student  => '/^\d{2}-\d{5}$/',
            self::Employee => '/^\d{3}$/',
        };
    }
}
