<?php
/** Kelola Jasa Kurir - daftar jasa kurir. */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/jasa_kurir/jasakurir.css';

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT k.*, (SELECT COUNT(*) FROM pengiriman pg WHERE pg.id_jasa_kurir = k.id_jasa_kurir) AS jml FROM jasa_kurir k';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE k.nama_kurir LIKE ?';
    $params = ["%$q%"];
}
$stmt = $pdo->prepare($sql . ' ORDER BY k.nama_kurir');
$stmt->execute($params);
$kurirList = $stmt->fetchAll();

admin_header('Kelola Jasa Kurir', 'jasa_kurir');
page_header('Kelola Jasa Kurir', 'Daftar jasa kirim yang bisa dipilih saat memproses pesanan.',
    '<a href="' . url('admin/jasa_kurir/tambah.php') . '" class="btn-a">+ Tambah jasa kurir</a>');
?>

<div class="panel">
    <div class="panel-head">
        <div><h2>Daftar Jasa Kurir</h2><p><?= count($kurirList) ?> data ditampilkan</p></div>
        <form method="GET" class="filter-bar">
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Cari jasa kurir..." style="width:240px">
            <button class="btn-a outline">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
            <tr><th>#</th><th>Jasa Kurir</th><th class="num">Dipakai untuk pengiriman</th><th class="num">Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$kurirList): ?>
                <tr><td colspan="4"><div class="empty-a">Belum ada data.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($kurirList as $i => $k): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                    <td><strong><?= h($k['nama_kurir']) ?></strong></td>
                    <td class="num"><span style="font-weight:700"><?= (int)$k['jml'] ?></span></td>
                    <td>
                        <div class="row-actions">
                            <a href="<?= url('admin/jasa_kurir/edit.php?id=' . (int)$k['id_jasa_kurir']) ?>" class="btn-a outline icon">Ubah</a>
                            <form method="POST" action="<?= url('admin/jasa_kurir/hapus.php') ?>" data-confirm="Hapus jasa kurir ini? Tindakan tidak dapat dibatalkan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$k['id_jasa_kurir'] ?>">
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
