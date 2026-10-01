<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalization: Extract free-text 'department' column on users into a
 * proper departments lookup table, eliminating string redundancy.
 *
 * Steps:
 *  1. Create departments table with canonical names
 *  2. Seed from distinct values already in users.department
 *  3. Add users.department_id FK column
 *  4. Backfill department_id from matching department names
 *  5. Drop the old users.department string column
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Create departments reference table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150)->unique();
            $table->string('code', 20)->nullable()->unique()->comment('Short code e.g. BSIT, CCS');
            $table->timestamps();
        });

        // 2. Seed departments from existing free-text values in users table
        $existing = DB::table('users')
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        foreach ($existing as $name) {
            DB::table('departments')->insertOrIgnore([
                'name'       => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 3. Add FK column to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')
                  ->nullable()
                  ->after('department')
                  ->constrained('departments')
                  ->nullOnDelete();
        });

        // 4. Backfill — map existing string values to the new FK
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE users SET department_id = (SELECT id FROM departments WHERE departments.name = users.department)');
        } else {
            DB::statement('
                UPDATE users u
                INNER JOIN departments d ON d.name = u.department
                SET u.department_id = d.id
            ');
        }

        // 5. Drop the old free-text column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        // Restore free-text column from FK before dropping
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('department_id');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('UPDATE users SET department = (SELECT name FROM departments WHERE departments.id = users.department_id)');
        } else {
            DB::statement('
                UPDATE users u
                INNER JOIN departments d ON d.id = u.department_id
                SET u.department = d.name
            ');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::dropIfExists('departments');
    }
};
