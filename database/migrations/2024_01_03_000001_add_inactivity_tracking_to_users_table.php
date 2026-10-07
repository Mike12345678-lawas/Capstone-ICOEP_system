<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('last_activity_at')->nullable()->after('last_login');
            $table->timestamp('deactivated_at')->nullable()->after('last_activity_at');
            $table->string('deactivation_reason', 30)->nullable()->after('deactivated_at');
            $table->timestamp('inactivity_warned_at')->nullable()->after('deactivation_reason');
        });

        // Backfill: Grace period of 14 days for existing active users
        DB::table('users')
            ->where('status', 'active')
            ->whereNull('deleted_at')
            ->update([
                'last_activity_at' => now(),
            ]);

        // Backfill: Existing inactive users are marked as admin-deactivated
        DB::table('users')
            ->where('status', 'inactive')
            ->update([
                'deactivation_reason' => 'admin',
                'deactivated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'last_activity_at',
                'deactivated_at',
                'deactivation_reason',
                'inactivity_warned_at',
            ]);
        });
    }
};
