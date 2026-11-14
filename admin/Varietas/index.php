<?php
/** Kelola Varietas - daftar varietas. */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Varietas/varietas.css';

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT v.*, (SELECT COUNT(*) FROM jenis_beras jb WHERE jb.id_varietas = v.id_varietas) AS jml FROM varietas v';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE v.nama_varietas LIKE ? OR v.deskripsi LIKE ?';
    $params = ["%$q%", "%$q%"];
}
$stmt = $pdo->prepare($sql . ' ORDER BY v.nama_varietas');
$stmt->execute($params);
$varietasList = $stmt->fetchAll();

admin_header('Kelola Varietas', 'varietas');
page_header('Kelola Varietas', 'Varietas padi yang mendasari jenis beras.',
    '<a href="' . url('admin/Varietas/tambah.php') . '" class="btn-a">+ Tambah varietas</a>');
?>

<div class="panel">
    <div class="panel-head">
        <div><h2>Daftar Varietas</h2><p><?= count($varietasList) ?> data ditampilkan</p></div>
        <form method="GET" class="filter-bar">
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Cari varietas..." style="width:240px">
            <button class="btn-a outline">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
            <tr><th>#</th><th>Varietas</th><th>Deskripsi</th><th class="num">Jenis Beras</th><th class="num">Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$varietasList): ?>
                <tr><td colspan="5"><div class="empty-a">Belum ada data.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($varietasList as $i => $v): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                    <td><strong><?= h($v['nama_varietas']) ?></strong></td>
                    <td><?= h($v['deskripsi'] ?? '-') ?></td>
                    <td class="num"><span style="font-weight:700"><?= (int)$v['jml'] ?></span></td>
                    <td>
                        <div class="row-actions">
                            <a href="<?= url('admin/Varietas/edit.php?id=' . (int)$v['id_varietas']) ?>" class="btn-a outline icon">Ubah</a>
                            <form method="POST" action="<?= url('admin/Varietas/hapus.php') ?>" data-confirm="Hapus varietas ini? Tindakan tidak dapat dibatalkan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$v['id_varietas'] ?>">
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
