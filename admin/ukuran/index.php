<?php
/** Kelola Ukuran - daftar ukuran. */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/ukuran/ukuran.css';

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT u.*, (SELECT COUNT(*) FROM produk p WHERE p.id_ukuran = u.id_ukuran) AS jml FROM ukuran u';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE u.nama_ukuran LIKE ?';
    $params = ["%$q%"];
}
$stmt = $pdo->prepare($sql . ' ORDER BY u.berat_kg');
$stmt->execute($params);
$ukuranList = $stmt->fetchAll();

admin_header('Kelola Ukuran', 'ukuran');
page_header('Kelola Ukuran', 'Ukuran kemasan beras. Berat dipakai untuk menghitung ongkir per pcs (berat x Rp ' . number_format(TARIF_ONGKIR_PER_KG, 0, ',', '.') . '/kg).',
    '<a href="' . url('admin/ukuran/tambah.php') . '" class="btn-a">+ Tambah ukuran</a>');
?>

<div class="panel">
    <div class="panel-head">
        <div><h2>Daftar Ukuran</h2><p><?= count($ukuranList) ?> data ditampilkan</p></div>
        <form method="GET" class="filter-bar">
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Cari ukuran..." style="width:240px">
            <button class="btn-a outline">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
            <tr><th>#</th><th>Ukuran</th><th class="num">Berat</th><th class="num">Produk</th><th class="num">Aksi</th></tr>
            </thead>
            <tbody>
            <?php if (!$ukuranList): ?>
                <tr><td colspan="5"><div class="empty-a">Belum ada data.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($ukuranList as $i => $u): ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                    <td><strong><?= h($u['nama_ukuran']) ?></strong></td>
                    <td class="num"><?= kg($u['berat_kg']) ?></td>
                    <td class="num"><span style="font-weight:700"><?= (int)$u['jml'] ?></span></td>
                    <td>
                        <div class="row-actions">
                            <a href="<?= url('admin/ukuran/edit.php?id=' . (int)$u['id_ukuran']) ?>" class="btn-a outline icon">Ubah</a>
                            <form method="POST" action="<?= url('admin/ukuran/hapus.php') ?>" data-confirm="Hapus ukuran ini? Tindakan tidak dapat dibatalkan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$u['id_ukuran'] ?>">
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
