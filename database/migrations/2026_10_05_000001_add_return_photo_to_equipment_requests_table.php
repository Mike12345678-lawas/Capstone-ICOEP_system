<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Borrowers must attach a photo of the item when they submit a return.
     */
    public function up(): void
    {
        Schema::table('equipment_requests', function (Blueprint $table) {
            $table->string('return_photo')->nullable()->after('return_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_requests', function (Blueprint $table) {
            $table->dropColumn('return_photo');
        });
    }
};
