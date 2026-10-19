<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create custodian_units table
        Schema::create('custodian_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        // 2. Add nullable FK custodian_unit_id to equipment
        Schema::table('equipment', function (Blueprint $table) {
            $table->foreignId('custodian_unit_id')
                ->nullable()
                ->after('id')
                ->constrained('custodian_units')
                ->nullOnDelete();
        });

        // 3. Add nullable FK custodian_unit_id to users
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('custodian_unit_id')
                ->nullable()
                ->after('department_id')
                ->constrained('custodian_units')
                ->nullOnDelete();
        });

        // 4. Update equipment.status enum in MySQL to include 'retired'
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE equipment MODIFY COLUMN status ENUM('available', 'maintenance', 'unavailable', 'retired') NOT NULL DEFAULT 'available'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE equipment MODIFY COLUMN status ENUM('available', 'maintenance', 'unavailable') NOT NULL DEFAULT 'available'");
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custodian_unit_id');
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custodian_unit_id');
        });

        Schema::dropIfExists('custodian_units');
    }
};
