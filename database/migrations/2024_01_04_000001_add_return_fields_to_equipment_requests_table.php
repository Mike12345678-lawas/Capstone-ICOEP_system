<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_requests', function (Blueprint $table) {
            $table->timestamp('return_requested_at')->nullable()->after('actual_return_date');
            $table->unsignedInteger('returned_quantity')->default(0)->after('return_requested_at');
            $table->timestamp('returned_at')->nullable()->after('returned_quantity');
            $table->foreignId('confirmed_by')->nullable()->after('returned_at')->constrained('users')->nullOnDelete();
            $table->string('return_condition')->nullable()->after('confirmed_by');
            $table->text('return_notes')->nullable()->after('return_condition');
            $table->unsignedInteger('days_late')->default(0)->after('return_notes');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_requests', function (Blueprint $table) {
            $table->dropForeign(['confirmed_by']);
            $table->dropColumn([
                'return_requested_at',
                'returned_quantity',
                'returned_at',
                'confirmed_by',
                'return_condition',
                'return_notes',
                'days_late',
            ]);
        });
    }
};
