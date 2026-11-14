<?php
/**
 * Kelola Produk - daftar produk.
 * Activity & Sequence "Kelola Produk": tampilkanDaftar -> klikTambah / klikUbah / klikHapus
 */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Produk/produk.css';

$q = trim((string)($_GET['q'] ?? ''));
$sql = 'SELECT p.*, jb.nama_jenis, u.nama_ukuran, k.nama_kadar_patahan, m.nama_mutu
        FROM produk p
        JOIN jenis_beras jb ON jb.id_jenis_beras = p.id_jenis_beras
        JOIN mutu m ON m.id_mutu = jb.id_mutu
        JOIN ukuran u ON u.id_ukuran = p.id_ukuran
        JOIN kadar_patahan k ON k.id_kadar_patahan = p.id_kadar_patahan';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE p.nama_produk LIKE ? OR p.merek LIKE ? OR jb.nama_jenis LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%"];
}
$stmt = $pdo->prepare($sql . ' ORDER BY p.id_produk DESC');
$stmt->execute($params);
$produkList = $stmt->fetchAll();

admin_header('Kelola Produk', 'produk');
page_header('Kelola Produk', 'Kelola merek, nama, harga, stok, foto, jenis beras, ukuran, dan kadar patahan produk.',
    '<a href="' . url('admin/Produk/tambah.php') . '" class="btn-a">+ Tambah produk</a>');
?>

<div class="panel">
    <div class="panel-head">
        <div><h2>Daftar Produk</h2><p><?= count($produkList) ?> data ditampilkan</p></div>
        <form method="GET" class="filter-bar">
            <input name="q" value="<?= h($q) ?>" class="input-a" placeholder="Cari produk..." style="width:240px">
            <button class="btn-a outline">Cari</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="table-admin">
            <thead>
            <tr>
                <th>#</th><th>Foto</th><th>Produk</th><th>Merek</th><th>Mutu</th><th>Ukuran</th><th>Patahan</th>
                <th class="num">Harga</th><th>Stok</th><th class="num">Aksi</th>
            </tr>
            </thead>
            <tbody>
            <?php if (!$produkList): ?>
                <tr><td colspan="10"><div class="empty-a">Belum ada data.</div></td></tr>
            <?php endif; ?>
            <?php foreach ($produkList as $i => $p): $foto = upload_url('produk', $p['foto']); ?>
                <tr>
                    <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                    <td><img src="<?= $foto ?>" data-zoom="<?= $foto ?>" class="thumb" alt="" style="cursor:zoom-in"></td>
                    <td><strong><?= h($p['nama_produk']) ?></strong><span class="cell-sub"><?= h($p['nama_jenis']) ?></span></td>
                    <td><?= h($p['merek']) ?></td>
                    <td><?= h($p['nama_mutu']) ?></td>
                    <td><?= h($p['nama_ukuran']) ?></td>
                    <td><?= h($p['nama_kadar_patahan']) ?></td>
                    <td class="num"><?= rupiah($p['harga']) ?></td>
                    <td>
                        <?php if ((int)$p['stok'] <= 0): ?>
                            <span class="badge-status danger">Habis</span>
                        <?php elseif ((int)$p['stok'] <= 5): ?>
                            <span class="badge-status warning"><?= (int)$p['stok'] ?> (menipis)</span>
                        <?php else: ?>
                            <strong><?= (int)$p['stok'] ?></strong>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="row-actions">
                            <a href="<?= url('admin/Produk/edit.php?id=' . (int)$p['id_produk']) ?>" class="btn-a outline icon">Ubah</a>
                            <form method="POST" action="<?= url('admin/Produk/hapus.php') ?>" data-confirm="Hapus produk ini? Tindakan tidak dapat dibatalkan.">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int)$p['id_produk'] ?>">
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
