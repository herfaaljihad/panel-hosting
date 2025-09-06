# 🛠️ Troubleshooting Installation

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

- **URL:** http://[IP-SERVER-ANDA]
- **Email:** admin@[IP-SERVER-ANDA]
- **Password:** Lihat di `/root/panel-credentials.txt`

---

*Last updated: September 6, 2025*
