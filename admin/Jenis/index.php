<?php
/**
 * Kelola Jenis Beras - daftar jenis beras.
 * Satu jenis beras = kombinasi mutu, tekstur, warna, varietas, dan bulir.
 */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Jenis/jenis-beras.css';

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT jb.*, m.nama_mutu, v.nama_varietas, t.nama_tekstur, w.nama_warna, b.nama_bulir,
               (SELECT COUNT(*) FROM produk p WHERE p.id_jenis_beras = jb.id_jenis_beras) AS jml_produk
        FROM jenis_beras jb
        JOIN mutu m ON m.id_mutu = jb.id_mutu
        JOIN varietas v ON v.id_varietas = jb.id_varietas
        JOIN tekstur t ON t.id_tekstur = jb.id_tekstur
        JOIN warna w ON w.id_warna = jb.id_warna
        JOIN bulir b ON b.id_bulir = jb.id_bulir';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE jb.nama_jenis LIKE ? OR v.nama_varietas LIKE ?';
    $params = ["%$q%", "%$q%"];
}
$stmt = $pdo->prepare($sql . ' ORDER BY jb.nama_jenis');
$stmt->execute($params);
$jenisList = $stmt->fetchAll();

admin_header('Kelola Jenis Beras', 'jenis_beras');
page_header('Kelola Jenis Beras', 'Satu jenis beras = kombinasi mutu, tekstur, warna, varietas, dan bulir.',
    '<a href="' . url('admin/Jenis/tambah.php') . '" class="btn-a">+ Tambah jenis beras</a>');
?>

<div class="panel">
    <div class="panel-head">
        <div><h2>Daftar Jenis Beras</h2><p><?= count($jenisList) ?> data ditampilkan</p></div>
        <form method="GET" class="filter-bar">
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Cari jenis beras..." style="width:240px">
            <button class="btn-a outline">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
            <tr><th>#</th><th>Jenis Beras</th><th>Mutu</th><th>Tekstur</th><th>Warna</th><th>Varietas</th><th>Bulir</th><th class="num">Produk</th><th class="num">Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$jenisList): ?>
                <tr><td colspan="9"><div class="empty-a">Belum ada data.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($jenisList as $i => $j): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= h($j['nama_jenis']) ?></strong>
                        <?php if (!empty($j['deskripsi'])): ?><span class="cell-sub"><?= h(mb_strimwidth($j['deskripsi'], 0, 70, '...')) ?></span><?php endif; ?>
                    </td>
                    <td><?= h($j['nama_mutu']) ?></td>
                    <td><?= h($j['nama_tekstur']) ?></td>
                    <td><?= h($j['nama_warna']) ?></td>
                    <td><?= h($j['nama_varietas']) ?></td>
                    <td><?= h($j['nama_bulir']) ?></td>
                    <td class="num"><span style="font-weight:700"><?= (int)$j['jml_produk'] ?></span></td>
                    <td>
                        <div class="row-actions">
                            <a href="<?= url('admin/Jenis/edit.php?id=' . (int)$j['id_jenis_beras']) ?>" class="btn-a outline icon">Ubah</a>
                            <form method="POST" action="<?= url('admin/Jenis/hapus.php') ?>" data-confirm="Hapus jenis beras ini? Tindakan tidak dapat dibatalkan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$j['id_jenis_beras'] ?>">
                                <button class="btn-a danger outline icon">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php admin_footer(); ?>
