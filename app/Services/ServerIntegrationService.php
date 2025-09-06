<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ServerIntegrationService
{
    protected string $webRoot;
    protected string $apacheConfigPath;
    protected string $nginxConfigPath;
    protected string $dnsConfigPath;

    public function __construct()
    {
        $this->webRoot = config('hosting.web_root', '/var/www');
        $this->apacheConfigPath = config('hosting.apache_config_path', '/etc/apache2/sites-available');
        $this->nginxConfigPath = config('hosting.nginx_config_path', '/etc/nginx/sites-available');
        $this->dnsConfigPath = config('hosting.dns_config_path', '/etc/bind');
    }

    /**
     * Create Apache virtual host
     */
    public function createApacheVirtualHost(string $domain, string $documentRoot): bool
    {
        try {
            $config = $this->generateApacheConfig($domain, $documentRoot);
            $configPath = "{$this->apacheConfigPath}/{$domain}.conf";
            
            file_put_contents($configPath, $config);
            
            // Enable the site
            $result = Process::run("a2ensite {$domain}.conf");
            if (!$result->successful()) {
                throw new \Exception("Failed to enable Apache site: " . $result->errorOutput());
            }
            
            // Reload Apache
            $result = Process::run("systemctl reload apache2");
            if (!$result->successful()) {
                throw new \Exception("Failed to reload Apache: " . $result->errorOutput());
            }
            
            Log::channel('performance')->info('Apache virtual host created', [
                'domain' => $domain,
                'document_root' => $documentRoot
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to create Apache virtual host', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create Nginx server block
     */
    public function createNginxServerBlock(string $domain, string $documentRoot): bool
    {
        try {
            $config = $this->generateNginxConfig($domain, $documentRoot);
            $configPath = "{$this->nginxConfigPath}/{$domain}";
            
            file_put_contents($configPath, $config);
            
            // Create symlink to sites-enabled
            $enabledPath = str_replace('sites-available', 'sites-enabled', $configPath);
            if (!file_exists($enabledPath)) {
                symlink($configPath, $enabledPath);
            }
            
            // Test Nginx configuration
            $result = Process::run("nginx -t");
            if (!$result->successful()) {
                throw new \Exception("Nginx configuration test failed: " . $result->errorOutput());
            }
            
            // Reload Nginx
            $result = Process::run("systemctl reload nginx");
            if (!$result->successful()) {
                throw new \Exception("Failed to reload Nginx: " . $result->errorOutput());
            }
            
            Log::channel('performance')->info('Nginx server block created', [
                'domain' => $domain,
                'document_root' => $documentRoot
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to create Nginx server block', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create DNS zone
     */
    public function createDnsZone(string $domain, string $ip): bool
    {
        try {
            $zoneConfig = $this->generateDnsZoneConfig($domain, $ip);
            $zonePath = "{$this->dnsConfigPath}/db.{$domain}";
            
            file_put_contents($zonePath, $zoneConfig);
            
            // Add zone to named.conf.local
            $namedConfig = "\nzone \"{$domain}\" {\n    type master;\n    file \"/etc/bind/db.{$domain}\";\n};\n";
            file_put_contents('/etc/bind/named.conf.local', $namedConfig, FILE_APPEND);
            
            // Check DNS configuration
            $result = Process::run("named-checkconf");
            if (!$result->successful()) {
                throw new \Exception("DNS configuration check failed: " . $result->errorOutput());
            }
            
            // Reload DNS
            $result = Process::run("systemctl reload bind9");
            if (!$result->successful()) {
                throw new \Exception("Failed to reload DNS: " . $result->errorOutput());
            }
            
            Log::channel('performance')->info('DNS zone created', [
                'domain' => $domain,
                'ip' => $ip
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to create DNS zone', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create FTP user
     */
    public function createFtpUser(string $username, string $password, string $homeDir): bool
    {
        try {
            // Create system user
            $result = Process::run("useradd -m -d {$homeDir} -s /bin/bash {$username}");
            if (!$result->successful() && !str_contains($result->errorOutput(), 'already exists')) {
                throw new \Exception("Failed to create user: " . $result->errorOutput());
            }
            
            // Set password
            $result = Process::run("echo '{$username}:{$password}' | chpasswd");
            if (!$result->successful()) {
                throw new \Exception("Failed to set password: " . $result->errorOutput());
            }
            
            // Set directory permissions
            $result = Process::run("chown -R {$username}:{$username} {$homeDir}");
            if (!$result->successful()) {
                throw new \Exception("Failed to set permissions: " . $result->errorOutput());
            }
            
            Log::channel('performance')->info('FTP user created', [
                'username' => $username,
                'home_dir' => $homeDir
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to create FTP user', [
                'username' => $username,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Create MySQL database
     */
    public function createMysqlDatabase(string $dbName, string $username, string $password): bool
    {
        try {
            $rootPassword = config('database.mysql_root_password');
            
            // Create database
            $result = Process::run("mysql -u root -p{$rootPassword} -e \"CREATE DATABASE IF NOT EXISTS {$dbName};\"");
            if (!$result->successful()) {
                throw new \Exception("Failed to create database: " . $result->errorOutput());
            }
            
            // Create user and grant privileges
            $result = Process::run("mysql -u root -p{$rootPassword} -e \"CREATE USER IF NOT EXISTS '{$username}'@'localhost' IDENTIFIED BY '{$password}';\"");
            if (!$result->successful()) {
                throw new \Exception("Failed to create user: " . $result->errorOutput());
            }
            
            $result = Process::run("mysql -u root -p{$rootPassword} -e \"GRANT ALL PRIVILEGES ON {$dbName}.* TO '{$username}'@'localhost';\"");
            if (!$result->successful()) {
                throw new \Exception("Failed to grant privileges: " . $result->errorOutput());
            }
            
            $result = Process::run("mysql -u root -p{$rootPassword} -e \"FLUSH PRIVILEGES;\"");
            if (!$result->successful()) {
                throw new \Exception("Failed to flush privileges: " . $result->errorOutput());
            }
            
            Log::channel('performance')->info('MySQL database created', [
                'database' => $dbName,
                'username' => $username
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to create MySQL database', [
                'database' => $dbName,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate SSL certificate using Let's Encrypt
     */
    public function generateSslCertificate(string $domain): bool
    {
        try {
            $result = Process::run("certbot --apache -d {$domain} --non-interactive --agree-tos --email admin@{$domain}");
            if (!$result->successful()) {
                throw new \Exception("Failed to generate SSL certificate: " . $result->errorOutput());
            }
            
            Log::channel('performance')->info('SSL certificate generated', [
                'domain' => $domain
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::channel('security')->error('Failed to generate SSL certificate', [
                'domain' => $domain,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate Apache configuration
     */
    private function generateApacheConfig(string $domain, string $documentRoot): string
    {
        return "
<VirtualHost *:80>
    ServerName {$domain}
    ServerAlias www.{$domain}
    DocumentRoot {$documentRoot}
    
    <Directory {$documentRoot}>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/{$domain}_error.log
    CustomLog \${APACHE_LOG_DIR}/{$domain}_access.log combined
</VirtualHost>
";
    }

    /**
     * Generate Nginx configuration
     */
    private function generateNginxConfig(string $domain, string $documentRoot): string
    {
        return "
server {
    listen 80;
    listen [::]:80;
    
    server_name {$domain} www.{$domain};
    root {$documentRoot};
    index index.php index.html index.htm;
    
    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
    
    access_log /var/log/nginx/{$domain}_access.log;
    error_log /var/log/nginx/{$domain}_error.log;
}
";
    }

    /**
     * Generate DNS zone configuration
     */
    private function generateDnsZoneConfig(string $domain, string $ip): string
    {
        $serial = date('Ymd') . '01';
        
        return "
\$TTL    604800
@       IN      SOA     {$domain}. admin.{$domain}. (
                     {$serial}         ; Serial
                         604800         ; Refresh
                          86400         ; Retry
                        2419200         ; Expire
                         604800 )       ; Negative Cache TTL
;
@       IN      NS      ns1.{$domain}.
@       IN      A       {$ip}
www     IN      A       {$ip}
ns1     IN      A       {$ip}
";
    }
}
