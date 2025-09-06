#!/bin/bash

# 🚀 LARAVEL HOSTING PANEL - UBUNTU INSTALLER
# Quick setup script for Ubuntu 22.04/24.04
# Run: bash install.sh

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

print_header() {
    echo -e "${BLUE}================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}================================${NC}"
}

# Check Ubuntu version
if ! grep -q "Ubuntu" /etc/os-release; then
    print_error "This script is designed for Ubuntu. Exiting."
    exit 1
fi

print_header "🚀 LARAVEL HOSTING PANEL INSTALLER"
print_status "Starting installation on Ubuntu..."

# Update system
print_status "Updating system packages..."
sudo apt update

# Install basic packages
print_status "Installing basic packages..."
sudo apt install -y curl wget git unzip software-properties-common apt-transport-https

# Install PHP 8.3
print_status "Installing PHP 8.3..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath

# Install Apache
print_status "Installing Apache..."
sudo apt install -y apache2
sudo a2enmod rewrite
sudo systemctl enable apache2

# Install MySQL
print_status "Installing MySQL..."
sudo apt install -y mysql-server
sudo systemctl enable mysql

# Secure MySQL installation (automated)
print_status "Securing MySQL installation..."
sudo mysql <<EOF
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'hosting_panel_2024';
CREATE DATABASE hosting_panel;
CREATE USER 'panel_user'@'localhost' IDENTIFIED BY 'panel_password_2024';
GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';
FLUSH PRIVILEGES;
EOF

# Install Composer
print_status "Installing Composer..."
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js
print_status "Installing Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Setup Laravel Panel
print_status "Setting up Laravel Panel..."
cd /var/www
sudo rm -rf panel-hosting 2>/dev/null || true

# Check if we're already in panel-hosting directory
if [[ $(basename "$PWD") == "panel-hosting" ]]; then
    print_status "Already in panel-hosting directory..."
    sudo cp -r . /var/www/panel-hosting/
    cd /var/www/panel-hosting
else
    sudo git clone https://github.com/herfaaljihad/panel-hosting.git
    cd panel-hosting
fi

# Set permissions
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo chmod -R 755 /var/www/panel-hosting
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache

# Install PHP dependencies
print_status "Installing PHP dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install Node dependencies
print_status "Installing Node dependencies..."
sudo -u www-data npm install
sudo -u www-data npm run build

# Setup environment
print_status "Setting up environment..."
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

# Configure .env file
sudo -u www-data tee .env > /dev/null <<EOF
APP_NAME="Laravel Hosting Panel"
APP_ENV=production
APP_KEY=$(sudo -u www-data php artisan key:generate --show)
APP_DEBUG=false
APP_URL=http://$(curl -s ifconfig.me)

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hosting_panel
DB_USERNAME=panel_user
DB_PASSWORD=panel_password_2024

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@$(curl -s ifconfig.me)"
MAIL_FROM_NAME="\${APP_NAME}"
EOF

# Run migrations
print_status "Running database migrations..."
sudo -u www-data php artisan migrate --force --seed

# Create admin user
print_status "Creating admin user..."
sudo -u www-data php artisan admin:create admin@panel.com password123

# Configure Apache Virtual Host
print_status "Configuring Apache Virtual Host..."
sudo tee /etc/apache2/sites-available/panel-hosting.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName $(curl -s ifconfig.me)
    DocumentRoot /var/www/panel-hosting/public
    
    <Directory /var/www/panel-hosting/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/panel-hosting_error.log
    CustomLog \${APACHE_LOG_DIR}/panel-hosting_access.log combined
</VirtualHost>
EOF

# Enable site
sudo a2ensite panel-hosting.conf
sudo a2dissite 000-default.conf
sudo systemctl reload apache2

# Setup cron job
print_status "Setting up cron job..."
(sudo -u www-data crontab -l 2>/dev/null; echo "* * * * * cd /var/www/panel-hosting && php artisan schedule:run >> /dev/null 2>&1") | sudo -u www-data crontab -

# Configure firewall
print_status "Configuring firewall..."
sudo ufw allow 22      # SSH
sudo ufw allow 80      # HTTP
sudo ufw allow 443     # HTTPS
sudo ufw --force enable

# Start services
print_status "Starting services..."
sudo systemctl start apache2 mysql
sudo systemctl enable apache2 mysql

# Final setup
print_status "Final setup..."
sudo systemctl reload apache2

# Get server IP
SERVER_IP=$(curl -s ifconfig.me)

print_header "🎉 INSTALLATION COMPLETED!"
echo -e "${GREEN}✅ Laravel Hosting Panel installed successfully!${NC}"
echo ""
echo -e "${YELLOW}📋 ACCESS INFORMATION:${NC}"
echo -e "🌐 Panel URL: ${GREEN}http://$SERVER_IP/admin${NC}"
echo -e "👤 Username: ${GREEN}admin@panel.com${NC}"
echo -e "🔑 Password: ${GREEN}password123${NC}"
echo ""
echo -e "${YELLOW}📋 DATABASE INFORMATION:${NC}"
echo -e "🗄️  Database: ${GREEN}hosting_panel${NC}"
echo -e "👤 DB User: ${GREEN}panel_user${NC}"
echo -e "🔑 DB Password: ${GREEN}panel_password_2024${NC}"
echo -e "🔑 MySQL Root Password: ${GREEN}hosting_panel_2024${NC}"
echo ""
echo -e "${BLUE}🔧 Next Steps:${NC}"
echo "1. Access the panel: http://$SERVER_IP/admin"
echo "2. Login with the credentials above"
echo "3. Change default passwords in Settings"
echo "4. Configure your domain in Settings"
echo ""
echo -e "${GREEN}Installation completed successfully! 🚀${NC}"
