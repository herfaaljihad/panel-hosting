# 🌟 Laravel Hosting Panel

<div align="center">
<img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
<img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white" alt="Bootstrap">
<img src="https://img.shields.io/badge/MySQL-00000F?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
<img src="https://img.shields.io/badge/Ubuntu-E95420?style=for-the-badge&logo=ubuntu&logoColor=white" alt="Ubuntu">
</div>

<div align="center">
  <h3>🚀 Production-Ready Hosting Control Panel</h3>
  <p>Panel hosting berbasis Laravel dengan fitur lengkap seperti DirectAdmin, siap produksi dengan installer otomatis.</p>
  
  [![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue.svg)](https://php.net/)
  [![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com/)
  [![Ubuntu](https://img.shields.io/badge/Ubuntu-22.04%20%7C%2024.04-orange.svg)](https://ubuntu.com/)
</div>

---

## � Quick Install (One Command)

```bash
bash <(curl -s https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/install.sh) --auto --production
```

**Installer otomatis akan:**

-   Install semua dependencies (Apache, PHP 8.3, MySQL, dll)
-   Setup database dan konfigurasi
-   Generate credentials admin otomatis
-   Deploy aplikasi siap produksi
-   Konfigurasi SSL dan firewall

---

## �📋 Daftar Isi

-   [✨ Fitur Utama](#-fitur-utama)
-   [🔧 Requirements](#-requirements)
-   [🚀 Instalasi Cepat](#-instalasi-cepat)
-   [📖 Instalasi Manual](#-instalasi-manual)
-   [⚙️ Konfigurasi](#️-konfigurasi)
-   [🎯 Penggunaan](#-penggunaan)
-   [🔒 Keamanan](#-keamanan)
-   [🤝 Kontribusi](#-kontribusi)
-   [📄 Lisensi](#-lisensi)

---

## ✨ Fitur Utama

### 🔐 **Autentikasi & Keamanan**

-   ✅ **Laravel Breeze Authentication** - Sistem login/logout yang aman
-   ✅ **Role-based Access Control** - Role Admin dan User
-   ✅ **Two-Factor Authentication (2FA)** - Keamanan berlapis
-   ✅ **Security Middleware** - Proteksi CSRF dan validasi input
-   ✅ **Rate Limiting** - Perlindungan dari serangan brute force

### 🌐 **Manajemen Domain**

-   ✅ **Domain CRUD Operations** - Buat, baca, update, hapus domain
-   ✅ **DNS Management** - Manajemen DNS record lengkap
-   ✅ **SSL Certificate Management** - SSL otomatis dengan Let's Encrypt
-   ✅ **Subdomain Support** - Manajemen subdomain penuh

### 🗄️ **Manajemen Database**

-   ✅ **Database CRUD Operations** - Manajemen database lengkap
-   ✅ **PHPMyAdmin Integration** - Administrasi database berbasis web
-   ✅ **Database User Management** - Kontrol akses pengguna yang aman
-   ✅ **Backup & Restore** - Backup database otomatis

### 📁 **Manajemen File**

-   ✅ **Advanced File Manager** - Upload, download, hapus file
-   ✅ **FTP Account Management** - Buat dan kelola akun FTP
-   ✅ **File Permissions** - Kontrol akses file yang aman
-   ✅ **Zip/Unzip Support** - Manajemen arsip

### 📧 **Manajemen Email**

-   ✅ **Email Account CRUD** - Manajemen akun email lengkap
-   ✅ **Mail Server Integration** - Konfigurasi SMTP/IMAP
-   ✅ **Email Forwarding** - Routing email lanjutan
-   ✅ **Webmail Integration** - Email berbasis browser

### 🚀 **Auto Installer (Seperti Softaculous)**

-   ✅ **WordPress Auto-Install** - Instalasi WordPress otomatis
-   ✅ **Popular CMS Support** - Drupal, Joomla, dll
-   ✅ **Framework Installers** - Laravel, CodeIgniter, dll
-   ✅ **E-commerce Platforms** - PrestaShop, OpenCart

### 📊 **Monitoring & Analytics**

-   ✅ **Resource Monitoring** - Monitor CPU, RAM, Disk
-   ✅ **Traffic Analytics** - Analisis lalu lintas website
-   ✅ **Error Logging** - Log error komprehensif
-   ✅ **Performance Metrics** - Metrik performa detail

---

## 🔧 Requirements

### **Server Requirements:**

-   **OS**: Ubuntu 22.04 LTS (Recommended) / 20.04 LTS / 24.04 LTS\*
-   **RAM**: 2GB minimum (4GB recommended)
-   **Storage**: 20GB SSD minimum
-   **CPU**: 1 vCore minimum (2 vCore recommended)
-   **Bandwidth**: Unmetered
-   **Root Access**: Required

> **⚡ Ubuntu 24.04 Support:** Fully supported dengan fix otomatis untuk NPM dan permissions

## 🚨 Quick Fix (Jika Sedang Install Manual)

**Jika Anda sedang mengalami masalah saat instalasi manual, jalankan commands ini:**

```bash
# Fix NPM permission errors
sudo chown -R 33:33 "/var/www/.npm" 2>/dev/null || true
sudo rm -rf /var/www/.npm
sudo npm cache clean --force

# Fix build process
cd /var/www/panel-hosting
sudo -u www-data npm install --no-optional
sudo -u www-data npx vite build

# Continue dengan setup environment
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
```

### **Software Stack:**

-   **Web Server**: Apache 2.4+ / Nginx 1.18+
-   **PHP**: 8.2+ dengan extensions (mbstring, xml, bcmath, etc.)
-   **Database**: MySQL 8.0+ / MariaDB 10.6+
-   **Mail Server**: Postfix + Dovecot
-   **DNS Server**: BIND9
-   **FTP Server**: vsftpd
-   **SSL**: Certbot (Let's Encrypt)

---

## 🚀 Instalasi Cepat

### **🎯 Automated Installation (Recommended)**

#### **Method 1: Production Install (Full Automation - Recommended)**

```bash
# One-line production install dengan auto-generated credentials
curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh | bash -s -- --production
```

**✅ Fitur Production Mode:**

-   🔐 Auto-generated secure passwords
-   🧹 Automatic cleanup development files
-   🚀 Production optimizations applied
-   📄 Credentials saved to `/root/panel-credentials.txt`
-   ⚡ Ready to use immediately

#### **Method 2: Download & Run (Kontrol Manual)**

```bash
# Download script installer
wget https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh

# Buat executable
chmod +x vps-auto-setup.sh

# Production mode
./vps-auto-setup.sh --production

# Or interactive mode
./vps-auto-setup.sh
```

#### **Method 3: Environment Variables Mode**

#### **Method 2: Non-Interactive Install (Recommended for VPS)**

```bash
# Download dan jalankan dengan setup parameter
wget https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh -O installer.sh
chmod +x installer.sh

# Set environment variables dulu
export DOMAIN_NAME="panel.yourdomain.com"
export EMAIL="admin@yourdomain.com"
export MYSQL_ROOT_PASSWORD="mysql_root_pass"
export PANEL_DB_PASSWORD="panel_db_pass"
export ADMIN_PASSWORD="admin_pass"

# Jalankan installer
./installer.sh --auto
```

#### **Method 3: One-Line Install (Interactive)**

```bash
# Untuk Ubuntu 22.04/20.04/24.04 (akan konfirmasi input)
curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh | bash
```

#### **Method 4: Force Install (Skip OS Check)**

```bash
# Force install tanpa konfirmasi OS
curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh | bash -s -- --force
```

**💡 Troubleshooting:**

-   **Jika script berhenti** di warning message: Gunakan **Method 1**
-   **Jika non-Ubuntu**: Gunakan **Method 3** atau install manual
-   **Jika error permission**: Pastikan user punya sudo access

**Script ini akan otomatis menginstall:**

-   ✅ Apache + PHP 8.3 dengan semua extensions
-   ✅ MySQL/MariaDB dengan konfigurasi optimal
-   ✅ Postfix + Dovecot (Mail server)
-   ✅ BIND9 (DNS server)
-   ✅ vsftpd (FTP server)
-   ✅ Certbot untuk SSL otomatis
-   ✅ Laravel Panel dengan semua dependencies

**📋 Data yang Dibutuhkan Saat Install:**

-   🌐 Domain name (e.g., panel.yourdomain.com)
-   📧 Email untuk SSL certificate
-   🔒 MySQL root password
-   🔑 Panel database password
-   👤 Admin user password

### **⏱️ Estimasi Waktu Installation:**

-   VPS 2GB RAM: ~15-20 menit
-   VPS 4GB RAM: ~10-15 menit

---

## 📖 Instalasi Manual

Jika Anda ingin kontrol penuh terhadap proses instalasi:

### **Step 1: Persiapan Sistem**

```bash
# Update sistem
sudo apt update && sudo apt upgrade -y

# Install packages dasar
sudo apt install -y curl wget git unzip software-properties-common \
    apt-transport-https ca-certificates gnupg lsb-release
```

### **Step 2: Install Web Server Stack**

```bash
# Install Apache
sudo apt install -y apache2

# Tambah repository PHP 8.3
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 dan extensions
sudo apt install -y php8.3 php8.3-cli php8.3-fpm php8.3-mysql \
    php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip \
    php8.3-gd php8.3-intl php8.3-bcmath php8.3-soap \
    php8.3-readline php8.3-msgpack php8.3-igbinary \
    libapache2-mod-php8.3

# Enable Apache modules
sudo a2enmod rewrite ssl headers
sudo systemctl restart apache2
```

### **Step 3: Install Database**

```bash
# Install MySQL
sudo apt install -y mysql-server

# Secure MySQL installation
sudo mysql_secure_installation

# Buat database dan user
sudo mysql -e "CREATE DATABASE hosting_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY 'secure_password_here';"
sudo mysql -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

### **Step 4: Install Development Tools**

```bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer

# Install Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### **Step 5: Deploy Panel Hosting**

```bash
# Clone repository
cd /var/www
sudo git clone https://github.com/herfaaljihad/panel-hosting.git
sudo chown -R www-data:www-data panel-hosting
cd panel-hosting

# Fix NPM cache permissions (Ubuntu 24.04 fix)
sudo chown -R 33:33 "/var/www/.npm" 2>/dev/null || true
sudo rm -rf /var/www/.npm 2>/dev/null || true

# Install dependencies dengan fix permissions
sudo -u www-data composer install --no-dev --optimize-autoloader

# Install NPM dengan global cache fix
sudo npm cache clean --force
sudo chown -R $(whoami) ~/.npm
sudo -u www-data npm install --no-optional

# Build frontend assets
sudo -u www-data npm run build

# Setup environment
sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate
```

> **⚠️ Ubuntu 24.04 Specific Fixes:**
>
> -   Database mungkin sudah ada dari instalasi sebelumnya (error normal)
> -   NPM cache permission issues sudah diatasi otomatis
> -   Node modules warnings bisa diabaikan selama build berhasil
>     sudo -u www-data composer install --no-dev --optimize-autoloader
>     sudo -u www-data npm install
>     sudo -u www-data npm run build

# Setup environment

sudo -u www-data cp .env.example .env
sudo -u www-data php artisan key:generate

````

### **Step 6: Konfigurasi Environment**

```bash
# Edit file .env
sudo nano .env
````

**Konfigurasi minimal `.env`:**

```env
APP_NAME="Hosting Panel"
APP_ENV=production
APP_KEY=base64:generated_key_here
APP_DEBUG=false
APP_URL=https://panel.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hosting_panel
DB_USERNAME=panel_user
DB_PASSWORD=secure_password_here

MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"

# Hosting Configuration
HOSTING_SERVER_IP=YOUR_VPS_IP
HOSTING_NAMESERVER_1=ns1.yourdomain.com
HOSTING_NAMESERVER_2=ns2.yourdomain.com
HOSTING_DEFAULT_EMAIL=admin@yourdomain.com
```

Untuk detail lengkap instalasi manual, lihat dokumentasi di dalam repository.

---

## ⚙️ Konfigurasi

### **🔑 Buat Admin Account**

```bash
# Masuk ke direktori panel
cd /var/www/panel-hosting

# Buat admin user
sudo -u www-data php artisan tinker
```

**Dalam tinker shell:**

```php
$user = new App\Models\User();
$user->name = 'Administrator';
$user->email = 'admin@yourdomain.com';
$user->password = bcrypt('secure_admin_password');
$user->email_verified_at = now();
$user->role = 'admin';
$user->save();
exit;
```

---

## 🎯 Penggunaan

### **🚀 Akses Panel**

1. **URL Panel**: `https://panel.yourdomain.com`
2. **Login Admin**: Email dan password yang dibuat di step sebelumnya
3. **Dashboard**: Overview resource dan statistik server

### **👥 Manajemen User**

1. **Buat User Hosting**:

    - Masuk ke menu "Users" → "Create New"
    - Set package dan limits
    - Generate password otomatis

2. **Assign Resources**:
    - Disk space
    - Bandwidth
    - Database limits
    - Email accounts

---

## 🚨 Troubleshooting

### **⚡ Masalah Instalasi**

#### **1. Script Berhenti di Warning Message**

```bash
# Problem: Script berhenti setelah "This script is designed for Ubuntu 22.04 LTS or 20.04 LTS"
# Solution: Download dan jalankan lokal
wget https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh
chmod +x vps-auto-setup.sh
./vps-auto-setup.sh
```

#### **2. OS Tidak Didukung**

```bash
# Check OS version
lsb_release -a

# Force install (gunakan dengan hati-hati)
curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh | bash -s -- --force
```

#### **3. Curl Error atau Network Issue**

```bash
# Alternative download
wget https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh -O vps-auto-setup.sh
chmod +x vps-auto-setup.sh
./vps-auto-setup.sh
```

#### **4. Permission Issues**

```bash
# Pastikan user punya sudo access
sudo whoami

# Jangan jalankan sebagai root
# Jika sudah login sebagai root, buat user baru:
adduser panel
usermod -aG sudo panel
su - panel
```

### **❌ Masalah Umum Post-Install**

#### **1. NPM Permission Errors (Ubuntu 24.04)**

```bash
# Fix npm cache ownership
sudo chown -R 33:33 "/var/www/.npm" 2>/dev/null || true
sudo rm -rf /var/www/.npm

# Reset npm cache globally
sudo npm cache clean --force
sudo chown -R $(whoami) ~/.npm

# Reinstall dependencies
cd /var/www/panel-hosting
sudo -u www-data npm install --no-optional
sudo -u www-data npm run build
```

#### **2. Vite Command Not Found**

```bash
# Install vite globally atau local
cd /var/www/panel-hosting
sudo -u www-data npm install vite --save-dev

# Atau gunakan npx
sudo -u www-data npx vite build
```

#### **3. Database Already Exists Errors**

```bash
# Normal jika database sudah ada, reset jika perlu
sudo mysql -e "DROP DATABASE IF EXISTS hosting_panel;"
sudo mysql -e "DROP USER IF EXISTS 'panel_user'@'localhost';"
sudo mysql -e "CREATE DATABASE hosting_panel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'panel_user'@'localhost' IDENTIFIED BY 'secure_password_here';"
sudo mysql -e "GRANT ALL PRIVILEGES ON hosting_panel.* TO 'panel_user'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"
```

#### **4. Permission Denied**

```bash
# Fix ownership dan permissions
sudo chown -R www-data:www-data /var/www/panel-hosting
sudo chmod -R 755 /var/www/panel-hosting
sudo chmod -R 775 /var/www/panel-hosting/storage
sudo chmod -R 775 /var/www/panel-hosting/bootstrap/cache
```

#### **5. Database Connection Error**

```bash
# Test koneksi database
mysql -u panel_user -p hosting_panel
# Jika gagal, cek credentials di .env
```

#### **6. Apache Not Starting**

```bash
# Check Apache status
sudo systemctl status apache2

# Check error logs
sudo tail -f /var/log/apache2/error.log

# Restart services
sudo systemctl restart apache2
```

#### **4. SSL Certificate Issues**

```bash
# Manual SSL setup
sudo certbot --apache -d yourdomain.com

# Check certificate status
sudo certbot certificates
```

### **🔍 Debug Commands**

```bash
# Check all services status
sudo systemctl status apache2 mysql postfix dovecot bind9 vsftpd

# Check Laravel logs
tail -f /var/www/panel-hosting/storage/logs/laravel.log

# Check system resources
htop
df -h
free -m
```

---

## 🤝 Kontribusi

Kami sangat menghargai kontribusi dari komunitas! Berikut cara berkontribusi:

### **� Melaporkan Bug**

1. Cek issue yang sudah ada
2. Buat issue baru dengan detail:
    - Langkah reproduksi
    - Expected behavior
    - Actual behavior
    - Environment info

### **💡 Feature Request**

1. Diskusikan di GitHub Discussions
2. Buat detailed proposal
3. Submit sebagai issue dengan label "enhancement"

### **🔧 Pull Request**

1. Fork repository
2. Buat feature branch
3. Commit changes dengan descriptive message
4. Create pull request

---

## 📞 Support

### **💬 Komunitas**

-   **GitHub Discussions**: [Diskusi & Q&A](https://github.com/herfaaljihad/panel-hosting/discussions)
-   **GitHub Issues**: [Bug Reports & Feature Requests](https://github.com/herfaaljihad/panel-hosting/issues)

### **📧 Commercial Support**

Untuk support enterprise dan custom development:

-   Email: admin@yourdomain.com
-   Website: https://panel.yourdomain.com

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah MIT License - lihat file [LICENSE](LICENSE) untuk detail.

```
MIT License

Copyright (c) 2024 Herfa Al Jihad

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

---

<div align="center">
  <h3>⭐ Jangan lupa berikan star jika project ini membantu!</h3>
  <p>Dibuat dengan ❤️ oleh <a href="https://github.com/herfaaljihad">Herfa Al Jihad</a></p>
</div>

-   ✅ **File Manager**: Upload, download, edit files
-   ✅ **FTP Accounts**: vsftpd integration
-   ✅ **File Permissions**: Secure access control
-   ✅ **Archive Tools**: Zip/unzip functionality

### 👥 **User & Reseller System**

-   ✅ **Multi-Role**: Admin, Reseller, User roles
-   ✅ **Package Management**: Hosting packages & limits
-   ✅ **Resource Monitoring**: Disk, bandwidth tracking
-   ✅ **Billing Integration**: Ready for billing systems

### 🔐 **Security & Monitoring**

-   ✅ **Two-Factor Auth**: Google Authenticator
-   ✅ **Security Hardening**: Multiple protection layers
-   ✅ **System Monitoring**: Real-time server stats
-   ✅ **Activity Logs**: Comprehensive audit trails

### ⚙️ **Advanced Features**

-   ✅ **Cron Jobs**: Web-based cron management
-   ✅ **Auto Installer**: WordPress, Drupal, etc.
-   ✅ **Plugin System**: Extensible architecture
-   ✅ **API Support**: RESTful API endpoints

## 💻 **Tech Stack**

| Component       | Technology             | Version    |
| --------------- | ---------------------- | ---------- |
| **Framework**   | Laravel                | 12.x       |
| **Language**    | PHP                    | 8.3+       |
| **Frontend**    | TailwindCSS + AlpineJS | Latest     |
| **Build Tool**  | Vite                   | 5.x        |
| **Database**    | MySQL/MariaDB/SQLite   | 8.0+       |
| **Cache**       | Redis (Optional)       | 7.x        |
| **Web Server**  | Apache/Nginx           | 2.4+/1.20+ |
| **Mail Server** | Postfix + Dovecot      | Latest     |
| **DNS Server**  | BIND9                  | 9.x        |
| **FTP Server**  | vsftpd                 | 3.x        |
| **SSL**         | Certbot/Let's Encrypt  | Latest     |

## 🛠️ **Instalasi**

### 📋 **Requirements**

**Minimum:**

-   PHP 8.3+
-   Composer 2.x
-   Node.js 18+
-   MySQL 8.0+ / MariaDB 10.4+

**Recommended untuk Production:**

-   Ubuntu 22.04+ / CentOS 8+
-   2GB+ RAM
-   20GB+ Storage
-   Dedicated IP

### 🖥️ **Development Setup (Windows/Mac/Linux)**

```bash
# 1. Clone repository
git clone https://github.com/herfaaljihad/panel-hosting.git
cd panel-hosting

# 2. Install PHP dependencies
composer install

# 3. Install Node dependencies
npm install

# 4. Setup environment
cp .env.example .env
php artisan key:generate

# 5. Configure database di .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hosting_panel
DB_USERNAME=root
DB_PASSWORD=

# 6. Create database
mysql -u root -p -e "CREATE DATABASE hosting_panel"

# 7. Run migrations & seeders
php artisan migrate --seed

# 8. Build frontend assets
npm run build

# 9. Create admin user
php artisan admin:create admin@panel.com password123

# 10. Start development server
php artisan serve
```

🎉 **Panel akan berjalan di: http://localhost:8000**

### ☁️ **Production Setup (VPS Linux)**

#### **🚀 Automated Installation (Recommended)**

```bash
# Download & run automated installer
curl -sSL https://raw.githubusercontent.com/herfaaljihad/panel-hosting/main/vps-auto-setup.sh | bash
```

Script akan otomatis install:

-   ✅ Apache/Nginx + PHP 8.3
-   ✅ MySQL/MariaDB
-   ✅ Postfix + Dovecot (Mail server)
-   ✅ BIND9 (DNS server)
-   ✅ vsftpd (FTP server)
-   ✅ Certbot (SSL automation)
-   ✅ Laravel Panel + Dependencies

#### **📖 Manual Installation**

Untuk instalasi manual langkah-demi-langkah, ikuti panduan di:
📄 **[VPS_INSTALLATION_COMPLETE_GUIDE.md](VPS_INSTALLATION_COMPLETE_GUIDE.md)**

### 🔧 **Post-Installation Setup**

```bash
# Set proper permissions
chown -R www-data:www-data /var/www/panel-hosting
chmod -R 755 /var/www/panel-hosting
chmod -R 775 storage bootstrap/cache

# Setup cron job
echo "* * * * * www-data /usr/bin/php /var/www/panel-hosting/artisan schedule:run >> /dev/null 2>&1" >> /etc/crontab

# Configure firewall
ufw allow 80,443,21,22,25,110,143,993,995/tcp
ufw enable

# Start services
systemctl enable apache2 mysql postfix dovecot bind9 vsftpd
systemctl start apache2 mysql postfix dovecot bind9 vsftpd
```

## 🔐 **Default Accounts**

### **Admin Panel**

-   **URL**: `http://your-domain.com/admin`
-   **Email**: `admin@panel.com`
-   **Password**: `password123`

### **User Panel**

-   **URL**: `http://your-domain.com/dashboard`
-   **Email**: `user@example.com`
-   **Password**: `password`

### **Membuat Admin Baru**

```bash
php artisan admin:create email@domain.com newpassword
```

## 📚 **Dokumentasi Lengkap**

| Dokumen              | Deskripsi                             | Link                                                                     |
| -------------------- | ------------------------------------- | ------------------------------------------------------------------------ |
| **Analisis Panel**   | Analisis fitur lengkap vs DirectAdmin | [PANEL_HOSTING_ANALYSIS.md](PANEL_HOSTING_ANALYSIS.md)                   |
| **Technical Stack**  | Detail teknologi dan arsitektur       | [TECHNICAL_STACK_ANALYSIS.md](TECHNICAL_STACK_ANALYSIS.md)               |
| **VPS Installation** | Panduan instalasi server lengkap      | [VPS_INSTALLATION_COMPLETE_GUIDE.md](VPS_INSTALLATION_COMPLETE_GUIDE.md) |
| **System Status**    | Status komponen server                | [SYSTEM_COMPONENT_STATUS.md](SYSTEM_COMPONENT_STATUS.md)                 |
| **Quick Setup**      | Setup cepat VPS                       | [VPS_QUICK_SETUP.md](VPS_QUICK_SETUP.md)                                 |

## 🖼️ **Screenshots**

### **Dashboard Admin**

-   📊 Real-time server monitoring
-   📈 Resource usage graphs
-   🔔 System notifications
-   📋 Quick actions panel

### **Domain Management**

-   🌐 Domain list dengan status
-   ⚙️ DNS record editor
-   🔒 SSL certificate manager
-   📝 Domain configuration

### **File Manager**

-   📁 Browse server files
-   ✏️ Built-in code editor
-   📤 Upload/download files
-   🔐 Permission management

### **Database Manager**

-   🗄️ Database list & stats
-   👤 User management
-   💾 Backup/restore tools
-   🔗 PHPMyAdmin integration

## 🚀 **Quick Start Guide**

### **1. Akses Panel**

```
http://localhost:8000/admin
```

### **2. Login sebagai Admin**

```
Email: admin@panel.com
Password: password123
```

### **3. Buat User Baru**

-   Masuk ke Users → Create User
-   Set role: user/reseller
-   Assign package & limits

### **4. Tambah Domain**

-   Domain → Add Domain
-   Configure DNS records
-   Setup SSL certificate

### **5. Buat Database**

-   Database → Create Database
-   Set user permissions
-   Configure backup schedule

## 🔧 **Konfigurasi Lanjutan**

### **Environment Variables**

```bash
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=hosting_panel

# Mail Server
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=587

# File Storage
FILESYSTEM_DISK=local
MAX_UPLOAD_SIZE=100M

# Security
SESSION_LIFETIME=120
SANCTUM_STATEFUL_DOMAINS=your-domain.com
```

### **Server Integration**

```bash
# Apache VirtualHost Path
WEB_SERVER_CONFIG=/etc/apache2/sites-available

# DNS Configuration
DNS_SERVER_CONFIG=/etc/bind/zones

# FTP Configuration
FTP_SERVER_CONFIG=/etc/vsftpd

# SSL Certificates
SSL_CERT_PATH=/etc/letsencrypt/live
```

## 🐛 **Troubleshooting**

### **Common Issues**

#### **Migration Error**

```bash
# Reset migrations
php artisan migrate:fresh --seed
```

#### **Permission Error**

```bash
# Fix permissions
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### **Server Not Starting**

```bash
# Check logs
tail -f storage/logs/laravel.log

# Check services
systemctl status apache2 mysql
```

### **Debug Mode**

```bash
# Enable debug
APP_DEBUG=true
LOG_LEVEL=debug
```

## 🤝 **Contributing**

Kami sangat welcome untuk kontribusi! Berikut cara berkontribusi:

### **1. Fork Repository**

```bash
git clone https://github.com/yourusername/panel-hosting.git
```

### **2. Create Feature Branch**

```bash
git checkout -b feature/new-feature
```

### **3. Make Changes & Test**

```bash
# Run tests
php artisan test

# Check code style
vendor/bin/phpcs
```

### **4. Submit Pull Request**

-   Buat descriptive commit messages
-   Include tests untuk new features
-   Update documentation jika perlu

### **Development Guidelines**

-   Follow PSR-12 coding standards
-   Write tests for new features
-   Keep documentation updated
-   Use meaningful commit messages

## 📞 **Support & Community**

-   🐛 **Bug Reports**: [GitHub Issues](https://github.com/herfaaljihad/panel-hosting/issues)
-   💡 **Feature Requests**: [GitHub Discussions](https://github.com/herfaaljihad/panel-hosting/discussions)
-   📧 **Email**: support@panel-hosting.com
-   💬 **Telegram**: @panelhosting

## 🏷️ **Versioning**

Kami menggunakan [Semantic Versioning](https://semver.org/):

-   **MAJOR**: Breaking changes
-   **MINOR**: New features (backward compatible)
-   **PATCH**: Bug fixes

## 📄 **License**

**MIT License** - Lihat [LICENSE](LICENSE) untuk detail lengkap.

```
Copyright (c) 2025 Laravel Hosting Panel

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:
```

---

<div align="center">
  <p><strong>⭐ Jangan lupa star repository ini jika bermanfaat! ⭐</strong></p>
  <p>Made with ❤️ by <a href="https://github.com/herfaaljihad">@herfaaljihad</a></p>
</div>
