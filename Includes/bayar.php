<?php
/**
 * Activity & Sequence "Melakukan Pembayaran" (include: Mengunggah Bukti Pembayaran)
 *
 * Status pesanan:
 *   Belum Melakukan Pembayaran  -> pelanggan belum mengunggah bukti bayar
 *   Sudah Upload Bukti          -> bukti sudah diunggah, menunggu disetujui admin
 * Saat bukti diunggah: insert pembayaran + insert pengiriman (Pengiriman "Setelah" Pembayaran, 1..1).
 */
require_once __DIR__ . '/../config/koneksi.php';
require_pelanggan();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM pesanan WHERE id_pesanan = ? AND id_user = ?');
$stmt->execute([$id, user_id()]);
$pesanan = $stmt->fetch();
if (!$pesanan) {
    flash('warning', 'Pesanan tidak ditemukan.');
    redirect('Includes/pesanan-saya.php');
}
$kode = kode($id);
$bisaBayar = $pesanan['status_pesanan'] === 'Belum Melakukan Pembayaran';

$stmt = $pdo->prepare('SELECT COALESCE(SUM(jumlah * ongkir_satuan), 0) FROM pesanan_produk WHERE id_pesanan = ?');
$stmt->execute([$id]);
$totalOngkir = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT pb.*, pg.alamat_pengiriman FROM pembayaran pb LEFT JOIN pengiriman pg ON pg.id_pembayaran = pb.id_pembayaran
                       WHERE pb.id_pesanan = ? ORDER BY pb.tanggal_pembayaran DESC, pb.id_pembayaran DESC');
$stmt->execute([$id]);
$riwayat  = $stmt->fetchAll();
$terakhir = $riwayat[0] ?? null;

// alamat: dari checkout (sesi) -> dari upload sebelumnya -> dari profil
$stmt = $pdo->prepare('SELECT * FROM `user` WHERE id_user = ?');
$stmt->execute([user_id()]);
$user = $stmt->fetch();
$alamatAwal = $_SESSION['alamat_pesanan'][$id] ?? ['nama' => $user['nama'], 'no_hp' => $user['no_telepon'], 'alamat' => $user['alamat'], 'kota' => $user['kota']];
if (!isset($_SESSION['alamat_pesanan'][$id]) && $terakhir && $terakhir['alamat_pengiriman']) {
    $lines = explode("\n", $terakhir['alamat_pengiriman'], 2);
    if (preg_match('/^(.*) \((.*)\)$/', $lines[0], $m) && isset($lines[1])) {
        $pos = strrpos($lines[1], ', ');
        $alamatAwal = ['nama' => $m[1], 'no_hp' => $m[2], 'alamat' => $pos ? substr($lines[1], 0, $pos) : $lines[1], 'kota' => $pos ? substr($lines[1], $pos + 2) : ''];
    }
}
$old = [
    'nama'         => trim((string)($_POST['nama'] ?? $alamatAwal['nama'])),
    'no_hp'        => trim((string)($_POST['no_hp'] ?? $alamatAwal['no_hp'])),
    'alamat'       => trim((string)($_POST['alamat'] ?? $alamatAwal['alamat'])),
    'kota'         => trim((string)($_POST['kota'] ?? $alamatAwal['kota'])),
    'catatan'      => trim((string)($_POST['catatan'] ?? '')),
    'jumlah_bayar' => $_POST['jumlah_bayar'] ?? (int)total_transfer($pesanan),
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $bisaBayar) {
    csrf_check();
    try {
        foreach (['nama' => 'Nama penerima', 'no_hp' => 'No. HP', 'alamat' => 'Alamat', 'kota' => 'Kota'] as $f => $label) {
            if ($old[$f] === '') {
                throw new RuntimeException("$label wajib diisi.");
            }
        }
        $jumlah = (float)preg_replace('/[^0-9]/', '', (string)$old['jumlah_bayar']);
        if ($jumlah <= 0) {
            throw new RuntimeException('Nominal transfer belum diisi.');
        }
        $file = upload_image('bukti_pembayaran', 'bukti_transfer', 'bukti_' . strtolower($kode));

        $pdo->beginTransaction();
        $pdo->prepare('INSERT INTO pembayaran (id_pesanan, tanggal_pembayaran, jumlah_bayar, bukti_pembayaran, status_pembayaran, catatan) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$id, now(), $jumlah, $file, 'Sudah Upload Bukti', $old['catatan'] !== '' ? mb_substr($old['catatan'], 0, 500) : null]);
        $idPembayaran = (int)$pdo->lastInsertId();
        $alamat = $old['nama'] . ' (' . $old['no_hp'] . ")\n" . $old['alamat'] . ', ' . $old['kota'];
        $pdo->prepare('INSERT INTO pengiriman (id_pembayaran, alamat_pengiriman, ongkir) VALUES (?, ?, ?)')
            ->execute([$idPembayaran, $alamat, $totalOngkir]);
        $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Sudah Upload Bukti' WHERE id_pesanan = ?")->execute([$id]);
        $pdo->commit();
        unset($_SESSION['alamat_pesanan'][$id]);

        flash('success', 'Bukti bayar sudah diunggah. Pesanan akan diproses setelah admin menyetujui pembayaran.');
        redirect('Includes/bayar.php?id=' . $id);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'Bukti bayar gagal disimpan.';
    }
}

$pageTitle  = 'Pembayaran ' . $kode;
$activeMenu = 'pesanan';
$pageCss = 'Includes/css/bayar.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="breadcrumb-lite">
        <a href="<?= url('Includes/pesanan-saya.php') ?>">Pesanan saya</a><span>/</span>
        <a href="<?= url('Includes/pesanan-detail.php?id=' . $id) ?>"><?= h($kode) ?></a><span>/</span><span>Pembayaran</span>
    </div>
    <div class="page-heading">
        <div>
            <h1>Pembayaran <?= h($kode) ?></h1>
            <p>Dipesan <?= tgl($pesanan['tanggal_pesanan'], true) ?></p>
        </div>
        <?= badge($pesanan['status_pesanan']) ?>
    </div>

    <div class="two-col" style="grid-template-columns:minmax(0,1fr) 380px">
        <div>
            <div class="card-box">
                <div class="summary-row"><span>Total pesanan</span><strong><?= rupiah($pesanan['total_harga']) ?></strong></div>
                <div class="summary-row"><span>Kode unik</span><strong>+ <?= rupiah(kode_unik($id)) ?></strong></div>
                <div class="tagihan-line"><span>Total yang harus ditransfer</span><span><strong><?= rupiah(total_transfer($pesanan)) ?></strong>
                    <button type="button" class="copy-btn" data-copy="<?= (int)total_transfer($pesanan) ?>">Salin</button></span></div>
                <p class="kode-unik-note">Transfer <strong>tepat</strong> sampai 3 digit terakhir (<?= str_pad((string)kode_unik($id), 3, '0', STR_PAD_LEFT) ?>) supaya pembayaran Anda cepat dikenali admin.</p>
                <div class="rekening-box">
                    <div class="lbl">Transfer ke rekening <?= h(REKENING_BANK) ?></div>
                    <div class="no"><?= h(REKENING_NOMOR) ?> <button type="button" class="copy-btn" data-copy="<?= h(REKENING_NOMOR) ?>">Salin</button></div>
                    <div class="lbl">a.n. <?= h(REKENING_NAMA) ?></div>
                </div>
            </div>

            <?php if ($bisaBayar): ?>
                <form method="POST" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <?php if ($error): ?>
                        <div class="alert-box danger" style="margin-top:16px"><span>Bukti bayar belum tersimpan: <?= h($error) ?> Silakan coba lagi.</span></div>
                    <?php endif; ?>
                    <?php if ($terakhir && $terakhir['status_pembayaran'] === 'Ditolak'): ?>
                        <div class="alert-box warning" style="margin-top:16px"><span>Bukti sebelumnya ditolak admin: <?= h($terakhir['catatan'] ?: 'tanpa keterangan') ?>. Unggah bukti yang benar.</span></div>
                    <?php endif; ?>

                    <div class="card-box" style="margin-top:16px">
                        <h2>Bukti transfer</h2>
                        <div class="form-grid">
                            <div>
                                <label class="form-label-x">Nominal yang ditransfer (Rp)</label>
                                <input name="jumlah_bayar" type="number" min="1" class="input-x" value="<?= h($old['jumlah_bayar']) ?>" required>
                            </div>
                            <div>
                                <label class="form-label-x">Catatan <span style="font-weight:400;color:var(--text-muted)">(opsional)</span></label>
                                <input name="catatan" class="input-x" value="<?= h($old['catatan']) ?>" placeholder="mis. transfer dari BRI a.n. Budi">
                            </div>
                            <div class="full">
                                <label class="upload-area" for="bukti_pembayaran" data-upload="bukti_pembayaran" data-preview="#previewImg" data-placeholder="#previewPlaceholder">
                                    <i class="fas fa-cloud-arrow-up upload-area__icon"></i>
                                    <strong>Seret file bukti transfer ke sini</strong>
                                    <span class="upload-area__or">atau</span>
                                    <span class="btn-outline-brown btn-sm-x upload-area__btn" data-upload-btn><i class="fas fa-folder-open"></i> Pilih file</span>
                                    <span class="upload-area__hint" data-filename>JPG, PNG atau WEBP, maks. <?= MAKS_UPLOAD_MB ?> MB</span>
                                </label>
                                <input type="file" name="bukti_pembayaran" id="bukti_pembayaran" class="upload-input" accept=".jpg,.jpeg,.png,.webp,image/*" required>
                            </div>
                        </div>
                    </div>

                    <div class="card-box">
                        <h2>Kirim ke</h2>
                        <div class="form-grid">
                            <div><label class="form-label-x">Nama penerima</label><input class="input-x" name="nama" value="<?= h($old['nama']) ?>" required></div>
                            <div><label class="form-label-x">No. HP</label><input class="input-x" name="no_hp" value="<?= h($old['no_hp']) ?>" required></div>
                            <div class="full"><label class="form-label-x">Alamat</label><textarea class="textarea-x" name="alamat" style="min-height:70px" required><?= h($old['alamat']) ?></textarea></div>
                            <div class="full"><label class="form-label-x">Kota</label><input class="input-x" name="kota" value="<?= h($old['kota']) ?>" required></div>
                        </div>
                    </div>
                    <button class="btn-orange btn-block" style="margin-top:16px">Unggah bukti bayar</button>
                </form>
            <?php elseif ($pesanan['status_pesanan'] === 'Sudah Upload Bukti'): ?>
                <div class="card-box" style="margin-top:16px">
                    <h2>Bukti bayar sudah diunggah</h2>
                    <p style="margin:0 0 12px">Admin sedang mengecek transfer Anda. Pesanan akan dikemas setelah pembayaran disetujui. Jika ditolak, Anda bisa mengunggah bukti baru dari halaman ini.</p>
                    <a href="<?= url('Includes/pesanan-detail.php?id=' . $id) ?>" class="btn-outline-brown btn-sm-x">Lihat rincian pesanan</a>
                </div>
            <?php else: ?>
                <div class="card-box" style="margin-top:16px">
                    <p style="margin:0 0 12px">Tidak ada tagihan untuk pesanan ini. Status sekarang: <strong><?= h($pesanan['status_pesanan']) ?></strong>.</p>
                    <a href="<?= url('Includes/pesanan-detail.php?id=' . $id) ?>" class="btn-outline-brown btn-sm-x">Lihat rincian pesanan</a>
                </div>
            <?php endif; ?>
        </div>

        <aside class="sticky-col">
            <div class="bukti-side">
                <div class="bukti-side__head">
                    <h3>Bukti transfer</h3>
                    <span><?= $terakhir ? badge($terakhir['status_pembayaran']) : '' ?></span>
                </div>
                <div class="bukti-side__img">
                    <?php $src = $terakhir && !$bisaBayar ? upload_url('bukti_transfer', $terakhir['bukti_pembayaran']) : ''; ?>
                    <img id="previewImg" src="<?= $src ?>" data-zoom="<?= $src ?>" alt="Pratinjau bukti transfer" style="cursor:zoom-in;<?= $src ? '' : 'display:none' ?>">
                    <div id="previewPlaceholder" class="bukti-side__placeholder" style="<?= $src ? 'display:none' : '' ?>">Pratinjau bukti transfer muncul di sini setelah file dipilih.</div>
                </div>
                <?php if ($riwayat): ?>
                    <div class="bukti-history">
                        <div class="form-label-x" style="font-size:12px">Riwayat unggahan</div>
                        <?php foreach ($riwayat as $r): $u = upload_url('bukti_transfer', $r['bukti_pembayaran']); ?>
                            <div class="bukti-history-item" data-src="<?= $u ?>">
                                <img src="<?= $u ?>" alt="">
                                <div>
                                    <?= rupiah($r['jumlah_bayar']) ?> &middot; <?= tgl($r['tanggal_pembayaran'], true) ?>
                                    <?php if ($r['catatan']): ?><br><span style="color:<?= $r['status_pembayaran'] === 'Ditolak' ? 'var(--danger)' : 'var(--text-muted)' ?>"><?= h($r['catatan']) ?></span><?php endif; ?>
                                </div>
                                <?= badge($r['status_pembayaran']) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>

<script>
document.querySelectorAll('.bukti-history-item').forEach(function (it) {
    it.addEventListener('click', function () {
        var img = document.getElementById('previewImg');
        img.src = it.dataset.src; img.dataset.zoom = it.dataset.src; img.style.display = 'block';
        document.getElementById('previewPlaceholder').style.display = 'none';
        document.querySelectorAll('.bukti-history-item').forEach(function (x) { x.classList.toggle('active', x === it); });
    });
});
</script>

<?php require __DIR__ . '/footer.php'; ?>
