# Suryatara v4 (Roemah Beras)

Isi dan fitur sama dengan Suryatara v3, hanya susunan foldernya dibuat seperti proyek Suryatara awal.

## Cara menjalankan
1. Salin folder `Suryatara-v4` ke `C:\xampp\htdocs\`.
2. Database tetap `db_suryatara_v3`. Kalau sudah pernah di-import untuk v3, tidak perlu import ulang.
   Kalau belum: import `database/db_suryatara_v3.sql` di phpMyAdmin atau buka `install.php`.
3. Buka `http://localhost/Suryatara-v4/`.

Akun: owner@suryatara.com / owner123, admin@suryatara.com / admin123, budi@gmail.com / pelanggan123

## Susunan folder
```
index.php                 beranda (header + carousel + produk unggulan + footer)
config/                   koneksi.php, functions.php, laporan.php (hitung laporan), pdf.php (buat PDF)
database/                 db_suryatara_v3.sql
images/                   logo, banner, dan file upload:
  Foto Produk/  Bukti Pembayaran/  Foto Retur/  Bukti Refund/
Includes/                 semua halaman pelanggan
  css/                    satu file css per halaman (header.css, produk.css, bayar.css, ...)
  js/script.js
admin/                    halaman admin + sidebar.php (layout)
  CSS/                    satu file css per halaman admin (sidebar.css, dashboard.css, ...)
  Produk/ Jenis/ Varietas/ ukuran/ jasa_kurir/
                          index.php (daftar), tambah.php, edit.php, hapus.php, form.php, validasi.php + css modul
owner/                    halaman owner + sidebar.php
  CSS/                    sidebar.css, laporan.css, admin.css
```

## Hosting di Railway
File `Dockerfile`, `docker/start.sh`, dan `railway.toml` sudah disiapkan.
1. Upload folder ini ke repository GitHub, lalu di Railway: New Project -> Deploy from GitHub repo.
2. Tambah database: + New -> Database -> MySQL.
3. Di service website, tab Variables, isi:
   MYSQLHOST=${{MySQL.MYSQLHOST}}  MYSQLPORT=${{MySQL.MYSQLPORT}}  MYSQLUSER=${{MySQL.MYSQLUSER}}
   MYSQLPASSWORD=${{MySQL.MYSQLPASSWORD}}  MYSQLDATABASE=${{MySQL.MYSQLDATABASE}}
   INSTALL_TOKEN=(isi kata rahasia bebas)   UPLOAD_DIR=/data
4. Tambah Volume ke service website dengan mount path /data (tempat foto produk, bukti bayar, foto retur, bukti refund).
5. Settings -> Networking -> Generate Domain.
6. Buka https://<domain>/install.php?token=<INSTALL_TOKEN> lalu klik Buat Ulang Database.
7. Setelah berhasil, hapus variabel INSTALL_TOKEN supaya installer tidak bisa dibuka lagi.
