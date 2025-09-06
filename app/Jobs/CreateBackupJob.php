<?php

namespace App\Jobs;

use App\Models\Backup;
use App\Models\Domain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class CreateBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 3;

    public function __construct(
        public Backup $backup
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Starting backup creation', ['backup_id' => $this->backup->id]);

            $this->backup->update(['status' => 'processing']);

            $domain = $this->backup->domain;
            $backupPath = $this->createBackup($domain);

            $this->backup->update([
                'status' => 'completed',
                'file_path' => $backupPath,
                'file_size' => Storage::size($backupPath),
                'completed_at' => now(),
            ]);

            Log::info('Backup creation completed', [
                'backup_id' => $this->backup->id,
                'file_path' => $backupPath,
                'file_size' => Storage::size($backupPath)
            ]);

        } catch (\Exception $e) {
            Log::error('Backup creation failed', [
                'backup_id' => $this->backup->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->backup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function createBackup(Domain $domain): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$domain->name}_{$this->backup->backup_type}_{$timestamp}.zip";
        $tempPath = storage_path("app/temp/{$filename}");
        $storagePath = "backups/{$filename}";

        // Create temp directory if it doesn't exist
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($tempPath, ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create backup file');
        }

        switch ($this->backup->backup_type) {
            case 'full':
                $this->addFilesToBackup($zip, $domain);
                $this->addDatabaseToBackup($zip, $domain);
                break;
            case 'files':
                $this->addFilesToBackup($zip, $domain);
                break;
            case 'database':
                $this->addDatabaseToBackup($zip, $domain);
                break;
        }

        $zip->close();

        // Move to storage
        Storage::put($storagePath, file_get_contents($tempPath));
        unlink($tempPath);

        return $storagePath;
    }

    private function addFilesToBackup(ZipArchive $zip, Domain $domain): void
    {
        // Simulate adding files - in real implementation, this would scan the domain's directory
        $zip->addFromString('files/index.html', '<html><body>Domain: ' . $domain->name . '</body></html>');
        $zip->addFromString('files/.htaccess', 'RewriteEngine On');
        $zip->addFromString('files/config.php', '<?php // Configuration file');
    }

    private function addDatabaseToBackup(ZipArchive $zip, Domain $domain): void
    {
        // Simulate database dump - in real implementation, this would use mysqldump
        $databases = $domain->user->databases;
        
        foreach ($databases as $database) {
            $dumpContent = "-- Database dump for {$database->name}\n";
            $dumpContent .= "-- Generated at: " . now()->toDateTimeString() . "\n\n";
            $dumpContent .= "CREATE DATABASE IF NOT EXISTS `{$database->name}`;\n";
            $dumpContent .= "USE `{$database->name}`;\n\n";
            $dumpContent .= "-- Sample table structure\n";
            $dumpContent .= "CREATE TABLE IF NOT EXISTS `sample_table` (\n";
            $dumpContent .= "  `id` int(11) NOT NULL AUTO_INCREMENT,\n";
            $dumpContent .= "  `name` varchar(255) NOT NULL,\n";
            $dumpContent .= "  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,\n";
            $dumpContent .= "  PRIMARY KEY (`id`)\n";
            $dumpContent .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;\n";

            $zip->addFromString("databases/{$database->name}.sql", $dumpContent);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Backup job failed', [
            'backup_id' => $this->backup->id,
            'exception' => $exception->getMessage()
        ]);

        $this->backup->update([
            'status' => 'failed',
            'error_message' => $exception->getMessage(),
        ]);
    }
}
