<?php
/**
 * Rincian pesanan + Activity/Sequence "Menerima Pesanan":
 *   status Dikirim -> tampilkan no. resi + tombol Pesanan Diterima -> dialog konfirmasi
 *   -> updateStatusPesanan(idPesanan, 'Selesai')
 */
require_once __DIR__ . '/../config/koneksi.php';
require_pelanggan();

$id = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'diterima') {
    csrf_check();
    $stmt = $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Selesai' WHERE id_pesanan = ? AND id_user = ? AND status_pesanan = 'Dikirim'");
    $stmt->execute([$id, user_id()]);
    flash($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount() ? 'Terima kasih, pesanan selesai. Kwitansi sudah bisa dicetak.' : 'Status pesanan tidak bisa diubah.');
    redirect('Includes/pesanan-detail.php?id=' . $id);
}

$stmt = $pdo->prepare('SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?');
$stmt->execute([$id, user_id()]);
$o = $stmt->fetch();
if (!$o) {
    flash('warning', 'Pesanan tidak ditemukan.');
    redirect('Includes/pesanan-saya.php');
}
$kode = kode($id);

// pengiriman milik pembayaran yang masih berlaku (bukan yang ditolak)
$stmt = $pdo->prepare("SELECT pg.*, jk.nama_kurir FROM pembayaran pb JOIN pengiriman pg ON pg.id_pembayaran = pb.id_pembayaran
                       LEFT JOIN jasa_kurir jk ON jk.id_jasa_kurir = pg.id_jasa_kurir
                       WHERE pb.id_pesanan = ? AND pb.status_pembayaran <> 'Ditolak' ORDER BY pb.id_pembayaran DESC LIMIT 1");
$stmt->execute([$id]);
$kirim = $stmt->fetch() ?: null;

$stmt = $pdo->prepare('SELECT pp.*, p.nama_produk, p.foto, u.nama_ukuran, r.id_retur, r.status_retur
                       FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk JOIN ukuran u ON u.id_ukuran = p.id_ukuran
                       LEFT JOIN retur r ON r.id_pesanan = pp.id_pesanan AND r.id_produk = pp.id_produk
                       WHERE pp.id_pesanan = ?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
$totalProduk = 0;
$totalOngkir = 0;
foreach ($items as $it) {
    $totalProduk += $it['jumlah'] * $it['harga_satuan'];
    $totalOngkir += $it['jumlah'] * $it['ongkir_satuan'];
}
$bisaRetur = $o['status_pesanan'] === 'Selesai' && count(array_filter($items, fn($i) => !$i['id_retur'])) > 0;

$stmt = $pdo->prepare('SELECT * FROM pembayaran WHERE id_pesanan = ? ORDER BY tanggal_pembayaran DESC');
$stmt->execute([$id]);
$bayar = $stmt->fetchAll();

$langkah = [
    'Belum Melakukan Pembayaran' => 'Pesanan dibuat',
    'Sudah Upload Bukti'         => 'Bukti bayar diunggah',
    'Diproses'                   => 'Pembayaran disetujui',
    'Dikirim'                    => 'Dikirim',
    'Selesai'                    => 'Diterima',
];
$keys = array_keys($langkah);
$pos  = array_search($o['status_pesanan'], $keys, true);

$pageTitle  = 'Pesanan ' . $kode;
$activeMenu = 'pesanan';
$pageCss = 'Includes/css/pesanan-detail.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="breadcrumb-lite"><a href="<?= url('Includes/pesanan-saya.php') ?>">Pesanan saya</a><span>/</span><span><?= h($kode) ?></span></div>
    <div class="page-heading">
        <div>
            <h1>Pesanan <?= h($kode) ?></h1>
            <p>Dipesan <?= tgl($o['tanggal_pesanan'], true) ?></p>
        </div>
        <?= badge($o['status_pesanan']) ?>
    </div>

    <?php if ($o['status_pesanan'] !== 'Dibatalkan'): ?>
        <ol class="steps">
            <?php foreach ($keys as $i => $k): $cls = $o['status_pesanan'] === 'Selesai' || $i < $pos ? 'done' : ($i === $pos ? 'current' : ''); ?>
                <li class="<?= $cls ?>"><b>Langkah <?= $i + 1 ?></b><?= h($langkah[$k]) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <div class="two-col" style="margin-top:16px">
        <div>
            <?php if ($o['status_pesanan'] === 'Dikirim'): ?>
                <div class="card-box" id="terima" style="border-color:var(--accent)">
                    <h2>Pesanan sedang dikirim</h2>
                    <div class="resi-box mb-3">
                        <div><small style="color:var(--text-secondary)"><?= h($kirim['nama_kurir'] ?? '-') ?>, nomor resi</small><br><strong><?= h($kirim['no_resi'] ?? '-') ?></strong></div>
                        <button type="button" class="copy-btn" data-copy="<?= h($kirim['no_resi'] ?? '') ?>">Salin</button>
                    </div>
                    <p style="font-size:13px;color:var(--text-secondary)">Kalau beras sudah sampai, tekan tombol di bawah supaya pesanan selesai.</p>
                    <button type="button" class="btn-orange" data-modal-open="modalTerima">Pesanan sudah diterima</button>
                </div>
            <?php elseif ($o['status_pesanan'] === 'Belum Melakukan Pembayaran'): ?>
                <div class="alert-box warning"><span>Pesanan ini belum dibayar. <a href="<?= url('Includes/bayar.php?id=' . $id) ?>" class="link-action">Bayar dan unggah bukti</a></span></div>
            <?php elseif ($o['status_pesanan'] === 'Sudah Upload Bukti'): ?>
                <div class="alert-box info"><span>Bukti bayar sudah diterima dan sedang dicek admin.</span></div>
            <?php elseif ($o['status_pesanan'] === 'Diproses'): ?>
                <div class="alert-box info"><span>Pembayaran sudah disetujui. Pesanan sedang dikemas, nomor resi muncul setelah dikirim.</span></div>
            <?php endif; ?>

            <div class="card-box">
                <h2>Produk</h2>
                <div style="overflow-x:auto">
                <table class="table-x">
                    <thead><tr><th>Produk</th><th class="num">Harga</th><th class="num">Ongkir/pcs</th><th class="num">Jml</th><th class="num">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><div class="mini-item"><img src="<?= upload_url('produk', $it['foto']) ?>" alt=""><div><strong><?= h($it['nama_produk']) ?></strong>
                                <small><?= h($it['nama_ukuran']) ?><?php if ($it['id_retur']): ?>, retur: <?= h(strtolower($it['status_retur'])) ?><?php endif; ?></small></div></div></td>
                            <td class="num"><?= rupiah($it['harga_satuan']) ?></td>
                            <td class="num"><?= rupiah($it['ongkir_satuan']) ?></td>
                            <td class="num"><?= (int)$it['jumlah'] ?></td>
                            <td class="num"><?= rupiah($it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="card-box">
                <h2>Pembayaran</h2>
                <?php if (!$bayar): ?>
                    <p style="color:var(--text-secondary);font-size:13px;margin:0">Belum ada bukti bayar yang diunggah.</p>
                <?php else: ?>
                    <table class="table-x">
                        <thead><tr><th>Tanggal</th><th class="num">Nominal</th><th>Bukti</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($bayar as $b): $u = upload_url('bukti_transfer', $b['bukti_pembayaran']); ?>
                            <tr>
                                <td><?= tgl($b['tanggal_pembayaran'], true) ?></td>
                                <td class="num"><?= rupiah($b['jumlah_bayar']) ?></td>
                                <td><a href="#" data-zoom="<?= $u ?>" class="link-action" style="font-weight:500">lihat</a></td>
                                <td><?= badge($b['status_pembayaran']) ?><?php if ($b['catatan']): ?><br><small style="color:<?= $b['status_pembayaran'] === 'Ditolak' ? 'var(--danger)' : 'var(--text-muted)' ?>"><?= h($b['catatan']) ?></small><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <aside class="sticky-col">
            <div class="card-box">
                <h2>Ringkasan</h2>
                <div class="summary-row"><span>Harga produk</span><strong><?= rupiah($totalProduk) ?></strong></div>
                <div class="summary-row"><span>Ongkir</span><strong><?= rupiah($totalOngkir) ?></strong></div>
                <div class="summary-divider"></div>
                <div class="summary-total"><span>Total</span><span><?= rupiah($o['total_harga']) ?></span></div>
                <?php if (!in_array($o['status_pesanan'], ['Dibatalkan'], true)): ?>
                    <div class="summary-row"><span>Kode unik transfer</span><strong>+ <?= rupiah(kode_unik($id)) ?></strong></div>
                    <div class="summary-row"><span>Total transfer</span><strong><?= rupiah(total_transfer($o)) ?></strong></div>
                <?php endif; ?>
                <div class="d-grid gap-2 mt-3">
                    <?php if ($o['status_pesanan'] === 'Belum Melakukan Pembayaran'): ?>
                        <a href="<?= url('Includes/bayar.php?id=' . $id) ?>" class="btn-orange">Bayar &amp; unggah bukti</a>
                    <?php endif; ?>
                    <?php if ($o['status_pesanan'] === 'Selesai'): ?>
                        <a href="<?= url('Includes/kwitansi.php?id=' . $id) ?>" class="btn-brown">Cetak kwitansi</a>
                        <?php if ($bisaRetur): ?>
                            <a href="<?= url('Includes/retur-ajukan.php?id=' . $id) ?>" class="btn-outline-brown">Ajukan retur</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-box">
                <h2>Pengiriman</h2>
                <?php if (!$kirim): ?>
                    <p style="font-size:13px;color:var(--text-secondary);margin:0">Alamat diisi saat mengunggah bukti bayar.</p>
                <?php else: ?>
                    <dl class="info-list" style="grid-template-columns:80px 1fr">
                        <dt>Alamat</dt><dd><?= nl2br(h($kirim['alamat_pengiriman'])) ?></dd>
                        <dt>Kurir</dt><dd><?= h($kirim['nama_kurir'] ?? 'dipilih toko saat dikirim') ?></dd>
                        <dt>No. resi</dt><dd><?= h($kirim['no_resi'] ?: 'belum ada') ?></dd>
                        <?php if ($kirim['tanggal_pengiriman']): ?><dt>Dikirim</dt><dd><?= tgl($kirim['tanggal_pengiriman'], true) ?></dd><?php endif; ?>
                    </dl>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<div class="modal-x" id="modalTerima">
    <div class="modal-x__box">
        <h3>Pesanan sudah diterima?</h3>
        <p>Setelah dikonfirmasi, status pesanan menjadi Selesai dan kwitansi bisa dicetak.</p>
        <form method="POST" class="modal-x__actions">
            <?= csrf_field() ?><input type="hidden" name="aksi" value="diterima">
            <button type="button" class="btn-outline-brown btn-sm-x" data-modal-close>Batal</button>
            <button class="btn-orange btn-sm-x">Ya, sudah diterima</button>
        </form>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
