<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('pending', 'upcoming', 'ongoing', 'completed', 'cancelled', 'rejected') NOT NULL DEFAULT 'upcoming'");
        }

        Schema::table('events', function (Blueprint $table) {
            if (!Schema::hasColumn('events', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('organizer_id')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('events', 'remarks')) {
                $table->text('remarks')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            if (Schema::hasColumn('events', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('events', 'remarks')) {
                $table->dropColumn('remarks');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE events MODIFY COLUMN status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming'");
        }
    }
};
