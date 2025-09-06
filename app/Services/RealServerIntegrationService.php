<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Domain;
use App\Models\Database;
use App\Models\EmailAccount;
use App\Models\FtpAccount;

class RealServerIntegrationService
{
    protected $serverConfig;

    public function __construct()
    {
        $this->serverConfig = config('server');
    }

    /**
     * Apache/Nginx Virtual Host Management
     */
    public function createVirtualHost(Domain $domain)
    {
        try {
            $template = $this->getVirtualHostTemplate($domain);
            $configPath = "/etc/apache2/sites-available/{$domain->name}.conf";
            
            // Create virtual host file
            $this->executeServerCommand("echo '{$template}' | sudo tee {$configPath}");
            
            // Enable site
            $this->executeServerCommand("sudo a2ensite {$domain->name}");
            
            // Create document root
            $docRoot = $domain->document_root ?: "/var/www/{$domain->name}";
            $this->executeServerCommand("sudo mkdir -p {$docRoot}");
            $this->executeServerCommand("sudo chown www-data:www-data {$docRoot}");
            
            // Create default index file
            $this->executeServerCommand("echo '<h1>Welcome to {$domain->name}</h1>' | sudo tee {$docRoot}/index.html");
            
            // Reload Apache
            $this->executeServerCommand("sudo systemctl reload apache2");
            
            // Update domain record
            $domain->update([
                'document_root' => $docRoot,
                'status' => 'active',
                'server_config_path' => $configPath
            ]);

            Log::info("Virtual host created for domain: {$domain->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create virtual host for {$domain->name}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteVirtualHost(Domain $domain)
    {
        try {
            // Disable site
            $this->executeServerCommand("sudo a2dissite {$domain->name}");
            
            // Remove config file
            $this->executeServerCommand("sudo rm -f /etc/apache2/sites-available/{$domain->name}.conf");
            
            // Reload Apache
            $this->executeServerCommand("sudo systemctl reload apache2");
            
            Log::info("Virtual host deleted for domain: {$domain->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete virtual host for {$domain->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * MySQL Database Management
     */
    public function createMysqlDatabase(Database $database)
    {
        try {
            $rootPassword = config('database.mysql_root_password');
            
            // Create database
            $this->executeServerCommand("mysql -u root -p{$rootPassword} -e \"CREATE DATABASE {$database->name};\"");
            
            // Create user
            $this->executeServerCommand("mysql -u root -p{$rootPassword} -e \"CREATE USER '{$database->username}'@'localhost' IDENTIFIED BY '{$database->password}';\"");
            
            // Grant privileges
            $this->executeServerCommand("mysql -u root -p{$rootPassword} -e \"GRANT ALL PRIVILEGES ON {$database->name}.* TO '{$database->username}'@'localhost';\"");
            
            // Flush privileges
            $this->executeServerCommand("mysql -u root -p{$rootPassword} -e \"FLUSH PRIVILEGES;\"");
            
            Log::info("MySQL database created: {$database->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create MySQL database {$database->name}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteMysqlDatabase(Database $database)
    {
        try {
            $rootPassword = config('database.mysql_root_password');
            
            // Drop database
            $this->executeServerCommand("mysql -u root -p{$rootPassword} -e \"DROP DATABASE IF EXISTS {$database->name};\"");
            
            // Drop user
            $this->executeServerCommand("mysql -u root -p{$rootPassword} -e \"DROP USER IF EXISTS '{$database->username}'@'localhost';\"");
            
            Log::info("MySQL database deleted: {$database->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete MySQL database {$database->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Email Account Management (Postfix + Dovecot)
     */
    public function createEmailAccount(EmailAccount $email)
    {
        try {
            // Add to virtual mailbox domains
            $this->executeServerCommand("echo '{$email->domain}' >> /etc/postfix/virtual_mailbox_domains");
            
            // Add to virtual mailbox maps
            $this->executeServerCommand("echo '{$email->username}@{$email->domain} {$email->domain}/{$email->username}/' >> /etc/postfix/virtual_mailbox_maps");
            
            // Create system user for email
            $this->executeServerCommand("sudo useradd -m -s /bin/false mail_{$email->username}");
            
            // Create maildir
            $maildir = "/var/mail/vhosts/{$email->domain}/{$email->username}";
            $this->executeServerCommand("sudo mkdir -p {$maildir}");
            $this->executeServerCommand("sudo chown mail:mail {$maildir}");
            
            // Set password
            $hashedPassword = password_hash($email->password, PASSWORD_DEFAULT);
            $this->executeServerCommand("echo '{$email->username}@{$email->domain}:{$hashedPassword}' >> /etc/dovecot/users");
            
            // Reload services
            $this->executeServerCommand("sudo systemctl reload postfix");
            $this->executeServerCommand("sudo systemctl reload dovecot");
            
            Log::info("Email account created: {$email->username}@{$email->domain}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create email account {$email->username}@{$email->domain}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteEmailAccount(EmailAccount $email)
    {
        try {
            // Remove from virtual mailbox maps
            $this->executeServerCommand("sudo sed -i '/{$email->username}@{$email->domain}/d' /etc/postfix/virtual_mailbox_maps");
            
            // Remove from dovecot users
            $this->executeServerCommand("sudo sed -i '/{$email->username}@{$email->domain}/d' /etc/dovecot/users");
            
            // Remove maildir
            $this->executeServerCommand("sudo rm -rf /var/mail/vhosts/{$email->domain}/{$email->username}");
            
            // Remove system user
            $this->executeServerCommand("sudo userdel mail_{$email->username}");
            
            // Reload services
            $this->executeServerCommand("sudo systemctl reload postfix");
            $this->executeServerCommand("sudo systemctl reload dovecot");
            
            Log::info("Email account deleted: {$email->username}@{$email->domain}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete email account {$email->username}@{$email->domain}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * FTP Account Management
     */
    public function createFtpAccount(FtpAccount $ftp)
    {
        try {
            // Create system user
            $homeDir = "/var/www/{$ftp->domain->name}";
            $this->executeServerCommand("sudo useradd -d {$homeDir} -s /bin/false {$ftp->username}");
            
            // Set password
            $this->executeServerCommand("echo '{$ftp->username}:{$ftp->password}' | sudo chpasswd");
            
            // Set directory permissions
            $this->executeServerCommand("sudo chown {$ftp->username}:www-data {$homeDir}");
            $this->executeServerCommand("sudo chmod 755 {$homeDir}");
            
            // Add to vsftpd config if needed
            $this->executeServerCommand("echo '{$ftp->username}' >> /etc/vsftpd.userlist");
            
            // Reload FTP service
            $this->executeServerCommand("sudo systemctl reload vsftpd");
            
            Log::info("FTP account created: {$ftp->username}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create FTP account {$ftp->username}: " . $e->getMessage());
            return false;
        }
    }

    public function deleteFtpAccount(FtpAccount $ftp)
    {
        try {
            // Remove from userlist
            $this->executeServerCommand("sudo sed -i '/{$ftp->username}/d' /etc/vsftpd.userlist");
            
            // Delete system user
            $this->executeServerCommand("sudo userdel {$ftp->username}");
            
            // Reload FTP service
            $this->executeServerCommand("sudo systemctl reload vsftpd");
            
            Log::info("FTP account deleted: {$ftp->username}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to delete FTP account {$ftp->username}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * DNS Zone Management
     */
    public function createDnsZone(Domain $domain)
    {
        try {
            $zoneFile = "/etc/bind/zones/db.{$domain->name}";
            $zoneTemplate = $this->getDnsZoneTemplate($domain);
            
            // Create zone file
            $this->executeServerCommand("echo '{$zoneTemplate}' | sudo tee {$zoneFile}");
            
            // Add zone to named.conf.local
            $namedConfig = "zone \"{$domain->name}\" {\n";
            $namedConfig .= "    type master;\n";
            $namedConfig .= "    file \"{$zoneFile}\";\n";
            $namedConfig .= "};\n";
            
            $this->executeServerCommand("echo '{$namedConfig}' | sudo tee -a /etc/bind/named.conf.local");
            
            // Check configuration
            $this->executeServerCommand("sudo named-checkconf");
            $this->executeServerCommand("sudo named-checkzone {$domain->name} {$zoneFile}");
            
            // Reload BIND
            $this->executeServerCommand("sudo systemctl reload bind9");
            
            Log::info("DNS zone created for domain: {$domain->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to create DNS zone for {$domain->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * SSL Certificate Management
     */
    public function generateSslCertificate(Domain $domain)
    {
        try {
            // Use Let's Encrypt with Certbot
            $command = "sudo certbot --apache -d {$domain->name} --non-interactive --agree-tos --email " . config('app.admin_email');
            $this->executeServerCommand($command);
            
            // Update domain with SSL info
            $domain->update([
                'ssl_enabled' => true,
                'ssl_certificate_path' => "/etc/letsencrypt/live/{$domain->name}/fullchain.pem",
                'ssl_private_key_path' => "/etc/letsencrypt/live/{$domain->name}/privkey.pem",
                'ssl_expiry_date' => now()->addDays(90),
            ]);
            
            Log::info("SSL certificate generated for domain: {$domain->name}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to generate SSL certificate for {$domain->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * System monitoring and health checks
     */
    public function getSystemStats()
    {
        try {
            $stats = [];
            
            // CPU usage
            $cpuUsage = $this->executeServerCommand("top -bn1 | grep 'Cpu(s)' | awk '{print $2}' | sed 's/%us,//'");
            $stats['cpu_usage'] = floatval($cpuUsage);
            
            // Memory usage
            $memInfo = $this->executeServerCommand("free -m | grep '^Mem'");
            preg_match('/\s+(\d+)\s+(\d+)\s+(\d+)/', $memInfo, $matches);
            $stats['memory_total'] = intval($matches[1]);
            $stats['memory_used'] = intval($matches[2]);
            $stats['memory_free'] = intval($matches[3]);
            
            // Disk usage
            $diskUsage = $this->executeServerCommand("df -h / | tail -1 | awk '{print $5}' | sed 's/%//'");
            $stats['disk_usage'] = intval($diskUsage);
            
            // Load average
            $loadAvg = $this->executeServerCommand("uptime | awk -F'load average:' '{print $2}'");
            $stats['load_average'] = trim($loadAvg);
            
            // Active connections
            $connections = $this->executeServerCommand("netstat -an | grep :80 | wc -l");
            $stats['active_connections'] = intval($connections);
            
            return $stats;

        } catch (\Exception $e) {
            Log::error("Failed to get system stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Service management
     */
    public function restartService($service)
    {
        try {
            $allowedServices = ['apache2', 'nginx', 'mysql', 'postfix', 'dovecot', 'vsftpd', 'bind9'];
            
            if (!in_array($service, $allowedServices)) {
                throw new \Exception("Service not allowed: {$service}");
            }
            
            $this->executeServerCommand("sudo systemctl restart {$service}");
            Log::info("Service restarted: {$service}");
            return true;

        } catch (\Exception $e) {
            Log::error("Failed to restart service {$service}: " . $e->getMessage());
            return false;
        }
    }

    public function getServiceStatus($service)
    {
        try {
            $status = $this->executeServerCommand("sudo systemctl is-active {$service}");
            return trim($status) === 'active';

        } catch (\Exception $e) {
            Log::error("Failed to check service status {$service}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute server command
     */
    protected function executeServerCommand($command)
    {
        // In production, this would execute on the actual server
        // For now, we'll simulate or use local execution
        
        if (config('app.env') === 'production') {
            // Execute on remote server via SSH
            return $this->executeRemoteCommand($command);
        } else {
            // Simulate command execution
            Log::info("Simulated command execution: {$command}");
            return "Command executed successfully";
        }
    }

    protected function executeRemoteCommand($command)
    {
        // SSH connection and command execution
        $host = config('server.host');
        $username = config('server.username');
        $keyPath = config('server.ssh_key_path');
        
        $sshCommand = "ssh -i {$keyPath} {$username}@{$host} '{$command}'";
        
        exec($sshCommand, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new \Exception("Command failed: {$command}");
        }
        
        return implode("\n", $output);
    }

    /**
     * Template generators
     */
    protected function getVirtualHostTemplate(Domain $domain)
    {
        return "
<VirtualHost *:80>
    ServerName {$domain->name}
    DocumentRoot {$domain->document_root}
    
    <Directory {$domain->document_root}>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/{$domain->name}_error.log
    CustomLog \${APACHE_LOG_DIR}/{$domain->name}_access.log combined
</VirtualHost>
        ";
    }

    protected function getDnsZoneTemplate(Domain $domain)
    {
        $serverIp = config('server.ip', '127.0.0.1');
        
        return "
\$TTL    604800
@       IN      SOA     {$domain->name}. admin.{$domain->name}. (
                     2024011601         ; Serial
                         604800         ; Refresh
                          86400         ; Retry
                        2419200         ; Expire
                         604800 )       ; Negative Cache TTL

@       IN      NS      ns1.{$domain->name}.
@       IN      NS      ns2.{$domain->name}.
@       IN      A       {$serverIp}
www     IN      A       {$serverIp}
mail    IN      A       {$serverIp}
ftp     IN      A       {$serverIp}
ns1     IN      A       {$serverIp}
ns2     IN      A       {$serverIp}

@       IN      MX      10 mail.{$domain->name}.
        ";
    }
}
