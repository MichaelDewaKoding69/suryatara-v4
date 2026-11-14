<?php
/**
 * Activity & Sequence "Mendownload Laporan Keuangan" (aktor: Owner):
 *   pilihPeriode(tglAwal, tglAkhir) -> klikTampilkan() -> getPesananSelesai + getPembayaranTerverifikasi + getRefund
 *   [ada transaksi]   hitungRekap -> tampilkanRekapLaporan -> klikDownload -> generatePDF -> unduhFile
 *   [tidak ada]       tampilkanPesanError
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'owner/CSS/laporan.css';
require __DIR__ . '/../config/laporan.php';

$awal  = (string)($_GET['awal'] ?? date('Y-m-01'));
$akhir = (string)($_GET['akhir'] ?? date('Y-m-d'));
$tampil = isset($_GET['tampil']);
$r = null;
if ($tampil) {
    if (!periode_valid($awal, $akhir)) {
        flash('danger', 'Periode laporan tidak valid. Tanggal awal harus sebelum tanggal akhir.');
        redirect('owner/laporan.php');
    }
    $r = laporan_data($pdo, $awal, $akhir);
}

admin_header('Laporan Keuangan', 'laporan');
page_header('Laporan Keuangan', 'Pilih periode, tampilkan rekap penjualan, ongkir, refund, dan pendapatan bersih, lalu unduh sebagai PDF.');
?>

<div class="panel no-print" style="margin-bottom:18px">
    <div class="panel-body">
        <form method="GET" class="filter-bar">
            <input type="hidden" name="tampil" value="1">
            <div><label class="label-a">Tanggal awal</label><input type="date" name="awal" value="<?= h($awal) ?>" class="input-a" required></div>
            <div><label class="label-a">Tanggal akhir</label><input type="date" name="akhir" value="<?= h($akhir) ?>" class="input-a" required></div>
            <button class="btn-a">Tampilkan Laporan</button>
            <a class="btn-a outline" href="<?= url('owner/laporan.php?tampil=1&awal=' . date('Y-m-01') . '&akhir=' . date('Y-m-d')) ?>">Bulan ini</a>
            <a class="btn-a outline" href="<?= url('owner/laporan.php?tampil=1&awal=' . date('Y-m-01', strtotime('first day of last month')) . '&akhir=' . date('Y-m-t', strtotime('last day of last month'))) ?>">Bulan lalu</a>
            <a class="btn-a outline" href="<?= url('owner/laporan.php?tampil=1&awal=' . date('Y-01-01') . '&akhir=' . date('Y-m-d')) ?>">Tahun ini</a>
        </form>
    </div>
</div>

<?php if (!$r): ?>
    <div class="panel"><div class="empty-a">Pilih periode lalu tekan <strong>Tampilkan Laporan</strong>.</div></div>
<?php elseif (!$r['ada_transaksi']): ?>
    <div class="alert-box warning"><span>Tidak ada transaksi pada periode <?= tgl($awal) ?> &ndash; <?= tgl($akhir) ?>.</span></div>
<?php else: ?>
    <div class="laporan-sheet">
        <div style="display:flex;justify-content:space-between;gap:16px;flex-wrap:wrap;align-items:flex-start">
            <div>
                <div style="color:var(--text-muted);font-size:11px;font-weight:700;letter-spacing:1px"><?= h(strtoupper(TOKO_PERUSAHAAN)) ?></div>
                <h2>Laporan Keuangan</h2>
                <div style="color:var(--text-secondary)">Periode <?= tgl($awal) ?> &ndash; <?= tgl($akhir) ?></div>
            </div>
            <div class="no-print" style="display:flex;gap:8px">
                <a href="<?= url('owner/laporan-pdf.php?awal=' . urlencode($awal) . '&akhir=' . urlencode($akhir)) ?>" class="btn-a">Download Laporan (PDF)</a>
                <button onclick="window.print()" class="btn-a outline">Cetak</button>
            </div>
        </div>

        <div class="rekap-grid">
            <div><span>Total Penjualan (<?= $r['jumlah_transaksi'] ?> transaksi)</span><strong><?= rupiah($r['total_penjualan']) ?></strong></div>
            <div><span>Total Ongkir</span><strong><?= rupiah($r['total_ongkir']) ?></strong></div>
            <div><span>Total Refund (<?= count($r['refund']) ?>)</span><strong style="color:var(--danger)">&minus; <?= rupiah($r['total_refund']) ?></strong></div>
            <div class="net"><span>Pendapatan Bersih</span><strong><?= rupiah($r['pendapatan_bersih']) ?></strong></div>
        </div>

        <h3 style="font-size:14px;color:var(--primary);margin:20px 0 8px">Rincian Transaksi (pesanan selesai &amp; pembayaran disetujui)</h3>
        <div class="table-wrap"><table class="table-admin">
            <thead><tr><th>Kode</th><th>Pelanggan</th><th>Tgl Bayar</th><th class="num">Penjualan</th><th class="num">Ongkir</th><th class="num">Total</th></tr></thead>
            <tbody>
            <?php if (!$r['pesanan']): ?><tr><td colspan="6"><div class="empty-a" style="padding:14px">Tidak ada pesanan selesai pada periode ini.</div></td></tr><?php endif; ?>
            <?php foreach ($r['pesanan'] as $p): ?>
                <tr><td><strong><?= h(kode((int)$p['id_pesanan'])) ?></strong></td><td><?= h($p['nama']) ?></td><td><?= tgl($p['tanggal_pembayaran']) ?></td>
                    <td class="num"><?= rupiah($p['penjualan']) ?></td><td class="num"><?= rupiah($p['ongkir']) ?></td><td class="num"><?= rupiah($p['total_harga']) ?></td></tr>
            <?php endforeach; ?>
            <tr><td colspan="3" class="num"><strong>Jumlah</strong></td><td class="num"><strong><?= rupiah($r['total_penjualan']) ?></strong></td><td class="num"><strong><?= rupiah($r['total_ongkir']) ?></strong></td><td class="num"><strong><?= rupiah($r['total_penjualan'] + $r['total_ongkir']) ?></strong></td></tr>
            </tbody>
        </table></div>

        <?php if ($r['per_produk']): ?>
            <h3 style="font-size:14px;color:var(--primary);margin:20px 0 8px">Penjualan per Produk</h3>
            <div class="table-wrap"><table class="table-admin">
                <thead><tr><th>Produk</th><th>Merek</th><th class="num">Terjual</th><th class="num">Penjualan</th></tr></thead>
                <tbody><?php foreach ($r['per_produk'] as $pp): ?><tr><td><?= h($pp['nama_produk']) ?></td><td><?= h($pp['merek']) ?></td><td class="num"><?= (int)$pp['qty'] ?></td><td class="num"><?= rupiah($pp['omzet']) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>

        <?php if ($r['refund']): ?>
            <h3 style="font-size:14px;color:var(--primary);margin:20px 0 8px">Refund Retur</h3>
            <div class="table-wrap"><table class="table-admin">
                <thead><tr><th>Retur</th><th>Pelanggan</th><th>Produk</th><th>Tgl Retur</th><th class="num">Nominal</th></tr></thead>
                <tbody><?php foreach ($r['refund'] as $rf): ?><tr><td>#<?= (int)$rf['id_retur'] ?> &middot; <?= h(kode((int)$rf['id_pesanan'])) ?></td><td><?= h($rf['nama']) ?></td><td><?= h($rf['nama_produk']) ?> (<?= (int)$rf['jumlah_retur'] ?>)</td><td><?= tgl($rf['tanggal_retur']) ?></td><td class="num"><?= rupiah($rf['nominal_refund']) ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php admin_footer(); ?>
