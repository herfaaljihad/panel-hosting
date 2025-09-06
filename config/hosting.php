<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Web Server Configuration
    |--------------------------------------------------------------------------
    */
    'web_server' => env('WEB_SERVER', 'apache'), // apache or nginx
    'web_root' => env('WEB_ROOT', '/var/www'),
    
    /*
    |--------------------------------------------------------------------------
    | Apache Configuration
    |--------------------------------------------------------------------------
    */
    'apache_config_path' => env('APACHE_CONFIG_PATH', '/etc/apache2/sites-available'),
    'apache_enabled_path' => env('APACHE_ENABLED_PATH', '/etc/apache2/sites-enabled'),
    
    /*
    |--------------------------------------------------------------------------
    | Nginx Configuration
    |--------------------------------------------------------------------------
    */
    'nginx_config_path' => env('NGINX_CONFIG_PATH', '/etc/nginx/sites-available'),
    'nginx_enabled_path' => env('NGINX_ENABLED_PATH', '/etc/nginx/sites-enabled'),
    
    /*
    |--------------------------------------------------------------------------
    | DNS Configuration
    |--------------------------------------------------------------------------
    */
    'dns_server' => env('DNS_SERVER', 'bind9'), // bind9 or other
    'dns_config_path' => env('DNS_CONFIG_PATH', '/etc/bind'),
    'dns_zone_path' => env('DNS_ZONE_PATH', '/etc/bind/zones'),
    
    /*
    |--------------------------------------------------------------------------
    | FTP Configuration
    |--------------------------------------------------------------------------
    */
    'ftp_server' => env('FTP_SERVER', 'vsftpd'), // vsftpd, proftpd, etc
    'ftp_config_path' => env('FTP_CONFIG_PATH', '/etc/vsftpd.conf'),
    'ftp_user_home_base' => env('FTP_USER_HOME_BASE', '/home'),
    
    /*
    |--------------------------------------------------------------------------
    | Email Configuration
    |--------------------------------------------------------------------------
    */
    'mail_server' => env('MAIL_SERVER', 'postfix'), // postfix, exim, etc
    'mail_config_path' => env('MAIL_CONFIG_PATH', '/etc/postfix'),
    'mail_spool_path' => env('MAIL_SPOOL_PATH', '/var/mail'),
    
    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'mysql_root_password' => env('MYSQL_ROOT_PASSWORD', ''),
    'mysql_config_path' => env('MYSQL_CONFIG_PATH', '/etc/mysql'),
    
    /*
    |--------------------------------------------------------------------------
    | SSL Configuration
    |--------------------------------------------------------------------------
    */
    'ssl_provider' => env('SSL_PROVIDER', 'letsencrypt'), // letsencrypt, selfsigned, custom
    'ssl_cert_path' => env('SSL_CERT_PATH', '/etc/ssl/certs'),
    'ssl_key_path' => env('SSL_KEY_PATH', '/etc/ssl/private'),
    
    /*
    |--------------------------------------------------------------------------
    | Backup Configuration
    |--------------------------------------------------------------------------
    */
    'backup_path' => env('BACKUP_PATH', '/var/backups/hosting'),
    'backup_retention_days' => env('BACKUP_RETENTION_DAYS', 30),
    'backup_compression' => env('BACKUP_COMPRESSION', true),
    
    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    */
    'security_scan_enabled' => env('SECURITY_SCAN_ENABLED', true),
    'firewall_enabled' => env('FIREWALL_ENABLED', true),
    'fail2ban_enabled' => env('FAIL2BAN_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'monitoring_enabled' => env('MONITORING_ENABLED', true),
    'performance_logging' => env('PERFORMANCE_LOGGING', true),
    'resource_limits' => [
        'max_domains_per_user' => env('MAX_DOMAINS_PER_USER', 10),
        'max_databases_per_user' => env('MAX_DATABASES_PER_USER', 10),
        'max_email_accounts_per_user' => env('MAX_EMAIL_ACCOUNTS_PER_USER', 50),
        'max_ftp_accounts_per_user' => env('MAX_FTP_ACCOUNTS_PER_USER', 10),
        'max_disk_space_mb' => env('MAX_DISK_SPACE_MB', 5000), // 5GB default
        'max_bandwidth_mb' => env('MAX_BANDWIDTH_MB', 50000), // 50GB default
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Server IPs
    |--------------------------------------------------------------------------
    */
    'server_ips' => [
        'primary' => env('PRIMARY_SERVER_IP', '127.0.0.1'),
        'secondary' => env('SECONDARY_SERVER_IP', null),
        'ipv6' => env('SERVER_IPV6', null),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Control Panel Configuration
    |--------------------------------------------------------------------------
    */
    'panel_name' => env('PANEL_NAME', 'Hosting Panel'),
    'panel_url' => env('PANEL_URL', 'https://panel.example.com'),
    'support_email' => env('SUPPORT_EMAIL', 'support@example.com'),
    'admin_email' => env('ADMIN_EMAIL', 'admin@example.com'),
    
    /*
    |--------------------------------------------------------------------------
    | Integration Settings
    |--------------------------------------------------------------------------
    */
    'integrations' => [
        'cloudflare' => [
            'enabled' => env('CLOUDFLARE_ENABLED', false),
            'api_token' => env('CLOUDFLARE_API_TOKEN', ''),
            'zone_id' => env('CLOUDFLARE_ZONE_ID', ''),
        ],
        'aws' => [
            'enabled' => env('AWS_ENABLED', false),
            'access_key' => env('AWS_ACCESS_KEY_ID', ''),
            'secret_key' => env('AWS_SECRET_ACCESS_KEY', ''),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
        'whm' => [
            'enabled' => env('WHM_ENABLED', false),
            'url' => env('WHM_URL', ''),
            'username' => env('WHM_USERNAME', ''),
            'token' => env('WHM_TOKEN', ''),
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cron Jobs Configuration
    |--------------------------------------------------------------------------
    */
    'cron' => [
        'enabled' => env('CRON_ENABLED', true),
        'max_jobs_per_user' => env('MAX_CRON_JOBS_PER_USER', 10),
        'min_interval_minutes' => env('MIN_CRON_INTERVAL_MINUTES', 1),
        'timeout_seconds' => env('CRON_TIMEOUT_SECONDS', 300),
        'log_output' => env('CRON_LOG_OUTPUT', true),
    ],
];
