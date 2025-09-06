#!/bin/bash
# Diagnose and fix Apache port 8080 issue

echo "🔍 Diagnosing Apache and port 8080 issues..."

# Check if Apache is running
echo "[CHECK] Apache service status:"
sudo systemctl status apache2 --no-pager -l

# Check if port 8080 is listening
echo ""
echo "[CHECK] Checking if port 8080 is listening:"
sudo netstat -tlnp | grep :8080 || echo "❌ Port 8080 is NOT listening"

# Check Apache configuration
echo ""
echo "[CHECK] Apache configuration for port 8080:"
sudo grep -r "Listen 8080" /etc/apache2/ || echo "❌ No Listen 8080 found in Apache config"

# Check if site is enabled
echo ""
echo "[CHECK] Enabled Apache sites:"
sudo a2ensite --list

# Fix Apache configuration
echo ""
echo "[FIX] Configuring Apache for port 8080..."

# Add port to ports.conf if missing
if ! sudo grep -q "Listen 8080" /etc/apache2/ports.conf; then
    echo "Listen 8080" | sudo tee -a /etc/apache2/ports.conf
    echo "✅ Added Listen 8080 to ports.conf"
fi

# Recreate virtual host configuration
sudo tee /etc/apache2/sites-available/panel-hosting.conf > /dev/null <<'EOF'
Listen 8080

<VirtualHost *:8080>
    ServerName 147.139.202.42
    DocumentRoot /var/www/panel-hosting/public
    
    <Directory /var/www/panel-hosting/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
        
        # Enable rewrite engine
        RewriteEngine On
        
        # Handle Laravel routing
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule ^(.*)$ index.php [QSA,L]
    </Directory>
    
    # Enable PHP processing
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    ErrorLog ${APACHE_LOG_DIR}/panel-hosting_error.log
    CustomLog ${APACHE_LOG_DIR}/panel-hosting_access.log combined
</VirtualHost>
EOF

echo "✅ Created new virtual host configuration"

# Enable required Apache modules
echo ""
echo "[FIX] Enabling required Apache modules..."
sudo a2enmod rewrite
sudo a2enmod php8.3

# Disable default site and enable panel site
echo ""
echo "[FIX] Configuring Apache sites..."
sudo a2dissite 000-default 2>/dev/null || true
sudo a2ensite panel-hosting

# Test Apache configuration
echo ""
echo "[TEST] Testing Apache configuration..."
if sudo apache2ctl configtest; then
    echo "✅ Apache configuration is valid"
else
    echo "❌ Apache configuration has errors"
fi

# Restart Apache
echo ""
echo "[FIX] Restarting Apache..."
sudo systemctl restart apache2

# Check if Apache started successfully
if sudo systemctl is-active --quiet apache2; then
    echo "✅ Apache is running"
else
    echo "❌ Apache failed to start"
    echo ""
    echo "Apache error log:"
    sudo tail -20 /var/log/apache2/error.log
fi

# Check port 8080 again
echo ""
echo "[VERIFY] Checking port 8080 after restart:"
sleep 2
sudo netstat -tlnp | grep :8080 && echo "✅ Port 8080 is now listening!" || echo "❌ Port 8080 still not listening"

# Check firewall
echo ""
echo "[CHECK] Checking firewall status:"
if command -v ufw >/dev/null 2>&1; then
    sudo ufw status
    echo ""
    echo "[FIX] Opening port 8080 in firewall..."
    sudo ufw allow 8080/tcp
    echo "✅ Port 8080 allowed in UFW firewall"
fi

# Check iptables
echo ""
echo "[CHECK] Checking iptables rules:"
sudo iptables -L -n | grep 8080 || echo "No specific iptables rules for port 8080"

# Final test
echo ""
echo "[TEST] Testing local connection to panel:"
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8080 | grep -q "200\|302\|500"; then
    echo "✅ Local connection to panel successful!"
else
    echo "❌ Local connection failed"
    echo ""
    echo "Checking Apache error log:"
    sudo tail -10 /var/log/apache2/panel-hosting_error.log 2>/dev/null || echo "No error log found"
fi

# Check file permissions
echo ""
echo "[CHECK] Checking file permissions:"
ls -la /var/www/panel-hosting/public/index.php 2>/dev/null || echo "❌ index.php not found"

# Fix permissions if needed
echo ""
echo "[FIX] Setting correct permissions..."
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo chmod -R 755 /var/www/panel-hosting
sudo chmod -R 775 /var/www/panel-hosting/storage /var/www/panel-hosting/bootstrap/cache

echo ""
echo "🎉 ==============================================="
echo "🎉          APACHE DIAGNOSTICS COMPLETE        "
echo "🎉 ==============================================="
echo ""
echo "🌐 Try accessing: http://147.139.202.42:8080"
echo ""
echo "If still not working, check:"
echo "1. Cloud provider firewall/security groups"
echo "2. VPS provider firewall settings"
echo "3. Network connectivity"
echo ""
echo "🔍 Debug commands:"
echo "   sudo systemctl status apache2"
echo "   sudo netstat -tlnp | grep :8080"
echo "   sudo tail -f /var/log/apache2/error.log"
echo ""
echo "🎉 ==============================================="
