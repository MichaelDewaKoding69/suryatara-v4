<?php
/**
 * =====================================================================
 *  KONEKSI DATABASE - Roemah Beras / Surya Tara Food
 *  PHP Native + PDO + MySQL (XAMPP / Laragon / Railway)
 * =====================================================================
 *  Di XAMPP dipakai nilai bawaan di bawah (localhost, root, tanpa password).
 *  Di Railway nilainya otomatis diambil dari variabel MYSQLHOST, MYSQLPORT,
 *  MYSQLUSER, MYSQLPASSWORD, dan MYSQLDATABASE milik service MySQL.
 */

$DB_HOST = getenv('MYSQLHOST') ?: 'localhost';
$DB_PORT = getenv('MYSQLPORT') ?: '3306';
$DB_NAME = getenv('MYSQLDATABASE') ?: 'db_suryatara_v3';   // nama database dari file database/db_suryatara_v3.sql
$DB_USER = getenv('MYSQLUSER') ?: 'root';
$DB_PASS = getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '';

/* ------------------------------------------------------------------ */

date_default_timezone_set('Asia/Jakarta');

$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

/** Halaman peringatan database (dengan tautan ke installer). */
function db_error_page(string $judul, string $pesan): void
{
    http_response_code(500);
    // hitung kedalaman folder halaman yang sedang dibuka, supaya link ke install.php tetap benar
    $root   = str_replace('\\', '/', dirname(__DIR__));
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_FILENAME'] ?? $root));
    $sub    = strpos($script, $root) === 0 ? trim(substr($script, strlen($root)), '/') : '';
    $link   = str_repeat('../', $sub === '' ? 0 : substr_count($sub, '/') + 1) . 'install.php';
    die('<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Database belum siap</title></head>
        <body style="margin:0;background:#f5f0e5;font-family:Arial,sans-serif;color:#3f3028">
        <div style="max-width:640px;margin:60px auto;background:#fbf8f1;border:1px solid #ded4c2;border-radius:14px;padding:28px">
            <h2 style="margin:0 0 10px;color:#5c3b2e;font-family:Georgia,serif">' . htmlspecialchars($judul) . '</h2>
            <p style="line-height:1.6">' . $pesan . '</p>
            <p style="line-height:1.6"><strong>Cara memperbaiki (pilih salah satu):</strong></p>
            <ol style="line-height:1.8">
                <li>Klik tombol di bawah untuk membuat ulang database secara otomatis, <em>atau</em></li>
                <li>Buka <code>http://localhost/phpmyadmin</code> &rarr; tab <strong>Import</strong> &rarr; pilih
                    <code>database/db_suryatara_v3.sql</code> &rarr; <strong>Go</strong>.</li>
            </ol>
            <a href="' . $link . '" style="display:inline-block;margin-top:6px;background:#5c3b2e;color:#fff;padding:11px 18px;border-radius:8px;text-decoration:none;font-weight:bold">Buka Halaman Instalasi Database</a>
        </div></body></html>');
}

// Mode installer: koneksi tanpa memilih database (dipakai install.php)
if (defined('RB_INSTALLER')) {
    $pdo = new PDO("mysql:host={$DB_HOST};port={$DB_PORT};charset=utf8mb4", $DB_USER, $DB_PASS, $pdoOptions);
    return;
}

try {
    // Mode uji (opsional): RB_SQLITE_PATH dipakai hanya untuk pengujian otomatis.
    $sqlitePath = getenv('RB_SQLITE_PATH');
    if ($sqlitePath) {
        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, $pdoOptions);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $pdo = new PDO(
            "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            $pdoOptions
        );
        $pdo->exec("SET time_zone = '+07:00'");
    }
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1049 || strpos($e->getMessage(), '1049') !== false) {
        db_error_page('Database belum dibuat', 'Database <code>' . htmlspecialchars($DB_NAME) . '</code> belum ada di MySQL.');
    }
    db_error_page('Koneksi database gagal',
        'Pastikan MySQL di XAMPP sudah aktif dan pengaturan di <code>config/koneksi.php</code> benar.<br><small>Detail: '
        . htmlspecialchars($e->getMessage()) . '</small>');
}

/* ------------------------------------------------------------------
 * Cek versi struktur database. Versi lama (tabel admin/pelanggan,
 * kolom nama_kadar, dst.) memakai nama database yang sama, sehingga
 * file SQL terbaru wajib di-import ulang.
 * ------------------------------------------------------------------ */
try {
    $pdo->query('SELECT id_role FROM role LIMIT 1');
    $pdo->query('SELECT id_pembayaran FROM pengiriman LIMIT 1');
    $pdo->query('SELECT nama_file FROM foto_retur LIMIT 1');
    $pdo->query('SELECT ekspedisi_retur FROM retur LIMIT 1');
} catch (PDOException $e) {
    db_error_page('Struktur database masih versi lama',
        'Database <code>' . htmlspecialchars($DB_NAME) . '</code> masih memakai tabel dari versi sebelumnya, '
        . 'sedangkan website ini memakai ERD terbaru (tabel <code>foto_retur</code>, pengiriman terhubung ke pembayaran, dll.). '
        . 'File SQL terbaru perlu di-import ulang.<br><small>Detail: ' . htmlspecialchars($e->getMessage()) . '</small>');
}

require_once __DIR__ . '/functions.php';
