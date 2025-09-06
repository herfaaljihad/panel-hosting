# 🛠️ Troubleshooting Installation

## Error yang Umum Terjadi

### 1. NPM Cache Error
```bash
# Error: npm error path /var/www/.npm
sudo chown -R www-data:www-data /var/www/.npm
sudo rm -rf /var/www/.npm
sudo rm -rf node_modules
sudo -u www-data npm install --prefer-offline=false
```

### 2. SQLite Driver Error  
```bash
# Error: could not find driver (Connection: sqlite)
sudo apt-get install -y php8.3-sqlite3 php8.3-mysql
sudo systemctl restart apache2
```

### 3. Database Connection Error
```bash
# Test koneksi database
mysql -u panel_user -p hosting_panel
# Jika gagal, reset user:
sudo mysql -e "DROP USER IF EXISTS 'panel_user'@'localhost';"
sudo mysql -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY 'password_baru';"
sudo mysql -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
```

### 4. Vite Build Error
```bash
# Error: sh: 1: vite: not found  
cd /var/www/panel-hosting
sudo -u www-data npm install
sudo -u www-data npx vite build
```

### 5. Permission Error
```bash
# Fix semua permission issues
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo chmod -R 755 /var/www/panel-hosting
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache
```

### 6. Psysh Config Error
```bash
# Error: Writing to directory /var/www/.config/psysh is not allowed
sudo mkdir -p /var/www/.config/psysh
sudo chown -R www-data:www-data /var/www/.config
```

## Jika Instalasi Terhenti

Jika instalasi utama terhenti di tengah jalan, Anda dapat melanjutkannya dengan script khusus:

```bash
# Lanjutkan instalasi yang tertunda
curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/continue-install.sh | bash
```

## Script yang Tersedia

1. **vps-auto-setup.sh** - Installer utama lengkap
2. **continue-install.sh** - Lanjutkan instalasi yang terhenti
3. **install.sh** - Installer alternatif

## Status Instalasi

Cek status layanan setelah instalasi:

```bash
# Cek status Apache
systemctl status apache2

# Cek status MySQL
systemctl status mysql

# Cek Laravel
cd /var/www/panel-hosting && php artisan --version

# Lihat credentials
cat /root/panel-credentials.txt
```

## Akses Panel

-   **URL:** http://[IP-SERVER-ANDA]
-   **Email:** admin@[IP-SERVER-ANDA]
-   **Password:** Lihat di `/root/panel-credentials.txt`

---

_Last updated: September 6, 2025_
