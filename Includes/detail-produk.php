<?php
/**
 * Activity "Membuat Pesanan": menampilkan informasi produk, memilih produk & variasi beras,
 * menentukan jumlah pesanan.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/produk-helper.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare(PRODUK_SELECT . ' WHERE p.id_produk = :id');
$stmt->execute([':id' => $id]);
$p = $stmt->fetch();
if (!$p) {
    flash('warning', 'Produk tidak ditemukan.');
    redirect('Includes/produk.php');
}

$namaBeras = produk_nama_dasar($p);

// Variasi ukuran dari beras yang sama (merek + jenis + kadar patahan)
$stmt = $pdo->prepare('SELECT p.id_produk, p.harga, p.stok, u.nama_ukuran FROM produk p JOIN ukuran u ON u.id_ukuran = p.id_ukuran
                       WHERE p.id_jenis_beras = :j AND p.merek = :mk AND p.id_kadar_patahan = :k ORDER BY u.berat_kg');
$stmt->execute([':j' => $p['id_jenis_beras'], ':mk' => $p['merek'], ':k' => $p['id_kadar_patahan']]);
$variasi = $stmt->fetchAll();

// Beras lain dengan mutu yang sama (bukan beras ini)
$stmt = $pdo->prepare(PRODUK_SELECT . ' WHERE jb.id_mutu = :m ORDER BY terjual DESC, p.id_produk ASC');
$stmt->execute([':m' => $p['id_mutu']]);
$kunciIni = produk_kunci($p);
$terkait = array_slice(array_values(array_filter(produk_grup($stmt->fetchAll()),
    fn($g) => produk_kunci($g) !== $kunciIni)), 0, 4);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(pp.jumlah),0) FROM pesanan_produk pp JOIN pesanan ps ON ps.id_pesanan = pp.id_pesanan
                       WHERE pp.id_produk = :id AND ps.status_pesanan <> 'Dibatalkan'");
$stmt->execute([':id' => $id]);
$terjual = (int)$stmt->fetchColumn();

$pageTitle  = $namaBeras . ' ' . $p['nama_ukuran'];
$activeMenu = 'produk';
$pageCss = 'Includes/css/detail-produk.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="breadcrumb-lite">
        <a href="<?= url('index.php') ?>">Beranda</a><span>&rsaquo;</span>
        <a href="<?= url('Includes/produk.php') ?>">Produk</a><span>&rsaquo;</span>
        <span><?= h($namaBeras) ?></span>
    </div>

    <div class="detail-product">
        <div class="detail-image-box">
            <img src="<?= upload_url('produk', $p['foto']) ?>" alt="<?= h($p['nama_produk']) ?>" data-zoom style="cursor:zoom-in">
        </div>

        <div class="detail-info">
            <span class="detail-category"><?= h($p['merek']) ?> &middot; <?= h($p['nama_jenis']) ?></span>
            <h1><?= h($namaBeras) ?></h1>
            <div style="color:var(--text-secondary);font-size:13px">
                <?= $terjual ?> terjual &middot; stok <?= (int)$p['stok'] > 0 ? (int)$p['stok'] : '<span style="color:var(--danger)">habis</span>' ?>
            </div>
            <div class="detail-price"><?= rupiah($p['harga']) ?></div>
            <div style="font-size:12px;color:var(--text-secondary);margin-bottom:12px">+ ongkir <?= rupiah(ongkir_satuan((float)$p['berat_kg'])) ?> per pcs (<?= kg($p['berat_kg']) ?> x <?= rupiah(TARIF_ONGKIR_PER_KG) ?>/kg)</div>

            <div class="form-label-x">Pilih ukuran</div>
            <div class="variant-row">
                <?php foreach ($variasi as $v): ?>
                    <a href="<?= url('Includes/detail-produk.php?id=' . (int)$v['id_produk']) ?>"
                       class="<?= (int)$v['id_produk'] === $id ? 'active' : '' ?><?= (int)$v['stok'] <= 0 ? ' habis' : '' ?>">
                        <strong><?= h($v['nama_ukuran']) ?></strong>
                        <small><?= rupiah($v['harga']) ?></small>
                    </a>
                <?php endforeach; ?>
            </div>

            <p class="detail-short"><?= nl2br(h($p['deskripsi'] ?: $p['deskripsi_jenis'])) ?></p>

            <table class="spec-table">
                <tr><th>Varietas</th><td><?= h($p['nama_varietas']) ?></td></tr>
                <tr><th>Mutu</th><td><?= h($p['nama_mutu']) ?></td></tr>
                <tr><th>Warna</th><td><?= h($p['nama_warna']) ?></td></tr>
                <tr><th>Tekstur</th><td><?= h($p['nama_tekstur']) ?></td></tr>
                <tr><th>Bulir</th><td><?= h($p['nama_bulir']) ?></td></tr>
                <tr><th>Kadar patahan</th><td><?= h($p['nama_kadar']) ?><?= $p['deskripsi_kadar'] ? ' <span style="font-weight:400;color:var(--text-secondary)">(' . h(lcfirst($p['deskripsi_kadar'])) . ')</span>' : '' ?></td></tr>
                <tr><th>Ukuran</th><td><?= h($p['nama_ukuran']) ?></td></tr>
            </table>


            <?php if ($p['stok'] > 0): ?>
                <form method="POST" action="<?= url('Includes/cart-view.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                    <div class="form-label-x">Jumlah</div>
                    <div class="qty-control" data-qty>
                        <button type="button" data-step="-1">&minus;</button>
                        <input type="number" name="jumlah" value="1" min="1" max="<?= (int)$p['stok'] ?>">
                        <button type="button" data-step="1">+</button>
                    </div>
                    <div class="detail-actions">
                        <button type="submit" name="aksi" value="tambah" class="btn-outline-brown">Masukkan keranjang</button>
                        <button type="submit" name="aksi" value="beli" class="btn-orange">Beli sekarang</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="alert-box warning"><span>Stok sedang habis, produk ini belum bisa dipesan.</span></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($terkait): ?>
        <div class="section-head" style="margin-top:36px">
            <h2 class="section-title">Mutu yang sama</h2>
            <a href="<?= url('Includes/produk.php?mutu[]=' . (int)$p['id_mutu']) ?>" class="link-more">Lihat semua</a>
        </div>
        <div class="produk-grid">
            <?php foreach ($terkait as $t) echo produk_card($t); ?>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
