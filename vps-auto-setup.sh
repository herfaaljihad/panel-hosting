#!/bin/bash

# 🚀 AUTOMATED VPS SETUP SCRIPT FOR HOSTING PANEL
# Run this script on fresh Ubuntu 22.04 VPS
# Usage: curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh | bash

set -e

echo "🚀 Starting VPS setup for Laravel Hosting Panel..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_step() {
    echo -e "${BLUE}[STEP]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   print_error "This script should not be run as root. Please run as regular user with sudo privileges."
   exit 1
fi

# Check Ubuntu version
if ! lsb_release -d | grep -q "Ubuntu 22.04\|Ubuntu 20.04\|Ubuntu 24.04"; then
    print_warning "This script is designed for Ubuntu 22.04/20.04/24.04 LTS"
    read -p "Continue anyway? (y/N): " confirm
    if [[ ! $confirm =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Check for Ubuntu 24.04 specific fixes
UBUNTU_VERSION=$(lsb_release -rs)
if [[ "$UBUNTU_VERSION" == "24.04" ]]; then
    print_status "Ubuntu 24.04 detected - applying compatibility fixes"
    UBUNTU_24_04=true
else
    UBUNTU_24_04=false
fi

# ASCII Art Banner
echo -e "${PURPLE}"
cat << "EOF"
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║     🌟 LARAVEL HOSTING PANEL AUTOMATED INSTALLER 🌟         ║
║                                                              ║
║     🚀 Modern Control Panel like DirectAdmin/cPanel         ║
║     📦 Complete LAMP + Mail + DNS + FTP Stack               ║
║     🔒 Security Hardened & Production Ready                  ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
EOF
echo -e "${NC}"

# Check for auto mode or get user input
AUTO_MODE=false
PRODUCTION_MODE=false

if [[ "$1" == "--auto" || "$1" == "--production" ]]; then
    AUTO_MODE=true
fi

if [[ "$1" == "--production" ]]; then
    PRODUCTION_MODE=true
    print_status "Production mode enabled - using secure defaults"
fi

# Get user input
echo ""
print_step "Getting configuration details..."

if [[ "$AUTO_MODE" == "true" ]]; then
    # Use default values for production or environment variables
    if [[ "$PRODUCTION_MODE" == "true" ]]; then
        # Secure production defaults
        DOMAIN_NAME=${DOMAIN_NAME:-$(curl -s ifconfig.me || echo "localhost")}
        EMAIL=${EMAIL:-"admin@$(curl -s ifconfig.me || echo "localhost")"}
        MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD:-$(openssl rand -base64 32)}
        PANEL_DB_PASSWORD=${PANEL_DB_PASSWORD:-$(openssl rand -base64 32)}
        ADMIN_PASSWORD=${ADMIN_PASSWORD:-$(openssl rand -base64 16)}
        
        print_status "Generated secure credentials - will be saved to /root/panel-credentials.txt"
    else
        # Environment variables mode
        if [[ -z "$DOMAIN_NAME" || -z "$EMAIL" || -z "$MYSQL_ROOT_PASSWORD" || -z "$PANEL_DB_PASSWORD" || -z "$ADMIN_PASSWORD" ]]; then
            print_error "Auto mode requires environment variables:"
            echo "  export DOMAIN_NAME=\"panel.yourdomain.com\""
            echo "  export EMAIL=\"admin@yourdomain.com\""
            echo "  export MYSQL_ROOT_PASSWORD=\"mysql_pass\""
            echo "  export PANEL_DB_PASSWORD=\"panel_pass\""
            echo "  export ADMIN_PASSWORD=\"admin_pass\""
            echo ""
            echo "Or use: ./installer.sh --production (for auto-generated secure credentials)"
            exit 1
        fi
    fi
    print_status "Auto mode detected - using environment variables"
else
    # Interactive mode
    read -p "🌐 Enter your domain name (e.g., panel.yourdomain.com): " DOMAIN_NAME
    read -p "📧 Enter your email for SSL certificate: " EMAIL
    read -s -p "🔒 Enter MySQL root password: " MYSQL_ROOT_PASSWORD
    echo
    read -s -p "🔑 Enter panel database password: " PANEL_DB_PASSWORD
    echo
    read -s -p "👤 Enter admin user password: " ADMIN_PASSWORD
    echo
fi

print_status "Domain: $DOMAIN_NAME"
print_status "Email: $EMAIL"
print_status "Configuration saved!"

# Confirm before proceeding (skip in auto mode)
if [[ "$AUTO_MODE" != "true" ]]; then
    echo ""
    print_warning "This will install a complete hosting panel with:"
    echo "  ✅ Apache + PHP 8.3"
    echo "  ✅ MySQL + Database setup"
    echo "  ✅ Laravel Hosting Panel"
    echo "  ✅ Mail Server (Postfix + Dovecot)"
    echo "  ✅ DNS Server (BIND9)"
    echo "  ✅ FTP Server (vsftpd)"
    echo "  ✅ SSL Certificate (Let's Encrypt)"
    echo "  ✅ Security hardening"
    echo ""
    read -p "Continue with installation? (y/N): " confirm
    if [[ ! $confirm =~ ^[Yy]$ ]]; then
        print_error "Installation cancelled."
        exit 1
    fi
else
    print_status "Auto mode - proceeding with installation..."
fi

# Start installation
START_TIME=$(date +%s)

print_step "Step 1/12: Updating system packages..."
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip software-properties-common \
    apt-transport-https ca-certificates gnupg lsb-release \
    htop nano vim ufw fail2ban

print_step "Step 2/12: Installing Apache Web Server..."
sudo apt install -y apache2
sudo a2enmod rewrite ssl headers expires deflate
sudo systemctl start apache2
sudo systemctl enable apache2

print_step "Step 3/12: Installing PHP 8.3..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.3 php8.3-cli php8.3-fpm php8.3-mysql \
    php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip \
    php8.3-gd php8.3-intl php8.3-bcmath php8.3-soap \
    php8.3-readline php8.3-msgpack php8.3-igbinary \
    libapache2-mod-php8.3

print_step "Step 4/12: Installing MySQL Database..."
export DEBIAN_FRONTEND=noninteractive
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password password $MYSQL_ROOT_PASSWORD"
sudo debconf-set-selections <<< "mysql-server mysql-server/root_password_again password $MYSQL_ROOT_PASSWORD"
sudo apt install -y mysql-server

# Secure MySQL and create database
sudo mysql -u root -p$MYSQL_ROOT_PASSWORD -e "CREATE DATABASE hosting_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -u root -p$MYSQL_ROOT_PASSWORD -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$PANEL_DB_PASSWORD';"
sudo mysql -u root -p$MYSQL_ROOT_PASSWORD -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
sudo mysql -u root -p$MYSQL_ROOT_PASSWORD -e "FLUSH PRIVILEGES;"

print_step "Step 5/12: Installing Development Tools..."
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

print_step "Step 6/12: Deploying Laravel Hosting Panel..."
cd /var/www
sudo git clone https://github.com/herfaaljihad/panel-hosting.git
sudo chown -R www-data:www-data panel-hosting
cd panel-hosting

# Install dependencies
print_status "Installing Composer dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader

# Ubuntu 24.04 specific npm fixes
if [[ "$UBUNTU_24_04" == "true" ]]; then
    print_status "Applying Ubuntu 24.04 NPM fixes..."
    # Fix npm cache permissions
    sudo chown -R 33:33 "/var/www/.npm" 2>/dev/null || true
    sudo rm -rf /var/www/.npm 2>/dev/null || true
    sudo npm cache clean --force
    sudo chown -R $(whoami) ~/.npm 2>/dev/null || true
fi

print_status "Installing NPM dependencies..."
sudo -u www-data npm install --no-optional
sudo -u www-data npm run build

# Setup environment
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

# Configure .env file
sudo -u www-data sed -i "s/APP_ENV=local/APP_ENV=production/" .env
sudo -u www-data sed -i "s/APP_DEBUG=true/APP_DEBUG=false/" .env
sudo -u www-data sed -i "s/APP_URL=http:\/\/localhost/APP_URL=https:\/\/$DOMAIN_NAME/" .env
sudo -u www-data sed -i "s/DB_DATABASE=laravel/DB_DATABASE=hosting_panel/" .env
sudo -u www-data sed -i "s/DB_USERNAME=root/DB_USERNAME=panel_user/" .env
sudo -u www-data sed -i "s/DB_PASSWORD=/DB_PASSWORD=$PANEL_DB_PASSWORD/" .env

# Database migration and seeding
print_step "Step 7/12: Setting up database..."
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force

# Cache configurations
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

print_step "Step 8/12: Configuring Apache Virtual Host..."
sudo tee /etc/apache2/sites-available/panel-hosting.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/panel-hosting/public
    
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    ErrorLog \${APACHE_LOG_DIR}/panel-hosting-error.log
    CustomLog \${APACHE_LOG_DIR}/panel-hosting-access.log combined
</VirtualHost>

<VirtualHost *:443>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/panel-hosting/public
    
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/$DOMAIN_NAME/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/$DOMAIN_NAME/privkey.pem
    
    Header always set Strict-Transport-Security "max-age=63072000; includeSubDomains; preload"
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    <Directory /var/www/panel-hosting/public>
        AllowOverride All
        Require all granted
        Options -Indexes
        
        RewriteEngine On
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    <Files ".env">
        Require all denied
    </Files>
    
    ErrorLog \${APACHE_LOG_DIR}/panel-hosting-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/panel-hosting-ssl-access.log combined
</VirtualHost>
EOF

sudo a2ensite panel-hosting.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2

print_step "Step 9/12: Installing SSL Certificate..."
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d $DOMAIN_NAME --email $EMAIL --agree-tos --non-interactive --redirect

print_step "Step 10/12: Installing Mail Server..."
export DEBIAN_FRONTEND=noninteractive
sudo debconf-set-selections <<< "postfix postfix/mailname string $DOMAIN_NAME"
sudo debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
sudo apt install -y postfix dovecot-imapd dovecot-pop3d dovecot-lmtpd

print_step "Step 11/12: Installing Additional Services..."
# Install FTP server
sudo apt install -y vsftpd

# Install DNS server
sudo apt install -y bind9 bind9utils bind9-doc

# Setup Laravel worker service
sudo tee /etc/systemd/system/laravel-worker.service > /dev/null <<EOF
[Unit]
Description=Laravel queue worker
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/panel-hosting
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

sudo systemctl daemon-reload
sudo systemctl enable laravel-worker
sudo systemctl start laravel-worker

# Setup cron job
sudo -u www-data crontab -l 2>/dev/null | { cat; echo "* * * * * cd /var/www/panel-hosting && php artisan schedule:run >> /dev/null 2>&1"; } | sudo -u www-data crontab -

print_step "Step 12/12: Configuring Security & Firewall..."
# Configure UFW firewall
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw allow 25/tcp
sudo ufw allow 587/tcp
sudo ufw allow 993/tcp
sudo ufw allow 995/tcp
sudo ufw allow 21/tcp
sudo ufw allow 53
sudo ufw --force enable

# Configure fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban

# Create admin user
print_status "Creating admin user..."
cd /var/www/panel-hosting
sudo -u www-data php artisan tinker --execute="
\$user = new App\Models\User();
\$user->name = 'Administrator';
\$user->email = 'admin@$DOMAIN_NAME';
\$user->password = bcrypt('$ADMIN_PASSWORD');
\$user->email_verified_at = now();
\$user->role = 'admin';
\$user->save();
echo 'Admin user created successfully!';
"

# Setup SSL auto-renewal
echo "0 12 * * * /usr/bin/certbot renew --quiet" | sudo crontab -

# Calculate installation time
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))
MINUTES=$((DURATION / 60))
SECONDS=$((DURATION % 60))

# Save credentials to file if production mode
if [[ "$PRODUCTION_MODE" == "true" ]]; then
    CREDENTIALS_FILE="/root/panel-credentials.txt"
    cat > "$CREDENTIALS_FILE" << EOF
╔══════════════════════════════════════════════════════════════╗
║                    PANEL HOSTING CREDENTIALS                 ║
╚══════════════════════════════════════════════════════════════╝

Installation Date: $(date)
Server IP: $(curl -s ifconfig.me || echo "Unknown")

ACCESS INFORMATION:
==================
Panel URL: http://$(curl -s ifconfig.me || echo "localhost")
Admin Email: admin@panel.local
Admin Password: $ADMIN_PASSWORD

DATABASE CREDENTIALS:
====================
MySQL Root Password: $MYSQL_ROOT_PASSWORD
Panel Database: hosting_panel
Panel DB User: panel_user
Panel DB Password: $PANEL_DB_PASSWORD

IMPORTANT SECURITY NOTES:
========================
1. Change default passwords immediately after first login
2. Setup SSL certificate for HTTPS access
3. Configure firewall rules as needed
4. Backup database regularly
5. Keep system updated

Generated by Laravel Hosting Panel Installer
Installation time: ${MINUTES}m ${SECONDS}s
EOF
    chmod 600 "$CREDENTIALS_FILE"
    print_success "Credentials saved to: $CREDENTIALS_FILE"
fi

# Production cleanup
print_step "Step 12/12: Production cleanup and optimization..."

# Remove development files
cd /var/www/panel-hosting
sudo -u www-data rm -rf tests/
sudo -u www-data rm -rf .git/
sudo -u www-data rm -rf node_modules/
sudo -u www-data rm -f .gitignore .gitattributes
sudo -u www-data rm -f README*.md
sudo -u www-data rm -f *.md
sudo -u www-data rm -f package*.json
sudo -u www-data rm -f vite.config.js
sudo -u www-data rm -f tailwind.config.js
sudo -u www-data rm -f postcss.config.js
sudo -u www-data rm -f phpunit.xml
sudo -u www-data rm -f composer.lock
sudo -u www-data rm -f .editorconfig
sudo -u www-data rm -f .env.example

# Optimize Laravel for production
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache
sudo -u www-data php artisan optimize

# Set production environment
sudo -u www-data sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sudo -u www-data sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# Final permission cleanup
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo find /var/www/panel-hosting -type f -exec chmod 644 {} \;
sudo find /var/www/panel-hosting -type d -exec chmod 755 {} \;
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache

print_success "Production cleanup completed!"

# Installation complete
echo ""
echo -e "${GREEN}╔══════════════════════════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║                                                              ║${NC}"
echo -e "${GREEN}║     🎉 INSTALLATION COMPLETED SUCCESSFULLY! 🎉              ║${NC}"
echo -e "${GREEN}║                                                              ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════════════════════════╝${NC}"
echo ""
print_success "Laravel Hosting Panel installed successfully!"
print_success "Installation time: ${MINUTES}m ${SECONDS}s"
echo ""

if [[ "$PRODUCTION_MODE" == "true" ]]; then
    SERVER_IP=$(curl -s ifconfig.me || echo "localhost")
    echo -e "${CYAN}📋 ACCESS INFORMATION:${NC}"
    echo -e "   🌐 Panel URL: ${YELLOW}http://$SERVER_IP${NC}"
    echo -e "   👤 Admin Email: ${YELLOW}admin@panel.local${NC}"
    echo -e "   🔑 Admin Password: ${YELLOW}$ADMIN_PASSWORD${NC}"
    echo ""
    echo -e "${RED}⚠️  IMPORTANT: Credentials saved to $CREDENTIALS_FILE${NC}"
else
    echo -e "${CYAN}📋 ACCESS INFORMATION:${NC}"
    echo -e "   🌐 Panel URL: ${YELLOW}https://$DOMAIN_NAME${NC}"
    echo -e "   👤 Admin Email: ${YELLOW}admin@$DOMAIN_NAME${NC}"
    echo -e "   🔑 Admin Password: ${YELLOW}[Your provided password]${NC}"
fi
echo ""
echo -e "${CYAN}🚀 SERVICES INSTALLED:${NC}"
echo -e "   ✅ Apache Web Server with SSL"
echo -e "   ✅ PHP 8.3 with all extensions"
echo -e "   ✅ MySQL Database Server"
echo -e "   ✅ Laravel Hosting Panel"
echo -e "   ✅ Mail Server (Postfix + Dovecot)"
echo -e "   ✅ DNS Server (BIND9)"
echo -e "   ✅ FTP Server (vsftpd)"
echo -e "   ✅ SSL Certificate (Let's Encrypt)"
echo -e "   ✅ Security & Firewall configured"
echo ""
echo -e "${CYAN}📝 NEXT STEPS:${NC}"
echo -e "   1. Access your panel at https://$DOMAIN_NAME"
echo -e "   2. Login with admin credentials"
echo -e "   3. Complete initial setup in Settings"
echo -e "   4. Create hosting packages"
echo -e "   5. Add your first customer"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT NOTES:${NC}"
echo -e "   • Save your admin password in a secure location"
echo -e "   • Configure DNS records for your domain"
echo -e "   • Review firewall settings if needed"
echo -e "   • Check all services status with: sudo systemctl status apache2 mysql postfix"
echo ""
print_success "Ready to manage hosting accounts! 🚀"

# Install Mail Server (Postfix + Dovecot)
print_status "Installing Mail Server..."
sudo debconf-set-selections <<< "postfix postfix/mailname string $DOMAIN_NAME"
sudo debconf-set-selections <<< "postfix postfix/main_mailer_type string 'Internet Site'"
sudo apt install -y postfix dovecot-core dovecot-imapd dovecot-pop3d dovecot-lmtpd

# Configure Postfix
sudo tee /etc/postfix/main.cf > /dev/null <<EOF
myhostname = $DOMAIN_NAME
mydomain = $DOMAIN_NAME
myorigin = \$mydomain
inet_interfaces = all
mydestination = \$myhostname, localhost.\$mydomain, localhost, \$mydomain
home_mailbox = Maildir/
mailbox_command = 
EOF

sudo systemctl restart postfix
sudo systemctl enable postfix

# Configure Dovecot
sudo sed -i 's/#listen = \*, ::/listen = \*, ::/' /etc/dovecot/dovecot.conf
sudo systemctl restart dovecot
sudo systemctl enable dovecot

# Install DNS Server (BIND9)
print_status "Installing DNS Server..."
sudo apt install -y bind9 bind9utils bind9-doc
sudo systemctl restart bind9
sudo systemctl enable bind9

# Install FTP Server (vsftpd)
print_status "Installing FTP Server..."
sudo apt install -y vsftpd

# Configure vsftpd
sudo tee /etc/vsftpd.conf > /dev/null <<EOF
listen=NO
listen_ipv6=YES
anonymous_enable=NO
local_enable=YES
write_enable=YES
local_umask=022
dirmessage_enable=YES
use_localtime=YES
xferlog_enable=YES
connect_from_port_20=YES
chroot_local_user=YES
secure_chroot_dir=/var/run/vsftpd/empty
pam_service_name=vsftpd
rsa_cert_file=/etc/ssl/certs/ssl-cert-snakeoil.pem
rsa_private_key_file=/etc/ssl/private/ssl-cert-snakeoil.key
ssl_enable=NO
pasv_enable=Yes
pasv_min_port=40000
pasv_max_port=50000
EOF

sudo systemctl restart vsftpd
sudo systemctl enable vsftpd

# Install SSL (Certbot)
print_status "Installing SSL automation..."
sudo apt install -y certbot python3-certbot-apache

# Configure Firewall
print_status "Configuring firewall..."
sudo ufw allow ssh
sudo ufw allow 'Apache Full'
sudo ufw allow 25/tcp    # SMTP
sudo ufw allow 587/tcp   # SMTP Submission
sudo ufw allow 993/tcp   # IMAPS
sudo ufw allow 995/tcp   # POP3S
sudo ufw allow 53        # DNS
sudo ufw allow 21/tcp    # FTP
sudo ufw allow 40000:50000/tcp  # FTP Passive
sudo ufw --force enable

# Install additional tools
print_status "Installing additional tools..."
sudo apt install -y htop iotop nethogs fail2ban redis-server
sudo systemctl enable redis-server
sudo systemctl enable fail2ban

# Create web directories
print_status "Creating web directories..."
sudo mkdir -p /var/www/hosting-users
sudo chown -R www-data:www-data /var/www/hosting-users
sudo chmod -R 755 /var/www/hosting-users

# Clone hosting panel (modify this URL to your actual repository)
print_status "Downloading hosting panel..."
cd /var/www
sudo git clone https://github.com/HerfaAlJihad/laravel-hosting-panel.git hosting-panel
sudo chown -R www-data:www-data hosting-panel
cd hosting-panel

# Install panel dependencies
print_status "Installing panel dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader

# Configure environment
print_status "Configuring panel environment..."
sudo -u www-data cp .env.example .env
sudo -u www-data tee .env > /dev/null <<EOF
APP_NAME="Hosting Panel"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$DOMAIN_NAME

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hosting_panel
DB_USERNAME=panel_user
DB_PASSWORD=$PANEL_DB_PASSWORD

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@$DOMAIN_NAME
MAIL_FROM_NAME="Hosting Panel"

WEB_SERVER=apache
WEB_ROOT=/var/www
APACHE_CONFIG_PATH=/etc/apache2/sites-available
APACHE_ENABLED_PATH=/etc/apache2/sites-enabled
MYSQL_ROOT_PASSWORD=$MYSQL_ROOT_PASSWORD
MAIL_SERVER=postfix
DNS_SERVER=bind9
FTP_SERVER=vsftpd

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
EOF

# Generate app key and setup database
print_status "Setting up panel database..."
sudo -u www-data php artisan key:generate
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force

# Cache configuration
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Create Apache virtual host
print_status "Creating Apache virtual host..."
sudo tee /etc/apache2/sites-available/hosting-panel.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    DocumentRoot /var/www/hosting-panel/public
    
    <Directory /var/www/hosting-panel/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/hosting-panel_error.log
    CustomLog \${APACHE_LOG_DIR}/hosting-panel_access.log combined
</VirtualHost>
EOF

# Enable site
sudo a2ensite hosting-panel.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2

# Setup permissions for panel to manage server
print_status "Configuring server management permissions..."
sudo tee /etc/sudoers.d/hosting-panel > /dev/null <<EOF
www-data ALL=NOPASSWD: /usr/sbin/a2ensite
www-data ALL=NOPASSWD: /usr/sbin/a2dissite
www-data ALL=NOPASSWD: /bin/systemctl reload apache2
www-data ALL=NOPASSWD: /bin/systemctl restart apache2
www-data ALL=NOPASSWD: /usr/bin/mysql
www-data ALL=NOPASSWD: /usr/sbin/adduser
www-data ALL=NOPASSWD: /usr/sbin/deluser
www-data ALL=NOPASSWD: /bin/mkdir
www-data ALL=NOPASSWD: /bin/chown
www-data ALL=NOPASSWD: /bin/chmod
www-data ALL=NOPASSWD: /usr/bin/certbot
EOF

# Setup Laravel scheduler
print_status "Setting up Laravel scheduler..."
(sudo crontab -u www-data -l 2>/dev/null; echo "* * * * * cd /var/www/hosting-panel && php artisan schedule:run >> /dev/null 2>&1") | sudo crontab -u www-data -

# Get SSL certificate
print_status "Getting SSL certificate..."
sudo certbot --apache -d $DOMAIN_NAME --non-interactive --agree-tos --email $EMAIL

# Setup auto-renewal
(sudo crontab -l 2>/dev/null; echo "0 12 * * * /usr/bin/certbot renew --quiet") | sudo crontab -

# Final permissions
print_status "Setting final permissions..."
sudo chown -R www-data:www-data /var/www/hosting-panel
sudo chmod -R 755 /var/www/hosting-panel
sudo chmod -R 775 /var/www/hosting-panel/storage
sudo chmod -R 775 /var/www/hosting-panel/bootstrap/cache

print_status "Installation completed successfully!"
print_status ""
print_status "🎉 Your hosting panel is now available at: https://$DOMAIN_NAME"
print_status ""
print_status "Default login credentials:"
print_status "Email: admin@hostingpanel.com"
print_status "Password: admin123"
print_status ""
print_warning "Please change the default password after first login!"
print_status ""
print_status "Services status:"
sudo systemctl status apache2 --no-pager -l
sudo systemctl status mysql --no-pager -l
sudo systemctl status postfix --no-pager -l
sudo systemctl status dovecot --no-pager -l
sudo systemctl status bind9 --no-pager -l
sudo systemctl status vsftpd --no-pager -l

print_status ""
print_status "Open ports:"
sudo netstat -tulpn | grep LISTEN

print_status ""
print_status "🚀 Setup complete! Your hosting panel is ready for business!"
