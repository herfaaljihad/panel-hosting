<?php

return [
    // Server connection settings
    'host' => env('SERVER_HOST', 'localhost'),
    'username' => env('SERVER_USERNAME', 'root'),
    'ssh_key_path' => env('SERVER_SSH_KEY_PATH', '/home/panel/.ssh/id_rsa'),
    'ip' => env('SERVER_IP', '127.0.0.1'),
    
    // MySQL settings
    'mysql_root_password' => env('MYSQL_ROOT_PASSWORD', 'root'),
    
    // Web server settings
    'web_server' => env('WEB_SERVER', 'apache2'), // apache2 or nginx
    'document_root_base' => env('DOCUMENT_ROOT_BASE', '/var/www'),
    
    // Email server settings
    'mail_server' => [
        'type' => env('MAIL_SERVER_TYPE', 'postfix'), // postfix or exim
        'dovecot_enabled' => env('DOVECOT_ENABLED', true),
        'mail_base_path' => env('MAIL_BASE_PATH', '/var/mail/vhosts'),
    ],
    
    // FTP server settings
    'ftp_server' => [
        'type' => env('FTP_SERVER_TYPE', 'vsftpd'), // vsftpd or proftpd
        'config_path' => env('FTP_CONFIG_PATH', '/etc/vsftpd.conf'),
    ],
    
    // DNS server settings
    'dns_server' => [
        'type' => env('DNS_SERVER_TYPE', 'bind9'), // bind9 or pdns
        'config_path' => env('DNS_CONFIG_PATH', '/etc/bind'),
        'zones_path' => env('DNS_ZONES_PATH', '/etc/bind/zones'),
    ],
    
    // SSL settings
    'ssl' => [
        'provider' => env('SSL_PROVIDER', 'letsencrypt'), // letsencrypt or custom
        'certbot_path' => env('CERTBOT_PATH', '/usr/bin/certbot'),
        'auto_renew' => env('SSL_AUTO_RENEW', true),
    ],
    
    // Security settings
    'security' => [
        'fail2ban_enabled' => env('FAIL2BAN_ENABLED', true),
        'firewall_enabled' => env('FIREWALL_ENABLED', true),
        'auto_updates' => env('AUTO_UPDATES_ENABLED', true),
    ],
    
    // Monitoring settings
    'monitoring' => [
        'enabled' => env('MONITORING_ENABLED', true),
        'log_path' => env('MONITORING_LOG_PATH', '/var/log'),
        'stats_interval' => env('MONITORING_STATS_INTERVAL', 300), // seconds
        'alerts_enabled' => env('MONITORING_ALERTS_ENABLED', true),
    ],
    
    // Backup settings
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'storage_path' => env('BACKUP_STORAGE_PATH', storage_path('app/backups')),
        'max_retention_days' => env('BACKUP_MAX_RETENTION_DAYS', 30),
        'compression_enabled' => env('BACKUP_COMPRESSION_ENABLED', true),
    ],
    
    // Resource limits
    'limits' => [
        'max_domains_per_user' => env('MAX_DOMAINS_PER_USER', 10),
        'max_databases_per_user' => env('MAX_DATABASES_PER_USER', 20),
        'max_email_accounts_per_user' => env('MAX_EMAIL_ACCOUNTS_PER_USER', 50),
        'max_disk_space_per_user' => env('MAX_DISK_SPACE_PER_USER', 1073741824), // 1GB in bytes
        'max_bandwidth_per_user' => env('MAX_BANDWIDTH_PER_USER', 10737418240), // 10GB in bytes
    ],
    
    // Performance settings
    'performance' => [
        'cache_enabled' => env('PERFORMANCE_CACHE_ENABLED', true),
        'compression_enabled' => env('PERFORMANCE_COMPRESSION_ENABLED', true),
        'cdn_enabled' => env('PERFORMANCE_CDN_ENABLED', false),
        'optimization_level' => env('PERFORMANCE_OPTIMIZATION_LEVEL', 'medium'), // low, medium, high
    ],
];
