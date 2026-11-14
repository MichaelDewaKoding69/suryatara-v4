<?php
/** Hapus varietas. Tidak bisa dihapus kalau masih dipakai oleh jenis beras. */
require __DIR__ . '/../sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $pdo->prepare('DELETE FROM varietas WHERE id_varietas = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Varietas berhasil dihapus.');
    } catch (PDOException $e) {
        flash('danger', 'Gagal menghapus: varietas ini masih digunakan oleh jenis beras.');
    }
}
redirect('admin/Varietas/index.php');
