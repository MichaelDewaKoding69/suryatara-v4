<?php
/**
 * Daftar pesanan pelanggan (titik masuk untuk Melakukan Pembayaran, Menerima Pesanan,
 * Menerima Kwitansi, dan Mengajukan Retur). Pesanan yang belum dibayar bisa dibatalkan.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_pelanggan();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'batal') {
    csrf_check();
    $id = (int)$_POST['id_pesanan'];
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Dibatalkan' WHERE id_pesanan = ? AND id_user = ? AND status_pesanan = 'Belum Melakukan Pembayaran'");
        $stmt->execute([$id, user_id()]);
        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Pesanan ini tidak bisa dibatalkan.');
        }
        $items = $pdo->prepare('SELECT id_produk, jumlah FROM pesanan_produk WHERE id_pesanan = ?');
        $items->execute([$id]);
        $upd = $pdo->prepare('UPDATE produk SET stok = stok + ? WHERE id_produk = ?');
        foreach ($items->fetchAll() as $it) {
            $upd->execute([$it['jumlah'], $it['id_produk']]);
        }
        $pdo->commit();
        flash('success', 'Pesanan ' . kode($id) . ' dibatalkan.');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash('danger', $e->getMessage());
    }
    redirect('Includes/pesanan-saya.php');
}

$statusList = ['Belum Melakukan Pembayaran', 'Sudah Upload Bukti', 'Diproses', 'Dikirim', 'Selesai', 'Dibatalkan'];
$tab = in_array($_GET['status'] ?? '', $statusList, true) ? $_GET['status'] : '';

$counts = [];
$stmt = $pdo->prepare('SELECT status_pesanan, COUNT(*) c FROM pesanan WHERE id_user = ? GROUP BY status_pesanan');
$stmt->execute([user_id()]);
foreach ($stmt->fetchAll() as $r) {
    $counts[$r['status_pesanan']] = (int)$r['c'];
}

$sql = "SELECT ps.*, pg.no_resi, jk.nama_kurir,
               (SELECT COUNT(*) FROM retur r WHERE r.id_pesanan = ps.id_pesanan) AS jml_retur,
               (SELECT COUNT(*) FROM pesanan_produk x WHERE x.id_pesanan = ps.id_pesanan) AS jml_item
        FROM pesanan ps
        LEFT JOIN pembayaran pb ON pb.id_pesanan = ps.id_pesanan AND pb.status_pembayaran = 'Disetujui'
        LEFT JOIN pengiriman pg ON pg.id_pembayaran = pb.id_pembayaran
        LEFT JOIN jasa_kurir jk ON jk.id_jasa_kurir = pg.id_jasa_kurir
        WHERE ps.id_user = ?" . ($tab ? ' AND ps.status_pesanan = ?' : '') . ' ORDER BY ps.tanggal_pesanan DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($tab ? [user_id(), $tab] : [user_id()]);
$orders = $stmt->fetchAll();

$itemStmt = $pdo->prepare('SELECT pp.*, p.nama_produk, p.foto FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk WHERE pp.id_pesanan = ?');

$catatan = [
    'Belum Melakukan Pembayaran' => 'Belum ada bukti bayar.',
    'Sudah Upload Bukti'         => 'Bukti bayar sedang dicek admin.',
    'Diproses'                   => 'Pembayaran disetujui, pesanan sedang dikemas.',
];

$pageTitle  = 'Pesanan Saya';
$activeMenu = 'pesanan';
$pageCss = 'Includes/css/pesanan-saya.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="page-heading">
        <div><h1>Pesanan saya</h1></div>
        <a href="<?= url('Includes/retur-saya.php') ?>" class="link-more">Lihat retur saya</a>
    </div>

    <div class="tab-row">
        <a href="<?= url('Includes/pesanan-saya.php') ?>" class="tab-pill <?= $tab === '' ? 'active' : '' ?>">Semua <small><?= array_sum($counts) ?></small></a>
        <?php foreach ($statusList as $s): ?>
            <a href="<?= url('Includes/pesanan-saya.php?status=' . urlencode($s)) ?>" class="tab-pill <?= $tab === $s ? 'active' : '' ?>"><?= h($s) ?><?php if (!empty($counts[$s])): ?> <small><?= $counts[$s] ?></small><?php endif; ?></a>
        <?php endforeach; ?>
    </div>

    <?php if (!$orders): ?>
        <div class="card-box empty-state">
            <h3>Belum ada pesanan di sini</h3>
            <p><?= $tab ? 'Tidak ada pesanan dengan status "' . h($tab) . '".' : 'Anda belum pernah memesan.' ?></p>
            <a href="<?= url('Includes/produk.php') ?>" class="btn-outline-brown">Lihat produk</a>
        </div>
    <?php endif; ?>

    <?php foreach ($orders as $o):
        $itemStmt->execute([$o['id_pesanan']]);
        $items = $itemStmt->fetchAll();
        $kodeO = kode((int)$o['id_pesanan']); ?>
        <div class="order-card">
            <div class="order-header">
                <div><span class="order-number"><?= h($kodeO) ?></span> <span class="order-date">&middot; <?= tgl($o['tanggal_pesanan'], true) ?></span></div>
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($o['jml_retur'] > 0): ?><a href="<?= url('Includes/retur-saya.php') ?>" style="font-size:12px;color:var(--text-secondary);text-decoration:underline"><?= (int)$o['jml_retur'] ?> retur</a><?php endif; ?>
                    <?= badge($o['status_pesanan']) ?>
                </div>
            </div>
            <div class="order-body">
                <div>
                    <?php foreach (array_slice($items, 0, 2) as $it): ?>
                        <div class="mini-item">
                            <img src="<?= upload_url('produk', $it['foto']) ?>" alt="">
                            <div><strong><?= h($it['nama_produk']) ?></strong><small><?= (int)$it['jumlah'] ?> x <?= rupiah($it['harga_satuan']) ?></small></div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($items) > 2): ?><small style="color:var(--text-muted)">dan <?= count($items) - 2 ?> produk lain</small><?php endif; ?>
                </div>
                <div class="order-note">
                    <?php if ($o['status_pesanan'] === 'Dikirim' && $o['no_resi']): ?>
                        <?= h($o['nama_kurir']) ?> &middot; resi <strong style="color:var(--primary)"><?= h($o['no_resi']) ?></strong>
                    <?php else: ?>
                        <?= h($catatan[$o['status_pesanan']] ?? '') ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="order-footer">
                <div class="order-total">Total <strong><?= rupiah($o['total_harga']) ?></strong></div>
                <div class="order-actions">
                    <?php if ($o['status_pesanan'] === 'Belum Melakukan Pembayaran'): ?>
                        <form method="POST" data-confirm="Batalkan pesanan <?= h($kodeO) ?>?">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="batal"><input type="hidden" name="id_pesanan" value="<?= (int)$o['id_pesanan'] ?>">
                            <button class="link-danger">Batalkan</button>
                        </form>
                        <a href="<?= url('Includes/bayar.php?id=' . (int)$o['id_pesanan']) ?>" class="btn-orange btn-sm-x">Bayar &amp; unggah bukti</a>
                    <?php endif; ?>
                    <?php if ($o['status_pesanan'] === 'Dikirim'): ?>
                        <a href="<?= url('Includes/pesanan-detail.php?id=' . (int)$o['id_pesanan']) ?>#terima" class="btn-orange btn-sm-x">Pesanan sudah diterima</a>
                    <?php endif; ?>
                    <?php if ($o['status_pesanan'] === 'Selesai'): ?>
                        <a href="<?= url('Includes/kwitansi.php?id=' . (int)$o['id_pesanan']) ?>" class="btn-outline-brown btn-sm-x">Kwitansi</a>
                        <?php if ($o['jml_retur'] < $o['jml_item']): ?>
                            <a href="<?= url('Includes/retur-ajukan.php?id=' . (int)$o['id_pesanan']) ?>" class="btn-outline-brown btn-sm-x">Ajukan retur</a>
                        <?php endif; ?>
                    <?php endif; ?>
                    <a href="<?= url('Includes/pesanan-detail.php?id=' . (int)$o['id_pesanan']) ?>" class="btn-brown btn-sm-x">Rincian</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
