<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update any existing users with locked status to inactive
        DB::table('users')->where('status', 'locked')->update(['status' => 'inactive']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN status ENUM('active', 'inactive', 'locked') NOT NULL DEFAULT 'active'");
        }
    }
};
