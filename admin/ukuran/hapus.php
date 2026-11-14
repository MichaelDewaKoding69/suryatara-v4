<?php
/** Hapus ukuran. Tidak bisa dihapus kalau masih dipakai oleh produk. */
require __DIR__ . '/../sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $pdo->prepare('DELETE FROM ukuran WHERE id_ukuran = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Ukuran berhasil dihapus.');
    } catch (PDOException $e) {
        flash('danger', 'Gagal menghapus: ukuran ini masih digunakan oleh produk.');
    }
}
redirect('admin/ukuran/index.php');
