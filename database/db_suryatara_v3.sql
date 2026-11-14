-- =====================================================================
--  DATABASE   : db_suryatara_v3   (nama baru, terpisah dari versi sebelumnya)
--  APLIKASI   : Sistem E-Commerce Penjualan Beras - Roemah Beras / Surya Tara Food
--  ACUAN      : ERD terbaru (Role, User, Mutu, Tekstur, Warna, Varietas, Bulir,
--               Jenis Beras, Kadar Patahan, Ukuran, Produk, Pesanan, Pesanan Produk,
--               Pembayaran, Pengiriman, Jasa Kurir, Retur, Foto Retur)
--
--  Cara import (phpMyAdmin): tab "Import" -> pilih file ini -> "Go / Kirim".
--  Database `db_suryatara_v3` dibuat otomatis beserta data contoh.
--  Database versi lama (suryatara_beras) tidak disentuh sama sekali.
--
--  Akun contoh:
--    Owner     : owner@suryatara.com  / owner123
--    Admin     : admin@suryatara.com  / admin123
--    Pelanggan : budi@gmail.com       / pelanggan123   (juga siti@gmail.com, andi@gmail.com)
--
--  Catatan perhitungan:
--    pesanan_produk.ongkir_satuan = berat ukuran (kg) x tarif ongkir per kg (Rp 4.000)
--    pesanan_produk.subtotal      = jumlah x (harga_satuan + ongkir_satuan)
--    pesanan.total_harga          = jumlah semua subtotal
--    pengiriman.ongkir            = jumlah x ongkir_satuan seluruh produk
--
--  Alur status:
--    pesanan    : Belum Melakukan Pembayaran -> Sudah Upload Bukti -> Diproses -> Dikirim -> Selesai (atau Dibatalkan)
--    pembayaran : Sudah Upload Bukti -> Disetujui / Ditolak
--    Pengiriman dibuat bersama pembayaran (relasi 1..1 "Setelah"); pesanan baru masuk menu
--    proses pesanan admin setelah pembayarannya Disetujui.
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+07:00";
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `db_suryatara_v3`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `db_suryatara_v3`;

DROP TABLE IF EXISTS `foto_retur`;
DROP TABLE IF EXISTS `retur`;
DROP TABLE IF EXISTS `pengiriman`;
DROP TABLE IF EXISTS `pembayaran`;
DROP TABLE IF EXISTS `pesanan_produk`;
DROP TABLE IF EXISTS `pesanan`;
DROP TABLE IF EXISTS `produk`;
DROP TABLE IF EXISTS `jenis_beras`;
DROP TABLE IF EXISTS `jasa_kurir`;
DROP TABLE IF EXISTS `kadar_patahan`;
DROP TABLE IF EXISTS `ukuran`;
DROP TABLE IF EXISTS `bulir`;
DROP TABLE IF EXISTS `warna`;
DROP TABLE IF EXISTS `tekstur`;
DROP TABLE IF EXISTS `varietas`;
DROP TABLE IF EXISTS `mutu`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `role`;

-- ---------------------------------------------------------------------
-- ROLE  (Role "Menentukan" User: 1..1 - 0..*)
-- ---------------------------------------------------------------------
CREATE TABLE `role` (
  `id_role`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_role` VARCHAR(50) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_role`),
  UNIQUE KEY `uk_nama_role` (`nama_role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- USER  (Owner, Admin, dan Pelanggan)
-- ---------------------------------------------------------------------
CREATE TABLE `user` (
  `id_user`    INT(11) NOT NULL AUTO_INCREMENT,
  `id_role`    INT(11) NOT NULL,
  `nama`       VARCHAR(100) NOT NULL,
  `email`      VARCHAR(100) NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `no_telepon` VARCHAR(20) DEFAULT NULL,
  `alamat`     TEXT DEFAULT NULL,
  `kota`       VARCHAR(100) DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `uk_user_email` (`email`),
  KEY `fk_user_role` (`id_role`),
  CONSTRAINT `fk_user_role` FOREIGN KEY (`id_role`) REFERENCES `role` (`id_role`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- MUTU, TEKSTUR, WARNA, VARIETAS, BULIR
-- ---------------------------------------------------------------------
CREATE TABLE `mutu` (
  `id_mutu`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_mutu` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT DEFAULT NULL,
  PRIMARY KEY (`id_mutu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `tekstur` (
  `id_tekstur`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_tekstur` VARCHAR(100) NOT NULL,
  `deskripsi`    TEXT DEFAULT NULL,
  PRIMARY KEY (`id_tekstur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `warna` (
  `id_warna`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_warna` VARCHAR(100) NOT NULL,
  `deskripsi`  TEXT DEFAULT NULL,
  PRIMARY KEY (`id_warna`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `varietas` (
  `id_varietas`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_varietas` VARCHAR(100) NOT NULL,
  `deskripsi`     TEXT DEFAULT NULL,
  PRIMARY KEY (`id_varietas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `bulir` (
  `id_bulir`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_bulir` VARCHAR(100) NOT NULL,
  `deskripsi`  TEXT DEFAULT NULL,
  PRIMARY KEY (`id_bulir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- JENIS BERAS  (Mutu Mengelompokkan, Tekstur Menggambarkan, Warna Mempunyai,
--               Varietas Mendasari, Bulir Membentuk)
-- ---------------------------------------------------------------------
CREATE TABLE `jenis_beras` (
  `id_jenis_beras` INT(11) NOT NULL AUTO_INCREMENT,
  `id_mutu`        INT(11) NOT NULL,
  `id_tekstur`     INT(11) NOT NULL,
  `id_warna`       INT(11) NOT NULL,
  `id_varietas`    INT(11) NOT NULL,
  `id_bulir`       INT(11) NOT NULL,
  `nama_jenis`     VARCHAR(100) NOT NULL,
  `deskripsi`      TEXT DEFAULT NULL,
  PRIMARY KEY (`id_jenis_beras`),
  KEY `fk_jenis_mutu` (`id_mutu`),
  KEY `fk_jenis_tekstur` (`id_tekstur`),
  KEY `fk_jenis_warna` (`id_warna`),
  KEY `fk_jenis_varietas` (`id_varietas`),
  KEY `fk_jenis_bulir` (`id_bulir`),
  CONSTRAINT `fk_jenis_mutu`     FOREIGN KEY (`id_mutu`)     REFERENCES `mutu` (`id_mutu`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jenis_tekstur`  FOREIGN KEY (`id_tekstur`)  REFERENCES `tekstur` (`id_tekstur`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jenis_warna`    FOREIGN KEY (`id_warna`)    REFERENCES `warna` (`id_warna`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jenis_varietas` FOREIGN KEY (`id_varietas`) REFERENCES `varietas` (`id_varietas`) ON UPDATE CASCADE,
  CONSTRAINT `fk_jenis_bulir`    FOREIGN KEY (`id_bulir`)    REFERENCES `bulir` (`id_bulir`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- KADAR PATAHAN, UKURAN
-- ---------------------------------------------------------------------
CREATE TABLE `kadar_patahan` (
  `id_kadar_patahan`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kadar_patahan` VARCHAR(100) NOT NULL,
  `deskripsi`          TEXT DEFAULT NULL,
  PRIMARY KEY (`id_kadar_patahan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `ukuran` (
  `id_ukuran`   INT(11) NOT NULL AUTO_INCREMENT,
  `nama_ukuran` VARCHAR(50) NOT NULL,
  `berat_kg`    DECIMAL(5,2) NOT NULL DEFAULT 1.00,
  PRIMARY KEY (`id_ukuran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PRODUK  (Jenis Beras Mengklasifikasikan, Ukuran Berukuran, Kadar Patahan Merincikan)
-- ---------------------------------------------------------------------
CREATE TABLE `produk` (
  `id_produk`        INT(11) NOT NULL AUTO_INCREMENT,
  `id_jenis_beras`   INT(11) NOT NULL,
  `id_ukuran`        INT(11) NOT NULL,
  `id_kadar_patahan` INT(11) NOT NULL,
  `merek`            VARCHAR(100) NOT NULL,
  `nama_produk`      VARCHAR(150) NOT NULL,
  `harga`            DECIMAL(12,2) NOT NULL DEFAULT 0,
  `stok`             INT(11) NOT NULL DEFAULT 0,
  `deskripsi`        TEXT DEFAULT NULL,
  `foto`             VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id_produk`),
  KEY `fk_produk_jenis` (`id_jenis_beras`),
  KEY `fk_produk_ukuran` (`id_ukuran`),
  KEY `fk_produk_kadar` (`id_kadar_patahan`),
  CONSTRAINT `fk_produk_jenis`  FOREIGN KEY (`id_jenis_beras`)   REFERENCES `jenis_beras` (`id_jenis_beras`) ON UPDATE CASCADE,
  CONSTRAINT `fk_produk_ukuran` FOREIGN KEY (`id_ukuran`)        REFERENCES `ukuran` (`id_ukuran`) ON UPDATE CASCADE,
  CONSTRAINT `fk_produk_kadar`  FOREIGN KEY (`id_kadar_patahan`) REFERENCES `kadar_patahan` (`id_kadar_patahan`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- JASA KURIR
-- ---------------------------------------------------------------------
CREATE TABLE `jasa_kurir` (
  `id_jasa_kurir` INT(11) NOT NULL AUTO_INCREMENT,
  `nama_kurir`    VARCHAR(50) NOT NULL,
  PRIMARY KEY (`id_jasa_kurir`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PESANAN  (User "Membuat" Pesanan: 1..1 - 0..*)
-- status_pesanan: Belum Melakukan Pembayaran | Sudah Upload Bukti | Diproses | Dikirim | Selesai | Dibatalkan
-- ---------------------------------------------------------------------
CREATE TABLE `pesanan` (
  `id_pesanan`      INT(11) NOT NULL AUTO_INCREMENT,
  `id_user`         INT(11) NOT NULL,
  `tanggal_pesanan` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `total_harga`     DECIMAL(12,2) NOT NULL DEFAULT 0,
  `status_pesanan`  VARCHAR(30) NOT NULL DEFAULT 'Belum Melakukan Pembayaran',
  PRIMARY KEY (`id_pesanan`),
  KEY `fk_pesanan_user` (`id_user`),
  CONSTRAINT `fk_pesanan_user` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PESANAN PRODUK  (PK gabungan id_pesanan + id_produk)
-- ---------------------------------------------------------------------
CREATE TABLE `pesanan_produk` (
  `id_pesanan`    INT(11) NOT NULL,
  `id_produk`     INT(11) NOT NULL,
  `jumlah`        INT(11) NOT NULL DEFAULT 1,
  `harga_satuan`  DECIMAL(12,2) NOT NULL DEFAULT 0,
  `ongkir_satuan` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `subtotal`      DECIMAL(12,2) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_pesanan`, `id_produk`),
  KEY `fk_pp_produk` (`id_produk`),
  CONSTRAINT `fk_pp_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pp_produk`  FOREIGN KEY (`id_produk`)  REFERENCES `produk` (`id_produk`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PEMBAYARAN  (Pesanan "Dibayar Melalui": 1..1 - 0..*)
-- status_pembayaran: Sudah Upload Bukti | Disetujui | Ditolak
-- ---------------------------------------------------------------------
CREATE TABLE `pembayaran` (
  `id_pembayaran`      INT(11) NOT NULL AUTO_INCREMENT,
  `id_pesanan`         INT(11) NOT NULL,
  `tanggal_pembayaran` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jumlah_bayar`       DECIMAL(12,2) NOT NULL DEFAULT 0,
  `bukti_pembayaran`   VARCHAR(255) NOT NULL,
  `status_pembayaran`  VARCHAR(30) NOT NULL DEFAULT 'Sudah Upload Bukti',
  `catatan`            TEXT DEFAULT NULL,
  PRIMARY KEY (`id_pembayaran`),
  KEY `fk_bayar_pesanan` (`id_pesanan`),
  CONSTRAINT `fk_bayar_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- PENGIRIMAN  (Pengiriman "Setelah" Pembayaran 1..1 - 1..1,
--              Pengiriman "Menggunakan" Jasa Kurir 0..* - 0..1)
-- ---------------------------------------------------------------------
CREATE TABLE `pengiriman` (
  `id_pengiriman`      INT(11) NOT NULL AUTO_INCREMENT,
  `id_pembayaran`      INT(11) NOT NULL,
  `id_jasa_kurir`      INT(11) DEFAULT NULL,
  `alamat_pengiriman`  TEXT NOT NULL,
  `ongkir`             DECIMAL(12,2) NOT NULL DEFAULT 0,
  `no_resi`            VARCHAR(50) DEFAULT NULL,
  `tanggal_pengiriman` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_pengiriman`),
  UNIQUE KEY `uk_pengiriman_pembayaran` (`id_pembayaran`),
  UNIQUE KEY `uk_no_resi` (`no_resi`),
  KEY `fk_kirim_kurir` (`id_jasa_kurir`),
  CONSTRAINT `fk_kirim_pembayaran` FOREIGN KEY (`id_pembayaran`) REFERENCES `pembayaran` (`id_pembayaran`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_kirim_kurir`      FOREIGN KEY (`id_jasa_kurir`) REFERENCES `jasa_kurir` (`id_jasa_kurir`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- RETUR  (Pesanan Produk "Diretur Melalui" Retur 1..1 - 0..1)
-- status_retur: Menunggu Verifikasi | Disetujui | Barang Retur Dikirim | Refund Selesai | Ditolak
-- ---------------------------------------------------------------------
CREATE TABLE `retur` (
  `id_retur`        INT(11) NOT NULL AUTO_INCREMENT,
  `id_pesanan`      INT(11) NOT NULL,
  `id_produk`       INT(11) NOT NULL,
  `tanggal_retur`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `alasan_retur`    TEXT NOT NULL,
  `jumlah_retur`    INT(11) NOT NULL DEFAULT 1,
  `rekening_refund` VARCHAR(50) NOT NULL,
  `no_resi_retur`   VARCHAR(50) DEFAULT NULL,
  `ekspedisi_retur` VARCHAR(50) DEFAULT NULL,
  `nominal_refund`  DECIMAL(12,2) DEFAULT NULL,
  `bukti_refund`    VARCHAR(255) DEFAULT NULL,
  `status_retur`    VARCHAR(30) NOT NULL DEFAULT 'Menunggu Verifikasi',
  PRIMARY KEY (`id_retur`),
  UNIQUE KEY `uk_retur_item` (`id_pesanan`, `id_produk`),
  CONSTRAINT `fk_retur_item` FOREIGN KEY (`id_pesanan`, `id_produk`) REFERENCES `pesanan_produk` (`id_pesanan`, `id_produk`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------
-- FOTO RETUR  (Retur "Dilengkapi" Foto Retur 1..1 - 1..*)
-- ---------------------------------------------------------------------
CREATE TABLE `foto_retur` (
  `id_foto_retur`  INT(11) NOT NULL AUTO_INCREMENT,
  `id_retur`       INT(11) NOT NULL,
  `nama_file`      VARCHAR(255) NOT NULL,
  `tanggal_upload` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_foto_retur`),
  KEY `fk_foto_retur` (`id_retur`),
  CONSTRAINT `fk_foto_retur` FOREIGN KEY (`id_retur`) REFERENCES `retur` (`id_retur`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================================
--  DATA CONTOH
-- =====================================================================

INSERT INTO `role` (`id_role`, `nama_role`, `deskripsi`) VALUES
(1, 'Owner',     'Pemilik toko: menambahkan admin dan mengunduh laporan keuangan'),
(2, 'Admin',     'Mengelola produk, pesanan, pembayaran, pengiriman, dan retur'),
(3, 'Pelanggan', 'Membeli beras, membayar pesanan, dan mengajukan retur');

INSERT INTO `user` (`id_user`, `id_role`, `nama`, `email`, `password`, `no_telepon`, `alamat`, `kota`) VALUES
(1, 1, 'Owner Surya Tara', 'owner@suryatara.com', '$2y$10$exqOHPQ3hNda7MPdIW58M.jGH.ctyrZkMPA4O.9OK/F7nUrUXxlue', '081571093990', NULL, NULL),
(2, 2, 'Admin Surya Tara', 'admin@suryatara.com', '$2y$10$RJuUK8ROV4DIxGZMhssA1uPtPZWxjHI12tqmfmvZfeYFcaNoQsDXm', '081571093991', NULL, NULL),
(3, 3, 'Budi Santoso',     'budi@gmail.com',      '$2y$10$qhhTLSTsTVHxpBUIbkuu7eYV7RTk2aGXxB7FR6qaz8w8mClkKTkka', '081234567801', 'Jl. Raya Cipondoh No. 12, RT 02/RW 05', 'Tangerang'),
(4, 3, 'Siti Rahmawati',   'siti@gmail.com',      '$2y$10$qhhTLSTsTVHxpBUIbkuu7eYV7RTk2aGXxB7FR6qaz8w8mClkKTkka', '081234567802', 'Jl. Kembangan Raya No. 45', 'Jakarta Barat'),
(5, 3, 'Andi Pratama',     'andi@gmail.com',      '$2y$10$qhhTLSTsTVHxpBUIbkuu7eYV7RTk2aGXxB7FR6qaz8w8mClkKTkka', '081234567803', 'Perum Depok Indah Blok C3 No. 7', 'Depok');

INSERT INTO `mutu` (`id_mutu`, `nama_mutu`, `deskripsi`) VALUES
(1, 'Premium',  'Butir kepala tinggi, butir patah maksimal 15%'),
(2, 'Medium',   'Butir patah maksimal 25%'),
(3, 'Khusus',   'Beras khusus: aromatik, beras merah, organik'),
(4, 'Ekonomis', 'Beras patahan dengan harga terjangkau');

INSERT INTO `tekstur` (`id_tekstur`, `nama_tekstur`, `deskripsi`) VALUES
(1, 'Pulen',          'Lembut dan lengket'),
(2, 'Pera',           'Butiran nasi terpisah, tidak lengket'),
(3, 'Pulen Berderai', 'Pulen namun tetap berderai'),
(4, 'Sedang',         'Antara pulen dan pera');

INSERT INTO `warna` (`id_warna`, `nama_warna`, `deskripsi`) VALUES
(1, 'Putih',  'Beras putih yang sudah disosoh, paling umum dikonsumsi'),
(2, 'Coklat', 'Beras pecah kulit, lapisan bekatul masih ada'),
(3, 'Merah',  'Kulit ari berwarna merah, kaya serat'),
(4, 'Hitam',  'Beras hitam, kandungan antosianin tinggi');

INSERT INTO `varietas` (`id_varietas`, `nama_varietas`, `deskripsi`) VALUES
(1, 'Rojolele',     'Varietas lokal Delanggu, Klaten - nasi pulen dan wangi'),
(2, 'Pandan Wangi', 'Varietas Cianjur dengan aroma pandan alami'),
(3, 'IR-42',        'Nasi pera, cocok untuk masakan Padang'),
(4, 'IR-64',        'Nasi pulen berderai'),
(5, 'Beras Merah',  'Beras merah Bandung, kaya serat'),
(6, 'Ciherang',     'Varietas unggul nasional dari Karawang');

INSERT INTO `bulir` (`id_bulir`, `nama_bulir`, `deskripsi`) VALUES
(1, 'Panjang', 'Bulir ramping panjang'),
(2, 'Sedang',  'Bulir ukuran sedang'),
(3, 'Pendek',  'Bulir pendek / bulat');

INSERT INTO `jenis_beras` (`id_jenis_beras`, `id_mutu`, `id_tekstur`, `id_warna`, `id_varietas`, `id_bulir`, `nama_jenis`, `deskripsi`) VALUES
(1, 1, 1, 1, 1, 2, 'Rojolele Premium',       'Beras Rojolele asli Delanggu, nasi pulen dan wangi.'),
(2, 3, 1, 1, 2, 3, 'Pandan Wangi Khusus',    'Beras aromatik dengan wangi pandan alami.'),
(3, 2, 2, 1, 3, 1, 'Pera IR-42',             'Nasi berderai, cocok untuk nasi Padang.'),
(4, 1, 3, 1, 4, 1, 'Pulen Berderai Premium', 'Tekstur pulen dan berderai, rasa khas kampung halaman.'),
(5, 3, 4, 3, 5, 2, 'Beras Merah Bandung',    'Beras merah sosoh 2x, nasi pulen tidak keras.'),
(6, 4, 1, 1, 6, 3, 'Beras Patah Ekonomis',   'Beras patah dengan harga ekonomis, kualitas unggul.'),
(7, 3, 4, 2, 6, 2, 'Beras Coklat Pecah Kulit', 'Beras pecah kulit, belum disosoh penuh.'),
(8, 3, 4, 4, 4, 2, 'Beras Hitam',            'Beras hitam, tekstur sedikit kenyal.');

INSERT INTO `kadar_patahan` (`id_kadar_patahan`, `nama_kadar_patahan`, `deskripsi`) VALUES
(1, 'Utuh',    'Butir patah maksimal 5%'),
(2, 'Rendah',  'Butir patah maksimal 15%'),
(3, 'Sedang',  'Butir patah maksimal 25%'),
(4, 'Patahan', '100% beras patah (menir)');

INSERT INTO `ukuran` (`id_ukuran`, `nama_ukuran`, `berat_kg`) VALUES
(1, '1 kg',   1.00),
(2, '2,5 kg', 2.50),
(3, '5 kg',   5.00),
(4, '10 kg', 10.00),
(5, '20 kg', 20.00);

INSERT INTO `produk` (`id_produk`, `id_jenis_beras`, `id_ukuran`, `id_kadar_patahan`, `merek`, `nama_produk`, `harga`, `stok`, `deskripsi`, `foto`) VALUES
(1, 1, 3, 2, 'Rojo Lele',     'Rojo Lele Delanggu 5 kg',            82000, 40, 'Beras Rojolele Super asli Solo. Nasi pulen, cocok untuk makanan sehari-hari.', 'rojolele-delanggu.webp'),
(2, 1, 4, 2, 'Rojo Lele',     'Rojo Lele Delanggu 10 kg',          160000, 25, 'Kemasan hemat 10 kg beras Rojolele Super asli Solo.', 'rojolele-delanggu.webp'),
(3, 2, 3, 1, 'BMW AAA',       'BMW AAA Pandan Wangi 5 kg',          85000, 35, '100% beras pandan wangi. Nasi pulen legit dengan aroma & rasa khas pandan wangi.', 'bmw-aaa-pandan-wangi.webp'),
(4, 3, 3, 2, 'Pusako Rancak', 'Pusako Rancak Pera IR-42 5 kg',      89000, 30, 'Nasi berderai / pera, cocok untuk nasi Padang. Rasa khas kampung halaman.', 'pusako-rancak-pera.webp'),
(5, 3, 5, 2, 'Pusako Rancak', 'Pusako Rancak Pera IR-42 20 kg',    350000, 12, 'Kemasan karung 20 kg untuk rumah makan dan usaha kuliner.', 'pusako-rancak-pera.webp'),
(6, 4, 3, 1, 'Pusako Rancak', 'Pusako Rancak Pulen Berderai 5 kg',  87000, 30, 'Beras premium pulen berderai, nasi wangi & harum.', 'pusako-rancak-premium.webp'),
(7, 4, 5, 1, 'Pusako Rancak', 'Pusako Rancak Pulen Berderai 20 kg',336000, 10, 'Kemasan karung 20 kg beras premium pulen berderai.', 'pusako-rancak-premium.webp'),
(8, 5, 2, 1, 'Surya Tara',    'Surya Tara Beras Merah 2,5 kg',      45000, 50, 'Beras merah Bandung, nasi pulen & tidak keras, pengupasan 2x.', 'surya-tara-beras-merah.webp'),
(9, 6, 3, 4, 'Bunga',         'Beras Patahan Bunga 5 kg',           62000, 45, '100% beras patah, nasi pulen & wangi, harga ekonomis kualitas unggul.', 'patahan-bunga.webp');

INSERT INTO `jasa_kurir` (`id_jasa_kurir`, `nama_kurir`) VALUES
(1, 'JNE'),
(2, 'J&T Express'),
(3, 'SiCepat'),
(4, 'GoSend'),
(5, 'JNE Trucking (Kargo)');

-- Pesanan contoh (tanggal relatif terhadap waktu import)
INSERT INTO `pesanan` (`id_pesanan`, `id_user`, `tanggal_pesanan`, `total_harga`, `status_pesanan`) VALUES
(1,  3, DATE_SUB(NOW(), INTERVAL 26 DAY),  204000, 'Selesai'),
(2,  4, DATE_SUB(NOW(), INTERVAL 21 DAY),  217000, 'Selesai'),
(3,  5, DATE_SUB(NOW(), INTERVAL 15 DAY),  109000, 'Selesai'),
(4,  3, DATE_SUB(NOW(), INTERVAL 9 DAY),   210000, 'Dikirim'),
(5,  4, DATE_SUB(NOW(), INTERVAL 6 DAY),   416000, 'Diproses'),
(6,  5, DATE_SUB(NOW(), INTERVAL 3 DAY),   184000, 'Sudah Upload Bukti'),
(7,  3, DATE_SUB(NOW(), INTERVAL 2 DAY),   214000, 'Sudah Upload Bukti'),
(8,  4, DATE_SUB(NOW(), INTERVAL 1 DAY),   430000, 'Sudah Upload Bukti'),
(9,  5, DATE_SUB(NOW(), INTERVAL 5 HOUR),   55000, 'Belum Melakukan Pembayaran'),
(10, 3, DATE_SUB(NOW(), INTERVAL 12 DAY),  200000, 'Dibatalkan');

INSERT INTO `pesanan_produk` (`id_pesanan`, `id_produk`, `jumlah`, `harga_satuan`, `ongkir_satuan`, `subtotal`) VALUES
(1,  1, 2,  82000, 20000, 204000),
(2,  6, 1,  87000, 20000, 107000),
(2,  8, 2,  45000, 10000, 110000),
(3,  4, 1,  89000, 20000, 109000),
(4,  3, 2,  85000, 20000, 210000),
(5,  7, 1, 336000, 80000, 416000),
(6,  1, 1,  82000, 20000, 102000),
(6,  9, 1,  62000, 20000,  82000),
(7,  6, 2,  87000, 20000, 214000),
(8,  5, 1, 350000, 80000, 430000),
(9,  8, 1,  45000, 10000,  55000),
(10, 2, 1, 160000, 40000, 200000);

INSERT INTO `pembayaran` (`id_pembayaran`, `id_pesanan`, `tanggal_pembayaran`, `jumlah_bayar`, `bukti_pembayaran`, `status_pembayaran`, `catatan`) VALUES
(1, 1, DATE_SUB(NOW(), INTERVAL 26 DAY),   204000, 'bukti_rb0001.png',         'Disetujui', NULL),
(2, 2, DATE_SUB(NOW(), INTERVAL 21 DAY),   217000, 'bukti_rb0002.png',         'Disetujui', NULL),
(3, 3, DATE_SUB(NOW(), INTERVAL 15 DAY),   109000, 'bukti_rb0003.png',         'Disetujui', NULL),
(4, 4, DATE_SUB(NOW(), INTERVAL 9 DAY),    210000, 'bukti_rb0004.png',         'Disetujui', NULL),
(5, 5, DATE_SUB(NOW(), INTERVAL 6 DAY),    416000, 'bukti_rb0005.png',         'Disetujui', NULL),
(6, 6, DATE_SUB(NOW(), INTERVAL 3 DAY),    184000, 'bukti_rb0006.png',         'Sudah Upload Bukti', NULL),
(7, 7, DATE_SUB(NOW(), INTERVAL 2 DAY),    200000, 'bukti_rb0007_ditolak.png', 'Ditolak', 'Nominal transfer kurang dari total tagihan'),
(8, 7, DATE_SUB(NOW(), INTERVAL 20 HOUR),  214000, 'bukti_rb0007.png',         'Sudah Upload Bukti', NULL),
(9, 8, DATE_SUB(NOW(), INTERVAL 1 DAY),    430000, 'bukti_rb0008.png',         'Sudah Upload Bukti', NULL);

INSERT INTO `pengiriman` (`id_pengiriman`, `id_pembayaran`, `id_jasa_kurir`, `alamat_pengiriman`, `ongkir`, `no_resi`, `tanggal_pengiriman`) VALUES
(1, 1, 1,    'Budi Santoso (081234567801)\nJl. Raya Cipondoh No. 12, RT 02/RW 05, Tangerang', 40000, 'JNE0102938475', DATE_SUB(NOW(), INTERVAL 24 DAY)),
(2, 2, 3,    'Siti Rahmawati (081234567802)\nJl. Kembangan Raya No. 45, Jakarta Barat',        40000, 'SCP7748201934', DATE_SUB(NOW(), INTERVAL 19 DAY)),
(3, 3, 2,    'Andi Pratama (081234567803)\nPerum Depok Indah Blok C3 No. 7, Depok',            20000, 'JT8800123987',  DATE_SUB(NOW(), INTERVAL 13 DAY)),
(4, 4, 1,    'Budi Santoso (081234567801)\nJl. Raya Cipondoh No. 12, RT 02/RW 05, Tangerang', 40000, 'JNE0109982231', DATE_SUB(NOW(), INTERVAL 7 DAY)),
(5, 5, NULL, 'Siti Rahmawati (081234567802)\nJl. Kembangan Raya No. 45, Jakarta Barat',        80000, NULL, NULL),
(6, 6, NULL, 'Andi Pratama (081234567803)\nPerum Depok Indah Blok C3 No. 7, Depok',            40000, NULL, NULL),
(7, 7, NULL, 'Budi Santoso (081234567801)\nJl. Raya Cipondoh No. 12, RT 02/RW 05, Tangerang', 40000, NULL, NULL),
(8, 8, NULL, 'Budi Santoso (081234567801)\nJl. Raya Cipondoh No. 12, RT 02/RW 05, Tangerang', 40000, NULL, NULL),
(9, 9, NULL, 'Siti Rahmawati (081234567802)\nJl. Kembangan Raya No. 45, Jakarta Barat',        80000, NULL, NULL);

INSERT INTO `retur` (`id_retur`, `id_pesanan`, `id_produk`, `tanggal_retur`, `alasan_retur`, `jumlah_retur`, `rekening_refund`, `no_resi_retur`, `ekspedisi_retur`, `nominal_refund`, `bukti_refund`, `status_retur`) VALUES
(1, 2, 8, DATE_SUB(NOW(), INTERVAL 16 DAY), 'Kemasan beras merah sobek saat diterima sehingga beras tumpah sebagian.', 1, 'Mandiri 1270009876543 a.n. Siti Rahmawati', NULL, NULL, NULL, NULL, 'Menunggu Verifikasi');

INSERT INTO `foto_retur` (`id_foto_retur`, `id_retur`, `nama_file`, `tanggal_upload`) VALUES
(1, 1, 'retur_rb0002.jpg',   DATE_SUB(NOW(), INTERVAL 16 DAY)),
(2, 1, 'retur_rb0002_b.jpg', DATE_SUB(NOW(), INTERVAL 16 DAY));

SET FOREIGN_KEY_CHECKS = 1;
