<?php
/**
 * Activity & Sequence "Mengirim Barang Retur" (include: Menginput Resi Retur)
 *   retur Disetujui + alamat toko -> isi ekspedisi & no. resi -> validasiData
 *   -> updateResiRetur -> status "Barang Retur Dikirim"
 */
require_once __DIR__ . '/../config/koneksi.php';
require_pelanggan();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'resi') {
    csrf_check();
    $idRetur   = (int)$_POST['id_retur'];
    $ekspedisi = trim((string)($_POST['ekspedisi_retur'] ?? ''));
    $resi      = strtoupper(trim((string)($_POST['no_resi_retur'] ?? '')));
    if ($ekspedisi === '' || mb_strlen($ekspedisi) > 50 || !preg_match('/^[A-Z0-9-]{6,40}$/', $resi)) {
        flash('danger', 'Data belum lengkap. Isi nama ekspedisi dan nomor resi (6-40 huruf/angka).');
    } else {
        $stmt = $pdo->prepare("UPDATE retur SET ekspedisi_retur = ?, no_resi_retur = ?, status_retur = 'Barang Retur Dikirim'
                               WHERE id_retur = ? AND status_retur = 'Disetujui'
                                 AND id_pesanan IN (SELECT id_pesanan FROM pesanan WHERE id_user = ?)");
        $stmt->execute([$ekspedisi, $resi, $idRetur, user_id()]);
        flash($stmt->rowCount() ? 'success' : 'danger', $stmt->rowCount() ? 'Resi retur tersimpan. Admin akan mengecek barang setelah sampai di toko.' : 'Retur tidak bisa diperbarui.');
    }
    redirect('Includes/retur-saya.php');
}

$stmt = $pdo->prepare('SELECT r.*, pp.harga_satuan, pp.jumlah AS jumlah_beli, p.nama_produk, p.foto
                       FROM retur r
                       JOIN pesanan ps ON ps.id_pesanan = r.id_pesanan
                       JOIN pesanan_produk pp ON pp.id_pesanan = r.id_pesanan AND pp.id_produk = r.id_produk
                       JOIN produk p ON p.id_produk = r.id_produk
                       WHERE ps.id_user = ? ORDER BY r.tanggal_retur DESC');
$stmt->execute([user_id()]);
$returList = $stmt->fetchAll();
$fotoStmt = $pdo->prepare('SELECT nama_file FROM foto_retur WHERE id_retur = ? ORDER BY id_foto_retur');
$kurirList = $pdo->query('SELECT nama_kurir FROM jasa_kurir ORDER BY nama_kurir')->fetchAll(PDO::FETCH_COLUMN);

$pageTitle  = 'Retur Saya';
$activeMenu = 'retur';
$pageCss = 'Includes/css/retur-saya.css';
require __DIR__ . '/header.php';
?>

<div class="page-wrap">
    <div class="page-heading">
        <div><h1>Retur saya</h1><p>Retur diajukan dari pesanan yang sudah selesai.</p></div>
        <a href="<?= url('Includes/pesanan-saya.php?status=Selesai') ?>" class="link-more">Pesanan selesai</a>
    </div>

    <?php if (!$returList): ?>
        <div class="card-box empty-state">
            <h3>Belum ada retur</h3>
            <p>Kalau ada beras yang rusak atau tidak sesuai, ajukan retur dari halaman rincian pesanan.</p>
        </div>
    <?php endif; ?>

    <datalist id="daftarEkspedisi"><?php foreach ($kurirList as $k): ?><option value="<?= h($k) ?>"><?php endforeach; ?></datalist>

    <?php foreach ($returList as $r):
        $fotoStmt->execute([$r['id_retur']]);
        $fotos = $fotoStmt->fetchAll(PDO::FETCH_COLUMN); ?>
        <div class="order-card">
            <div class="order-header">
                <div><span class="order-number">Retur #<?= (int)$r['id_retur'] ?></span> <span class="order-date">&middot; <?= h(kode((int)$r['id_pesanan'])) ?> &middot; <?= tgl($r['tanggal_retur'], true) ?></span></div>
                <?= badge($r['status_retur']) ?>
            </div>
            <div class="order-body" style="grid-template-columns:1fr">
                <div>
                    <div class="mini-item"><img src="<?= upload_url('produk', $r['foto']) ?>" alt=""><div><strong><?= h($r['nama_produk']) ?></strong><small><?= (int)$r['jumlah_retur'] ?> dari <?= (int)$r['jumlah_beli'] ?> pcs x <?= rupiah($r['harga_satuan']) ?></small></div></div>
                    <p style="font-size:13px;margin:8px 0 2px">Alasan: <?= h($r['alasan_retur']) ?></p>
                    <p style="font-size:12px;color:var(--text-secondary);margin:0 0 8px">Refund ke <?= h($r['rekening_refund']) ?></p>
                    <div class="thumbs">
                        <?php foreach ($fotos as $f): $u = upload_url('retur', $f); ?><img src="<?= $u ?>" data-zoom="<?= $u ?>" alt="Foto retur"><?php endforeach; ?>
                    </div>

                    <?php if ($r['status_retur'] === 'Ditolak'): ?>
                        <div class="alert-box danger mt-3"><span>Retur tidak disetujui. Silakan hubungi kami lewat WhatsApp kalau ada pertanyaan.</span></div>
                    <?php elseif ($r['status_retur'] === 'Menunggu Verifikasi'): ?>
                        <div class="alert-box info mt-3"><span>Pengajuan sedang dicek admin.</span></div>
                    <?php elseif ($r['status_retur'] === 'Disetujui'): ?>
                        <div class="alert-box success mt-3"><span>Retur disetujui. Kirim berasnya ke <?= h(TOKO_NAMA) ?>, <?= h(TOKO_ALAMAT) ?> (<?= h(TOKO_TELEPON) ?>), lalu isi nomor resinya di bawah.</span></div>
                        <form method="POST" class="form-grid" style="grid-template-columns:1fr 1fr auto;align-items:end">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="resi"><input type="hidden" name="id_retur" value="<?= (int)$r['id_retur'] ?>">
                            <div><label class="form-label-x">Ekspedisi</label><input name="ekspedisi_retur" class="input-x" list="daftarEkspedisi" maxlength="50" placeholder="mis. JNE" required></div>
                            <div><label class="form-label-x">Nomor resi retur</label><input name="no_resi_retur" class="input-x" required></div>
                            <button class="btn-orange">Simpan</button>
                        </form>
                    <?php elseif ($r['status_retur'] === 'Barang Retur Dikirim'): ?>
                        <div class="resi-box mt-3"><div><small style="color:var(--text-secondary)"><?= h($r['ekspedisi_retur']) ?>, resi retur</small><br><strong><?= h($r['no_resi_retur']) ?></strong></div><span style="font-size:12px;color:var(--text-secondary)">Menunggu barang sampai di toko</span></div>
                    <?php elseif ($r['status_retur'] === 'Refund Selesai'): ?>
                        <div class="alert-box success mt-3"><span>Refund <?= rupiah($r['nominal_refund']) ?> sudah ditransfer.
                            <?php if ($r['bukti_refund']): ?><a href="#" data-zoom="<?= upload_url('refund', $r['bukti_refund']) ?>" class="link-action">Lihat bukti transfer</a><?php endif; ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/footer.php'; ?>
