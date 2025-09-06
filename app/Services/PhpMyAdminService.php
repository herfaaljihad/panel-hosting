<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Database as DatabaseModel;

class PhpMyAdminService
{
    protected string $phpMyAdminUrl;
    protected string $configPath;
    protected string $tempPath;

    public function __construct()
    {
        $this->phpMyAdminUrl = config('hosting.phpmyadmin_url', 'http://localhost/phpmyadmin');
        $this->configPath = config('hosting.phpmyadmin_config', '/etc/phpmyadmin/config.inc.php');
        $this->tempPath = storage_path('app/temp');
    }

    /**
     * Generate phpMyAdmin single sign-on URL
     */
    public function generateSsoUrl(string $databaseName, string $username): string
    {
        $token = $this->generateSsoToken($databaseName, $username);
        $params = [
            'pma_username' => $username,
            'pma_password' => '', // Will be handled by SSO
            'server' => 1,
            'target' => 'index.php',
            'token' => $token
        ];

        return $this->phpMyAdminUrl . '/?' . http_build_query($params);
    }

    /**
     * Generate secure SSO token
     */
    private function generateSsoToken(string $databaseName, string $username): string
    {
        $data = [
            'username' => $username,
            'database' => $databaseName,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ];

        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return $payload . '.' . $signature;
    }

    /**
     * Verify SSO token
     */
    public function verifySsoToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);
        
        // Check if token is not expired (5 minutes)
        if (time() - $data['timestamp'] > 300) {
            return null;
        }

        return $data;
    }

    /**
     * Create phpMyAdmin configuration for user
     */
    public function createUserConfig(string $username, string $password, array $databases): bool
    {
        try {
            $config = $this->generatePhpMyAdminConfig($username, $password, $databases);
            $configFile = $this->tempPath . "/pma_config_{$username}.inc.php";
            
            if (!file_exists($this->tempPath)) {
                mkdir($this->tempPath, 0755, true);
            }

            file_put_contents($configFile, $config);
            
            Log::channel('performance')->info('phpMyAdmin config created', [
                'username' => $username,
                'databases' => count($databases)
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('phpMyAdmin config creation failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate phpMyAdmin configuration content
     */
    private function generatePhpMyAdminConfig(string $username, string $password, array $databases): string
    {
        $allowedDatabases = implode("', '", $databases);
        
        return "<?php
/**
 * phpMyAdmin configuration for user: {$username}
 * Generated on: " . date('Y-m-d H:i:s') . "
 */

\$cfg['blowfish_secret'] = '" . config('app.key') . "';

\$i = 0;

// Server configuration
\$i++;
\$cfg['Servers'][\$i]['auth_type'] = 'config';
\$cfg['Servers'][\$i]['host'] = '" . config('database.connections.mysql.host') . "';
\$cfg['Servers'][\$i]['port'] = '" . config('database.connections.mysql.port') . "';
\$cfg['Servers'][\$i]['user'] = '{$username}';
\$cfg['Servers'][\$i]['password'] = '{$password}';
\$cfg['Servers'][\$i]['only_db'] = array('{$allowedDatabases}');
\$cfg['Servers'][\$i]['hide_db'] = '^(information_schema|performance_schema|mysql|sys)\$';

// User interface settings
\$cfg['DefaultLang'] = 'en';
\$cfg['ServerDefault'] = 1;
\$cfg['UploadDir'] = '';
\$cfg['SaveDir'] = '';
\$cfg['MaxNavigationItems'] = 50;
\$cfg['NavigationTreePointerEnable'] = true;
\$cfg['BrowsePointerEnable'] = true;
\$cfg['BrowseMarkerEnable'] = true;
\$cfg['TextareaCols'] = 40;
\$cfg['TextareaRows'] = 15;
\$cfg['CharEditing'] = 'input';
\$cfg['CharTextareaCols'] = 40;
\$cfg['CharTextareaRows'] = 7;
\$cfg['MaxRows'] = 25;
\$cfg['Order'] = 'ASC';
\$cfg['DefaultDisplay'] = 'horizontal';
\$cfg['GridEditing'] = 'click';

// Security settings
\$cfg['CheckConfigurationPermissions'] = false;
\$cfg['AllowArbitraryServer'] = false;
\$cfg['LoginCookieRecall'] = false;
\$cfg['LoginCookieValidity'] = 1440;
\$cfg['LoginCookieStore'] = 0;
\$cfg['LoginCookieDeleteAll'] = true;

// Export/Import settings
\$cfg['Export']['lock_tables'] = true;
\$cfg['Export']['csv_enclosed'] = '\"';
\$cfg['Export']['csv_escaped'] = '\"';
\$cfg['Import']['charset'] = 'utf-8';

// Theme
\$cfg['ThemeDefault'] = 'pmahomme';
?>";
    }

    /**
     * Generate database access URL with auto-login
     */
    public function getDatabaseAccessUrl(DatabaseModel $database): string
    {
        $user = $database->user;
        $username = $this->getDatabaseUsername($database);
        
        // Create temporary access token
        $accessToken = $this->createAccessToken($database->id, $user->id);
        
        return route('phpmyadmin.access', [
            'database' => $database->id,
            'token' => $accessToken
        ]);
    }

    /**
     * Create temporary access token
     */
    private function createAccessToken(int $databaseId, int $userId): string
    {
        $data = [
            'database_id' => $databaseId,
            'user_id' => $userId,
            'timestamp' => time(),
            'nonce' => bin2hex(random_bytes(16))
        ];

        $payload = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $payload, config('app.key'));

        return $payload . '.' . $signature;
    }

    /**
     * Verify access token
     */
    public function verifyAccessToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;
        $expectedSignature = hash_hmac('sha256', $payload, config('app.key'));

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $data = json_decode(base64_decode($payload), true);
        
        // Check if token is not expired (10 minutes)
        if (time() - $data['timestamp'] > 600) {
            return null;
        }

        return $data;
    }

    /**
     * Execute SQL query through phpMyAdmin API
     */
    public function executeQuery(DatabaseModel $database, string $query): array
    {
        try {
            // Switch to the specific database
            $this->switchDatabase($database->name);
            
            $result = DB::select($query);
            
            Log::channel('audit')->info('SQL query executed via phpMyAdmin', [
                'database' => $database->name,
                'user_id' => $database->user_id,
                'query' => substr($query, 0, 100) . '...'
            ]);

            return [
                'success' => true,
                'data' => $result,
                'affected_rows' => count($result)
            ];
        } catch (\Exception $e) {
            Log::error('SQL query execution failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'affected_rows' => 0
            ];
        }
    }

    /**
     * Get database schema information
     */
    public function getDatabaseSchema(DatabaseModel $database): array
    {
        try {
            $this->switchDatabase($database->name);
            
            $tables = DB::select("SHOW TABLES");
            $schema = [];
            
            foreach ($tables as $table) {
                $tableName = reset($table);
                $columns = DB::select("DESCRIBE `{$tableName}`");
                $indexes = DB::select("SHOW INDEX FROM `{$tableName}`");
                
                $schema[$tableName] = [
                    'columns' => $columns,
                    'indexes' => $indexes,
                    'row_count' => DB::table($tableName)->count()
                ];
            }

            return $schema;
        } catch (\Exception $e) {
            Log::error('Database schema retrieval failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Export database
     */
    public function exportDatabase(DatabaseModel $database, string $format = 'sql'): ?string
    {
        try {
            $exportPath = $this->tempPath . "/export_{$database->name}_" . time() . ".{$format}";
            
            if (!file_exists($this->tempPath)) {
                mkdir($this->tempPath, 0755, true);
            }

            switch ($format) {
                case 'sql':
                    return $this->exportToSql($database, $exportPath);
                case 'csv':
                    return $this->exportToCsv($database, $exportPath);
                default:
                    throw new \Exception('Unsupported export format');
            }
        } catch (\Exception $e) {
            Log::error('Database export failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Import database from file
     */
    public function importDatabase(DatabaseModel $database, string $filePath): bool
    {
        try {
            if (!file_exists($filePath)) {
                throw new \Exception('Import file not found');
            }

            $content = file_get_contents($filePath);
            $extension = pathinfo($filePath, PATHINFO_EXTENSION);

            switch ($extension) {
                case 'sql':
                    return $this->importFromSql($database, $content);
                case 'csv':
                    return $this->importFromCsv($database, $content);
                default:
                    throw new \Exception('Unsupported import format');
            }
        } catch (\Exception $e) {
            Log::error('Database import failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database size
     */
    public function getDatabaseSize(DatabaseModel $database): array
    {
        try {
            $sizeQuery = "
                SELECT 
                    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                    ROUND(SUM(data_length) / 1024 / 1024, 2) AS data_mb,
                    ROUND(SUM(index_length) / 1024 / 1024, 2) AS index_mb,
                    COUNT(*) AS table_count
                FROM information_schema.tables 
                WHERE table_schema = ?
            ";

            $result = DB::select($sizeQuery, [$database->name]);
            
            return [
                'size_mb' => $result[0]->size_mb ?? 0,
                'data_mb' => $result[0]->data_mb ?? 0,
                'index_mb' => $result[0]->index_mb ?? 0,
                'table_count' => $result[0]->table_count ?? 0
            ];
        } catch (\Exception $e) {
            Log::error('Database size calculation failed: ' . $e->getMessage());
            return ['size_mb' => 0, 'data_mb' => 0, 'index_mb' => 0, 'table_count' => 0];
        }
    }

    // Helper methods
    private function switchDatabase(string $databaseName): void
    {
        DB::statement("USE `{$databaseName}`");
    }

    private function getDatabaseUsername(DatabaseModel $database): string
    {
        return 'user_' . $database->user_id . '_' . $database->id;
    }

    private function exportToSql(DatabaseModel $database, string $exportPath): string
    {
        $command = sprintf(
            'mysqldump -h %s -P %s -u %s -p%s %s > %s',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.port'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            $database->name,
            $exportPath
        );

        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($exportPath)) {
            return $exportPath;
        }
        
        throw new \Exception('SQL export failed: ' . implode("\n", $output));
    }

    private function exportToCsv(DatabaseModel $database, string $exportPath): string
    {
        $this->switchDatabase($database->name);
        $tables = DB::select("SHOW TABLES");
        $zip = new \ZipArchive();
        
        $zipPath = str_replace('.csv', '.zip', $exportPath);
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
            throw new \Exception('Cannot create ZIP file');
        }

        foreach ($tables as $table) {
            $tableName = reset($table);
            $rows = DB::table($tableName)->get();
            
            $csvContent = $this->arrayToCsv($rows->toArray());
            $zip->addFromString("{$tableName}.csv", $csvContent);
        }

        $zip->close();
        return $zipPath;
    }

    private function importFromSql(DatabaseModel $database, string $content): bool
    {
        $this->switchDatabase($database->name);
        
        // Split SQL commands and execute them one by one
        $commands = array_filter(array_map('trim', explode(';', $content)));
        
        foreach ($commands as $command) {
            if (!empty($command)) {
                DB::statement($command);
            }
        }

        return true;
    }

    private function importFromCsv(DatabaseModel $database, string $content): bool
    {
        // This is a simplified CSV import - would need table structure info
        throw new \Exception('CSV import not yet implemented');
    }

    private function arrayToCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'w');
        
        // Add headers
        fputcsv($output, array_keys((array)$data[0]));
        
        // Add data
        foreach ($data as $row) {
            fputcsv($output, (array)$row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }
}
