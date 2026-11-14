<?php
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/pelanggan.css';

$q = trim((string)($_GET['q'] ?? ''));
$sql = "SELECT pl.*,
               (SELECT COUNT(*) FROM pesanan ps WHERE ps.id_user = pl.id_user AND ps.status_pesanan <> 'Dibatalkan') AS jml_pesanan,
               (SELECT COALESCE(SUM(ps.total_harga),0) FROM pesanan ps WHERE ps.id_user = pl.id_user AND ps.status_pesanan IN ('Diproses','Dikirim','Selesai')) AS total_belanja,
               (SELECT MAX(ps.tanggal_pesanan) FROM pesanan ps WHERE ps.id_user = pl.id_user) AS terakhir
        FROM `user` pl JOIN role r ON r.id_role = pl.id_role WHERE r.nama_role = 'Pelanggan'";
$params = [];
if ($q !== '') {
    $sql .= ' AND (pl.nama LIKE :a OR pl.email LIKE :b OR pl.kota LIKE :c OR pl.no_telepon LIKE :d)';
    $params = [':a' => "%$q%", ':b' => "%$q%", ':c' => "%$q%", ':d' => "%$q%"];
}
$st = $pdo->prepare($sql . ' ORDER BY pl.id_user DESC');
$st->execute($params);
$rows = $st->fetchAll();

admin_header('Pelanggan', 'pelanggan');
page_header('Data Pelanggan', 'Daftar pelanggan yang terdaftar beserta riwayat belanjanya.');
?>
<div class="panel">
    <div class="panel-head">
        <div><h2>Pelanggan</h2><p><?= count($rows) ?> pelanggan</p></div>
        <form method="GET" class="filter-bar"><input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Nama, email, kota, no. HP" style="width:240px"><button class="btn-a outline">Cari</button></form>
    </div>
    <div class="table-wrap"><table class="table-admin">
        <thead><tr><th>Pelanggan</th><th>Kontak</th><th>Alamat</th><th class="num">Pesanan</th><th class="num">Total Belanja</th><th>Pesanan Terakhir</th></tr></thead>
        <tbody>
        <?php if (!$rows): ?><tr><td colspan="6"><div class="empty-a">Tidak ada pelanggan.</div></td></tr><?php endif; ?>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><div style="display:flex;gap:10px;align-items:center"><div class="admin-avatar" style="width:34px;height:34px;font-weight:800"><?= h(mb_strtoupper(mb_substr($r['nama'], 0, 1))) ?></div><strong><?= h($r['nama']) ?></strong></div></td>
                <td><?= h($r['email']) ?><span class="cell-sub"><?= h($r['no_telepon']) ?></span></td>
                <td><?= h(mb_strimwidth($r['alamat'], 0, 40, '...')) ?><span class="cell-sub"><?= h($r['kota']) ?></span></td>
                <td class="num"><a href="<?= url('admin/pesanan.php?q=' . urlencode($r['nama'])) ?>"><strong><?= (int)$r['jml_pesanan'] ?></strong></a></td>
                <td class="num"><?= rupiah($r['total_belanja']) ?></td>
                <td><?= tgl($r['terakhir']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>
<?php admin_footer(); ?>
