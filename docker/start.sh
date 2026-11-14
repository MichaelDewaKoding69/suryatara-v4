#!/bin/sh
# Dijalankan setiap container Railway menyala.
set -e

# 0. Pastikan hanya satu MPM Apache yang aktif (prefork, yang dipakai mod_php).
#    Tanpa ini Railway bisa gagal start dengan "More than one MPM loaded".
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*
[ -e /etc/apache2/mods-enabled/mpm_prefork.load ] || a2enmod mpm_prefork >/dev/null

# 1. Apache mendengarkan port dari Railway (variabel PORT)
PORT="${PORT:-8080}"
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# 2. Folder upload disimpan di volume (UPLOAD_DIR, mis. /data) supaya tidak hilang saat redeploy.
#    Isi awal (foto contoh) disalin sekali, lalu folder di images/ diganti symlink ke volume.
if [ -n "$UPLOAD_DIR" ]; then
    for d in "Foto Produk" "Bukti Pembayaran" "Foto Retur" "Bukti Refund"; do
        mkdir -p "$UPLOAD_DIR/$d"
        if [ -d "/var/www/html/images/$d" ] && [ ! -L "/var/www/html/images/$d" ]; then
            cp -n "/var/www/html/images/$d/"* "$UPLOAD_DIR/$d/" 2>/dev/null || true
            rm -rf "/var/www/html/images/$d"
        fi
        ln -sfn "$UPLOAD_DIR/$d" "/var/www/html/images/$d"
    done
    chown -R www-data:www-data "$UPLOAD_DIR"
fi

exec apache2-foreground
