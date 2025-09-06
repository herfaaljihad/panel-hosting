#!/bin/bash
# Fix Apache duplicate Listen directive issue

echo "🔧 Fixing Apache Listen 8080 duplicate issue..."

# Remove Listen directive from virtual host (should only be in ports.conf)
echo "[FIX] Removing duplicate Listen directive from virtual host..."

sudo tee /etc/apache2/sites-available/panel-hosting.conf > /dev/null <<'EOF'
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

echo "✅ Fixed virtual host configuration (removed duplicate Listen)"

# Ensure ports.conf has Listen 8080
echo "[FIX] Ensuring ports.conf has Listen 8080..."
if ! grep -q "Listen 8080" /etc/apache2/ports.conf; then
    echo "Listen 8080" | sudo tee -a /etc/apache2/ports.conf
    echo "✅ Added Listen 8080 to ports.conf"
else
    echo "✅ Listen 8080 already in ports.conf"
fi

# Test Apache configuration
echo "[TEST] Testing Apache configuration..."
if sudo apache2ctl configtest; then
    echo "✅ Apache configuration is now valid"
else
    echo "❌ Apache configuration still has errors"
    exit 1
fi

# Restart Apache
echo "[FIX] Restarting Apache..."
sudo systemctl restart apache2

# Wait a moment for Apache to start
sleep 3

# Check if Apache is running
if sudo systemctl is-active --quiet apache2; then
    echo "✅ Apache is running"
else
    echo "❌ Apache failed to start"
    echo "Error log:"
    sudo tail -10 /var/log/apache2/error.log
    exit 1
fi

# Check if port 8080 is listening
echo "[VERIFY] Checking if port 8080 is listening..."
if sudo netstat -tlnp | grep -q ":8080"; then
    echo "✅ Port 8080 is now listening!"
    sudo netstat -tlnp | grep ":8080"
else
    echo "❌ Port 8080 still not listening"
    echo ""
    echo "Apache processes:"
    ps aux | grep apache2
    echo ""
    echo "Apache error log:"
    sudo tail -20 /var/log/apache2/error.log
fi

# Test local connection
echo "[TEST] Testing local connection..."
sleep 2
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080)
echo "HTTP response code: $HTTP_CODE"

if [[ "$HTTP_CODE" =~ ^(200|302|500)$ ]]; then
    echo "✅ Local connection successful!"
else
    echo "❌ Local connection failed"
    
    # Check if index.php exists and is readable
    echo ""
    echo "Checking Laravel files:"
    ls -la /var/www/panel-hosting/public/
    
    echo ""
    echo "Testing direct PHP access:"
    echo "<?php phpinfo(); ?>" | sudo tee /var/www/panel-hosting/public/test.php
    curl -s http://localhost:8080/test.php | head -20
    sudo rm -f /var/www/panel-hosting/public/test.php
fi

# Show final status
echo ""
echo "🎉 ==============================================="
echo "🎉           APACHE FIX COMPLETE               "
echo "🎉 ==============================================="
echo ""
echo "🌐 Panel URL: http://147.139.202.42:8080"
echo ""
echo "🔍 Final checks:"
echo "   Apache status: $(sudo systemctl is-active apache2)"
echo "   Port 8080 listening: $(sudo netstat -tlnp | grep -q ":8080" && echo "Yes" || echo "No")"
echo "   HTTP response: $HTTP_CODE"
echo ""
echo "If still not accessible from browser:"
echo "1. Check Alibaba Cloud Security Groups"
echo "2. Open port 8080 in cloud firewall"
echo "3. Try: curl -I http://147.139.202.42:8080"
echo ""
echo "🎉 ==============================================="
