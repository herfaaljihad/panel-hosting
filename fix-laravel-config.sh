#!/bin/bash
# Fix Laravel configuration to use correct database user

echo "🔧 Fixing Laravel database configuration..."

# Navigate to project directory
cd /var/www/panel-hosting

# Generate new database password
DB_PASSWORD="panel$(date +%s)fix"
ADMIN_PASSWORD=$(openssl rand -hex 8)
PANEL_PORT="8080"

# Detect public IP
PUBLIC_IP=$(curl -s ifconfig.me || curl -s ipinfo.io/ip || curl -s icanhazip.com)
if [ -z "$PUBLIC_IP" ]; then
    PUBLIC_IP="localhost"
fi

echo "[INFO] Using IP: $PUBLIC_IP"
echo "[INFO] Database password: $DB_PASSWORD"
echo "[INFO] Admin password: $ADMIN_PASSWORD"

# Connect to MySQL
if sudo mysql -u root -e "SELECT 1;" 2>/dev/null; then
    MYSQL_CMD="sudo mysql -u root"
elif mysql -u root -e "SELECT 1;" 2>/dev/null; then
    MYSQL_CMD="mysql -u root"
else
    MYSQL_CMD="sudo mysql --defaults-file=/etc/mysql/debian.cnf"
fi

# Reset database user
echo "[INFO] Resetting database user..."
$MYSQL_CMD -e "DROP USER IF EXISTS 'panel_user'@'localhost';" 2>/dev/null || true
$MYSQL_CMD -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY '$DB_PASSWORD';"
$MYSQL_CMD -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
$MYSQL_CMD -e "FLUSH PRIVILEGES;"

# Create a completely new .env file to ensure clean configuration
echo "[INFO] Creating new .env file..."
sudo -u www-data tee .env > /dev/null <<EOF
APP_NAME="Laravel Hosting Panel"
APP_ENV=production
APP_KEY=$(sudo -u www-data php artisan --version > /dev/null 2>&1 && sudo -u www-data php artisan key:generate --show || echo "base64:$(openssl rand -base64 32)")
APP_DEBUG=false
APP_TIMEZONE=UTC
APP_URL=http://$PUBLIC_IP:$PANEL_PORT

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hosting_panel
DB_USERNAME=panel_user
DB_PASSWORD=$DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=database
CACHE_PREFIX=

MEMCACHED_HOST=127.0.0.1

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="\${APP_NAME}"
EOF

# Generate application key if not set
echo "[INFO] Generating application key..."
sudo -u www-data php artisan key:generate --force

# Clear all caches completely
echo "[INFO] Clearing all Laravel caches..."
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan route:clear
sudo -u www-data php artisan view:clear

# Test database connection
echo "[INFO] Testing database connection..."
if mysql -u panel_user -p$DB_PASSWORD hosting_panel -e "SELECT 1;" 2>/dev/null; then
    echo "✅ Database connection successful!"
else
    echo "❌ Database connection failed!"
    exit 1
fi

# Run migrations fresh
echo "[INFO] Running fresh migrations..."
sudo -u www-data php artisan migrate:fresh --force

# Seed database
echo "[INFO] Seeding database..."
sudo -u www-data php artisan db:seed --force

# Create admin user using artisan command
echo "[INFO] Creating admin user..."
sudo -u www-data php artisan tinker --execute="
\$admin = \App\Models\User::firstOrCreate([
    'email' => 'admin@$PUBLIC_IP'
], [
    'name' => 'Administrator',
    'password' => bcrypt('$ADMIN_PASSWORD'),
    'email_verified_at' => now(),
    'role' => 'admin'
]);
echo 'Admin user created: ' . \$admin->email;
"

# Restart Apache
echo "[INFO] Restarting Apache..."
sudo systemctl restart apache2

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
echo "🎉        CONFIGURATION FIXED SUCCESSFULLY!    "
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
