<?php
/** Hapus jenis beras. Tidak bisa dihapus kalau masih dipakai oleh produk. */
require __DIR__ . '/../sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $pdo->prepare('DELETE FROM jenis_beras WHERE id_jenis_beras = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Jenis beras berhasil dihapus.');
    } catch (PDOException $e) {
        flash('danger', 'Gagal menghapus: jenis beras ini masih digunakan oleh produk.');
    }
}
redirect('admin/Jenis/index.php');
