<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--rotate : Delete all previous backups before creating a new one (keeps only the latest)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a complete SQL backup of the system database';

    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Starting database backup...');

        try {
            $existing = $backupService->getBackups();

            $backup = $backupService->createBackup();

            // If --rotate flag is set, delete old backups only after the new backup succeeded (keeps only latest)
            if ($this->option('rotate') && count($existing) > 0) {
                $this->info("Rotating: removing " . count($existing) . " old backup(s)...");
                foreach ($existing as $old) {
                    if ($old['filename'] !== $backup['filename']) {
                        $backupService->deleteBackup($old['filename']);
                        $this->line("  Deleted: {$old['filename']}");
                    }
                }
            }

            $this->info("Backup completed successfully!");
            $this->table(
                ['Property', 'Value'],
                [
                    ['Filename', $backup['filename']],
                    ['File Size', $backup['size']],
                    ['Storage Path', $backup['path']],
                    ['Timestamp', $backup['created_at']],
                ]
            );

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
