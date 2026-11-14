<?php
/**
 * Hapus produk. klikHapus -> konfirmasi -> deleteProduk -> konfirmasiTerhapus
 * Produk yang sudah pernah dipesan tidak bisa dihapus (dipakai di pesanan_produk).
 */
require __DIR__ . '/../sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $pdo->prepare('DELETE FROM produk WHERE id_produk = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Produk berhasil dihapus.');
    } catch (PDOException $e) {
        flash('danger', 'Gagal menghapus: produk ini masih digunakan oleh data pesanan.');
    }
}
redirect('admin/Produk/index.php');
