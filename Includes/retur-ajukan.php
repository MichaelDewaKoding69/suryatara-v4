<?php
/**
 * Activity & Sequence "Mengajukan Retur" (include: Mengunggah Foto Barang Retur)
 *   pilih pesanan Selesai -> isi alasan, jumlah, rekening refund -> unggah foto (1..5) -> validasiData
 *   -> insertRetur + insert foto_retur (Retur "Dilengkapi" Foto Retur 1..*)
 */
require_once __DIR__ . '/../config/koneksi.php';
require_pelanggan();

const MAKS_FOTO_RETUR = 5;

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?');
$stmt->execute([$id, user_id()]);
$o = $stmt->fetch();
if (!$o || $o['status_pesanan'] !== 'Selesai') {
    flash('warning', 'Retur hanya bisa diajukan untuk pesanan yang sudah selesai.');
    redirect('Includes/pesanan-saya.php?status=Selesai');
}
$kode = kode($id);

$stmt = $pdo->prepare('SELECT pp.*, p.nama_produk, p.foto FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk
                       WHERE pp.id_pesanan = ? AND NOT EXISTS (SELECT 1 FROM retur r WHERE r.id_pesanan = pp.id_pesanan AND r.id_produk = pp.id_produk)');
$stmt->execute([$id]);
$items = $stmt->fetchAll();
if (!$items) {
    flash('info', 'Semua produk di pesanan ini sudah diajukan retur.');
    redirect('Includes/retur-saya.php');
}
$itemMap = [];
foreach ($items as $it) {
    $itemMap[(int)$it['id_produk']] = $it;
}

$old = [
    'id_produk'    => (int)($_POST['id_produk'] ?? $items[0]['id_produk']),
    'jumlah_retur' => (int)($_POST['jumlah_retur'] ?? 1),
    'alasan_retur' => trim((string)($_POST['alasan_retur'] ?? '')),
    'bank'         => trim((string)($_POST['bank'] ?? '')),
    'no_rekening'  => preg_replace('/\D/', '', (string)($_POST['no_rekening'] ?? '')),
    'atas_nama'    => trim((string)($_POST['atas_nama'] ?? $_SESSION['nama'])),
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $item = $itemMap[$old['id_produk']] ?? null;
    if (!$item) {
        $errors[] = 'Pilih produk yang mau diretur.';
    } elseif ($old['jumlah_retur'] < 1 || $old['jumlah_retur'] > (int)$item['jumlah']) {
        $errors[] = 'Jumlah retur harus 1 sampai ' . (int)$item['jumlah'] . '.';
    }
    if (mb_strlen($old['alasan_retur']) < 10) {
        $errors[] = 'Tulis alasan retur minimal 10 karakter.';
    }
    $rekening = trim($old['bank'] . ' ' . $old['no_rekening'] . ' a.n. ' . $old['atas_nama']);
    if ($old['bank'] === '' || strlen($old['no_rekening']) < 6 || $old['atas_nama'] === '') {
        $errors[] = 'Lengkapi rekening untuk refund (bank, nomor, dan nama pemilik).';
    } elseif (mb_strlen($rekening) > 50) {
        $errors[] = 'Data rekening terlalu panjang (maks. 50 karakter), singkat nama bank atau nama pemilik.';
    }
    if (!$errors) {
        try {
            $fotos = upload_images('foto_retur', 'retur', 'retur_' . strtolower($kode), 1, MAKS_FOTO_RETUR);
            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO retur (id_pesanan, id_produk, tanggal_retur, alasan_retur, jumlah_retur, rekening_refund, status_retur) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id, $old['id_produk'], now(), $old['alasan_retur'], $old['jumlah_retur'], $rekening, 'Menunggu Verifikasi']);
            $idRetur = (int)$pdo->lastInsertId();
            $insFoto = $pdo->prepare('INSERT INTO foto_retur (id_retur, nama_file, tanggal_upload) VALUES (?, ?, ?)');
            foreach ($fotos as $f) {
                $insFoto->execute([$idRetur, $f, now()]);
            }
            $pdo->commit();
            flash('success', 'Pengajuan retur terkirim dengan ' . count($fotos) . ' foto. Tunggu konfirmasi dari admin.');
            redirect('Includes/retur-saya.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Pengajuan retur gagal disimpan.';
        }
    }
}

$pageTitle  = 'Ajukan Retur';
$activeMenu = 'retur';
$pageCss = 'Includes/css/retur-ajukan.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="breadcrumb-lite">
        <a href="<?= url('Includes/pesanan-saya.php?status=Selesai') ?>">Pesanan selesai</a><span>/</span>
        <a href="<?= url('Includes/pesanan-detail.php?id=' . $id) ?>"><?= h($kode) ?></a><span>/</span><span>Ajukan retur</span>
    </div>
    <div class="page-heading"><div><h1>Ajukan retur</h1><p>Pesanan <?= h($kode) ?>, <?= tgl($o['tanggal_pesanan']) ?></p></div></div>

    <?php if ($errors): ?>
        <div class="alert-box danger"><span><?= h(implode(' ', $errors)) ?></span></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="two-col" style="grid-template-columns:minmax(0,1fr) 360px">
        <?= csrf_field() ?>
        <div>
            <div class="card-box">
                <h2>Produk yang diretur</h2>
                <table class="table-x">
                    <tbody>
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td style="width:30px"><input type="radio" name="id_produk" value="<?= (int)$it['id_produk'] ?>" data-max="<?= (int)$it['jumlah'] ?>" <?= $old['id_produk'] === (int)$it['id_produk'] ? 'checked' : '' ?> style="accent-color:var(--primary)" id="pr<?= (int)$it['id_produk'] ?>"></td>
                            <td><label for="pr<?= (int)$it['id_produk'] ?>" class="mini-item" style="cursor:pointer"><img src="<?= upload_url('produk', $it['foto']) ?>" alt=""><div><strong><?= h($it['nama_produk']) ?></strong><small>dibeli <?= (int)$it['jumlah'] ?> x <?= rupiah($it['harga_satuan']) ?></small></div></label></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="form-grid mt-3">
                    <div>
                        <label class="form-label-x">Jumlah yang diretur (pcs)</label>
                        <input type="number" name="jumlah_retur" id="jumlahRetur" class="input-x" min="1" value="<?= (int)$old['jumlah_retur'] ?>" required>
                    </div>
                    <div class="full">
                        <label class="form-label-x">Alasan retur</label>
                        <textarea name="alasan_retur" class="textarea-x" placeholder="Contoh: karung sobek, beras berkutu, jenis beras tidak sesuai" required><?= h($old['alasan_retur']) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-box">
                <h2>Rekening untuk refund</h2>
                <div class="form-grid">
                    <div><label class="form-label-x">Bank</label><input name="bank" class="input-x" value="<?= h($old['bank']) ?>" placeholder="BCA, BRI, Mandiri..." maxlength="15" required></div>
                    <div><label class="form-label-x">Nomor rekening</label><input name="no_rekening" class="input-x" value="<?= h($old['no_rekening']) ?>" inputmode="numeric" maxlength="20" required></div>
                    <div class="full"><label class="form-label-x">Nama pemilik rekening</label><input name="atas_nama" class="input-x" value="<?= h($old['atas_nama']) ?>" maxlength="25" required></div>
                </div>
            </div>
        </div>
        <aside class="sticky-col">
            <div class="bukti-side">
                <div class="bukti-side__head"><h3>Foto barang retur</h3><span style="font-size:12px;color:var(--text-muted)">1 - <?= MAKS_FOTO_RETUR ?> foto</span></div>
                <div style="padding:14px">
                    <label class="upload-area" for="foto_retur">
                        <strong>Pilih foto</strong>
                        <span id="fotoInfo">Foto kemasan dan isi beras. JPG/PNG/WEBP, maks. <?= MAKS_UPLOAD_MB ?> MB per foto.</span>
                    </label>
                    <input type="file" name="foto_retur[]" id="foto_retur" class="upload-input" accept=".jpg,.jpeg,.png,.webp,image/*" multiple required>
                    <div class="thumbs" id="fotoPreview" style="margin-top:12px"></div>
                    <button class="btn-orange btn-block mt-3">Kirim pengajuan</button>
                </div>
            </div>
        </aside>
    </form>
</div>

<script>
(function () {
    var j = document.getElementById('jumlahRetur');
    function sync() {
        var r = document.querySelector('input[name=id_produk]:checked');
        if (r) { j.max = r.dataset.max; if (+j.value > +r.dataset.max) j.value = r.dataset.max; }
    }
    document.querySelectorAll('input[name=id_produk]').forEach(function (r) { r.addEventListener('change', sync); });
    sync();

    var input = document.getElementById('foto_retur'), box = document.getElementById('fotoPreview'), info = document.getElementById('fotoInfo');
    input.addEventListener('change', function () {
        box.innerHTML = '';
        var n = input.files.length;
        info.textContent = n ? n + ' foto dipilih' + (n > <?= MAKS_FOTO_RETUR ?> ? ' (maks. <?= MAKS_FOTO_RETUR ?>, kurangi dulu)' : '') : 'Belum ada foto dipilih';
        Array.prototype.forEach.call(input.files, function (f) {
            var img = document.createElement('img');
            img.src = window.URL.createObjectURL(f);
            img.dataset.zoom = img.src;
            box.appendChild(img);
        });
    });
})();
</script>

<?php require __DIR__ . '/footer.php'; ?>
