<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DatabaseBackupService
{
    protected string $backupDir = 'backups';

    /**
     * Get the absolute path to the backup directory.
     */
    public function getStoragePath(): string
    {
        $path = storage_path('app/' . $this->backupDir);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }

    /**
     * Create a complete SQL database backup.
     * Uses PDO connection to reliably export structure and data across
     * all environments, SSL connections (Aiven Cloud), and authentication plugins.
     *
     * @return array Metadata about the created backup file
     * @throws \Exception
     */
    public function createBackup(): array
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $storagePath = $this->getStoragePath();
        $timestamp   = now()->format('Y-m-d_His');
        $filename    = "icoep_backup_{$timestamp}.sql";
        $filePath    = $storagePath . DIRECTORY_SEPARATOR . $filename;

        $pdo      = DB::connection()->getPdo();
        $database = config('database.connections.mysql.database');

        $handle = fopen($filePath, 'w');
        if (!$handle) {
            throw new \Exception('Failed to create backup file in storage directory.');
        }

        try {
            // SQL Header
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- ICOEP System Database Backup\n");
            fwrite($handle, "-- Generated: " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- Database: {$database}\n");
            fwrite($handle, "-- Institution: " . config('icoep.institution_name', 'SLMCB') . "\n");
            fwrite($handle, "-- --------------------------------------------------------\n\n");
            fwrite($handle, "SET NAMES utf8mb4;\n");
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($handle, "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n");
            fwrite($handle, "SET time_zone = \"+00:00\";\n\n");

            // Retrieve all base tables
            $tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
            $tables     = $tablesStmt->fetchAll(\PDO::FETCH_COLUMN, 0);

            foreach ($tables as $table) {
                // Table Structure
                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "-- Table structure for `{$table}`\n");
                fwrite($handle, "-- --------------------------------------------------------\n\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow  = $createStmt->fetch(\PDO::FETCH_NUM);
                if (!empty($createRow[1])) {
                    fwrite($handle, $createRow[1] . ";\n\n");
                }

                // Table Data
                $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $totalRows = (int) $countStmt->fetchColumn();

                if ($totalRows === 0) {
                    continue;
                }

                fwrite($handle, "-- --------------------------------------------------------\n");
                fwrite($handle, "-- Dumping data for table `{$table}` ({$totalRows} rows)\n");
                fwrite($handle, "-- --------------------------------------------------------\n\n");

                // Columns
                $colStmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                $columns = $colStmt->fetchAll(\PDO::FETCH_COLUMN, 0);
                $colList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));

                $chunkSize = 200;
                $offset    = 0;

                while ($offset < $totalRows) {
                    $dataStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}");
                    $rows     = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

                    if (empty($rows)) {
                        break;
                    }

                    $valueStrings = [];
                    foreach ($rows as $row) {
                        $escapedValues = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $escapedValues[] = 'NULL';
                            } elseif (is_numeric($val) && !is_string($val)) {
                                $escapedValues[] = $val;
                            } else {
                                $escapedValues[] = $pdo->quote((string)$val);
                            }
                        }
                        $valueStrings[] = '(' . implode(', ', $escapedValues) . ')';
                    }

                    if (!empty($valueStrings)) {
                        fwrite($handle, "INSERT INTO `{$table}` ({$colList}) VALUES\n" . implode(",\n", $valueStrings) . ";\n\n");
                    }

                    $offset += $chunkSize;
                }
            }

            // Footer
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
            fwrite($handle, "-- --------------------------------------------------------\n");
            fwrite($handle, "-- Backup completed successfully at " . date('Y-m-d H:i:s') . "\n");
            fwrite($handle, "-- --------------------------------------------------------\n");

            fclose($handle);

            if (!file_exists($filePath) || filesize($filePath) === 0) {
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
                throw new \Exception('Backup file was created but contains no data.');
            }

            return [
                'filename'   => $filename,
                'path'       => $filePath,
                'size_bytes' => filesize($filePath),
                'size'       => $this->formatBytes(filesize($filePath)),
                'created_at' => date('M d, Y h:i A'),
            ];
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
            throw $e;
        }
    }

    /**
     * Get all available backup files sorted newest first.
     */
    public function getBackups(): array
    {
        $dir = $this->getStoragePath();
        $files = [];

        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            if (!preg_match('/\.(sql|sql\.gz)$/i', $file)) continue;

            $path = $dir . DIRECTORY_SEPARATOR . $file;
            $files[] = [
                'filename'   => $file,
                'path'       => $path,
                'size'       => $this->formatBytes(filesize($path)),
                'size_bytes' => filesize($path),
                'created_at' => date('M d, Y h:i A', filemtime($path)),
                'timestamp'  => filemtime($path),
            ];
        }

        usort($files, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $files;
    }

    /**
     * Get the absolute path for a validated backup filename.
     */
    public function getBackupPath(string $filename): ?string
    {
        if (!$this->isValidBackupFilename($filename)) {
            return null;
        }

        $path = $this->getStoragePath() . DIRECTORY_SEPARATOR . $filename;
        return file_exists($path) ? $path : null;
    }

    /**
     * Delete a backup file.
     */
    public function deleteBackup(string $filename): bool
    {
        $path = $this->getBackupPath($filename);
        if ($path && file_exists($path)) {
            return unlink($path);
        }
        return false;
    }

    /**
     * Validate that a filename is a safe SQL backup filename.
     */
    public function isValidBackupFilename(string $filename): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_\-\.]+\.(sql|sql\.gz)$/i', $filename);
    }

    /**
     * Format bytes to readable size string.
     */
    public function formatBytes(int $bytes): string
    {
        if ($bytes === 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Get the path to the auto-backup settings file.
     */
    public function getSettingsPath(): string
    {
        return storage_path('app/backup_settings.json');
    }

    /**
     * Get auto-backup settings.
     */
    public function getSettings(): array
    {
        $path = $this->getSettingsPath();
        $defaults = [
            'auto_backup_enabled' => true,
            'last_auto_backup_date' => null,
            'last_auto_backup_file' => null,
        ];

        if (!file_exists($path)) {
            return $defaults;
        }

        $data = json_decode(file_get_contents($path), true);
        return array_merge($defaults, $data ?? []);
    }

    /**
     * Save auto-backup settings.
     */
    public function saveSettings(array $settings): void
    {
        $current = $this->getSettings();
        $merged = array_merge($current, $settings);
        file_put_contents($this->getSettingsPath(), json_encode($merged, JSON_PRETTY_PRINT));
    }

    /**
     * Check if auto-backup is enabled.
     */
    public function isAutoBackupEnabled(): bool
    {
        return (bool) ($this->getSettings()['auto_backup_enabled'] ?? true);
    }

    /**
     * Toggle auto-backup on/off.
     */
    public function toggleAutoBackup(bool $enabled): void
    {
        $this->saveSettings(['auto_backup_enabled' => $enabled]);
    }

    /**
     * Check if an auto-backup has already been done today.
     */
    public function hasBackupToday(): bool
    {
        $settings = $this->getSettings();
        return $settings['last_auto_backup_date'] === date('Y-m-d');
    }

    /**
     * Run the auto-backup: delete old backups, create a new one, update settings.
     * Uses non-blocking file lock to prevent simultaneous duplicate runs.
     */
    public function runAutoBackup(): ?array
    {
        if (!$this->isAutoBackupEnabled() || $this->hasBackupToday()) {
            return null;
        }

        $lockPath = storage_path('app/backup.lock');
        $lock = @fopen($lockPath, 'c+');
        if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
            if ($lock) fclose($lock);
            return null;
        }

        try {
            // Re-verify after lock acquisition
            if (!$this->isAutoBackupEnabled() || $this->hasBackupToday()) {
                flock($lock, LOCK_UN);
                fclose($lock);
                return null;
            }

            // Immediately mark today's date to prevent race conditions
            $this->saveSettings([
                'last_auto_backup_date' => date('Y-m-d'),
            ]);

            // Snapshot existing backups before creating the new one
            $oldBackups = $this->getBackups();

            // Create new backup first
            $backup = $this->createBackup();

            // Delete old backups only after the new backup has succeeded (rotation - keeps only latest)
            foreach ($oldBackups as $old) {
                if ($old['filename'] !== $backup['filename']) {
                    $this->deleteBackup($old['filename']);
                }
            }

            // Update settings with the completed file info
            $this->saveSettings([
                'last_auto_backup_date' => date('Y-m-d'),
                'last_auto_backup_file' => $backup['filename'],
            ]);

            flock($lock, LOCK_UN);
            fclose($lock);

            return $backup;
        } catch (\Throwable $e) {
            // If failed, reset the date so it can retry later
            $this->saveSettings([
                'last_auto_backup_date' => null,
            ]);
            flock($lock, LOCK_UN);
            fclose($lock);
            throw $e;
        }
    }
}

