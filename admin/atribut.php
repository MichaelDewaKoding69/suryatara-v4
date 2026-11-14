<?php
/**
 * Atribut beras: Mutu, Tekstur, Warna, Bulir, Kadar Patahan.
 * Nilainya sudah tetap dan tidak ada use case untuk mengelolanya, jadi halaman ini hanya menampilkan
 * data (tanpa tambah / ubah / hapus). Nilai dipakai saat Kelola Jenis Beras dan Kelola Produk.
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/atribut.css';

$atribut = [
    'mutu'          => ['Mutu', 'jenis_beras', 'jenis beras'],
    'tekstur'       => ['Tekstur', 'jenis_beras', 'jenis beras'],
    'warna'         => ['Warna', 'jenis_beras', 'jenis beras'],
    'bulir'         => ['Bulir', 'jenis_beras', 'jenis beras'],
    'kadar_patahan' => ['Kadar Patahan', 'produk', 'produk'],
];

admin_header('Atribut Beras', 'atribut');
page_header('Atribut Beras', 'Nilai atribut sudah ditetapkan dan tidak bisa ditambah, diubah, atau dihapus.');
?>

<div class="info-note">Atribut ini dipakai sebagai pilihan saat mengisi <a href="<?= url('admin/Jenis/index.php') ?>" style="text-decoration:underline">jenis beras</a> (mutu, tekstur, warna, bulir) dan <a href="<?= url('admin/Produk/index.php') ?>" style="text-decoration:underline">produk</a> (kadar patahan).</div>

<div class="grid-2" style="grid-template-columns:1fr 1fr">
    <?php foreach ($atribut as $t => [$judul, $child, $childLabel]):
        $rows = $pdo->query("SELECT x.*, (SELECT COUNT(*) FROM $child c WHERE c.id_$t = x.id_$t) AS jml FROM $t x ORDER BY x.id_$t")->fetchAll(); ?>
        <div class="panel">
            <div class="panel-head"><h2><?= h($judul) ?></h2><span style="font-size:12px;color:var(--text-muted)"><?= count($rows) ?> nilai</span></div>
            <table class="table-admin">
                <thead><tr><th style="width:36px">#</th><th><?= h($judul) ?></th><th>Keterangan</th><th class="num">Dipakai</th></tr></thead>
                <tbody>
                <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                        <td><strong><?= h($r["nama_$t"]) ?></strong></td>
                        <td style="color:var(--text-secondary)"><?= h($r['deskripsi'] ?? '') ?></td>
                        <td class="num"><?= (int)$r['jml'] ?> <?= $childLabel ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

<?php admin_footer(); ?>
