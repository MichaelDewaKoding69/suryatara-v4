<?php
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/dashboard.css';

$hari = (int)($_GET['hari'] ?? 14);
if (!in_array($hari, [7, 14, 30], true)) {
    $hari = 14;
}
$mulaiBulan = date('Y-m-01 00:00:00');
$mulaiBulanLalu = date('Y-m-01 00:00:00', strtotime('first day of last month'));

function scalar(string $sql, array $p = [])
{
    global $pdo;
    $st = $pdo->prepare($sql);
    $st->execute($p);
    return $st->fetchColumn();
}

// Penjualan = pesanan dengan pembayaran terverifikasi (tidak dibatalkan)
$penjualanSql = "SELECT COALESCE(SUM(ps.total_harga),0) FROM pesanan ps
                 WHERE ps.status_pesanan IN ('Diproses','Dikirim','Selesai')
                   AND EXISTS (SELECT 1 FROM pembayaran pb WHERE pb.id_pesanan = ps.id_pesanan AND pb.status_pembayaran = 'Disetujui' AND pb.tanggal_pembayaran >= ? AND pb.tanggal_pembayaran < ?)";
$jualIni  = (float)scalar($penjualanSql, [$mulaiBulan, date('Y-m-d H:i:s', strtotime('+1 day'))]);
$jualLalu = (float)scalar($penjualanSql, [$mulaiBulanLalu, $mulaiBulan]);
$pesananIni  = (int)scalar("SELECT COUNT(*) FROM pesanan WHERE tanggal_pesanan >= ? AND status_pesanan <> 'Dibatalkan'", [$mulaiBulan]);
$pesananLalu = (int)scalar("SELECT COUNT(*) FROM pesanan WHERE tanggal_pesanan >= ? AND tanggal_pesanan < ? AND status_pesanan <> 'Dibatalkan'", [$mulaiBulanLalu, $mulaiBulan]);
$produkAktif = (int)scalar('SELECT COUNT(*) FROM produk WHERE stok > 0');
$produkTotal = (int)scalar('SELECT COUNT(*) FROM produk');
$pelanggan   = (int)scalar('SELECT COUNT(*) FROM `user` u JOIN role r ON r.id_role = u.id_role WHERE r.nama_role = ?', ['Pelanggan']);
$pelangganAktif = (int)scalar('SELECT COUNT(DISTINCT id_user) FROM pesanan WHERE tanggal_pesanan >= ?', [$mulaiBulan]);
$c = admin_counts();
$returBaru = (int)scalar("SELECT COUNT(*) FROM retur WHERE status_retur = 'Menunggu Verifikasi'");
$returTiba = (int)scalar("SELECT COUNT(*) FROM retur WHERE status_retur = 'Barang Retur Dikirim'");

function persen(float $now, float $prev): string
{
    if ($prev <= 0) {
        return $now > 0 ? '<span class="positive" style="color:var(--success)">baru</span>' : '<span style="color:var(--text-muted)">-</span>';
    }
    $p = ($now - $prev) / $prev * 100;
    return '<span style="color:' . ($p >= 0 ? 'var(--success)' : 'var(--danger)') . '">' . ($p >= 0 ? '+' : '-') . number_format(abs($p), 1, ',', '.') . '%</span> dari bulan lalu';
}

// Grafik penjualan harian (berdasarkan tanggal verifikasi pembayaran)
$st = $pdo->prepare("SELECT DATE(pb.tanggal_pembayaran) d, SUM(ps.total_harga) t FROM pembayaran pb JOIN pesanan ps ON ps.id_pesanan = pb.id_pesanan
                     WHERE pb.status_pembayaran = 'Disetujui' AND ps.status_pesanan <> 'Dibatalkan' AND pb.tanggal_pembayaran >= ? GROUP BY DATE(pb.tanggal_pembayaran)");
$st->execute([date('Y-m-d 00:00:00', strtotime('-' . ($hari - 1) . ' days'))]);
$byDay = array_column($st->fetchAll(), 't', 'd');
$series = [];
for ($i = $hari - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $series[$d] = (float)($byDay[$d] ?? 0);
}
$totalPeriode = array_sum($series);

$topProduk = $pdo->query("SELECT p.nama_produk, SUM(pp.jumlah) qty, SUM(pp.subtotal) omzet FROM pesanan_produk pp
                          JOIN pesanan ps ON ps.id_pesanan = pp.id_pesanan JOIN produk p ON p.id_produk = pp.id_produk
                          WHERE ps.status_pesanan IN ('Diproses','Dikirim','Selesai') GROUP BY p.id_produk, p.nama_produk ORDER BY qty DESC LIMIT 5")->fetchAll();
$maxQty = $topProduk ? max(array_column($topProduk, 'qty')) : 1;
$terbaru = $pdo->query('SELECT ps.*, u.nama FROM pesanan ps JOIN `user` u ON u.id_user = ps.id_user ORDER BY ps.tanggal_pesanan DESC LIMIT 6')->fetchAll();
$stokTipis = $pdo->query('SELECT id_produk, nama_produk, stok, foto FROM produk WHERE stok <= 10 ORDER BY stok ASC LIMIT 5')->fetchAll();

admin_header('Dashboard', 'dashboard');
page_header('Dashboard', 'Ringkasan toko per ' . tgl(now()) . '.');
?>

<div class="stat-grid todo">
    <a href="<?= url('admin/pembayaran.php?status=Sudah+Upload+Bukti') ?>" class="stat-card"><div class="stat-title">Bukti bayar belum dicek</div><div class="stat-value"><?= $c['bayar'] ?></div></a>
    <a href="<?= url('admin/pesanan.php?status=Diproses') ?>" class="stat-card"><div class="stat-title">Pesanan perlu dikirim</div><div class="stat-value"><?= $c['proses'] ?></div></a>
    <a href="<?= url('admin/retur.php?status=Menunggu+Verifikasi') ?>" class="stat-card"><div class="stat-title">Retur baru</div><div class="stat-value"><?= $returBaru ?></div></a>
    <a href="<?= url('admin/retur.php?status=Barang+Retur+Dikirim') ?>" class="stat-card"><div class="stat-title">Barang retur dalam perjalanan</div><div class="stat-value"><?= $returTiba ?></div></a>
</div>

<div class="stat-grid">
    <div class="stat-card"><div class="stat-title">Penjualan bulan ini</div><div class="stat-value"><?= rupiah($jualIni) ?></div><div class="stat-sub"><?= persen($jualIni, $jualLalu) ?></div></div>
    <div class="stat-card"><div class="stat-title">Pesanan bulan ini</div><div class="stat-value"><?= $pesananIni ?></div><div class="stat-sub"><?= persen($pesananIni, $pesananLalu) ?></div></div>
    <div class="stat-card"><div class="stat-title">Produk ada stok</div><div class="stat-value"><?= $produkAktif ?> <span style="font-size:13px;color:var(--text-muted);font-weight:400">dari <?= $produkTotal ?></span></div><div class="stat-sub"><?= $c['stok'] ?> produk stoknya tinggal 5 atau kurang</div></div>
    <div class="stat-card"><div class="stat-title">Pelanggan terdaftar</div><div class="stat-value"><?= $pelanggan ?></div><div class="stat-sub"><?= $pelangganAktif ?> belanja bulan ini</div></div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-head">
            <div><h2>Penjualan harian</h2><p>Total <?= $hari ?> hari terakhir: <strong style="color:var(--primary)"><?= rupiah($totalPeriode) ?></strong></p></div>
            <div class="view-toggle">
                <?php foreach ([7, 14, 30] as $hh): ?>
                    <a href="<?= url('admin/dashboard.php?hari=' . $hh) ?>" style="padding:7px 12px;font-weight:700;<?= $hh === $hari ? 'background:var(--primary);color:#fff' : 'color:var(--text-secondary)' ?>"><?= $hh ?>H</a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="panel-body">
            <?php
            $W = 760; $H = 260; $pl = 58; $pb = 30; $pt = 18;
            $max = max(1, max($series));
            $step = pow(10, floor(log10($max)));
            $niceMax = ceil($max / $step) * $step;
            $n = count($series);
            $bw = ($W - $pl - 10) / $n;
            ?>
            <svg class="chart-svg" viewBox="0 0 <?= $W ?> <?= $H ?>" preserveAspectRatio="none" role="img" aria-label="Grafik penjualan harian">
                <?php for ($g = 0; $g <= 4; $g++): $y = $pt + ($H - $pt - $pb) * (1 - $g / 4); ?>
                    <line class="grid" x1="<?= $pl ?>" x2="<?= $W - 10 ?>" y1="<?= $y ?>" y2="<?= $y ?>"/>
                    <text class="axis" x="<?= $pl - 6 ?>" y="<?= $y + 3 ?>" text-anchor="end"><?= $niceMax * $g / 4 >= 1000000 ? number_format($niceMax * $g / 4 / 1000000, 1, ',', '.') . 'jt' : number_format($niceMax * $g / 4 / 1000, 0, ',', '.') . 'rb' ?></text>
                <?php endfor; ?>
                <?php $i = 0; foreach ($series as $d => $v): $bh = ($H - $pt - $pb) * $v / $niceMax; $x = $pl + $i * $bw + $bw * 0.18; $y = $H - $pb - $bh; ?>
                    <rect class="bar" x="<?= $x ?>" y="<?= $y ?>" width="<?= $bw * 0.64 ?>" height="<?= max(0, $bh) ?>" rx="3"><title><?= tgl($d) ?>: <?= rupiah($v) ?></title></rect>
                    <?php if ($n <= 14 || $i % 3 === 0): ?><text class="axis" x="<?= $x + $bw * 0.32 ?>" y="<?= $H - 12 ?>" text-anchor="middle"><?= date('d/m', strtotime($d)) ?></text><?php endif; ?>
                <?php $i++; endforeach; ?>
            </svg>
        </div>
    </div>

    <div class="panel">
        <div class="panel-head"><h2>Paling laris</h2></div>
        <div class="panel-body">
            <?php if (!$topProduk): ?><div class="empty-a">Belum ada penjualan.</div><?php endif; ?>
            <div class="bar-list">
                <?php foreach ($topProduk as $t): ?>
                    <div class="bar-list-item">
                        <div class="top"><span style="font-weight:600"><?= h($t['nama_produk']) ?></span><span style="color:var(--text-secondary)"><?= (int)$t['qty'] ?> terjual</span></div>
                        <div class="track"><div class="fill" style="width:<?= round($t['qty'] / $maxQty * 100) ?>%"></div></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="grid-2" style="margin-top:18px">
    <div class="panel">
        <div class="panel-head"><h2>Pesanan terbaru</h2></div>
        <div class="table-wrap"><table class="table-admin">
            <thead><tr><th>Kode</th><th>Pelanggan</th><th>Tanggal</th><th class="num">Total</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($terbaru as $o): ?>
                <tr onclick="location.href='<?= url(in_array($o['status_pesanan'], ['Diproses', 'Dikirim', 'Selesai'], true) ? 'admin/pesanan.php?id=' . (int)$o['id_pesanan'] : 'admin/pembayaran.php?q=' . kode((int)$o['id_pesanan'])) ?>'" style="cursor:pointer">
                    <td><strong><?= h(kode((int)$o['id_pesanan'])) ?></strong></td><td><?= h($o['nama']) ?></td><td><?= tgl($o['tanggal_pesanan'], true) ?></td>
                    <td class="num"><?= rupiah($o['total_harga']) ?></td><td><?= badge($o['status_pesanan']) ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
    <div class="panel">
        <div class="panel-head"><h2>Stok menipis</h2><a href="<?= url('admin/Produk/index.php') ?>" class="btn-a outline sm">Kelola</a></div>
        <div class="panel-body">
            <?php if (!$stokTipis): ?><div class="empty-a" style="padding:20px">Semua stok aman.</div><?php endif; ?>
            <?php foreach ($stokTipis as $s): ?>
                <div style="display:flex;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid var(--border-light)">
                    <img src="<?= upload_url('produk', $s['foto']) ?>" class="thumb" style="width:40px;height:40px;border-radius:8px;object-fit:cover" alt="">
                    <a href="<?= url('admin/Produk/edit.php?id=' . (int)$s['id_produk']) ?>" style="flex:1;font-weight:600"><?= h($s['nama_produk']) ?></a>
                    <span class="badge-status <?= $s['stok'] <= 5 ? 'danger' : 'warning' ?>"><?= (int)$s['stok'] ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php admin_footer(); ?>
