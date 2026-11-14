<?php
/**
 * Header pelanggan. Variabel opsional sebelum include:
 *   $pageTitle  - judul tab
 *   $activeMenu - beranda | produk | keranjang | pesanan | retur
 *   $pageCss    - file css khusus halaman, mis. 'Includes/css/produk.css'
 */
require_once __DIR__ . '/../config/koneksi.php';

$pageTitle  = $pageTitle ?? TOKO_NAMA;
$activeMenu = $activeMenu ?? '';
$cartCount  = cart_count();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($pageTitle) ?> | <?= h(TOKO_NAMA) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('Includes/css/header.css') ?>">
    <link rel="stylesheet" href="<?= asset('Includes/css/footer.css') ?>">
<?php foreach ((array)($pageCss ?? []) as $css): ?>
    <link rel="stylesheet" href="<?= asset($css) ?>">
<?php endforeach; ?>
    <link rel="icon" href="<?= asset('images/favicon.svg') ?>">
</head>
<body>

<nav class="navbar custom-navbar">
    <div class="container-fluid d-flex align-items-center justify-content-between position-relative">
        <div class="d-flex align-items-center">
            <button type="button" class="nav-icon" data-bs-toggle="offcanvas" data-bs-target="#menuOffcanvas" aria-label="Buka menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="nav-links">
                <a href="<?= url('index.php') ?>" class="<?= $activeMenu === 'beranda' ? 'active' : '' ?>">Beranda</a>
                <a href="<?= url('Includes/produk.php') ?>" class="<?= $activeMenu === 'produk' ? 'active' : '' ?>">Produk</a>
                <?php if (is_pelanggan()): ?>
                    <a href="<?= url('Includes/pesanan-saya.php') ?>" class="<?= $activeMenu === 'pesanan' ? 'active' : '' ?>">Pesanan Saya</a>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?= url('index.php') ?>" class="navbar-logo">
            <img src="<?= asset('images/logo.png') ?>" class="logo-full" alt="<?= h(TOKO_NAMA) ?>">
        </a>

        <div class="d-flex align-items-center gap-1">
            <button type="button" class="nav-icon" onclick="document.getElementById('searchBox').classList.toggle('show')" aria-label="Cari">
                <i class="fas fa-search"></i>
            </button>
            <a href="<?= url('Includes/cart-view.php') ?>" class="nav-icon position-relative" aria-label="Keranjang">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cartCount > 0): ?><span class="cart-badge"><?= $cartCount ?></span><?php endif; ?>
            </a>
            <?php if (is_pelanggan()): ?>
                <a href="<?= url('Includes/pesanan-saya.php') ?>" class="account-link ms-2"><?= h($_SESSION['nama']) ?></a>
                <a href="<?= url('Includes/logout.php') ?>" class="nav-icon" title="Keluar" aria-label="Keluar"
                   onclick="return confirm('Keluar dari akun?')"><i class="fas fa-right-from-bracket"></i></a>
            <?php elseif (is_admin() || is_owner()): ?>
                <a href="<?= url(home_for_role()) ?>" class="account-link ms-2"><?= is_owner() ? 'Panel Owner' : 'Panel Admin' ?></a>
            <?php else: ?>
                <a href="<?= url('Includes/login.php') ?>" class="account-link ms-2">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div id="searchBox">
    <form action="<?= url('Includes/produk.php') ?>" method="GET">
        <input type="text" name="q" placeholder="Cari beras..." required>
        <button type="submit" class="search-button">Cari</button>
    </form>
</div>

<div class="offcanvas offcanvas-start suryatara-offcanvas" tabindex="-1" id="menuOffcanvas">
    <div class="offcanvas-header">
        <img src="<?= asset('images/logo.png') ?>" alt="<?= h(TOKO_NAMA) ?>" class="offcanvas-logo">
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        <a href="<?= url('index.php') ?>" class="hamburg-menu-item <?= $activeMenu === 'beranda' ? 'active' : '' ?>">Beranda</a>
        <a href="<?= url('Includes/produk.php') ?>" class="hamburg-menu-item <?= $activeMenu === 'produk' ? 'active' : '' ?>">Produk</a>
        <a href="<?= url('Includes/cart-view.php') ?>" class="hamburg-menu-item <?= $activeMenu === 'keranjang' ? 'active' : '' ?>">Keranjang<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>
        <?php if (is_pelanggan()): ?>
            <a href="<?= url('Includes/pesanan-saya.php') ?>" class="hamburg-menu-item <?= $activeMenu === 'pesanan' ? 'active' : '' ?>">Pesanan Saya</a>
            <a href="<?= url('Includes/retur-saya.php') ?>" class="hamburg-menu-item <?= $activeMenu === 'retur' ? 'active' : '' ?>">Retur Saya</a>
        <?php endif; ?>
        <a href="<?= url('index.php#kontak') ?>" class="hamburg-menu-item">Kontak</a>
        <div class="offcanvas-divider"></div>
        <?php if (is_pelanggan()): ?>
            <a href="<?= url('Includes/logout.php') ?>" class="hamburg-menu-item">Keluar</a>
        <?php elseif (is_admin() || is_owner()): ?>
            <a href="<?= url(home_for_role()) ?>" class="hamburg-menu-item"><?= is_owner() ? 'Panel Owner' : 'Panel Admin' ?></a>
        <?php else: ?>
            <a href="<?= url('Includes/login.php') ?>" class="hamburg-menu-item">Masuk</a>
            <a href="<?= url('Includes/register.php') ?>" class="hamburg-menu-item">Daftar</a>
        <?php endif; ?>
    </div>
</div>

<?= flash_render() ?>
