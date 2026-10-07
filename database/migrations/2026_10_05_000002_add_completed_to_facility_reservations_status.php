<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE facility_reservations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE facility_reservations MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending'");
        }
    }
};
