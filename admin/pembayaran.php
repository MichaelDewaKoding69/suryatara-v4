<?php
/**
 * Activity "Konfirmasi Pembayaran" & Sequence "Mengkonfirmasi Pembayaran":
 *   getPembayaranByStatus('Sudah Upload Bukti') -> pilihPembayaran -> tampilkanBuktiBayar
 *   [valid]   updateStatusPembayaran('Disetujui') + updateStatusPesanan('Diproses')
 *             -> pesanan baru muncul di menu Proses Pesanan
 *   [invalid] isiAlasan -> updateStatusPembayaran('Ditolak') + updateStatusPesanan('Belum Melakukan Pembayaran')
 *
 * Tata letak: FILTER (kiri)  |  DAFTAR PEMBAYARAN (tengah)  |  BUKTI TRANSFER (panel samping kanan)
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/pembayaran.css';

$STATUS = ['Sudah Upload Bukti', 'Disetujui', 'Ditolak'];

/* ============================== AKSI ============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int)($_POST['id_pembayaran'] ?? 0);
    $aksi   = $_POST['aksi'] ?? '';
    $back   = (string)($_POST['back'] ?? '');
    $st = $pdo->prepare('SELECT pb.* FROM pembayaran pb WHERE pb.id_pembayaran = ?');
    $st->execute([$id]);
    $pb = $st->fetch();

    if (!$pb || $pb['status_pembayaran'] !== 'Sudah Upload Bukti') {
        flash('warning', 'Pembayaran tidak ditemukan atau sudah diproses sebelumnya.');
    } elseif ($aksi === 'verifikasi') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'Disetujui' WHERE id_pembayaran = ?")->execute([$id]);
        $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Diproses' WHERE id_pesanan = ?")->execute([$pb['id_pesanan']]);
        $pdo->commit();
        flash('success', 'Pembayaran ' . kode((int)$pb['id_pesanan']) . ' disetujui. Pesanan sekarang ada di menu Proses Pesanan.');
    } elseif ($aksi === 'tolak') {
        $alasan = trim((string)($_POST['alasan'] ?? ''));
        if ($alasan === '') {
            flash('danger', 'Alasan penolakan wajib diisi.');
        } else {
            $pdo->beginTransaction();
            // alasan penolakan disimpan di kolom catatan
            $pdo->prepare("UPDATE pembayaran SET status_pembayaran = 'Ditolak', catatan = ? WHERE id_pembayaran = ?")
                ->execute([mb_substr($alasan, 0, 500), $id]);
            $pdo->prepare("UPDATE pesanan SET status_pesanan = 'Belum Melakukan Pembayaran' WHERE id_pesanan = ?")->execute([$pb['id_pesanan']]);
            $pdo->commit();
            flash('warning', 'Pembayaran ' . kode((int)$pb['id_pesanan']) . ' ditolak. Status pesanan kembali ke Belum Melakukan Pembayaran.');
        }
    }
    parse_str(ltrim($back, '?'), $qs);
    $qs['sel'] = $id;
    redirect('admin/pembayaran.php?' . http_build_query($qs));
}

/* ============================== FILTER ============================== */
$f = [
    'status' => in_array($_GET['status'] ?? '', $STATUS, true) ? $_GET['status'] : '',
    'q'      => trim((string)($_GET['q'] ?? '')),
    'min'    => ctype_digit((string)($_GET['min'] ?? '')) ? (int)$_GET['min'] : '',
    'max'    => ctype_digit((string)($_GET['max'] ?? '')) ? (int)$_GET['max'] : '',
    'dari'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['dari'] ?? '') ? $_GET['dari'] : '',
    'sampai' => preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['sampai'] ?? '') ? $_GET['sampai'] : '',
    'cocok'  => in_array($_GET['cocok'] ?? '', ['sesuai', 'tidak'], true) ? $_GET['cocok'] : '',
    'urut'   => $_GET['urut'] ?? 'prioritas',
];

$where = [];
$params = [];
if ($f['status'] !== '') {
    $where[] = 'pb.status_pembayaran = :status';
    $params[':status'] = $f['status'];
}
if ($f['q'] !== '') {
    // kode RB-0007 diturunkan dari id_pesanan
    $idCari = preg_match('/^(?:RB-?)?0*(\d+)$/i', $f['q'], $mm) ? (int)$mm[1] : -1;
    $where[] = '(ps.id_pesanan = :qid OR u.nama LIKE :q2 OR u.email LIKE :q3 OR pb.catatan LIKE :q4)';
    $params[':qid'] = $idCari;
    $params[':q2'] = $params[':q3'] = $params[':q4'] = '%' . $f['q'] . '%';
}
if ($f['min'] !== '') {
    $where[] = 'pb.jumlah_bayar >= :min';
    $params[':min'] = $f['min'];
}
if ($f['max'] !== '') {
    $where[] = 'pb.jumlah_bayar <= :max';
    $params[':max'] = $f['max'];
}
if ($f['dari'] !== '') {
    $where[] = 'pb.tanggal_pembayaran >= :dari';
    $params[':dari'] = $f['dari'] . ' 00:00:00';
}
if ($f['sampai'] !== '') {
    $where[] = 'pb.tanggal_pembayaran <= :sampai';
    $params[':sampai'] = $f['sampai'] . ' 23:59:59';
}
if ($f['cocok'] === 'sesuai') {
    $where[] = 'pb.jumlah_bayar = ps.total_harga';
} elseif ($f['cocok'] === 'tidak') {
    $where[] = 'pb.jumlah_bayar <> ps.total_harga';
}

$orderBy = [
    'prioritas' => "CASE pb.status_pembayaran WHEN 'Sudah Upload Bukti' THEN 0 WHEN 'Ditolak' THEN 2 ELSE 1 END, pb.tanggal_pembayaran DESC",
    'terbaru'   => 'pb.tanggal_pembayaran DESC',
    'terlama'   => 'pb.tanggal_pembayaran ASC',
    'tertinggi' => 'pb.jumlah_bayar DESC',
    'terendah'  => 'pb.jumlah_bayar ASC',
][$f['urut']] ?? 'pb.tanggal_pembayaran DESC';

$sql = 'SELECT pb.*, ps.total_harga, ps.status_pesanan, ps.tanggal_pesanan,
               u.nama AS nama_pelanggan, u.email, u.no_telepon, pg.alamat_pengiriman
        FROM pembayaran pb
        JOIN pesanan ps ON ps.id_pesanan = pb.id_pesanan
        JOIN `user` u ON u.id_user = ps.id_user
        LEFT JOIN pengiriman pg ON pg.id_pembayaran = pb.id_pembayaran'
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . " ORDER BY $orderBy";
$st = $pdo->prepare($sql);
$st->execute($params);
$list = $st->fetchAll();

// hitungan untuk sidebar filter
$countStatus = array_fill_keys($STATUS, 0);
foreach ($pdo->query('SELECT status_pembayaran s, COUNT(*) c FROM pembayaran GROUP BY status_pembayaran') as $r) {
    $countStatus[$r['s']] = (int)$r['c'];
}
$pendingTotal = (float)$pdo->query("SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE status_pembayaran = 'Sudah Upload Bukti'")->fetchColumn();
$verifiedTotal = (float)$pdo->query("SELECT COALESCE(SUM(jumlah_bayar),0) FROM pembayaran WHERE status_pembayaran = 'Disetujui'")->fetchColumn();

// riwayat pembayaran per pesanan (untuk panel samping)
$history = [];
if ($list) {
    $ids = array_unique(array_column($list, 'id_pesanan'));
    $st = $pdo->prepare('SELECT id_pembayaran, id_pesanan, tanggal_pembayaran, jumlah_bayar, bukti_pembayaran, status_pembayaran FROM pembayaran
                         WHERE id_pesanan IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY tanggal_pembayaran DESC');
    $st->execute(array_values($ids));
    foreach ($st->fetchAll() as $r) {
        $history[$r['id_pesanan']][] = [
            'id' => (int)$r['id_pembayaran'], 'img' => upload_url('bukti_transfer', $r['bukti_pembayaran']),
            'status' => $r['status_pembayaran'], 'tgl' => tgl($r['tanggal_pembayaran'], true), 'jumlah' => rupiah($r['jumlah_bayar']),
        ];
    }
}

$payload = [];
foreach ($list as $p) {
    $selisih = (float)$p['jumlah_bayar'] - (float)$p['total_harga'];
    $payload[(int)$p['id_pembayaran']] = [
        'id' => (int)$p['id_pembayaran'], 'kode' => kode((int)$p['id_pesanan']), 'id_pesanan' => (int)$p['id_pesanan'],
        'img' => upload_url('bukti_transfer', $p['bukti_pembayaran']), 'file' => $p['bukti_pembayaran'],
        'pelanggan' => $p['nama_pelanggan'], 'email' => $p['email'], 'hp' => $p['no_telepon'],
        'tgl' => tgl($p['tanggal_pembayaran'], true), 'tgl_pesan' => tgl($p['tanggal_pesanan'], true),
        'jumlah' => rupiah($p['jumlah_bayar']), 'tagihan' => rupiah($p['total_harga']),
        'selisih' => $selisih, 'selisih_txt' => ($selisih > 0 ? '+' : ($selisih < 0 ? '-' : '')) . rupiah(abs($selisih)),
        'status' => $p['status_pembayaran'], 'status_class' => status_class($p['status_pembayaran']),
        'status_pesanan' => $p['status_pesanan'], 'catatan' => $p['catatan'], 'alamat' => $p['alamat_pengiriman'],
        'history' => $history[$p['id_pesanan']] ?? [],
    ];
}
$sel = (int)($_GET['sel'] ?? 0);
if (!isset($payload[$sel])) {
    $sel = $list ? (int)$list[0]['id_pembayaran'] : 0;
}

$qsBase = $_GET;
unset($qsBase['sel']);
function filter_url(array $over): string
{
    global $qsBase;
    $q = array_merge($qsBase, $over);
    $q = array_filter($q, fn($v) => $v !== '' && $v !== null && $v !== []);
    return url('admin/pembayaran.php' . ($q ? '?' . http_build_query($q) : ''));
}
$statusLabel = ['Sudah Upload Bukti' => 'Belum dicek', 'Disetujui' => 'Disetujui', 'Ditolak' => 'Ditolak'];

admin_header('Konfirmasi Pembayaran', 'pembayaran');
page_header('Konfirmasi Pembayaran',
    $countStatus['Sudah Upload Bukti'] . ' bukti bayar belum dicek (' . rupiah($pendingTotal) . '). Pesanan baru masuk ke Proses Pesanan setelah pembayarannya disetujui.');
?>

<div class="pay-layout">
    <!-- ======================= FILTER (KIRI) ======================= -->
    <form method="GET" class="pay-filter" id="payFilter">
        <div class="fgroup">
            <h3>Status</h3>
            <a href="<?= filter_url(['status' => '']) ?>" class="status-chip <?= $f['status'] === '' ? 'active' : '' ?>"><span>Semua</span><span class="count"><?= array_sum($countStatus) ?></span></a>
            <?php foreach ($STATUS as $s): ?>
                <a href="<?= filter_url(['status' => $s]) ?>" class="status-chip <?= $f['status'] === $s ? 'active' : '' ?>">
                    <span><?= h($statusLabel[$s]) ?></span><span class="count"><?= $countStatus[$s] ?></span>
                </a>
            <?php endforeach; ?>
            <input type="hidden" name="status" value="<?= h($f['status']) ?>">
            <button type="button" class="btn-a outline sm filter-more-btn" onclick="this.closest('form').classList.toggle('open')">Filter lainnya</button>
        </div>
        <div class="fgroup more">
            <h3>Cari</h3>
            <input name="q" value="<?= h($f['q']) ?>" class="input-a" placeholder="Kode (RB-0007), pelanggan, catatan...">
        </div>
        <div class="fgroup more">
            <h3>Tanggal transfer</h3>
            <label class="label-a" style="font-weight:500">Dari</label>
            <input type="date" name="dari" value="<?= h($f['dari']) ?>" class="input-a" style="margin-bottom:8px">
            <label class="label-a" style="font-weight:500">Sampai</label>
            <input type="date" name="sampai" value="<?= h($f['sampai']) ?>" class="input-a">
            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                <a class="btn-a outline sm" href="<?= filter_url(['dari' => date('Y-m-d'), 'sampai' => date('Y-m-d')]) ?>">Hari ini</a>
                <a class="btn-a outline sm" href="<?= filter_url(['dari' => date('Y-m-d', strtotime('-6 days')), 'sampai' => date('Y-m-d')]) ?>">7 hari</a>
            </div>
        </div>
        <div class="fgroup more">
            <h3>Nominal transfer (Rp)</h3>
            <div style="display:flex;gap:6px">
                <input type="number" name="min" min="0" step="1000" value="<?= h($f['min']) ?>" class="input-a" placeholder="Min">
                <input type="number" name="max" min="0" step="1000" value="<?= h($f['max']) ?>" class="input-a" placeholder="Maks">
            </div>
            <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                <a class="btn-a outline sm" href="<?= filter_url(['min' => '', 'max' => 200000]) ?>">&le; 200rb</a>
                <a class="btn-a outline sm" href="<?= filter_url(['min' => 200000, 'max' => '']) ?>">&gt; 200rb</a>
            </div>
        </div>
        <div class="fgroup more">
            <h3>Nominal vs tagihan</h3>
            <?php foreach (['' => 'Semua', 'sesuai' => 'Sesuai tagihan', 'tidak' => 'Tidak sesuai'] as $k => $v): ?>
                <label class="check-a"><input type="radio" name="cocok" value="<?= $k ?>" <?= $f['cocok'] === $k ? 'checked' : '' ?> data-autosubmit> <?= $v ?></label>
            <?php endforeach; ?>
        </div>
        <div class="fgroup more">
            <h3>Urutkan</h3>
            <select name="urut" class="select-a" data-autosubmit>
                <?php foreach (['prioritas' => 'Prioritas (menunggu dulu)', 'terbaru' => 'Terbaru', 'terlama' => 'Terlama', 'tertinggi' => 'Nominal tertinggi', 'terendah' => 'Nominal terendah'] as $k => $v): ?>
                    <option value="<?= $k ?>" <?= $f['urut'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="fgroup-actions more" style="display:flex;gap:6px">
            <button class="btn-a" style="flex:1">Terapkan</button>
            <a href="<?= url('admin/pembayaran.php') ?>" class="btn-a outline">Reset</a>
        </div>
    </form>

    <!-- ======================= DAFTAR (TENGAH) ======================= -->
    <section>
        <div class="pay-toolbar">
            <div class="muted"><strong style="color:var(--text)"><?= count($list) ?></strong> pembayaran ditampilkan<?= $f['status'] ? ' &middot; ' . h($f['status']) : '' ?></div>
            <div class="view-toggle" role="group" aria-label="Tampilan">
                <button type="button" data-view="list" title="Daftar">Daftar</button>
                <button type="button" data-view="gallery" title="Galeri bukti transfer">Galeri</button>
            </div>
        </div>
        <?php if (!$list): ?>
            <div class="panel"><div class="empty-a">Tidak ada pembayaran yang sesuai filter.</div></div>
        <?php else: ?>
            <div class="pay-list" id="payList">
                <?php foreach ($list as $p): $d = $payload[(int)$p['id_pembayaran']]; ?>
                    <div class="pay-item <?= $d['id'] === $sel ? 'active' : '' ?>" data-id="<?= $d['id'] ?>" tabindex="0">
                        <img src="<?= $d['img'] ?>" alt="Bukti <?= h($d['kode']) ?>" loading="lazy">
                        <div style="min-width:0">
                            <div class="t1"><?= h($d['kode']) ?> <span style="font-weight:500;color:var(--text-secondary)">&middot; #<?= $d['id'] ?></span></div>
                            <div class="t2"><?= h($d['pelanggan']) ?><?php if ($d['catatan'] && $d['status'] !== 'Ditolak'): ?> &nbsp; <?= h(mb_strimwidth($d['catatan'], 0, 40, '...')) ?><?php endif; ?></div>
                            <div class="t2"><?= $d['tgl'] ?></div>
                        </div>
                        <div class="right">
                            <span class="amount"><?= $d['jumlah'] ?></span>
                            <?php if ($d['selisih'] == 0): ?><span class="match ok">Sesuai</span>
                            <?php else: ?><span class="match bad">Selisih <?= $d['selisih_txt'] ?></span><?php endif; ?>
                            <?= badge($d['status']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- ======================= BUKTI TRANSFER (PANEL SAMPING) ======================= -->
    <aside class="bukti-panel" id="buktiPanel" <?= $list ? '' : 'style="display:none"' ?>>
        <div class="bukti-panel__head">
            <h3>Bukti transfer <span id="bpKode"></span></h3>
            <span id="bpBadge"></span>
        </div>
        <div class="bukti-panel__scroll">
            <div class="bukti-stage">
                <img id="bpImg" src="" alt="Bukti transfer">
                <div class="bukti-tools">
                    <button type="button" id="bpRotate" title="Putar">Putar</button>
                    <button type="button" id="bpZoom" title="Perbesar">Perbesar</button>
                    <a id="bpOpen" href="#" target="_blank" title="Buka di tab baru">Buka</a>
                    <a id="bpDownload" href="#" download title="Unduh">Unduh</a>
                </div>
            </div>
            <div class="bukti-info">
                <dl>
                    <dt>Pelanggan</dt><dd id="bpPelanggan"></dd>
                    <dt>Waktu transfer</dt><dd id="bpTgl"></dd>
                    <dt>Tanggal pesan</dt><dd id="bpTglPesan"></dd>
                    <dt>Status pesanan</dt><dd id="bpStatusPesanan"></dd>
                    <dt>Catatan</dt><dd id="bpCatatan"></dd>
                    <dt>Alamat kirim</dt><dd id="bpAlamat"></dd>
                </dl>
                <div id="bpAlasan" class="alert-box danger" style="margin-top:12px;display:none"><span></span></div>
                <div id="bpHistoryWrap" style="margin-top:12px">
                    <div class="label-a">Riwayat bukti pesanan ini</div>
                    <div class="mini-history" id="bpHistory"></div>
                </div>
                <a id="bpPesananLink" href="#" class="btn-a outline sm" style="margin-top:12px">Buka di Proses Pesanan</a>
            </div>
        </div>
        <div class="bukti-actions" id="bpActions">
            <form method="POST" id="formVerif" data-confirm="Verifikasi pembayaran ini? Pesanan akan berubah menjadi Diproses.">
                <?= csrf_field() ?>
                <input type="hidden" name="aksi" value="verifikasi">
                <input type="hidden" name="id_pembayaran" class="bpId">
                <input type="hidden" name="back" value="<?= h(http_build_query($qsBase)) ?>">
                <button class="btn-a success block">Setujui pembayaran</button>
            </form>
            <button type="button" class="btn-a danger outline block" style="margin-top:8px" data-modal-open="modalTolak">Tolak</button>
        </div>
    </aside>
</div>

<div class="modal-x" id="modalTolak">
    <div class="modal-x__box">
        <h3>Tolak pembayaran</h3>
        <p>Status pesanan kembali ke <strong>Belum Melakukan Pembayaran</strong> dan pelanggan bisa mengunggah bukti baru.</p>
        <form method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="aksi" value="tolak">
            <input type="hidden" name="id_pembayaran" class="bpId">
            <input type="hidden" name="back" value="<?= h(http_build_query($qsBase)) ?>">
            <label class="label-a">Alasan penolakan <span class="req">*</span></label>
            <select class="select-a" onchange="if(this.value)document.getElementById('alasanTolak').value=this.value" style="margin-bottom:8px">
                <option value="">Pilih alasan cepat...</option>
                <option>Nominal transfer kurang dari total tagihan</option>
                <option>Bukti transfer tidak terbaca / buram</option>
                <option>Dana belum masuk ke rekening toko</option>
                <option>Rekening tujuan transfer salah</option>
                <option>Bukti transfer terindikasi tidak asli</option>
            </select>
            <textarea name="alasan" id="alasanTolak" class="textarea-a" required placeholder="Tulis alasan penolakan..."></textarea>
            <div class="modal-x__actions">
                <button type="button" class="btn-a outline" data-modal-close>Batal</button>
                <button class="btn-a danger">Tolak</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var DATA = <?= json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var PESANAN_URL = <?= json_encode(url('admin/pesanan.php?id=')) ?>;
    var rot = 0;
    function $(id) { return document.getElementById(id); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function badge(s, c) { return '<span class="badge-status ' + c + '">' + esc(s) + '</span>'; }

    function show(id) {
        var d = DATA[id];
        if (!d) return;
        rot = 0;
        var img = $('bpImg');
        img.style.transform = '';
        img.src = d.img;
        $('bpKode').textContent = d.kode;
        $('bpBadge').innerHTML = badge(d.status, d.status_class);
        $('bpOpen').href = d.img; $('bpDownload').href = d.img; $('bpDownload').setAttribute('download', d.file);
        $('bpPelanggan').innerHTML = esc(d.pelanggan) + '<span class="cell-sub">' + esc(d.email) + ' &middot; ' + esc(d.hp) + '</span>';
        $('bpTgl').textContent = d.tgl; $('bpTglPesan').textContent = d.tgl_pesan;
        $('bpStatusPesanan').innerHTML = esc(d.status_pesanan);
        $('bpCatatan').textContent = d.catatan && d.status !== 'Ditolak' ? d.catatan : '-';
        $('bpAlamat').innerText = d.alamat || '-';
        var al = $('bpAlasan');
        if (d.status === 'Ditolak' && d.catatan) { al.style.display = 'flex'; al.querySelector('span').textContent = 'Alasan ditolak: ' + d.catatan; } else { al.style.display = 'none'; }
        var h = d.history || [];
        $('bpHistoryWrap').style.display = h.length > 1 ? 'block' : 'none';
        $('bpHistory').innerHTML = h.map(function (x) {
            return '<img src="' + esc(x.img) + '" class="' + (x.id === d.id ? 'current' : '') + '" title="' + esc(x.tgl + ' - ' + x.jumlah + ' - ' + x.status) + '" data-hid="' + x.id + '">';
        }).join('');
        $('bpPesananLink').href = PESANAN_URL + d.id_pesanan;
        $('bpPesananLink').style.display = d.status === 'Disetujui' ? '' : 'none';
        document.querySelectorAll('.bpId').forEach(function (i) { i.value = d.id; });
        $('bpActions').style.display = d.status === 'Sudah Upload Bukti' ? 'block' : 'none';
        document.querySelectorAll('.pay-item').forEach(function (el) { el.classList.toggle('active', +el.dataset.id === id); });
        try { history.replaceState(null, '', updateQuery('sel', id)); } catch (e) {}
    }
    function updateQuery(k, v) {
        var u = new URL(location.href); u.searchParams.set(k, v); return u.toString();
    }

    document.querySelectorAll('.pay-item').forEach(function (el) {
        el.addEventListener('click', function () { show(+el.dataset.id); if (window.innerWidth < 992) $('buktiPanel').scrollIntoView({behavior: 'smooth'}); });
        el.addEventListener('keydown', function (e) { if (e.key === 'Enter') show(+el.dataset.id); });
    });
    $('bpHistory').addEventListener('click', function (e) {
        var t = e.target.closest('img'); if (!t) return;
        var hid = +t.dataset.hid;
        if (DATA[hid]) { show(hid); } else { $('bpImg').src = t.src; document.querySelectorAll('#bpHistory img').forEach(function (x) { x.classList.toggle('current', x === t); }); }
    });
    $('bpRotate').addEventListener('click', function () { rot = (rot + 90) % 360; $('bpImg').style.transform = 'rotate(' + rot + 'deg)'; });
    function zoom() { var lb = $('lightbox'); lb.querySelector('img').src = $('bpImg').src; lb.classList.add('show'); }
    $('bpZoom').addEventListener('click', zoom);
    $('bpImg').addEventListener('click', zoom);

    // navigasi keyboard atas/bawah
    document.addEventListener('keydown', function (e) {
        if (['ArrowDown', 'ArrowUp'].indexOf(e.key) < 0 || /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) return;
        var items = Array.prototype.slice.call(document.querySelectorAll('.pay-item'));
        var i = items.findIndex(function (x) { return x.classList.contains('active'); });
        var n = items[Math.max(0, Math.min(items.length - 1, i + (e.key === 'ArrowDown' ? 1 : -1)))];
        if (n) { e.preventDefault(); show(+n.dataset.id); n.scrollIntoView({block: 'nearest'}); }
    });

    // tampilan daftar / galeri (diingat per browser)
    var list = $('payList');
    function setView(v) {
        if (list) list.classList.toggle('gallery', v === 'gallery');
        document.querySelectorAll('[data-view]').forEach(function (b) { b.classList.toggle('active', b.dataset.view === v); });
        try { localStorage.setItem('payView', v); } catch (e) {}
    }
    document.querySelectorAll('[data-view]').forEach(function (b) { b.addEventListener('click', function () { setView(b.dataset.view); }); });
    var saved = 'list';
    try { saved = localStorage.getItem('payView') || 'list'; } catch (e) {}
    setView(saved);

    <?php if ($sel): ?>show(<?= $sel ?>);<?php endif; ?>
})();
</script>

<?php admin_footer(); ?>
