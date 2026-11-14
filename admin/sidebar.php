<?php
/**
 * Layout panel Admin: sidebar + topbar. Dipanggil di awal setiap halaman admin/ dengan
 *   require __DIR__ . '/sidebar.php';         (halaman di admin/)
 *   require __DIR__ . '/../sidebar.php';      (halaman di subfolder, mis. admin/Produk/)
 * Sebelum admin_header(), isi $pageCss dengan file css halaman tersebut.
 * CSS sidebar/topbar: admin/CSS/sidebar.css
 */
require_once __DIR__ . '/../config/koneksi.php';
require_admin();

function admin_counts(): array
{
    global $pdo;
    static $c = null;
    if ($c === null) {
        $c = [
            'bayar'  => (int)$pdo->query("SELECT COUNT(*) FROM pembayaran WHERE status_pembayaran = 'Sudah Upload Bukti'")->fetchColumn(),
            'proses' => (int)$pdo->query("SELECT COUNT(*) FROM pesanan WHERE status_pesanan = 'Diproses'")->fetchColumn(),
            'retur'  => (int)$pdo->query("SELECT COUNT(*) FROM retur WHERE status_retur IN ('Menunggu Verifikasi','Barang Retur Dikirim')")->fetchColumn(),
            'stok'   => (int)$pdo->query("SELECT COUNT(*) FROM produk WHERE stok <= 5")->fetchColumn(),
        ];
    }
    return $c;
}

function admin_header(string $title, string $active): void
{
    $c = admin_counts();
    $menu = [
        ['', 'Dashboard', 'admin/dashboard.php', 'fa-house', 'dashboard', 0],
        ['DATA BERAS'],
        ['', 'Produk', 'admin/Produk/index.php', 'fa-box-open', 'produk', $c['stok']],
        ['', 'Jenis Beras', 'admin/Jenis/index.php', 'fa-wheat-awn', 'jenis_beras', 0],
        ['', 'Varietas', 'admin/Varietas/index.php', 'fa-seedling', 'varietas', 0],
        ['', 'Ukuran', 'admin/ukuran/index.php', 'fa-weight-hanging', 'ukuran', 0],
        ['', 'Atribut Beras', 'admin/atribut.php', 'fa-list', 'atribut', 0],
        ['TRANSAKSI'],
        ['', 'Konfirmasi Pembayaran', 'admin/pembayaran.php', 'fa-credit-card', 'pembayaran', $c['bayar']],
        ['', 'Proses Pesanan', 'admin/pesanan.php', 'fa-receipt', 'pesanan', $c['proses']],
        ['', 'Pengiriman', 'admin/pengiriman.php', 'fa-truck', 'pengiriman', 0],
        ['', 'Retur', 'admin/retur.php', 'fa-rotate-left', 'retur', $c['retur']],
        ['', 'Pelanggan', 'admin/pelanggan.php', 'fa-users', 'pelanggan', 0],
        ['PENGATURAN'],
        ['', 'Jasa Kurir', 'admin/jasa_kurir/index.php', 'fa-truck-fast', 'jasa_kurir', 0],
        ['', 'Profil Saya', 'admin/profil.php', 'fa-user-gear', 'profil', 0],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> - Admin <?= h(TOKO_NAMA) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('admin/CSS/sidebar.css') ?>">
<?php foreach ((array)($GLOBALS['pageCss'] ?? []) as $css): ?>
    <link rel="stylesheet" href="<?= asset($css) ?>">
<?php endforeach; ?>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>">
</head>
<body class="admin-body">
<div class="admin-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <img src="<?= asset('images/logo.png') ?>" alt="<?= h(TOKO_NAMA) ?>" class="sidebar-logo">
        </div>
        <nav class="sidebar-menu">
            <?php foreach ($menu as $m): ?>
                <?php if (count($m) === 1): ?>
                    <div class="sidebar-section-title"><?= h($m[0]) ?></div>
                <?php else: ?>
                    <a href="<?= url($m[2]) ?>" class="menu-item <?= $active === $m[4] ? 'active' : '' ?>">
                        <i class="fas <?= $m[3] ?>"></i><span><?= h($m[1]) ?></span>
                        <?php if ($m[5] > 0): ?><span class="menu-badge"><?= (int)$m[5] ?></span><?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="sidebar-divider"></div>
            <a href="<?= url('index.php') ?>" class="menu-item" target="_blank"><i class="fas fa-store"></i><span>Lihat Toko</span></a>
            <a href="<?= url('admin/logout.php') ?>" class="menu-item logout-item" onclick="return confirm('Keluar dari panel?')"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a>
        </nav>
    </aside>
    <div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('open');this.classList.remove('show')"></div>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="sidebar-toggle" type="button" onclick="document.getElementById('sidebar').classList.add('open');document.querySelector('.sidebar-backdrop').classList.add('show')" aria-label="Menu"><i class="fas fa-bars"></i></button>
                <span class="topbar-title"><?= h($title) ?></span>
            </div>
            <div class="topbar-right">
                <?php if ($c['bayar'] > 0): ?>
                <a href="<?= url('admin/pembayaran.php?status=Sudah+Upload+Bukti') ?>" class="notification-button"><?= $c['bayar'] ?> bukti bayar belum dicek</a>
                <?php endif; ?>
                <a href="<?= url('admin/profil.php') ?>" class="admin-profile">
                    <div class="admin-avatar"><i class="fas fa-user"></i></div>
                    <span class="admin-name"><?= h($_SESSION['nama']) ?></span> <small style="color:var(--text-muted);font-weight:400">Admin</small>
                </a>
            </div>
        </header>
        <div class="content-container">
            <?= str_replace('class="flash-wrap"', 'class="flash-wrap admin-flash"', flash_render()) ?>
    <?php
}

function admin_footer(): void
{
    ?>
        </div>
    </main>
</div>
<div class="lightbox" id="lightbox" onclick="this.classList.remove('show')"><img src="" alt="Pratinjau"></div>
<script src="<?= asset('Includes/js/script.js') ?>"></script>
</body>
</html>
    <?php
}

/** Header halaman (judul + breadcrumb + tombol kanan) */
function page_header(string $title, string $desc = '', string $right = ''): void
{
    echo '<div class="page-header"><div><div class="breadcrumb-a"><a href="' . url(home_for_role()) . '">' . 'Dashboard' . '</a><span>&rsaquo;</span><span>' . h($title) . '</span></div>'
        . '<h1>' . h($title) . '</h1>' . ($desc ? '<p>' . h($desc) . '</p>' : '') . '</div>' . $right . '</div>';
}
