<?php
/**
 * Activity & Sequence "Memproses Pesanan" (include: Menginput Resi Pengiriman)
 *
 * Hanya pesanan yang pembayarannya sudah DISETUJUI admin yang tampil di sini
 * (status Diproses, Dikirim, Selesai). Pesanan "Belum Melakukan Pembayaran" dan
 * "Sudah Upload Bukti" ditangani di menu Konfirmasi Pembayaran.
 *
 *   pilihPengiriman -> getJasaKirim -> pilihJasaKirim -> serahkanBarangKeKurir -> inputResi
 *   -> updatePengiriman(idJasaKirim, noResi) -> updateStatus('Dikirim')
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/pesanan.css';

$STATUS = ['Diproses', 'Dikirim', 'Selesai'];
$IN = "('Diproses','Dikirim','Selesai')";

// pengiriman milik pembayaran yang disetujui
const KIRIM_JOIN = "JOIN pembayaran pb ON pb.id_pesanan = ps.id_pesanan AND pb.status_pembayaran = 'Disetujui'
                    JOIN pengiriman pg ON pg.id_pembayaran = pb.id_pembayaran
                    LEFT JOIN jasa_kurir jk ON jk.id_jasa_kurir = pg.id_jasa_kurir";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'kirim') {
    csrf_check();
    $id    = (int)$_POST['id_pesanan'];
    $kurir = (int)($_POST['id_jasa_kurir'] ?? 0);
    $resi  = strtoupper(trim((string)($_POST['no_resi'] ?? '')));
    $st = $pdo->prepare('SELECT pg.id_pengiriman FROM pesanan ps ' . KIRIM_JOIN . " WHERE ps.id_pesanan = ? AND ps.status_pesanan = 'Diproses'");
    $st->execute([$id]);
    $idKirim = $st->fetchColumn();
    $cek = $pdo->prepare('SELECT COUNT(*) FROM pengiriman WHERE no_resi = ? AND id_pengiriman <> ?');
    $cek->execute([$resi, (int)$idKirim]);
    if (!$idKirim) {
        flash('danger', 'Pesanan ini tidak bisa dikirim (status harus Diproses).');
    } elseif ($kurir <= 0 || !preg_match('/^[A-Z0-9-]{6,40}$/', $resi)) {
        flash('danger', 'Pilih jasa kurir dan isi nomor resi (6-40 huruf/angka).');
    } elseif ($cek->fetchColumn() > 0) {
        flash('danger', 'Nomor resi ' . $resi . ' sudah dipakai pesanan lain.');
    } else {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE pengiriman SET id_jasa_kurir = ?, no_resi = ?, tanggal_pengiriman = ? WHERE id_pengiriman = ?')
            ->execute([$kurir, $resi, now(), $idKirim]);
        $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Dikirim' WHERE id_pesanan = ?")->execute([$id]);
        $pdo->commit();
        flash('success', 'Resi ' . $resi . ' tersimpan, pesanan ' . kode($id) . ' berstatus Dikirim.');
    }
    redirect('admin/pesanan.php?id=' . $id);
}

$kurirList = $pdo->query('SELECT * FROM jasa_kurir ORDER BY nama_kurir')->fetchAll();

/* ============================== DETAIL ============================== */
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $st = $pdo->prepare('SELECT ps.*, u.nama AS nama_pelanggan, u.email, u.no_telepon, pb.id_pembayaran, pb.jumlah_bayar, pb.tanggal_pembayaran,
                                pb.bukti_pembayaran, pg.alamat_pengiriman, pg.ongkir, pg.no_resi, pg.tanggal_pengiriman, jk.nama_kurir
                         FROM pesanan ps JOIN `user` u ON u.id_user = ps.id_user ' . KIRIM_JOIN . "
                         WHERE ps.id_pesanan = ? AND ps.status_pesanan IN $IN");
    $st->execute([$id]);
    $o = $st->fetch();
    if (!$o) {
        flash('warning', 'Pesanan ' . kode($id) . ' belum ada di Proses Pesanan karena pembayarannya belum disetujui.');
        redirect('admin/pembayaran.php');
    }
    $kode = kode($id);
    $st = $pdo->prepare('SELECT pp.*, p.nama_produk, p.foto, p.stok, u.nama_ukuran, r.id_retur, r.status_retur
                         FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk JOIN ukuran u ON u.id_ukuran = p.id_ukuran
                         LEFT JOIN retur r ON r.id_pesanan = pp.id_pesanan AND r.id_produk = pp.id_produk
                         WHERE pp.id_pesanan = ?');
    $st->execute([$id]);
    $items = $st->fetchAll();
    $totalProduk = 0;
    foreach ($items as $it) {
        $totalProduk += $it['jumlah'] * $it['harga_satuan'];
    }
    $bukti = upload_url('bukti_transfer', $o['bukti_pembayaran']);

    admin_header('Pesanan ' . $kode, 'pesanan');
    page_header('Pesanan ' . $kode, 'Dipesan ' . tgl($o['tanggal_pesanan'], true) . ' oleh ' . $o['nama_pelanggan'],
        '<div style="display:flex;gap:8px;align-items:center">' . badge($o['status_pesanan']) . '<a href="' . url('admin/pesanan.php') . '" class="btn-a outline">Kembali</a></div>');
    ?>
    <div class="grid-2">
        <div>
            <?php if ($o['status_pesanan'] === 'Diproses'): ?>
                <div class="panel" style="margin-bottom:16px;border-color:var(--accent)">
                    <div class="panel-head"><div><h2>Kirim pesanan</h2><p>Serahkan barang ke kurir, lalu masukkan nomor resinya.</p></div></div>
                    <form method="POST" class="panel-body">
                        <?= csrf_field() ?><input type="hidden" name="aksi" value="kirim"><input type="hidden" name="id_pesanan" value="<?= $id ?>">
                        <div class="form-grid-a">
                            <div><label class="label-a">Jasa kurir <span class="req">*</span></label>
                                <select name="id_jasa_kurir" class="select-a" required>
                                    <option value="">Pilih jasa kurir</option>
                                    <?php foreach ($kurirList as $k): ?><option value="<?= (int)$k['id_jasa_kurir'] ?>"><?= h($k['nama_kurir']) ?></option><?php endforeach; ?>
                                </select></div>
                            <div><label class="label-a">Nomor resi <span class="req">*</span></label><input name="no_resi" class="input-a" required></div>
                        </div>
                        <button class="btn-a" style="margin-top:12px">Simpan resi &amp; kirim</button>
                    </form>
                </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-head"><h2>Produk</h2></div>
                <div class="table-wrap"><table class="table-admin">
                    <thead><tr><th>Produk</th><th class="num">Harga</th><th class="num">Ongkir/pcs</th><th class="num">Jml</th><th class="num">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr><td><div style="display:flex;gap:10px;align-items:center"><img src="<?= upload_url('produk', $it['foto']) ?>" class="thumb" alt=""><div><strong><?= h($it['nama_produk']) ?></strong><span class="cell-sub"><?= h($it['nama_ukuran']) ?>, stok sekarang <?= (int)$it['stok'] ?><?php if ($it['id_retur']): ?>, <a href="<?= url('admin/retur.php?id=' . (int)$it['id_retur']) ?>" style="text-decoration:underline">retur <?= h(strtolower($it['status_retur'])) ?></a><?php endif; ?></span></div></div></td>
                            <td class="num"><?= rupiah($it['harga_satuan']) ?></td><td class="num"><?= rupiah($it['ongkir_satuan']) ?></td><td class="num"><?= (int)$it['jumlah'] ?></td><td class="num"><?= rupiah($it['subtotal']) ?></td></tr>
                    <?php endforeach; ?>
                    <tr><td colspan="4" class="num">Harga produk</td><td class="num"><?= rupiah($totalProduk) ?></td></tr>
                    <tr><td colspan="4" class="num">Ongkir</td><td class="num"><?= rupiah($o['ongkir']) ?></td></tr>
                    <tr><td colspan="4" class="num"><strong>Total</strong></td><td class="num"><strong><?= rupiah($o['total_harga']) ?></strong></td></tr>
                    </tbody>
                </table></div>
            </div>
        </div>

        <div>
            <div class="panel">
                <div class="panel-head"><h2>Pengiriman</h2></div>
                <div class="panel-body">
                    <dl class="info-list" style="grid-template-columns:100px 1fr">
                        <dt>Pelanggan</dt><dd><?= h($o['nama_pelanggan']) ?><span class="cell-sub"><?= h($o['email']) ?>, <?= h($o['no_telepon']) ?></span></dd>
                        <dt>Kirim ke</dt><dd><?= nl2br(h($o['alamat_pengiriman'])) ?></dd>
                        <dt>Kurir</dt><dd><?= h($o['nama_kurir'] ?? '-') ?></dd>
                        <dt>No. resi</dt><dd><?= h($o['no_resi'] ?: '-') ?></dd>
                        <dt>Tgl kirim</dt><dd><?= tgl($o['tanggal_pengiriman'], true) ?></dd>
                    </dl>
                    <?php if ($o['status_pesanan'] === 'Selesai'): ?>
                        <a href="<?= url('Includes/kwitansi.php?id=' . $id) ?>" class="btn-a outline" style="margin-top:12px">Lihat kwitansi</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="panel">
                <div class="panel-head"><h2>Pembayaran disetujui</h2></div>
                <div class="panel-body" style="display:flex;gap:12px;align-items:flex-start">
                    <img src="<?= $bukti ?>" data-zoom="<?= $bukti ?>" alt="Bukti" style="width:70px;height:92px;object-fit:cover;cursor:zoom-in;border:1px solid var(--border)">
                    <div style="font-size:13px"><?= rupiah($o['jumlah_bayar']) ?><br><span style="color:var(--text-muted)"><?= tgl($o['tanggal_pembayaran'], true) ?></span><br>
                        <a href="<?= url('admin/pembayaran.php?sel=' . (int)$o['id_pembayaran']) ?>" style="text-decoration:underline">lihat di Konfirmasi Pembayaran</a></div>
                </div>
            </div>
        </div>
    </div>
    <?php
    admin_footer();
    exit;
}

/* ============================== DAFTAR ============================== */
$tab  = in_array($_GET['status'] ?? '', $STATUS, true) ? $_GET['status'] : '';
$q    = trim((string)($_GET['q'] ?? ''));
$dari = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dari'] ?? '') ? $_GET['dari'] : '';
$smp  = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sampai'] ?? '') ? $_GET['sampai'] : '';

$where = ["ps.status_pesanan IN $IN"];
$params = [];
if ($tab) { $where[] = 'ps.status_pesanan = :st'; $params[':st'] = $tab; }
if ($q !== '') {
    $idCari = preg_match('/^(?:RB-?)?0*(\d+)$/i', $q, $mm) ? (int)$mm[1] : -1;
    $where[] = '(ps.id_pesanan = :qid OR u.nama LIKE :q2 OR pg.alamat_pengiriman LIKE :q3 OR pg.no_resi LIKE :q4)';
    $params[':qid'] = $idCari;
    $params[':q2'] = $params[':q3'] = $params[':q4'] = "%$q%";
}
if ($dari) { $where[] = 'ps.tanggal_pesanan >= :d1'; $params[':d1'] = "$dari 00:00:00"; }
if ($smp) { $where[] = 'ps.tanggal_pesanan <= :d2'; $params[':d2'] = "$smp 23:59:59"; }

$st = $pdo->prepare('SELECT ps.*, u.nama AS nama_pelanggan, u.kota, pg.no_resi, jk.nama_kurir,
                            (SELECT COUNT(*) FROM pesanan_produk pp WHERE pp.id_pesanan = ps.id_pesanan) AS jml_item
                     FROM pesanan ps JOIN `user` u ON u.id_user = ps.id_user ' . KIRIM_JOIN . '
                     WHERE ' . implode(' AND ', $where) . " ORDER BY CASE ps.status_pesanan WHEN 'Diproses' THEN 0 WHEN 'Dikirim' THEN 1 ELSE 2 END, ps.tanggal_pesanan DESC");
$st->execute($params);
$orders = $st->fetchAll();

$counts = [];
foreach ($pdo->query("SELECT status_pesanan s, COUNT(*) c FROM pesanan WHERE status_pesanan IN $IN GROUP BY status_pesanan") as $r) {
    $counts[$r['s']] = (int)$r['c'];
}

admin_header('Proses Pesanan', 'pesanan');
page_header('Proses Pesanan', 'Pesanan yang pembayarannya sudah disetujui. Pilih kurir dan masukkan resi untuk mengirim.');
?>
<div class="tabs-a">
    <a href="<?= url('admin/pesanan.php') ?>" class="<?= $tab === '' ? 'active' : '' ?>">Semua <small><?= array_sum($counts) ?></small></a>
    <?php foreach ($STATUS as $s): ?>
        <a href="<?= url('admin/pesanan.php?status=' . urlencode($s)) ?>" class="<?= $tab === $s ? 'active' : '' ?>"><?= $s === 'Diproses' ? 'Perlu dikirim' : h($s) ?> <small><?= $counts[$s] ?? 0 ?></small></a>
    <?php endforeach; ?>
</div>

<div class="panel">
    <div class="panel-head">
        <form method="GET" class="filter-bar">
            <input type="hidden" name="status" value="<?= h($tab) ?>">
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Kode, pelanggan, alamat, resi" style="width:230px">
            <input type="date" name="dari" value="<?= h($dari) ?>" class="input-a" style="width:145px">
            <input type="date" name="sampai" value="<?= h($smp) ?>" class="input-a" style="width:145px">
            <button class="btn-a outline">Terapkan</button>
        </form>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($orders) ?> pesanan</span>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead><tr><th>Kode</th><th>Tanggal</th><th>Pelanggan</th><th>Kurir / resi</th><th class="num">Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (!$orders): ?><tr><td colspan="7"><div class="empty-a">Belum ada pesanan dengan pembayaran disetujui.</div></td></tr><?php endif; ?>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td><strong><?= h(kode((int)$o['id_pesanan'])) ?></strong><span class="cell-sub"><?= (int)$o['jml_item'] ?> produk</span></td>
                    <td><?= tgl($o['tanggal_pesanan'], true) ?></td>
                    <td><?= h($o['nama_pelanggan']) ?><span class="cell-sub"><?= h($o['kota']) ?></span></td>
                    <td><?= h($o['nama_kurir'] ?? '-') ?><span class="cell-sub"><?= h($o['no_resi'] ?: 'belum ada resi') ?></span></td>
                    <td class="num"><?= rupiah($o['total_harga']) ?></td>
                    <td><?= badge($o['status_pesanan']) ?></td>
                    <td class="num"><a href="<?= url('admin/pesanan.php?id=' . (int)$o['id_pesanan']) ?>" class="btn-a <?= $o['status_pesanan'] === 'Diproses' ? '' : 'outline' ?> sm"><?= $o['status_pesanan'] === 'Diproses' ? 'Kirim' : 'Detail' ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_footer(); ?>
