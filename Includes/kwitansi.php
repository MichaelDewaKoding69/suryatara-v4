<?php
/**
 * Activity & Sequence "Menerima Kwitansi": getKwitansi -> susunKwitansi -> tampilkan / cetak / unduh (PDF via print).
 */
require_once __DIR__ . '/../config/koneksi.php';
if (!is_pelanggan() && !is_admin() && !is_owner()) {
    require_pelanggan();
}

$id  = (int)($_GET['id'] ?? 0);
$sql = 'SELECT ps.*, u.nama AS nama_pelanggan, u.email, pg.alamat_pengiriman, pg.ongkir, pg.no_resi, jk.nama_kurir
        FROM pesanan ps JOIN `user` u ON u.id_user = ps.id_user
        LEFT JOIN pembayaran pbk ON pbk.id_pesanan = ps.id_pesanan AND pbk.status_pembayaran = \'Disetujui\'
        LEFT JOIN pengiriman pg ON pg.id_pembayaran = pbk.id_pembayaran
        LEFT JOIN jasa_kurir jk ON jk.id_jasa_kurir = pg.id_jasa_kurir
        WHERE ps.id_pesanan = ?' . (is_pelanggan() ? ' AND ps.id_user = ' . user_id() : '');
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$o = $stmt->fetch();
if (!$o || $o['status_pesanan'] !== 'Selesai') {
    flash('warning', 'Kwitansi hanya tersedia untuk pesanan berstatus Selesai.');
    redirect(is_pelanggan() ? 'Includes/pesanan-saya.php' : home_for_role());
}

$stmt = $pdo->prepare('SELECT pp.*, p.nama_produk FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk WHERE pp.id_pesanan = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM pembayaran WHERE id_pesanan = ? AND status_pembayaran = 'Disetujui' ORDER BY tanggal_pembayaran DESC LIMIT 1");
$stmt->execute([$id]);
$bayar = $stmt->fetch();

$kode = kode($id);
$totalProduk = 0;
foreach ($items as $it) {
    $totalProduk += $it['jumlah'] * $it['harga_satuan'];
}
$pageTitle  = 'Kwitansi ' . $kode;
$activeMenu = 'pesanan';
$pageCss = 'Includes/css/kwitansi.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap narrow">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print flex-wrap gap-2">
        <a href="<?= url(is_admin() ? 'admin/pesanan.php?id=' . $id : 'Includes/pesanan-detail.php?id=' . $id) ?>" class="btn-outline-brown btn-sm-x">Kembali</a>
        <button onclick="window.print()" class="btn-brown btn-sm-x">Cetak / simpan PDF</button>
    </div>

    <div class="kwitansi">
        <div class="kwitansi-head">
            <div>
                <img src="<?= asset('images/logo.png') ?>" alt="Logo">
                <div style="font-size:12px;color:var(--text-secondary);margin-top:6px;max-width:320px"><?= h(TOKO_PERUSAHAAN) ?><br><?= h(TOKO_ALAMAT) ?><br><?= h(TOKO_TELEPON) ?></div>
            </div>
            <div style="text-align:right">
                <h2>KWITANSI</h2>
                <div style="font-size:13px;margin-top:6px">No. <strong>KW/<?= h($kode) ?></strong></div>
                <div style="font-size:12px;color:var(--text-secondary)">Tanggal: <?= tgl($bayar['tanggal_pembayaran'] ?? $o['tanggal_pesanan']) ?></div>
            </div>
        </div>

        <div class="row" style="font-size:13px">
            <div class="col-sm-6 mb-3">
                <div style="color:var(--text-muted);font-size:11px;font-weight:700;letter-spacing:1px">DITERIMA DARI</div>
                <strong><?= h($o['nama_pelanggan']) ?></strong><br><?= h($o['email']) ?><br>
                <?= nl2br(h($o['alamat_pengiriman'])) ?>
            </div>
            <div class="col-sm-6 mb-3" style="text-align:right">
                <div style="color:var(--text-muted);font-size:11px;font-weight:700;letter-spacing:1px">PESANAN</div>
                <?= h($kode) ?> &middot; <?= tgl($o['tanggal_pesanan']) ?><br>
                Kurir: <?= h($o['nama_kurir'] ?? '-') ?><br>
                Resi: <?= h($o['no_resi'] ?: '-') ?>
            </div>
        </div>

        <table class="table-x">
            <thead><tr><th>#</th><th>Produk</th><th class="num">Harga</th><th class="num">Ongkir/pcs</th><th class="num">Jml</th><th class="num">Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $i => $it): ?>
                <tr><td><?= $i + 1 ?></td><td><?= h($it['nama_produk']) ?></td><td class="num"><?= rupiah($it['harga_satuan']) ?></td><td class="num"><?= rupiah($it['ongkir_satuan']) ?></td><td class="num"><?= (int)$it['jumlah'] ?></td><td class="num"><?= rupiah($it['subtotal']) ?></td></tr>
            <?php endforeach; ?>
            <tr><td colspan="5" class="num">Total harga produk</td><td class="num"><?= rupiah($totalProduk) ?></td></tr>
            <tr><td colspan="5" class="num">Total ongkos kirim</td><td class="num"><?= rupiah($o['ongkir']) ?></td></tr>
            <tr><td colspan="5" class="num"><strong>TOTAL DIBAYAR</strong></td><td class="num"><strong style="color:var(--primary);font-size:16px"><?= rupiah($o['total_harga']) ?></strong></td></tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between align-items-end mt-4 flex-wrap gap-3">
            <div style="font-size:12px;color:var(--text-secondary)">
                <?php if ($bayar): ?>
                    Dibayar via transfer ke <?= h(REKENING_BANK . ' ' . REKENING_NOMOR) ?> sebesar <?= rupiah($bayar['jumlah_bayar']) ?><br>
                    Tanggal pembayaran: <?= tgl($bayar['tanggal_pembayaran'], true) ?>
                <?php endif; ?>
            </div>
            <div style="text-align:center">
                <span class="kwitansi-stamp">LUNAS</span>
                <div style="font-size:12px;margin-top:14px"><?= h(TOKO_NAMA) ?></div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
