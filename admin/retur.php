<?php
/**
 * Activity & Sequence "Memproses Retur" dan "Mengkonfirmasi Barang Retur" (include: Memproses Refund):
 *   Menunggu Verifikasi --setujui--> Disetujui --(pelanggan input resi)--> Barang Retur Dikirim
 *   Barang Retur Dikirim --barang sesuai: hitungNominalRefund + upload bukti transfer--> Refund Selesai
 *   (penolakan pengajuan / barang tidak sesuai --> Ditolak)
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/retur.css';

$STATUS = ['Menunggu Verifikasi', 'Disetujui', 'Barang Retur Dikirim', 'Refund Selesai', 'Ditolak'];

const RETUR_SQL = 'SELECT r.*, ps.tanggal_pesanan, u.nama AS nama_pelanggan, u.email, u.no_telepon,
                          pp.harga_satuan, pp.ongkir_satuan, pp.jumlah AS jumlah_beli, p.nama_produk, p.foto,
                          (SELECT f.nama_file FROM foto_retur f WHERE f.id_retur = r.id_retur ORDER BY f.id_foto_retur LIMIT 1) AS foto_utama,
                          (SELECT COUNT(*) FROM foto_retur f WHERE f.id_retur = r.id_retur) AS jml_foto
                   FROM retur r
                   JOIN pesanan ps ON ps.id_pesanan = r.id_pesanan
                   JOIN `user` u ON u.id_user = ps.id_user
                   JOIN pesanan_produk pp ON pp.id_pesanan = r.id_pesanan AND pp.id_produk = r.id_produk
                   JOIN produk p ON p.id_produk = r.id_produk';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id   = (int)$_POST['id_retur'];
    $aksi = $_POST['aksi'] ?? '';
    $st = $pdo->prepare(RETUR_SQL . ' WHERE r.id_retur = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    try {
        if (!$r) {
            throw new RuntimeException('Data retur tidak ditemukan.');
        }
        $set = $pdo->prepare('UPDATE retur SET status_retur = ? WHERE id_retur = ?');
        if ($aksi === 'setujui' && $r['status_retur'] === 'Menunggu Verifikasi') {
            $set->execute(['Disetujui', $id]);
            flash('success', 'Retur disetujui. Menunggu pelanggan mengirim barang retur.');
        } elseif ($aksi === 'tolak' && $r['status_retur'] === 'Menunggu Verifikasi') {
            $set->execute(['Ditolak', $id]);
            flash('warning', 'Pengajuan retur ditolak.');
        } elseif ($aksi === 'tolak_barang' && $r['status_retur'] === 'Barang Retur Dikirim') {
            $set->execute(['Ditolak', $id]);
            flash('warning', 'Barang retur tidak sesuai. Status retur menjadi Ditolak.');
        } elseif ($aksi === 'refund' && $r['status_retur'] === 'Barang Retur Dikirim') {
            $nominal = nominal_refund($r['harga_satuan'], $r['ongkir_satuan'], (int)$r['jumlah_retur']);   // hitungNominalRefund()
            $bukti = upload_image('bukti_refund', 'refund', 'refund_' . strtolower(kode((int)$r['id_pesanan'])));
            $pdo->prepare("UPDATE retur SET status_retur = 'Refund Selesai', nominal_refund = ?, bukti_refund = ? WHERE id_retur = ?")
                ->execute([$nominal, $bukti, $id]);
            flash('success', 'Refund ' . rupiah($nominal) . ' tercatat. Status retur: Refund Selesai.');
        } else {
            throw new RuntimeException('Aksi tidak sesuai dengan status retur saat ini.');
        }
    } catch (RuntimeException $e) {
        flash('danger', $e->getMessage());
    }
    redirect('admin/retur.php?id=' . $id);
}

/* ============================== DETAIL ============================== */
$id = (int)($_GET['id'] ?? 0);
if ($id) {
    $st = $pdo->prepare(RETUR_SQL . ' WHERE r.id_retur = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    if (!$r) {
        flash('warning', 'Data retur tidak ditemukan.');
        redirect('admin/retur.php');
    }
    $kode = kode((int)$r['id_pesanan']);
    $nominal = nominal_refund($r['harga_satuan'], $r['ongkir_satuan'], (int)$r['jumlah_retur']);
    $st = $pdo->prepare('SELECT * FROM foto_retur WHERE id_retur = ? ORDER BY id_foto_retur');
    $st->execute([$id]);
    $fotos = $st->fetchAll();
    $foto = upload_url('retur', $fotos[0]['nama_file'] ?? null);

    admin_header('Retur #' . $id, 'retur');
    page_header('Retur #' . $id . ' - ' . $kode, 'Diajukan ' . tgl($r['tanggal_retur'], true) . ' oleh ' . $r['nama_pelanggan'],
        '<div style="display:flex;gap:8px;align-items:center">' . badge($r['status_retur']) . '<a href="' . url('admin/retur.php') . '" class="btn-a outline">Kembali</a></div>');
    ?>
    <div class="pay-layout" style="grid-template-columns:minmax(0,1fr) 400px">
        <div>
            <div class="panel">
                <div class="panel-head"><h2>Detail pengajuan</h2></div>
                <div class="panel-body">
                    <div class="detail-grid">
                        <dl class="info-list" style="grid-template-columns:120px 1fr">
                            <dt>Pelanggan</dt><dd><?= h($r['nama_pelanggan']) ?><span class="cell-sub"><?= h($r['email']) ?> &middot; <?= h($r['no_telepon']) ?></span></dd>
                            <dt>Pesanan</dt><dd><a href="<?= url('admin/pesanan.php?id=' . (int)$r['id_pesanan']) ?>"><?= h($kode) ?></a><span class="cell-sub"><?= tgl($r['tanggal_pesanan']) ?></span></dd>
                            <dt>Produk</dt><dd><?= h($r['nama_produk']) ?></dd>
                            <dt>Jumlah retur</dt><dd><?= (int)$r['jumlah_retur'] ?> dari <?= (int)$r['jumlah_beli'] ?> pcs &times; <?= rupiah($r['harga_satuan']) ?></dd>
                        </dl>
                        <dl class="info-list" style="grid-template-columns:120px 1fr">
                            <dt>Rekening refund</dt><dd><?= h($r['rekening_refund']) ?></dd>
                            <dt>Ekspedisi retur</dt><dd><?= h($r['ekspedisi_retur'] ?: '-') ?></dd>
                            <dt>Resi retur</dt><dd><?= h($r['no_resi_retur'] ?: '-') ?></dd>
                            <dt>Nominal refund</dt><dd><?= $r['nominal_refund'] !== null ? rupiah($r['nominal_refund']) : '-' ?></dd>
                        </dl>
                    </div>
                    <div style="margin-top:14px;padding:12px 14px;background:#fff;border:1px solid var(--border-light);border-radius:10px">
                        <div class="label-a">Alasan retur</div><?= nl2br(h($r['alasan_retur'])) ?>
                    </div>
                    <?php if ($r['status_retur'] === 'Refund Selesai'): ?>
                        <div class="alert-box success" style="margin-top:12px">                            <span>Refund <strong><?= rupiah($r['nominal_refund']) ?></strong> telah ditransfer ke <?= h($r['rekening_refund']) ?>.</span>
                            <a href="#" data-zoom="<?= upload_url('refund', $r['bukti_refund']) ?>" class="btn-a outline sm">Bukti refund</a></div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($r['status_retur'] === 'Menunggu Verifikasi'): ?>
                <div class="panel">
                    <div class="panel-head"><div><h2>Verifikasi retur</h2><p>Cek alasan dan foto yang dikirim pelanggan.</p></div></div>
                    <div class="panel-body" style="display:flex;gap:10px;flex-wrap:wrap">
                        <form method="POST" data-confirm="Setujui pengajuan retur ini?">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="setujui"><input type="hidden" name="id_retur" value="<?= $id ?>">
                            <button class="btn-a success">Setujui retur</button>
                        </form>
                        <form method="POST" data-confirm="Tolak pengajuan retur ini?">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="tolak"><input type="hidden" name="id_retur" value="<?= $id ?>">
                            <button class="btn-a danger outline">Tolak retur</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($r['status_retur'] === 'Barang Retur Dikirim'): ?>
                <div class="panel">
                    <div class="panel-head"><div><h2>Barang retur sudah sampai?</h2><p>Cek kondisi dan jumlah beras, lalu transfer refund ke rekening pelanggan.</p></div></div>
                    <div class="panel-body">
                        <div class="compare-box" style="margin-top:0">
                            <div><span>Nominal refund: <?= (int)$r['jumlah_retur'] ?> &times; (<?= rupiah($r['harga_satuan']) ?> + 2 &times; ongkir <?= rupiah($r['ongkir_satuan']) ?>)</span><strong><?= rupiah($nominal) ?></strong></div>
                            <div><span>Transfer ke</span><strong style="font-size:13px"><?= h($r['rekening_refund']) ?></strong></div>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="refund"><input type="hidden" name="id_retur" value="<?= $id ?>">
                            <label class="label-a">Bukti transfer refund <span class="req">*</span></label>
                            <input type="file" name="bukti_refund" class="input-a" style="padding-top:8px;max-width:420px" accept=".jpg,.jpeg,.png,.webp,image/*" required>
                            <div><button class="btn-a success" style="margin-top:12px">Barang sesuai, refund selesai</button></div>
                        </form>
                        <form method="POST" style="margin-top:18px;padding-top:14px;border-top:1px dashed var(--border)" data-confirm="Barang retur tidak sesuai? Retur akan ditolak.">
                            <?= csrf_field() ?><input type="hidden" name="aksi" value="tolak_barang"><input type="hidden" name="id_retur" value="<?= $id ?>">
                            <button class="btn-a danger outline">Barang tidak sesuai, tolak retur</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($r['status_retur'] === 'Disetujui'): ?>
                <div class="alert-box info"><span>Menunggu pelanggan mengirim barang retur dan menginput nomor resi.</span></div>
            <?php endif; ?>
        </div>

        <aside class="bukti-panel">
            <div class="bukti-panel__head"><h3>Foto barang retur</h3><span style="font-size:12px;color:var(--text-muted)"><?= count($fotos) ?> foto</span></div>
            <div class="bukti-stage"><img id="fotoBesar" src="<?= $foto ?>" data-zoom="<?= $foto ?>" alt="Foto retur"></div>
            <?php if (count($fotos) > 1): ?>
                <div class="foto-grid">
                    <?php foreach ($fotos as $i => $f): $u = upload_url('retur', $f['nama_file']); ?>
                        <img src="<?= $u ?>" class="<?= $i === 0 ? 'current' : '' ?>" alt="" title="Diunggah <?= h(tgl($f['tanggal_upload'], true)) ?>"
                             onclick="var b=document.getElementById('fotoBesar');b.src=this.src;b.dataset.zoom=this.src;this.parentNode.querySelectorAll('img').forEach(function(x){x.classList.remove('current')});this.classList.add('current')">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="bukti-info"><div style="display:flex;gap:10px;align-items:center"><img src="<?= upload_url('produk', $r['foto']) ?>" style="width:46px;height:46px;object-fit:cover" alt=""><div><strong><?= h($r['nama_produk']) ?></strong><span class="cell-sub">foto produk untuk pembanding</span></div></div></div>
        </aside>
    </div>
    <?php
    admin_footer();
    exit;
}

/* ============================== DAFTAR ============================== */
$tab = in_array($_GET['status'] ?? '', $STATUS, true) ? $_GET['status'] : '';
$st = $pdo->prepare(RETUR_SQL . ($tab ? ' WHERE r.status_retur = ?' : '') . " ORDER BY CASE r.status_retur WHEN 'Menunggu Verifikasi' THEN 0 WHEN 'Barang Retur Dikirim' THEN 1 ELSE 2 END, r.tanggal_retur DESC");
$st->execute($tab ? [$tab] : []);
$rows = $st->fetchAll();
$counts = [];
foreach ($pdo->query('SELECT status_retur s, COUNT(*) c FROM retur GROUP BY status_retur') as $c) {
    $counts[$c['s']] = (int)$c['c'];
}

admin_header('Kelola Retur', 'retur');
page_header('Retur', 'Pengajuan retur dari pelanggan, barang retur yang dikirim balik, dan refund.');
?>
<div class="tabs-a">
    <a href="<?= url('admin/retur.php') ?>" class="<?= $tab === '' ? 'active' : '' ?>">Semua <small><?= array_sum($counts) ?></small></a>
    <?php foreach ($STATUS as $s): ?>
        <a href="<?= url('admin/retur.php?status=' . urlencode($s)) ?>" class="<?= $tab === $s ? 'active' : '' ?>"><?= h($s) ?> <?php if (!empty($counts[$s])): ?><small><?= $counts[$s] ?></small><?php endif; ?></a>
    <?php endforeach; ?>
</div>
<div class="panel">
    <div class="table-wrap">
        <table class="table-admin">
            <thead><tr><th></th><th>Retur</th><th>Pelanggan</th><th>Produk</th><th>Resi Retur</th><th class="num">Nilai</th><th>Status</th><th class="num">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?><tr><td colspan="8"><div class="empty-a">Belum ada data retur.</div></td></tr><?php endif; ?>
            <?php foreach ($rows as $r): $u = upload_url('retur', $r['foto_utama']); ?>
                <tr>
                    <td><img src="<?= $u ?>" data-zoom="<?= $u ?>" class="thumb" style="cursor:zoom-in" alt=""><span class="cell-sub"><?= (int)$r['jml_foto'] ?> foto</span></td>
                    <td><strong>#<?= (int)$r['id_retur'] ?> &middot; <?= h(kode((int)$r['id_pesanan'])) ?></strong><span class="cell-sub"><?= tgl($r['tanggal_retur'], true) ?></span></td>
                    <td><?= h($r['nama_pelanggan']) ?></td>
                    <td><?= h($r['nama_produk']) ?><span class="cell-sub"><?= (int)$r['jumlah_retur'] ?> pcs &middot; <?= h(mb_strimwidth($r['alasan_retur'], 0, 50, '...')) ?></span></td>
                    <td><?= h($r['no_resi_retur'] ?: '-') ?><span class="cell-sub"><?= h($r['ekspedisi_retur'] ?? '') ?></span></td>
                    <td class="num"><?= rupiah($r['nominal_refund'] ?? nominal_refund($r['harga_satuan'], $r['ongkir_satuan'], (int)$r['jumlah_retur'])) ?></td>
                    <td><?= badge($r['status_retur']) ?></td>
                    <td class="num"><a href="<?= url('admin/retur.php?id=' . (int)$r['id_retur']) ?>" class="btn-a <?= in_array($r['status_retur'], ['Menunggu Verifikasi', 'Barang Retur Dikirim'], true) ? '' : 'outline' ?> sm">Proses</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php admin_footer(); ?>
