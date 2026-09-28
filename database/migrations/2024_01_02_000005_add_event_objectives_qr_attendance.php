<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add mandatory objectives/targets field to events
        Schema::table('events', function (Blueprint $table) {
            $table->text('objectives')->after('description');
        });

        // Add QR code and attendance tracking to event_registrations
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->string('qr_code', 64)->unique()->nullable()->after('user_id');
            $table->timestamp('checked_in_at')->nullable()->after('has_attended');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('objectives');
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropUnique(['qr_code']);
            $table->dropColumn(['qr_code', 'checked_in_at']);
        });
    }
};
