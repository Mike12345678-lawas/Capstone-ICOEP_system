<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalization: Add standard `timestamps()` to event_registrations.
 *
 * The original migration only has `registered_at` (a single timestamp),
 * which is inconsistent with all other tables and prevents tracking updates.
 * Adding `created_at`/`updated_at` makes ORM behaviour consistent.
 *
 * We keep `registered_at` as a semantic alias (it auto-mirrors created_at).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            // Add standard timestamps; existing rows get current time
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
