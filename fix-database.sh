#!/bin/bash
# Script untuk memperbaiki koneksi database Laravel Hosting Panel

echo "🔧 Memperbaiki koneksi database..."

cd /var/www/panel-hosting

# Deteksi IP publik otomatis
echo "[INFO] Detecting public IP address..."
PUBLIC_IP=$(curl -s ifconfig.me || curl -s ipinfo.io/ip || curl -s icanhazip.com)
if [ -z "$PUBLIC_IP" ]; then
    PUBLIC_IP="localhost"
    echo "[WARNING] Could not detect public IP, using localhost"
else
    echo "[INFO] Detected public IP: $PUBLIC_IP"
fi

# Generate password sederhana tanpa karakter special
DB_PASSWORD="panel$(date +%s)pass"
ADMIN_PASSWORD=$(openssl rand -hex 8)
PANEL_PORT="8080"

echo "Password database baru: $DB_PASSWORD"
echo "Admin password: $ADMIN_PASSWORD"
echo "Panel port: $PANEL_PORT"

# Reset database user and permissions  
echo "[INFO] Resetting database user..."
# Try different MySQL connection methods
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

$MYSQL_CMD -e "DROP USER IF EXISTS 'panel_user'@'localhost';" 2>/dev/null || true
$MYSQL_CMD -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';" 
$MYSQL_CMD -e "CREATE DATABASE IF NOT EXISTS hosting_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
$MYSQL_CMD -e "FLUSH PRIVILEGES;"# Update .env file using safe delimiters
echo "[INFO] Updating .env configuration..."
sudo -u www-data sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sudo -u www-data sed -i 's/DB_HOST=.*/DB_HOST=127.0.0.1/' .env  
sudo -u www-data sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sudo -u www-data sed -i 's/DB_DATABASE=.*/DB_DATABASE=hosting_panel/' .env
sudo -u www-data sed -i 's/DB_USERNAME=.*/DB_USERNAME=panel_user/' .env
sudo -u www-data sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
sudo -u www-data sed -i "s|APP_URL=.*|APP_URL=http://$PUBLIC_IP:$PANEL_PORT|" .env

# Clear cache
echo "[INFO] Clearing Laravel cache..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear

# Test connection
echo "[INFO] Testing database connection..."
if mysql -u panel_user -p$DB_PASSWORD hosting_panel -e "SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection successful!"
    
    # Run migrations
    echo "[INFO] Running migrations..."
    sudo -u www-data php artisan migrate --force
    
    # Run seeds
    echo "[INFO] Seeding database..."
    sudo -u www-data php artisan db:seed --force
    
    # Create admin user
    echo "[INFO] Creating admin user..."
    
    # Update Apache configuration for custom port
    echo "[INFO] Updating Apache configuration..."
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
    
    sudo a2ensite panel-hosting
    sudo systemctl restart apache2
    
    # Create admin user
    echo "[INFO] Creating admin user..."
    cat > /tmp/create_admin.php << 'EOPHP'
<?php
require '/var/www/panel-hosting/vendor/autoload.php';
$app = require_once '/var/www/panel-hosting/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $admin = \App\Models\User::firstOrCreate(
        ['email' => 'admin@$PUBLIC_IP'],
        [
            'name' => 'Administrator', 
            'password' => bcrypt('$ADMIN_PASSWORD'),
            'email_verified_at' => now(),
            'role' => 'admin'
        ]
    );
    echo "Admin user created successfully\n";
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
    echo "🎉        DATABASE FIXED SUCCESSFULLY!         "
    echo "🎉 ==============================================="
    echo ""
    echo "🌐 Panel URL: http://$PUBLIC_IP:$PANEL_PORT"
    echo "👤 Username: admin"
    echo "📧 Email: admin@$PUBLIC_IP"
    echo "🔑 Password: $ADMIN_PASSWORD"
    echo ""
    echo "📊 Server Info:"
    echo "   • Public IP: $PUBLIC_IP"
    echo "   • Panel Port: $PANEL_PORT"
    echo "   • Database: hosting_panel"
    echo ""
    echo "📁 Credentials saved to: /root/panel-credentials.txt"
    echo ""
    echo "🚀 Akses panel hosting di:"
    echo "   http://$PUBLIC_IP:$PANEL_PORT"
    echo ""
    echo "🔐 Login menggunakan:"
    echo "   Username: admin"  
    echo "   Password: $ADMIN_PASSWORD"
    echo ""
    echo "🎉 ==============================================="
    
else
    echo "❌ Database connection failed!"
    echo "Please check MySQL service and try again."
fi
