<?php
/**
 * Sequence "Mendownload Laporan Keuangan": klikDownload() -> downloadLaporan(periode) -> generatePDF() -> unduhFile()
 */
require_once __DIR__ . '/../config/koneksi.php';
require_owner();
require __DIR__ . '/../config/laporan.php';
require __DIR__ . '/../config/pdf.php';

$awal  = (string)($_GET['awal'] ?? '');
$akhir = (string)($_GET['akhir'] ?? '');
if (!periode_valid($awal, $akhir)) {
    flash('danger', 'Periode laporan tidak valid.');
    redirect('owner/laporan.php');
}
$r = laporan_data($pdo, $awal, $akhir);
if (!$r['ada_transaksi']) {
    flash('danger', 'Tidak ada transaksi pada periode tersebut.');
    redirect('owner/laporan.php?tampil=1&awal=' . $awal . '&akhir=' . $akhir);
}

$BROWN = [92, 59, 46];
$TEXT  = [63, 48, 40];
$MUTED = [137, 121, 109];
$CREAM = [251, 248, 241];
$HEAD  = [248, 243, 234];
$LINE  = [222, 212, 194];
$RED   = [169, 93, 77];
$L = 40;
$CW = SimplePdf::W - 2 * $L;

$pdf = new SimplePdf();
$pdf->addPage();

// ---------- kop laporan ----------
$pdf->rect(0, 0, SimplePdf::W, 92, $BROWN);
$pdf->text($L, 40, 'LAPORAN KEUANGAN', 20, true, [255, 255, 255]);
$pdf->text($L, 58, TOKO_PERUSAHAAN . ' - ' . TOKO_NAMA, 9, false, [231, 220, 196]);
$pdf->text($L, 72, TOKO_ALAMAT, 8, false, [231, 220, 196]);
$pdf->text($L, 40, 'Periode', 9, false, [231, 220, 196], 'R', $CW);
$pdf->text($L, 56, tgl($awal) . ' - ' . tgl($akhir), 11, true, [255, 255, 255], 'R', $CW);
$pdf->text($L, 72, 'Dibuat: ' . tgl(now(), true) . ' oleh ' . $_SESSION['nama'], 8, false, [231, 220, 196], 'R', $CW);

// ---------- ringkasan ----------
$y = 112;
$boxes = [
    ['Total Penjualan (' . $r['jumlah_transaksi'] . ' transaksi)', rupiah($r['total_penjualan']), false],
    ['Total Ongkir', rupiah($r['total_ongkir']), false],
    ['Total Refund (' . count($r['refund']) . ')', '- ' . rupiah($r['total_refund']), false],
    ['Pendapatan Bersih', rupiah($r['pendapatan_bersih']), true],
];
$gap = 8;
$bw = ($CW - 3 * $gap) / 4;
foreach ($boxes as $i => [$label, $val, $dark]) {
    $x = $L + $i * ($bw + $gap);
    $pdf->rect($x, $y, $bw, 54, $dark ? $BROWN : $CREAM, $dark ? null : $LINE);
    $pdf->text($x + 10, $y + 18, $pdf->fit($label, $bw - 20, 7.5), 7.5, false, $dark ? [231, 220, 196] : $MUTED);
    $pdf->text($x + 10, $y + 40, $val, 12, true, $dark ? [255, 255, 255] : ($i === 2 ? $RED : $BROWN));
}
$y += 54 + 14;
$pdf->text($L, $y, 'Pendapatan bersih = total penjualan - total refund. Ongkir diteruskan ke jasa kurir dan tidak dihitung sebagai pendapatan.', 7.5, false, $MUTED);
$y += 22;

// ---------- tabel dengan pindah halaman otomatis ----------
$table = function (string $title, array $cols, array $rows, ?array $totalRow = null) use ($pdf, &$y, $L, $CW, $BROWN, $TEXT, $HEAD, $LINE) {
    $rowH = 18;
    $bottom = SimplePdf::H - 60;
    $header = function () use ($pdf, &$y, $L, $CW, $cols, $HEAD, $LINE, $BROWN) {
        $pdf->rect($L, $y, $CW, 20, $HEAD);
        $pdf->line($L, $y + 20, $L + $CW, $y + 20, $LINE, 0.8);
        $x = $L;
        foreach ($cols as [$label, $w, $align]) {
            $pdf->text($x + 6, $y + 13.5, strtoupper($label), 7.5, true, $BROWN, $align, $w - 12);
            $x += $w;
        }
        $y += 20;
    };
    if ($y + 60 > $bottom) {
        $pdf->addPage();
        $y = 50;
    }
    $pdf->text($L, $y, $title, 11.5, true, $BROWN);
    $y += 10;
    $header();
    $all = $rows;
    if ($totalRow) {
        $all[] = $totalRow + ['__total' => true];
    }
    foreach ($all as $row) {
        if ($y + $rowH > $bottom) {
            $pdf->addPage();
            $y = 50;
            $header();
        }
        $isTotal = !empty($row['__total']);
        if ($isTotal) {
            $pdf->rect($L, $y, $CW, $rowH, [243, 234, 215]);
        }
        $x = $L;
        foreach ($cols as $i => [$label, $w, $align]) {
            $val = (string)($row[$i] ?? '');
            $pdf->text($x + 6, $y + 12.5, $pdf->fit($val, $w - 12, 8.5, $isTotal), 8.5, $isTotal, $TEXT, $align, $w - 12);
            $x += $w;
        }
        $pdf->line($L, $y + $rowH, $L + $CW, $y + $rowH, [238, 231, 219]);
        $y += $rowH;
    }
    $y += 22;
};

// Rincian transaksi
$rows = [];
foreach ($r['pesanan'] as $i => $p) {
    $rows[] = [$i + 1, kode((int)$p['id_pesanan']), tgl($p['tanggal_pembayaran']), $p['nama'], rupiah($p['penjualan']), rupiah($p['ongkir']), rupiah($p['total_harga'])];
}
$table('Rincian Transaksi', [['No', 28, 'L'], ['Kode', 62, 'L'], ['Tgl Bayar', 72, 'L'], ['Pelanggan', 118, 'L'], ['Penjualan', 80, 'R'], ['Ongkir', 72, 'R'], ['Total', 83.28, 'R']],
    $rows, ['', '', '', 'Jumlah', rupiah($r['total_penjualan']), rupiah($r['total_ongkir']), rupiah($r['total_penjualan'] + $r['total_ongkir'])]);

// Penjualan per produk
if ($r['per_produk']) {
    $rows = [];
    foreach ($r['per_produk'] as $i => $pp) {
        $rows[] = [$i + 1, $pp['nama_produk'], $pp['merek'], (int)$pp['qty'] . ' pcs', rupiah($pp['omzet'])];
    }
    $table('Penjualan per Produk', [['No', 28, 'L'], ['Produk', 210, 'L'], ['Merek', 110, 'L'], ['Terjual', 70, 'R'], ['Penjualan', 97.28, 'R']], $rows);
}

// Refund
if ($r['refund']) {
    $rows = [];
    foreach ($r['refund'] as $i => $rf) {
        $rows[] = [$i + 1, '#' . $rf['id_retur'] . ' / ' . kode((int)$rf['id_pesanan']), $rf['nama'], $rf['nama_produk'] . ' (' . (int)$rf['jumlah_retur'] . ')', tgl($rf['tanggal_retur']), rupiah($rf['nominal_refund'])];
    }
    $table('Refund Retur', [['No', 28, 'L'], ['Retur', 82, 'L'], ['Pelanggan', 100, 'L'], ['Produk', 150, 'L'], ['Tgl Retur', 70, 'L'], ['Nominal', 85.28, 'R']],
        $rows, ['', '', '', '', 'Jumlah', rupiah($r['total_refund'])]);
}

$file = $pdf->render(function (SimplePdf $p, int $page, int $total) use ($L, $CW, $MUTED, $LINE, $awal, $akhir) {
    $yy = SimplePdf::H - 34;
    $p->line($L, $yy - 12, $L + $CW, $yy - 12, $LINE);
    $p->text($L, $yy, TOKO_NAMA . ' - Laporan Keuangan ' . tgl($awal) . ' s/d ' . tgl($akhir), 7.5, false, $MUTED);
    $p->text($L, $yy, "Halaman $page dari $total", 7.5, false, $MUTED, 'R', $CW);
});

$nama = 'Laporan-Keuangan_' . $awal . '_sd_' . $akhir . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $nama . '"');
header('Content-Length: ' . strlen($file));
header('Cache-Control: private, no-store');
echo $file;
