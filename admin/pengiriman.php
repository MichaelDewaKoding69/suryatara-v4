<?php
/**
 * Daftar pengiriman (Pesanan "Dikirim Melalui" Pengiriman, Pengiriman "Menggunakan" Jasa Kurir).
 * Hanya pengiriman milik pembayaran yang Disetujui (Pengiriman "Setelah" Pembayaran).
 * Status pengiriman diturunkan dari status pesanan (ERD tidak memiliki kolom status pengiriman).
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/pengiriman.css';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'resi') {
    csrf_check();
    $resi = strtoupper(trim((string)($_POST['no_resi'] ?? '')));
    $idKirim = (int)$_POST['id_pengiriman'];
    $cek = $pdo->prepare('SELECT COUNT(*) FROM pengiriman WHERE no_resi = ? AND id_pengiriman <> ?');
    $cek->execute([$resi, $idKirim]);
    if (!preg_match('/^[A-Z0-9-]{6,40}$/', $resi)) {
        flash('danger', 'Nomor resi tidak valid.');
    } elseif ($cek->fetchColumn() > 0) {
        flash('danger', 'Nomor resi sudah dipakai pengiriman lain.');
    } else {
        $pdo->prepare('UPDATE pengiriman SET no_resi = ? WHERE id_pengiriman = ? AND no_resi IS NOT NULL')->execute([$resi, $idKirim]);
        flash('success', 'Nomor resi diperbarui.');
    }
    redirect('admin/pengiriman.php?' . http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'kurir' => $_GET['kurir'] ?? ''])));
}

$STATUS = [
    'Belum Dikirim' => "ps.status_pesanan = 'Diproses'",
    'Dikirim'       => "ps.status_pesanan = 'Dikirim'",
    'Diterima'      => "ps.status_pesanan = 'Selesai'",
];
$tab   = isset($STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$kurir = (int)($_GET['kurir'] ?? 0);
$q     = trim((string)($_GET['q'] ?? ''));

$where = ["1 = 1"];
$params = [];
if ($tab) { $where[] = $STATUS[$tab]; }
if ($kurir) { $where[] = 'pg.id_jasa_kurir = :k'; $params[':k'] = $kurir; }
if ($q !== '') {
    $idCari = preg_match('/^(?:RB-?)?0*(\d+)$/i', $q, $mm) ? (int)$mm[1] : -1;
    $where[] = '(ps.id_pesanan = :qid OR pg.no_resi LIKE :q2 OR u.nama LIKE :q3)';
    $params[':qid'] = $idCari;
    $params[':q2'] = $params[':q3'] = "%$q%";
}

$st = $pdo->prepare('SELECT pg.*, ps.id_pesanan, ps.status_pesanan, ps.tanggal_pesanan, u.nama AS nama_pelanggan, jk.nama_kurir,
                            (SELECT SUM(pp.jumlah * uk.berat_kg) FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk JOIN ukuran uk ON uk.id_ukuran = p.id_ukuran WHERE pp.id_pesanan = ps.id_pesanan) AS berat
                     FROM pengiriman pg
                     JOIN pembayaran pb ON pb.id_pembayaran = pg.id_pembayaran AND pb.status_pembayaran = \'Disetujui\'
                     JOIN pesanan ps ON ps.id_pesanan = pb.id_pesanan JOIN `user` u ON u.id_user = ps.id_user
                     LEFT JOIN jasa_kurir jk ON jk.id_jasa_kurir = pg.id_jasa_kurir
                     WHERE ' . implode(' AND ', $where) . ' ORDER BY COALESCE(pg.tanggal_pengiriman, ps.tanggal_pesanan) DESC');
$st->execute($params);
$rows = $st->fetchAll();
$kurirList = $pdo->query('SELECT * FROM jasa_kurir ORDER BY nama_kurir')->fetchAll();
$counts = [];
foreach ($STATUS as $k => $cond) {
    $counts[$k] = (int)$pdo->query("SELECT COUNT(*) FROM pengiriman pg JOIN pembayaran pb ON pb.id_pembayaran = pg.id_pembayaran AND pb.status_pembayaran = 'Disetujui' JOIN pesanan ps ON ps.id_pesanan = pb.id_pesanan WHERE $cond")->fetchColumn();
}
$total = array_sum($counts);

function status_kirim(array $r): string
{
    if ($r['status_pesanan'] === 'Selesai') return 'Diterima';
    if ($r['status_pesanan'] === 'Dikirim') return 'Dikirim';
    return 'Belum Dikirim';
}

admin_header('Pengiriman', 'pengiriman');
page_header('Pengiriman', 'Alamat, kurir, ongkir, dan resi untuk pesanan yang pembayarannya sudah disetujui.');
?>
<div class="tabs-a">
    <a href="<?= url('admin/pengiriman.php') ?>" class="<?= $tab === '' ? 'active' : '' ?>">Semua <small><?= $total ?></small></a>
    <?php foreach ($STATUS as $s => $c): ?><a href="<?= url('admin/pengiriman.php?status=' . urlencode($s)) ?>" class="<?= $tab === $s ? 'active' : '' ?>"><?= $s ?> <?php if ($counts[$s]): ?><small><?= $counts[$s] ?></small><?php endif; ?></a><?php endforeach; ?>
</div>
<div class="panel">
    <div class="panel-head">
        <div><h2>Daftar Pengiriman</h2><p><?= count($rows) ?> data</p></div>
        <form method="GET" class="filter-bar">
            <input type="hidden" name="status" value="<?= h($tab) ?>">
            <select name="kurir" class="select-a" style="width:170px" data-autosubmit><option value="">Semua kurir</option>
                <?php foreach ($kurirList as $k): ?><option value="<?= (int)$k['id_jasa_kurir'] ?>" <?= $kurir === (int)$k['id_jasa_kurir'] ? 'selected' : '' ?>><?= h($k['nama_kurir']) ?></option><?php endforeach; ?>
            </select>
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Kode / resi / pelanggan" style="width:200px">
            <button class="btn-a outline">Cari</button>
        </form>
    </div>
    <div class="table-wrap"><table class="table-admin">
        <thead><tr><th>Pesanan</th><th>Alamat Pengiriman</th><th>Kurir</th><th>No. Resi</th><th class="num">Berat</th><th class="num">Ongkir</th><th>Tgl Kirim</th><th>Status</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-a">Tidak ada data.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): $sk = status_kirim($r); ?>
            <tr>
                <td><a href="<?= url('admin/pesanan.php?id=' . (int)$r['id_pesanan']) ?>"><strong><?= h(kode((int)$r['id_pesanan'])) ?></strong></a><span class="cell-sub"><?= h($r['status_pesanan']) ?></span></td>
                <td style="max-width:260px;font-size:12px"><?= nl2br(h($r['alamat_pengiriman'])) ?></td>
                <td><?= h($r['nama_kurir'] ?? '-') ?></td>
                <td>
                    <?php if ($sk === 'Dikirim'): ?>
                        <form method="POST" style="display:flex;gap:4px">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="resi"><input type="hidden" name="id_pengiriman" value="<?= (int)$r['id_pengiriman'] ?>">
                            <input name="no_resi" value="<?= h($r['no_resi']) ?>" class="input-a" style="height:32px;width:150px">
                            <button class="btn-a outline icon" title="Simpan koreksi resi">Simpan</button>
                        </form>
                    <?php else: ?><?= h($r['no_resi'] ?: '-') ?><?php endif; ?>
                </td>
                <td class="num"><?= kg($r['berat']) ?></td>
                <td class="num"><?= rupiah($r['ongkir']) ?></td>
                <td><?= tgl($r['tanggal_pengiriman'], true) ?></td>
                <td><?= badge($sk) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php admin_footer(); ?>
