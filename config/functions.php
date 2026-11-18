<?php
/**
 * Helper umum aplikasi.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* =====================================================================
   PENGATURAN TOKO
   ===================================================================== */
const TOKO_NAMA        = 'Roemah Beras';
const TOKO_PERUSAHAAN  = 'CV Surya Tara Food';
const TOKO_ALAMAT      = 'Jl. Raya Cipondoh No. 88, Cipondoh, Kota Tangerang, Banten 15148';
const TOKO_TELEPON     = '+62 815-7109-3991';
const TOKO_WHATSAPP    = '6281571093991';
const TOKO_EMAIL       = 'suryatarafood@gmail.com';
const REKENING_BANK    = 'BCA';
const REKENING_NOMOR   = '123456789';
const REKENING_NAMA    = 'SAIPUL HIDAYAT';
const MAKS_UPLOAD_MB   = 5;
const TARIF_ONGKIR_PER_KG = 4000;   // ongkir_satuan = berat ukuran (kg) x tarif ini

define('APP_ROOT', realpath(__DIR__ . '/..'));

/* =====================================================================
   URL DASAR (otomatis mengikuti nama folder di htdocs)
   ===================================================================== */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    $root    = str_replace('\\', '/', APP_ROOT);
    if ($docRoot) {
        $docRoot = rtrim(str_replace('\\', '/', $docRoot), '/');
        if (stripos($root, $docRoot) === 0) {
            $base = substr($root, strlen($docRoot));
            $base = '/' . trim(str_replace(' ', '%20', $base), '/');
            return $base = rtrim($base, '/');
        }
    }
    return $base = '';
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

/**
 * Link file statis (gambar di images/, css dan js di Includes/ atau admin/CSS/).
 * File css/js diberi ?v=waktu ubah supaya browser tidak memakai versi lama dari cache.
 */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = APP_ROOT . '/' . $path;
    return url(str_replace(' ', '%20', $path)) . (preg_match('/\.(css|js)$/', $path) && is_file($file) ? '?v=' . filemtime($file) : '');
}

/** Folder upload ada di dalam images/ */
const FOLDER_UPLOAD = [
    'produk'         => 'Foto Produk',
    'bukti_transfer' => 'Bukti Pembayaran',
    'retur'          => 'Foto Retur',
    'refund'         => 'Bukti Refund',
];

function folder_upload(string $folder): string
{
    return FOLDER_UPLOAD[$folder] ?? $folder;
}

function upload_url(string $folder, ?string $file): string
{
    if (!$file) {
        return asset('images/no-image.svg');
    }
    if (preg_match('#^https?://#', $file)) {
        return $file;
    }
    return url('images/' . rawurlencode(folder_upload($folder)) . '/' . rawurlencode(basename($file)));
}

function redirect(string $path): void
{
    header('Location: ' . (preg_match('#^https?://#', $path) ? $path : url($path)));
    exit;
}

/* =====================================================================
   FORMAT
   ===================================================================== */
function h($value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function rupiah($value): string
{
    return 'Rp ' . number_format((float)$value, 0, ',', '.');
}

function tgl($datetime, bool $withTime = false): string
{
    if (!$datetime) {
        return '-';
    }
    $bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime((string)$datetime);
    $out = date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    return $withTime ? $out . ', ' . date('H:i', $ts) : $out;
}

function kg($value): string
{
    $v = (float)$value;
    return rtrim(rtrim(number_format($v, 2, ',', '.'), '0'), ',') . ' kg';
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/** Kelas warna badge untuk setiap status pada sistem. */
function status_class(?string $status): string
{
    $map = [
        'Belum Melakukan Pembayaran' => 'muted',
        'Sudah Upload Bukti'   => 'warning',
        'Menunggu Verifikasi'  => 'warning',
        'Diproses'             => 'info',
        'Dikirim'              => 'primary',
        'Selesai'              => 'success',
        'Dibatalkan'           => 'danger',
        'Terverifikasi'        => 'success',
        'Ditolak'              => 'danger',
        'Disetujui'            => 'success',
        'Barang Retur Dikirim' => 'primary',
        'Refund Selesai'       => 'success',
        'Belum Dikirim'        => 'muted',
        'Diterima'             => 'success',
        'Owner'                => 'primary',
        'Admin'                => 'info',
        'Pelanggan'            => 'muted',
    ];
    return $map[$status] ?? 'muted';
}

function badge(?string $status): string
{
    return '<span class="badge-status ' . status_class($status) . '">' . h($status) . '</span>';
}

/* =====================================================================
   FLASH MESSAGE
   ===================================================================== */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flash_render(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $html .= '<div class="alert-box ' . h($f['type']) . '"><span>' . h($f['message']) . '</span>'
               . '<button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Tutup">&times;</button></div>';
    }
    unset($_SESSION['flash']);
    return '<div class="flash-wrap">' . $html . '</div>';
}

/* =====================================================================
   CSRF
   ===================================================================== */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_check(): void
{
    if (!hash_equals(csrf_token(), (string)($_POST['csrf'] ?? ''))) {
        flash('danger', 'Sesi formulir kedaluwarsa. Silakan coba lagi.');
        redirect(ltrim(str_replace(base_url(), '', $_SERVER['REQUEST_URI'] ?? '/'), '/'));
    }
}

/* =====================================================================
   AUTENTIKASI
   ===================================================================== */
function is_pelanggan(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'pelanggan';
}

function is_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function is_owner(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'owner';
}

function require_owner(): void
{
    if (!is_owner()) {
        flash('warning', 'Halaman ini khusus Owner. Silakan login sebagai owner.');
        redirect('Includes/login.php');
    }
}

/** Halaman tujuan setelah login sesuai peran. */
function home_for_role(): string
{
    if (is_admin()) {
        return 'admin/dashboard.php';
    }
    if (is_owner()) {
        return 'owner/laporan.php';
    }
    return 'index.php';
}

/** id_role berdasarkan nama_role (Owner / Admin / Pelanggan). */
function role_id(string $nama): int
{
    global $pdo;
    $st = $pdo->prepare('SELECT id_role FROM role WHERE LOWER(nama_role) = LOWER(?)');
    $st->execute([$nama]);
    $id = $st->fetchColumn();
    if ($id === false) {
        throw new RuntimeException("Role $nama belum ada di tabel role.");
    }
    return (int)$id;
}

function require_pelanggan(): void
{
    if (!is_pelanggan()) {
        flash('warning', 'Silakan login sebagai pelanggan terlebih dahulu.');
        redirect('Includes/login.php?next=' . urlencode($_SERVER['REQUEST_URI'] ?? ''));
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('warning', 'Silakan login sebagai admin terlebih dahulu.');
        redirect('Includes/login.php');
    }
}

function user_id(): int
{
    return (int)($_SESSION['user_id'] ?? 0);
}

/* =====================================================================
   UPLOAD GAMBAR
   ===================================================================== */
/**
 * Validasi & simpan file gambar. Mengembalikan nama file, atau melempar
 * RuntimeException dengan pesan yang ramah pengguna.
 */
function upload_image(string $field, string $folder, string $prefix, bool $required = true): ?string
{
    $file = $_FILES[$field] ?? null;
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        if ($required) {
            throw new RuntimeException('File gambar wajib diunggah.');
        }
        return null;
    }
    return simpan_gambar($file, $folder, $prefix);
}

/**
 * Upload beberapa gambar sekaligus (input name="field[]" multiple).
 * Semua file divalidasi dulu; bila ada yang tidak valid, tidak ada yang disimpan.
 */
function upload_images(string $field, string $folder, string $prefix, int $min = 1, int $max = 5): array
{
    $f = $_FILES[$field] ?? null;
    $files = [];
    if ($f && is_array($f['name'])) {
        foreach ($f['name'] as $i => $name) {
            if (($f['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = ['name' => $name, 'type' => $f['type'][$i], 'tmp_name' => $f['tmp_name'][$i], 'error' => $f['error'][$i], 'size' => $f['size'][$i]];
        }
    }
    if (count($files) < $min) {
        throw new RuntimeException("Unggah minimal $min foto.");
    }
    if (count($files) > $max) {
        throw new RuntimeException("Maksimal $max foto.");
    }
    foreach ($files as $file) {
        cek_gambar($file);
    }
    $saved = [];
    foreach ($files as $i => $file) {
        $saved[] = simpan_gambar($file, $folder, $prefix . '_' . ($i + 1));
    }
    return $saved;
}

function cek_gambar(array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload gagal (kode ' . (int)$file['error'] . '). Coba file lain.');
    }
    if ($file['size'] > MAKS_UPLOAD_MB * 1024 * 1024) {
        throw new RuntimeException('Ukuran file maksimal ' . MAKS_UPLOAD_MB . ' MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('Format file harus JPG, PNG, atau WEBP.');
    }
    if (@getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('File "' . $file['name'] . '" bukan gambar yang valid.');
    }
    return $ext;
}

function simpan_gambar(array $file, string $folder, string $prefix): string
{
    $ext = cek_gambar($file);
    $dir = APP_ROOT . '/images/' . folder_upload($folder);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $name = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $ok = is_uploaded_file($file['tmp_name'])
        ? move_uploaded_file($file['tmp_name'], $dir . '/' . $name)
        : rename($file['tmp_name'], $dir . '/' . $name);
    if (!$ok) {
        throw new RuntimeException('Gagal menyimpan file ke server.');
    }
    return $name;
}

/* =====================================================================
   KERANJANG (session)
   ===================================================================== */
function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(cart());
}

/**
 * Nominal refund retur = (harga barang + 2 x ongkir per pcs) x jumlah retur.
 * Ongkir dihitung 2x: ongkir saat pesanan dikirim + ongkir pelanggan mengirim balik barang.
 * ongkir_satuan diambil dari pesanan_produk (berat x tarif per kg saat pesanan dibuat).
 */
function nominal_refund($hargaSatuan, $ongkirSatuan, int $jumlah): float
{
    return ((float)$hargaSatuan + 2 * (float)$ongkirSatuan) * $jumlah;
}

/** Ongkir per pcs produk = berat ukuran (kg) x tarif ongkir per kg. */
function ongkir_satuan(float $beratKg): float
{
    return round($beratKg * TARIF_ONGKIR_PER_KG);
}

/**
 * Kode unik transfer (1-999), dihitung dari id pesanan.
 * Ditambahkan ke tagihan supaya setiap transfer punya nominal berbeda dan admin mudah
 * mencocokkan transfer dengan pesanannya. Tidak disimpan di database (ERD tetap) dan
 * tidak dihitung sebagai penjualan di laporan; nominal transfernya tersimpan di pembayaran.jumlah_bayar.
 */
function kode_unik(int $idPesanan): int
{
    return ($idPesanan - 1) % 999 + 1;
}

/** Nominal yang harus ditransfer pelanggan = total pesanan + kode unik. */
function total_transfer(array $pesanan): float
{
    return (float)$pesanan['total_harga'] + kode_unik((int)$pesanan['id_pesanan']);
}

/** Ekspresi SQL yang sama dengan total_transfer(), untuk filter di query (alias tabel pesanan: ps). */
const SQL_TOTAL_TRANSFER = '(ps.total_harga + ((ps.id_pesanan - 1) % 999 + 1))';

/** Kode tampilan pesanan, contoh RB-0007 (diturunkan dari id_pesanan). */
function kode(int $idPesanan): string
{
    return sprintf('RB-%04d', $idPesanan);
}
