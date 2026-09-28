<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('password');
            $table->foreignId('role_id')->default(4)->constrained('roles');
            $table->string('department')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('avatar')->nullable();
            $table->enum('status', ['active', 'inactive', 'locked'])->default('active');
            $table->integer('login_attempts')->default(0);
            $table->timestamp('locked_until')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->boolean('privacy_consent')->default(false);
            $table->timestamp('privacy_consent_date')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
