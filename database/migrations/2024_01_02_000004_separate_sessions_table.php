<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Normalization: Separate the sessions table into its own migration file.
 *
 * In the original code, the sessions table was bundled inside
 * 2024_01_01_000014_create_privacy_consents_table.php, which violates
 * the "one concern per migration" principle and makes it impossible
 * to roll back sessions independently.
 *
 * This migration is a no-op if the sessions table was already created
 * by the bundled migration; we guard with createIfNotExists.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // Add index on last_activity for session GC performance (idempotent)
        if (Schema::hasTable('sessions') && !$this->indexExists('sessions', 'sessions_last_activity_index')) {
            Schema::table('sessions', function (Blueprint $table) {
                $table->index('last_activity');
            });
        }
    }

    public function down(): void
    {
        // Only drop if this migration was responsible for creating it.
        // In practice, sessions management is handled by Laravel; leave intact.
    }

    private function indexExists(string $table, string $index): bool
    {
        $conn    = Schema::getConnection();
        if ($conn->getDriverName() === 'sqlite') {
            return DB::table('sqlite_master')->where('type', 'index')->where('name', $index)->exists();
        }
        $dbName  = $conn->getDatabaseName();
        $count   = $conn->table('information_schema.statistics')
                        ->where('table_schema', $dbName)
                        ->where('table_name', $table)
                        ->where('index_name', $index)
                        ->count();
        return $count > 0;
    }
};
