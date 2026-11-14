<?php
/**
 * Layout panel Owner: sidebar + topbar. Dipanggil di awal setiap halaman owner/ dengan
 *   require __DIR__ . '/sidebar.php';         (halaman di owner/)
 *   require __DIR__ . '/../sidebar.php';      (halaman di subfolder, mis. owner/)
 * Sebelum admin_header(), isi $pageCss dengan file css halaman tersebut.
 * CSS sidebar/topbar: owner/CSS/sidebar.css
 */
require_once __DIR__ . '/../config/koneksi.php';
require_owner();

function admin_header(string $title, string $active): void
{
    $menu = [
        ['OWNER'],
        ['', 'Laporan Keuangan', 'owner/laporan.php', 'fa-chart-line', 'laporan', 0],
        ['', 'Kelola Admin', 'owner/admin.php', 'fa-user-shield', 'kelola_admin', 0],
    ];
    ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h($title) ?> - Owner <?= h(TOKO_NAMA) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= asset('owner/CSS/sidebar.css') ?>">
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
            <a href="<?= url('owner/logout.php') ?>" class="menu-item logout-item" onclick="return confirm('Keluar dari panel?')"><i class="fas fa-right-from-bracket"></i><span>Keluar</span></a>
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
                <a href="<?= url('owner/laporan.php') ?>" class="admin-profile">
                    <div class="admin-avatar"><i class="fas fa-crown"></i></div>
                    <span class="admin-name"><?= h($_SESSION['nama']) ?></span> <small style="color:var(--text-muted);font-weight:400">Owner</small>
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
    echo '<div class="page-header"><div><div class="breadcrumb-a"><a href="' . url(home_for_role()) . '">' . 'Panel Owner' . '</a><span>&rsaquo;</span><span>' . h($title) . '</span></div>'
        . '<h1>' . h($title) . '</h1>' . ($desc ? '<p>' . h($desc) . '</p>' : '') . '</div>' . $right . '</div>';
}
