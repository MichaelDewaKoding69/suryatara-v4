<?php
/**
 * Activity "Membuat Pesanan" - langkah awal: membuka menu, mengambil & menampilkan data produk.
 */
$pageTitle  = 'Produk';
$activeMenu = 'produk';
$pageCss = 'Includes/css/produk.css';
require __DIR__ . '/header.php';
require __DIR__ . '/produk-helper.php';

$q    = trim((string)($_GET['q'] ?? ''));
$urut = (string)($_GET['urut'] ?? 'populer');

$filterDefs = [
    'mutu'     => ['Mutu',          'SELECT id_mutu AS id, nama_mutu AS nama FROM mutu ORDER BY id_mutu',                         'jb.id_mutu'],
    'varietas' => ['Varietas',      'SELECT id_varietas AS id, nama_varietas AS nama FROM varietas ORDER BY nama_varietas',        'jb.id_varietas'],
    'warna'    => ['Warna',         'SELECT id_warna AS id, nama_warna AS nama FROM warna ORDER BY id_warna',                      'jb.id_warna'],
    'tekstur'  => ['Tekstur',       'SELECT id_tekstur AS id, nama_tekstur AS nama FROM tekstur ORDER BY id_tekstur',              'jb.id_tekstur'],
    'ukuran'   => ['Ukuran',        'SELECT id_ukuran AS id, nama_ukuran AS nama FROM ukuran ORDER BY berat_kg',                   'p.id_ukuran'],
    'kadar'    => ['Kadar Patahan', 'SELECT id_kadar_patahan AS id, nama_kadar_patahan AS nama FROM kadar_patahan ORDER BY id_kadar_patahan', 'p.id_kadar_patahan'],
];

$where  = [];
$params = [];
$aktif  = [];
foreach ($filterDefs as $key => [$label, $sql, $col]) {
    $ids = array_values(array_filter(array_map('intval', (array)($_GET[$key] ?? []))));
    if ($ids) {
        $ph = [];
        foreach ($ids as $i => $id) {
            $ph[] = ":{$key}{$i}";
            $params[":{$key}{$i}"] = $id;
        }
        $where[] = "$col IN (" . implode(',', $ph) . ")";
    }
    $filterDefs[$key][] = $ids;
}
if ($q !== '') {
    $where[] = "(p.nama_produk LIKE :q OR jb.nama_jenis LIKE :q2 OR v.nama_varietas LIKE :q3 OR p.merek LIKE :q4)";
    $params[':q'] = $params[':q2'] = $params[':q3'] = $params[':q4'] = "%$q%";
}
if (!empty($_GET['tersedia'])) {
    $where[] = 'p.stok > 0';
}

$orderBy = [
    'populer'  => 'terjual DESC, p.id_produk ASC',
    'terbaru'  => 'p.id_produk DESC',
    'termurah' => 'p.harga ASC',
    'termahal' => 'p.harga DESC',
    'nama'     => 'p.nama_produk ASC',
][$urut] ?? 'terjual DESC, p.id_produk ASC';

$stmt = $pdo->prepare(PRODUK_SELECT . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $orderBy");
$stmt->execute($params);
$produkList = produk_grup($stmt->fetchAll());
?>

<div class="page-wrap">
    <div class="katalog-layout">
        <form method="GET" id="filterForm" class="filter-panel">
            <div class="filter-card filter-card--search">
                <div class="filter-card__head"><h3>Cari</h3></div>
                <div class="filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" value="<?= h($q) ?>" class="input-x" placeholder="Nama beras, merek, varietas...">
                </div>
                <label class="filter-switch">
                    <input type="checkbox" name="tersedia" value="1" data-autosubmit <?= !empty($_GET['tersedia']) ? 'checked' : '' ?>>
                    <span class="filter-switch__track"></span>
                    Hanya yang ada stok
                </label>
            </div>
            <?php foreach ($filterDefs as $key => [$label, $sql, $col, $selected]): ?>
                <div class="filter-card filter-card--<?= $key ?>">
                    <div class="filter-card__head">
                        <h3><?= h($label) ?></h3>
                        <?php if ($selected): ?><span class="filter-card__count"><?= count($selected) ?> dipilih</span><?php endif; ?>
                    </div>
                    <div class="filter-chips">
                        <?php foreach ($pdo->query($sql)->fetchAll() as $opt): ?>
                            <label class="filter-chip">
                                <input type="checkbox" name="<?= $key ?>[]" value="<?= (int)$opt['id'] ?>" data-autosubmit
                                    <?= in_array((int)$opt['id'], $selected, true) ? 'checked' : '' ?>>
                                <?= h($opt['nama']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="filter-actions">
                <a href="<?= url('Includes/produk.php') ?>" class="btn-outline-brown btn-sm-x">Reset semua</a>
                <button class="btn-brown btn-sm-x">Terapkan</button>
            </div>
        </form>

        <section>
            <div class="katalog-toolbar">
                <p><?= count($produkList) ?> produk<?= $q !== '' ? ' untuk "' . h($q) . '"' : '' ?></p>
                <div class="d-flex align-items-center gap-2">
                    <label for="urut" style="font-size:12px;color:var(--text-secondary)">Urutkan</label>
                    <select name="urut" id="urut" form="filterForm" class="select-x" style="height:38px;width:160px" data-autosubmit>
                        <?php foreach (['populer' => 'Paling laris', 'terbaru' => 'Terbaru', 'termurah' => 'Harga terendah', 'termahal' => 'Harga tertinggi', 'nama' => 'Nama A-Z'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $urut === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php if ($produkList): ?>
                <div class="produk-grid cols-3">
                    <?php foreach ($produkList as $p) echo produk_card($p); ?>
                </div>
            <?php else: ?>
                <div class="card-box empty-state">
                    <h3>Tidak ada produk yang cocok</h3>
                    <p>Coba kata kunci lain atau kurangi filter.</p>
                    <a href="<?= url('Includes/produk.php') ?>" class="btn-outline-brown">Hapus filter</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<?php require __DIR__ . '/footer.php'; ?>
