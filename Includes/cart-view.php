<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/produk-helper.php';

/* ------------------------------ AKSI ------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aksi = $_POST['aksi'] ?? '';
    $id   = (int)($_POST['id_produk'] ?? 0);
    $qty  = max(1, (int)($_POST['jumlah'] ?? 1));
    $cart = cart();

    if (in_array($aksi, ['tambah', 'beli', 'ubah'], true)) {
        $stmt = $pdo->prepare('SELECT nama_produk, stok FROM produk WHERE id_produk = ?');
        $stmt->execute([$id]);
        $prod = $stmt->fetch();
        if (!$prod) {
            flash('danger', 'Produk tidak ditemukan.');
            redirect('Includes/produk.php');
        }
        $baru = $aksi === 'ubah' ? $qty : ($cart[$id] ?? 0) + $qty;
        // cekStok(idProduk, jumlah)
        if ($baru > (int)$prod['stok']) {
            flash('danger', 'Stok "' . $prod['nama_produk'] . '" tidak cukup. Sisa stok: ' . (int)$prod['stok'] . '.');
            redirect($aksi === 'ubah' ? 'Includes/cart-view.php' : 'Includes/detail-produk.php?id=' . $id);
        }
        $cart[$id] = $baru;
        $_SESSION['cart'] = $cart;
        if ($aksi === 'beli') {
            redirect('Includes/checkout.php');
        }
        if ($aksi === 'tambah') {
            flash('success', '"' . $prod['nama_produk'] . '" ditambahkan ke keranjang.');
            $back = $_SERVER['HTTP_REFERER'] ?? url('Includes/cart-view.php');
            header('Location: ' . $back);
            exit;
        }
    } elseif ($aksi === 'hapus') {
        unset($cart[$id]);
        $_SESSION['cart'] = $cart;
        flash('info', 'Produk dihapus dari keranjang.');
    } elseif ($aksi === 'kosongkan') {
        $_SESSION['cart'] = [];
    }
    redirect('Includes/cart-view.php');
}

/* ------------------------------ DATA ------------------------------ */
$items = [];
$subtotal = 0;
$berat = 0;
$ongkir = 0;
if ($cart = cart()) {
    $ids = array_map('intval', array_keys($cart));
    $ph  = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare(PRODUK_SELECT . " WHERE p.id_produk IN ($ph)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
        $p['qty'] = $cart[$p['id_produk']];
        $p['sub'] = $p['qty'] * $p['harga'];
        $subtotal += $p['sub'];
        $berat    += $p['qty'] * $p['berat_kg'];
        $ongkir   += $p['qty'] * ongkir_satuan((float)$p['berat_kg']);
        $items[]   = $p;
    }
}

$pageTitle  = 'Keranjang';
$activeMenu = 'keranjang';
$pageCss = 'Includes/css/cart-view.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="breadcrumb-lite"><a href="<?= url('Includes/produk.php') ?>">Produk</a><span>/</span><span>Keranjang</span></div>
    <div class="page-heading">
        <div>
            <h1>Keranjang</h1>
        </div>
        <a href="<?= url('Includes/produk.php') ?>" class="link-more">Lanjut belanja</a>
    </div>

    <?php if (!$items): ?>
        <div class="card-box empty-state">
            <h3>Keranjang masih kosong</h3>
            <p>Belum ada beras yang dimasukkan ke keranjang.</p>
            <a href="<?= url('Includes/produk.php') ?>" class="btn-brown">Lihat produk</a>
        </div>
    <?php else: ?>
        <div class="two-col">
            <div class="card-box">
                <div class="d-flex justify-content-between align-items-center" style="border-bottom:1px solid var(--border-light);padding-bottom:10px">
                    <strong style="color:var(--primary)"><?= count($items) ?> produk</strong>
                    <form method="POST" data-confirm="Kosongkan seluruh keranjang?">
                        <?= csrf_field() ?><input type="hidden" name="aksi" value="kosongkan">
                        <button class="link-danger">Kosongkan keranjang</button>
                    </form>
                </div>
                <?php foreach ($items as $it): ?>
                    <div class="cart-item">
                        <img src="<?= upload_url('produk', $it['foto']) ?>" alt="">
                        <div>
                            <h4><a href="<?= url('Includes/detail-produk.php?id=' . (int)$it['id_produk']) ?>"><?= h($it['nama_produk']) ?></a></h4>
                            <small><?= rupiah($it['harga']) ?> &middot; sisa stok <?= (int)$it['stok'] ?></small>
                        </div>
                        <div class="cart-right">
                            <strong style="color:var(--primary)"><?= rupiah($it['sub']) ?></strong>
                            <form method="POST" class="d-flex align-items-center gap-2">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="ubah">
                                <input type="hidden" name="id_produk" value="<?= (int)$it['id_produk'] ?>">
                                <div class="qty-control" data-qty>
                                    <button type="button" data-step="-1">&minus;</button>
                                    <input type="number" name="jumlah" value="<?= (int)$it['qty'] ?>" min="1" max="<?= (int)$it['stok'] ?>" data-autosubmit>
                                    <button type="button" data-step="1">+</button>
                                </div>
                            </form>
                            <form method="POST">
                                <?= csrf_field() ?>
                                <input type="hidden" name="aksi" value="hapus">
                                <input type="hidden" name="id_produk" value="<?= (int)$it['id_produk'] ?>">
                                <button class="link-danger">Hapus</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <aside class="card-box sticky-col">
                <h2>Ringkasan belanja</h2>
                <div class="summary-row"><span>Jumlah barang</span><strong><?= array_sum(array_column($items, 'qty')) ?> pcs (<?= kg($berat) ?>)</strong></div>
                <div class="summary-row"><span>Harga produk</span><strong><?= rupiah($subtotal) ?></strong></div>
                <div class="summary-row"><span>Ongkos kirim</span><strong><?= rupiah($ongkir) ?></strong></div>
                <div class="summary-divider"></div>
                <div class="summary-total"><span>Total</span><span><?= rupiah($subtotal + $ongkir) ?></span></div>
                <a href="<?= url('Includes/checkout.php') ?>" class="btn-orange btn-block mt-3">Checkout</a>
                <p class="form-hint mt-2">Ongkir dihitung per pcs: berat kemasan x <?= rupiah(TARIF_ONGKIR_PER_KG) ?>/kg.</p>
            </aside>
        </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
