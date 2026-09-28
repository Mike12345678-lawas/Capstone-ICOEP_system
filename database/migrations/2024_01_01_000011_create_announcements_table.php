<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('priority', ['general', 'important', 'urgent'])->default('general');
            $table->enum('audience', ['all', 'students', 'faculty', 'staff', 'administrators'])->default('all');
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();
            $table->string('image')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
