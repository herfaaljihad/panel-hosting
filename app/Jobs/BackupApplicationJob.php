<?php

namespace App\Jobs;

use App\Models\AppInstallation;
use App\Models\Backup;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use ZipArchive;

class BackupApplicationJob implements ShouldQueue
{
    use Queueable;

    public AppInstallation $installation;

    public function __construct(AppInstallation $installation)
    {
        $this->installation = $installation;
    }

    public function handle(): void
    {
        try {
            $backupName = $this->installation->app_name . '_' . $this->installation->domain->name . '_' . date('Y-m-d_H-i-s');
            $backupPath = storage_path('app/backups/' . $backupName . '.zip');
            
            // Create backup directory
            File::ensureDirectoryExists(dirname($backupPath));

            $zip = new ZipArchive();
            if ($zip->open($backupPath, ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Cannot create backup archive');
            }

            // Backup files
            $installationPath = $this->getInstallationPath();
            if (File::exists($installationPath)) {
                $this->addDirectoryToZip($zip, $installationPath, '');
            }

            // Backup database
            if ($this->installation->database_name) {
                $sqlDump = $this->createDatabaseDump();
                $zip->addFromString('database.sql', $sqlDump);
            }

            // Add metadata
            $metadata = [
                'app_name' => $this->installation->app_name,
                'app_version' => $this->installation->app_version,
                'domain' => $this->installation->domain->name,
                'backup_date' => now()->toISOString(),
                'installation_path' => $this->installation->installation_path
            ];
            $zip->addFromString('backup_info.json', json_encode($metadata, JSON_PRETTY_PRINT));

            $zip->close();

            // Create backup record
            Backup::create([
                'user_id' => $this->installation->user_id,
                'backup_type' => 'application',
                'file_path' => $backupPath,
                'file_size' => filesize($backupPath),
                'status' => 'completed',
                'description' => "Backup of {$this->installation->app_name} on {$this->installation->domain->name}"
            ]);

            Log::info('Application backup completed', [
                'app' => $this->installation->app_name,
                'backup_file' => $backupName
            ]);

        } catch (\Exception $e) {
            Log::error('Application backup failed', [
                'error' => $e->getMessage(),
                'app' => $this->installation->app_name
            ]);
        }
    }

    private function getInstallationPath(): string
    {
        return storage_path('app/domains/' . $this->installation->domain->name . '/' . $this->installation->installation_path);
    }

    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $base): void
    {
        $files = File::allFiles($dir);
        
        foreach ($files as $file) {
            $relativePath = str_replace($dir . '/', '', $file->getPathname());
            $zip->addFile($file->getPathname(), $base . $relativePath);
        }
    }

    private function createDatabaseDump(): string
    {
        $databaseName = $this->installation->database_name;
        $tables = DB::select("SHOW TABLES FROM `{$databaseName}`");
        
        $dump = "-- Database dump for {$databaseName}\n";
        $dump .= "-- Generated on " . now() . "\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            
            // Get CREATE TABLE statement
            $createTable = DB::select("SHOW CREATE TABLE `{$databaseName}`.`{$tableName}`")[0];
            $dump .= $createTable->{'Create Table'} . ";\n\n";
            
            // Get table data
            $rows = DB::connection()->table($databaseName . '.' . $tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function($value) {
                    return is_null($value) ? 'NULL' : "'" . addslashes($value) . "'";
                }, (array)$row);
                
                $dump .= "INSERT INTO `{$tableName}` VALUES (" . implode(', ', $values) . ");\n";
            }
            $dump .= "\n";
        }

        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $dump;
    }
}
