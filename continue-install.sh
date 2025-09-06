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
sudo -u www-data npm install
sudo -u www-data npm run build

# Step 9: Configure Laravel
echo "[STEP] Step 9/12: Configuring Laravel..."
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate --force

# Generate secure passwords
DB_PASSWORD=$(openssl rand -base64 24)
ADMIN_PASSWORD=$(openssl rand -base64 16)

# Update .env
sudo -u www-data sed -i "s/DB_DATABASE=.*/DB_DATABASE=hosting_panel/" .env
sudo -u www-data sed -i "s/DB_USERNAME=.*/DB_USERNAME=panel_user/" .env  
sudo -u www-data sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
sudo -u www-data sed -i "s/APP_URL=.*/APP_URL=http:\/\/147.139.202.42/" .env

# Step 10: Setup database user
echo "[STEP] Step 10/12: Setting up database..."
mysql -u root -e "DROP USER IF EXISTS 'panel_user'@'localhost';" 2>/dev/null || true
mysql -u root -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
mysql -u root -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
mysql -u root -e "FLUSH PRIVILEGES;"

# Run migrations
sudo -u www-data php artisan migrate --force
sudo -u www-data php artisan db:seed --force

# Step 11: Configure Apache
echo "[STEP] Step 11/12: Configuring Apache..."
sudo tee /etc/apache2/sites-available/panel-hosting.conf > /dev/null <<EOF
<VirtualHost *:80>
    ServerName 147.139.202.42
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

sudo a2dissite 000-default 2>/dev/null || true
sudo a2ensite panel-hosting
sudo systemctl restart apache2

# Step 12: Set permissions
echo "[STEP] Step 12/12: Setting permissions..."
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo chmod -R 755 /var/www/panel-hosting
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache

# Create admin user
echo "[INFO] Creating admin user..."
sudo -u www-data php artisan tinker --execute="
\$admin = App\Models\User::firstOrCreate(
    ['email' => 'admin@147.139.202.42'],
    [
        'name' => 'Administrator', 
        'password' => bcrypt('$ADMIN_PASSWORD'),
        'email_verified_at' => now(),
        'role' => 'admin'
    ]
);
echo 'Admin user ready';
"

# Save credentials
sudo tee /root/panel-credentials.txt > /dev/null <<EOF
=====================================
   LARAVEL HOSTING PANEL CREDENTIALS
=====================================

Panel URL: http://147.139.202.42
Admin Email: admin@147.139.202.42
Admin Password: $ADMIN_PASSWORD

Database:
- Username: panel_user  
- Password: $DB_PASSWORD

Installation Date: $(date)
=====================================
EOF

echo ""
echo "✅ Installation completed!"
echo "🌐 Panel URL: http://147.139.202.42"
echo "📧 Email: admin@147.139.202.42"
echo "🔑 Password: $ADMIN_PASSWORD"
echo "📁 Full credentials saved to: /root/panel-credentials.txt"
