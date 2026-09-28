<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalization: Add `id_format` discriminator column to users table.
 *
 * Problem: The `employee_id` column holds both student IDs (YY-NNNNN)
 * and employee numbers (NNN) with no schema-level distinction.
 *
 * Fix: Add an `id_type` ENUM column that makes the format explicit,
 * enabling DB-level documentation and future indexed queries by type.
 *
 * Also:
 *  - Rename `employee_id` → `username_id` to be semantically neutral
 *  - Add unique constraint on (username_id) so duplicates are caught at DB level
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add discriminator before the id column
            $table->enum('id_type', ['student', 'employee'])
                  ->default('student')
                  ->after('id')
                  ->comment('Determines the format: student=YY-NNNNN, employee=NNN');

            // Make employee_id unique so no two users share the same identifier
            // (nullable unique allows multiple NULLs in MySQL)
            $table->unique('employee_id', 'users_employee_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_employee_id_unique');
            $table->dropColumn('id_type');
        });
    }
};
