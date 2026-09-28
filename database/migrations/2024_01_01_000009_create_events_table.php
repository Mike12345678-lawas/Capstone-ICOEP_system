<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('event_type', ['academic', 'cultural', 'sports', 'spiritual', 'other'])->default('academic');
            $table->date('event_date');
            $table->time('time_start');
            $table->time('time_end');
            $table->string('venue')->nullable();
            $table->integer('max_participants')->default(0);
            $table->string('image_banner')->nullable();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['upcoming', 'ongoing', 'completed', 'cancelled'])->default('upcoming');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
