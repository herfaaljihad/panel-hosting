#!/bin/bash
# Script melanjutkan instalasi Laravel Hosting Panel

echo "🔄 Melanjutkan instalasi dari Step 5..."

# Step 5: Install Composer
echo "[STEP] Step 5/12: Installing Composer..."
if ! command -v composer &> /dev/null; then
    cd /tmp
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

# Step 6: Install Node.js  
echo "[STEP] Step 6/12: Installing Node.js..."
if ! command -v node &> /dev/null; then
    curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

# Step 7: Download project
echo "[STEP] Step 7/12: Downloading Laravel project..."
cd /var/www
sudo rm -rf panel-hosting 2>/dev/null || true
sudo git clone https://github.com/herfaaljihad/panel-hosting.git
sudo chown -R www-data:www-data panel-hosting
cd panel-hosting

# Step 8: Install dependencies
echo "[STEP] Step 8/12: Installing dependencies..."
sudo -u www-data composer install --no-dev --optimize-autoloader

# Fix npm cache permission issue
echo "[INFO] Fixing npm cache permissions..."
sudo chown -R www-data:www-data /var/www/.npm 2>/dev/null || true
sudo chown -R www-data:www-data /var/www/.cache 2>/dev/null || true
sudo chown -R www-data:www-data /var/www/.config 2>/dev/null || true

# Clean npm cache and install fresh
echo "[INFO] Cleaning npm cache..."
sudo rm -rf /var/www/.npm 2>/dev/null || true
sudo mkdir -p /tmp/npm-cache
sudo chown -R www-data:www-data /tmp/npm-cache

# Install and build with proper permissions
echo "[INFO] Installing npm packages..."
sudo -u www-data npm install --cache /tmp/npm-cache --prefer-offline=false
echo "[INFO] Building assets..."
sudo -u www-data npx vite build

# Step 9: Configure Laravel
echo "[STEP] Step 9/12: Configuring Laravel..."
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate --force

# Deteksi IP publik otomatis
echo "[INFO] Detecting public IP address..."
PUBLIC_IP=$(curl -s ifconfig.me || curl -s ipinfo.io/ip || curl -s icanhazip.com)
if [ -z "$PUBLIC_IP" ]; then
    PUBLIC_IP="localhost"
    echo "[WARNING] Could not detect public IP, using localhost"
else
    echo "[INFO] Detected public IP: $PUBLIC_IP"
fi

# Generate secure passwords (timestamp-based untuk menghindari karakter special)
DB_PASSWORD="panel$(date +%s)db"
ADMIN_PASSWORD=$(openssl rand -hex 8)
PANEL_PORT="8080"

echo "[INFO] Panel will be accessible on port: $PANEL_PORT"
echo "[INFO] Generated admin password: $ADMIN_PASSWORD"

# Update .env with proper database configuration
sudo -u www-data sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sudo -u www-data sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env
sudo -u www-data sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sudo -u www-data sed -i 's/DB_DATABASE=.*/DB_DATABASE=hosting_panel/' .env
sudo -u www-data sed -i 's/DB_USERNAME=.*/DB_USERNAME=panel_user/' .env
sudo -u www-data sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
sudo -u www-data sed -i "s|APP_URL=.*|APP_URL=http://$PUBLIC_IP:$PANEL_PORT|" .env
sudo -u www-data sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sudo -u www-data sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env

# Step 10: Setup database user
echo "[STEP] Step 10/12: Setting up database..."

# Install SQLite3 extension for PHP if missing
echo "[INFO] Installing PHP SQLite extension..."
sudo apt-get install -y php8.3-sqlite3 php8.3-mysql

# Get MySQL root password or try without password first
echo "[INFO] Attempting MySQL connection..."
if sudo mysql -u root -e "SELECT 1;" 2>/dev/null; then
    MYSQL_CMD="sudo mysql -u root"
    echo "[INFO] Connected to MySQL using sudo"
elif mysql -u root -e "SELECT 1;" 2>/dev/null; then
    MYSQL_CMD="mysql -u root"
    echo "[INFO] Connected to MySQL without password"
else
    echo "[WARNING] Cannot connect to MySQL as root. Trying with debian-sys-maint..."
    MYSQL_CMD="sudo mysql --defaults-file=/etc/mysql/debian.cnf"
fi

# Create database if not exists
echo "[INFO] Creating database..."
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS hosting_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Create database user with new password
$MYSQL_CMD -e "DROP USER IF EXISTS 'panel_user'@'localhost';" 2>/dev/null || true
$MYSQL_CMD -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
$MYSQL_CMD -e "FLUSH PRIVILEGES;"

# Test database connection
echo "[INFO] Testing database connection..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear

# Test if we can connect with the new user before proceeding
echo "[INFO] Verifying database connection..."
if ! mysql -u panel_user -p$DB_PASSWORD hosting_panel -e "SELECT 1;" 2>/dev/null; then
    echo "[ERROR] Database connection failed. Retrying with different approach..."
    # Try to fix the connection
    $MYSQL_CMD -e "ALTER USER 'panel_user'@'localhost' IDENTIFIED WITH mysql_native_password BY '$DB_PASSWORD';"
    $MYSQL_CMD -e "FLUSH PRIVILEGES;"
fi

# Run migrations and seeds
echo "[INFO] Running database migrations..."
sudo -u www-data php artisan migrate --force
echo "[INFO] Seeding database..."
sudo -u www-data php artisan db:seed --force

# Step 11: Configure Apache
echo "[STEP] Step 11/12: Configuring Apache with custom port..."
sudo tee /etc/apache2/sites-available/panel-hosting.conf > /dev/null <<EOF
Listen $PANEL_PORT

<VirtualHost *:$PANEL_PORT>
    ServerName $PUBLIC_IP
    DocumentRoot /var/www/panel-hosting/public
    
    <Directory /var/www/panel-hosting/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    
    ErrorLog \${APACHE_LOG_DIR}/panel-hosting_error.log
    CustomLog \${APACHE_LOG_DIR}/panel-hosting_access.log combined
</VirtualHost>
EOF

# Add port to Apache ports.conf if not already present
if ! grep -q "Listen $PANEL_PORT" /etc/apache2/ports.conf; then
    echo "Listen $PANEL_PORT" | sudo tee -a /etc/apache2/ports.conf
fi

sudo a2dissite 000-default 2>/dev/null || true
sudo a2ensite panel-hosting
sudo systemctl restart apache2

# Step 12: Set permissions
echo "[STEP] Step 12/12: Setting permissions..."
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo chmod -R 755 /var/www/panel-hosting
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache

# Create admin user using direct PHP script instead of tinker
echo "[INFO] Creating admin user..."
cat > /tmp/create_admin.php << 'EOPHP'
<?php
require '/var/www/panel-hosting/vendor/autoload.php';
$app = require_once '/var/www/panel-hosting/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Use the correct password from environment variable
    $password = '$ADMIN_PASSWORD';
    $email = 'admin@$PUBLIC_IP';
    
    $admin = \App\Models\User::firstOrCreate(
        ['email' => $email],
        [
            'name' => 'Administrator', 
            'password' => bcrypt($password),
            'email_verified_at' => now(),
            'role' => 'admin'
        ]
    );
    echo "Admin user created successfully with email: $email\n";
} catch (Exception $e) {
    echo "Error creating admin user: " . $e->getMessage() . "\n";
}
EOPHP

sudo -u www-data php /tmp/create_admin.php
rm -f /tmp/create_admin.php

# Save credentials
sudo tee /root/panel-credentials.txt > /dev/null <<EOF
=====================================
   LARAVEL HOSTING PANEL CREDENTIALS
=====================================

Panel URL: http://$PUBLIC_IP:$PANEL_PORT
Admin Username: admin
Admin Email: admin@$PUBLIC_IP
Admin Password: $ADMIN_PASSWORD

Database:
- Username: panel_user  
- Password: $DB_PASSWORD

Server Details:
- Public IP: $PUBLIC_IP
- Panel Port: $PANEL_PORT

Installation Date: $(date)
=====================================
EOF

echo ""
echo "🎉 ==============================================="
echo "🎉          INSTALASI BERHASIL COMPLETED!       "
echo "🎉 ==============================================="
echo ""
echo "🌐 Panel URL: http://$PUBLIC_IP:$PANEL_PORT"
echo "� Username: admin"
echo "�📧 Email: admin@$PUBLIC_IP"
echo "🔑 Password: $ADMIN_PASSWORD"
echo ""
echo "📊 Server Info:"
echo "   • Public IP: $PUBLIC_IP"
echo "   • Panel Port: $PANEL_PORT"
echo "   • Database: hosting_panel"
echo ""
echo "📁 Full credentials saved to: /root/panel-credentials.txt"
echo ""
echo "🚀 Anda sekarang bisa mengakses panel hosting di:"
echo "   http://$PUBLIC_IP:$PANEL_PORT"
echo ""
echo "🔐 Login menggunakan:"
echo "   Username: admin"  
echo "   Password: $ADMIN_PASSWORD"
echo ""
echo "🎉 ==============================================="
