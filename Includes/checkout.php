<?php
/**
 * Sequence "Membuat Pesanan": cekStok -> hitungTotal -> insertPesanan -> insertDetailPesanan
 *
 * Catatan ERD terbaru: Pengiriman berelasi ke Pembayaran ("Setelah", 1..1), jadi baris pengiriman
 * dibuat saat pelanggan mengunggah bukti bayar. Alamat yang diisi di sini disimpan sementara di sesi
 * dan otomatis terisi di halaman pembayaran.
 */
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/produk-helper.php';
require_pelanggan();

$cart = cart();
if (!$cart) {
    flash('info', 'Keranjang masih kosong.');
    redirect('Includes/produk.php');
}

$stmt = $pdo->prepare('SELECT * FROM `user` WHERE id_user = ?');
$stmt->execute([user_id()]);
$user = $stmt->fetch();

$ids = array_map('intval', array_keys($cart));
$ph  = implode(',', array_fill(0, count($ids), '?'));
$stmt = $pdo->prepare(PRODUK_SELECT . " WHERE p.id_produk IN ($ph)");
$stmt->execute($ids);
$items = [];
$totalProduk = 0;
$totalOngkir = 0;
$berat = 0;
foreach ($stmt->fetchAll() as $p) {
    $p['qty']     = (int)$cart[$p['id_produk']];
    $p['ongkir']  = ongkir_satuan((float)$p['berat_kg']);
    $p['sub']     = $p['qty'] * ((float)$p['harga'] + $p['ongkir']);
    $totalProduk += $p['qty'] * (float)$p['harga'];
    $totalOngkir += $p['qty'] * $p['ongkir'];
    $berat       += $p['qty'] * (float)$p['berat_kg'];
    $items[]      = $p;
}
$total = $totalProduk + $totalOngkir;

$old = [
    'nama'   => trim((string)($_POST['nama'] ?? $user['nama'])),
    'no_hp'  => trim((string)($_POST['no_hp'] ?? $user['no_telepon'])),
    'alamat' => trim((string)($_POST['alamat'] ?? $user['alamat'])),
    'kota'   => trim((string)($_POST['kota'] ?? $user['kota'])),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (['nama' => 'Nama penerima', 'no_hp' => 'No. HP', 'alamat' => 'Alamat', 'kota' => 'Kota'] as $f => $label) {
        if ($old[$f] === '') {
            $errors[] = "$label wajib diisi.";
        }
    }
    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO pesanan (id_user, tanggal_pesanan, total_harga, status_pesanan) VALUES (?, ?, ?, ?)')
                ->execute([user_id(), now(), $total, 'Belum Melakukan Pembayaran']);
            $idPesanan = (int)$pdo->lastInsertId();

            $insDetail = $pdo->prepare('INSERT INTO pesanan_produk (id_pesanan, id_produk, jumlah, harga_satuan, ongkir_satuan, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            $kurangi   = $pdo->prepare('UPDATE produk SET stok = stok - ? WHERE id_produk = ? AND stok >= ?');
            foreach ($items as $it) {
                $kurangi->execute([$it['qty'], $it['id_produk'], $it['qty']]);
                if ($kurangi->rowCount() === 0) {
                    throw new RuntimeException('Stok ' . $it['nama_produk'] . ' tidak cukup. Ubah jumlahnya di keranjang.');
                }
                $insDetail->execute([$idPesanan, $it['id_produk'], $it['qty'], $it['harga'], $it['ongkir'], $it['sub']]);
            }
            $pdo->commit();

            $_SESSION['alamat_pesanan'][$idPesanan] = $old;
            $_SESSION['cart'] = [];
            flash('success', 'Pesanan ' . kode($idPesanan) . ' sudah dibuat. Silakan transfer lalu unggah bukti bayarnya.');
            redirect('Includes/bayar.php?id=' . $idPesanan);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Pesanan gagal dibuat: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Checkout';
$pageCss = 'Includes/css/checkout.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="breadcrumb-lite"><a href="<?= url('Includes/cart-view.php') ?>">Keranjang</a><span>/</span><span>Checkout</span></div>
    <div class="page-heading"><div><h1>Checkout</h1></div></div>

    <?php foreach ($errors as $e): ?>
        <div class="alert-box danger"><span><?= h($e) ?></span></div>
    <?php endforeach; ?>

    <form method="POST" class="two-col">
        <?= csrf_field() ?>
        <div>
            <div class="card-box">
                <h2>Alamat pengiriman</h2>
                <div class="form-grid">
                    <div><label class="form-label-x">Nama penerima</label><input class="input-x" name="nama" value="<?= h($old['nama']) ?>" required></div>
                    <div><label class="form-label-x">No. HP</label><input class="input-x" name="no_hp" value="<?= h($old['no_hp']) ?>" required></div>
                    <div class="full"><label class="form-label-x">Alamat lengkap</label><textarea class="textarea-x" name="alamat" required><?= h($old['alamat']) ?></textarea></div>
                    <div class="full"><label class="form-label-x">Kota</label><input class="input-x" name="kota" value="<?= h($old['kota']) ?>" required></div>
                </div>
            </div>

            <div class="card-box">
                <h2>Pesanan</h2>
                <div style="overflow-x:auto">
                <table class="table-x">
                    <thead><tr><th>Produk</th><th class="num">Harga</th><th class="num">Ongkir/pcs</th><th class="num">Jml</th><th class="num">Subtotal</th></tr></thead>
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td><div class="mini-item"><img src="<?= upload_url('produk', $it['foto']) ?>" alt=""><div><strong><?= h($it['nama_produk']) ?></strong><small><?= h($it['merek']) ?></small></div></div></td>
                            <td class="num"><?= rupiah($it['harga']) ?></td>
                            <td class="num"><?= rupiah($it['ongkir']) ?></td>
                            <td class="num"><?= (int)$it['qty'] ?></td>
                            <td class="num"><?= rupiah($it['sub']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <p class="form-hint">Jasa kurir dipilih oleh toko waktu pesanan dikirim.</p>
            </div>
        </div>

        <aside class="card-box sticky-col">
            <h2>Total</h2>
            <div class="summary-row"><span>Harga produk</span><strong><?= rupiah($totalProduk) ?></strong></div>
            <div class="summary-row"><span>Ongkir (<?= kg($berat) ?>)</span><strong><?= rupiah($totalOngkir) ?></strong></div>
            <div class="summary-divider"></div>
            <div class="summary-total"><span>Total bayar</span><span><?= rupiah($total) ?></span></div>
            <p class="form-hint mt-2">Pembayaran lewat transfer <?= h(REKENING_BANK) ?>. Setelah pesanan dibuat, unggah bukti transfernya di halaman berikutnya.</p>
            <button type="submit" class="btn-orange btn-block">Buat pesanan</button>
        </aside>
    </form>
</div>

<?php require __DIR__ . '/footer.php'; ?>
