<?php
/**
 * Data laporan keuangan (Activity & Sequence "Mendownload Laporan Keuangan"):
 *   getPesananSelesai(periode) + getPembayaranTerverifikasi(periode) + getRefund(periode) -> hitungRekap()
 *
 * Definisi:
 *   Transaksi         = pesanan berstatus Selesai dengan pembayaran Disetujui,
 *                       tanggal transaksi = tanggal pembayaran yang disetujui.
 *   Total penjualan   = jumlah x harga_satuan
 *   Total ongkir      = jumlah x ongkir_satuan (diteruskan ke jasa kurir)
 *   Total refund      = nominal_refund retur berstatus "Refund Selesai" (tanggal retur dalam periode)
 *   Pendapatan bersih = total penjualan - total refund
 */
function laporan_data(PDO $pdo, string $awal, string $akhir): array
{
    $a = $awal . ' 00:00:00';
    $b = $akhir . ' 23:59:59';

    $st = $pdo->prepare("SELECT ps.id_pesanan, ps.tanggal_pesanan, ps.total_harga, u.nama, pb.tanggal_pembayaran, pb.jumlah_bayar,
                                (SELECT SUM(pp.jumlah * pp.harga_satuan) FROM pesanan_produk pp WHERE pp.id_pesanan = ps.id_pesanan) AS penjualan,
                                (SELECT SUM(pp.jumlah * pp.ongkir_satuan) FROM pesanan_produk pp WHERE pp.id_pesanan = ps.id_pesanan) AS ongkir
                         FROM pesanan ps
                         JOIN `user` u ON u.id_user = ps.id_user
                         JOIN pembayaran pb ON pb.id_pesanan = ps.id_pesanan AND pb.status_pembayaran = 'Disetujui'
                         WHERE ps.status_pesanan = 'Selesai' AND pb.tanggal_pembayaran BETWEEN ? AND ?
                         ORDER BY pb.tanggal_pembayaran");
    $st->execute([$a, $b]);
    $pesanan = $st->fetchAll();
    $ids = array_column($pesanan, 'id_pesanan');

    $perProduk = [];
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $st = $pdo->prepare("SELECT p.merek, p.nama_produk, SUM(pp.jumlah) qty, SUM(pp.jumlah * pp.harga_satuan) omzet
                             FROM pesanan_produk pp JOIN produk p ON p.id_produk = pp.id_produk
                             WHERE pp.id_pesanan IN ($ph) GROUP BY p.id_produk, p.merek, p.nama_produk ORDER BY omzet DESC");
        $st->execute($ids);
        $perProduk = $st->fetchAll();
    }

    $st = $pdo->prepare("SELECT r.id_retur, r.id_pesanan, r.tanggal_retur, r.jumlah_retur, r.nominal_refund, u.nama, p.nama_produk
                         FROM retur r JOIN pesanan ps ON ps.id_pesanan = r.id_pesanan JOIN `user` u ON u.id_user = ps.id_user
                         JOIN produk p ON p.id_produk = r.id_produk
                         WHERE r.status_retur = 'Refund Selesai' AND r.tanggal_retur BETWEEN ? AND ? ORDER BY r.tanggal_retur");
    $st->execute([$a, $b]);
    $refund = $st->fetchAll();

    $penjualan = array_sum(array_map('floatval', array_column($pesanan, 'penjualan')));
    $ongkir    = array_sum(array_map('floatval', array_column($pesanan, 'ongkir')));
    $totRefund = array_sum(array_map('floatval', array_column($refund, 'nominal_refund')));

    return [
        'awal' => $awal, 'akhir' => $akhir,
        'pesanan' => $pesanan, 'per_produk' => $perProduk, 'refund' => $refund,
        'jumlah_transaksi' => count($pesanan),
        'total_penjualan' => $penjualan, 'total_ongkir' => $ongkir, 'total_refund' => $totRefund,
        'pendapatan_bersih' => $penjualan - $totRefund,
        'ada_transaksi' => count($pesanan) > 0 || count($refund) > 0,
    ];
}

function periode_valid(string $awal, string $akhir): bool
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $awal) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $akhir) && $awal <= $akhir;
}
