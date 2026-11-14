<?php
/** Hapus jasa kurir. Tidak bisa dihapus kalau masih dipakai oleh pengiriman. */
require __DIR__ . '/../sidebar.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        $pdo->prepare('DELETE FROM jasa_kurir WHERE id_jasa_kurir = ?')->execute([(int)($_POST['id'] ?? 0)]);
        flash('success', 'Jasa Kurir berhasil dihapus.');
    } catch (PDOException $e) {
        flash('danger', 'Gagal menghapus: jasa kurir ini masih digunakan oleh data pengiriman.');
    }
}
redirect('admin/jasa_kurir/index.php');
