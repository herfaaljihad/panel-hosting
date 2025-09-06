#!/bin/bash
# Script untuk memperbaiki koneksi database Laravel Hosting Panel

echo "🔧 Memperbaiki koneksi database..."

cd /var/www/panel-hosting

# Generate password sederhana tanpa karakter special
DB_PASSWORD="panel$(date +%s)pass"
ADMIN_PASSWORD="admin$(date +%s)"
echo "Password database baru: $DB_PASSWORD"

# Reset database user
echo "[INFO] Resetting database user..."
sudo mysql -e "DROP USER IF EXISTS 'panel_user'@'localhost';" 2>/dev/null || true
sudo mysql -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';" 
sudo mysql -e "CREATE DATABASE IF NOT EXISTS hosting_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# Update .env file menggunakan double quotes untuk menghindari masalah
echo "[INFO] Updating .env configuration..."
sudo -u www-data sed -i 's|DB_CONNECTION=.*|DB_CONNECTION=mysql|' .env
sudo -u www-data sed -i 's|DB_HOST=.*|DB_HOST=127.0.0.1|' .env  
sudo -u www-data sed -i 's|DB_PORT=.*|DB_PORT=3306|' .env
sudo -u www-data sed -i 's|DB_DATABASE=.*|DB_DATABASE=hosting_panel|' .env
sudo -u www-data sed -i 's|DB_USERNAME=.*|DB_USERNAME=panel_user|' .env
sudo -u www-data sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=$DB_PASSWORD|" .env

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
    
    sudo -u www-data php -r "
    require '/var/www/panel-hosting/vendor/autoload.php';
    \$app = require_once '/var/www/panel-hosting/bootstrap/app.php';
    \$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

    try {
        \$admin = \App\Models\User::firstOrCreate(
            ['email' => 'admin@147.139.202.42'],
            [
                'name' => 'Administrator', 
                'password' => bcrypt('$ADMIN_PASSWORD'),
                'email_verified_at' => now(),
                'role' => 'admin'
            ]
        );
        echo 'Admin user created successfully' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Error creating admin user: ' . \$e->getMessage() . PHP_EOL;
    }
    "
        );
        echo 'Admin user created successfully' . PHP_EOL;
    } catch (Exception \$e) {
        echo 'Error creating admin user: ' . \$e->getMessage() . PHP_EOL;
    }
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
    echo "✅ Database fixed successfully!"
    echo "🌐 Panel URL: http://147.139.202.42"
    echo "📧 Email: admin@147.139.202.42"
    echo "🔑 Password: $ADMIN_PASSWORD"
    echo "📁 Credentials saved to: /root/panel-credentials.txt"
    
else
    echo "❌ Database connection failed!"
    echo "Please check MySQL service and try again."
fi
