<?php
/** Ubah jasa kurir. klikUbah -> tampilkanForm(data lama) -> submit -> validasiData -> update */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/jasa_kurir/jasakurir.css';

$idKurir = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM jasa_kurir WHERE id_jasa_kurir = ?');
$stmt->execute([$idKurir]);
$lama = $stmt->fetch();
if (!$lama) {
    flash('warning', 'Data jasa kurir tidak ditemukan.');
    redirect('admin/jasa_kurir/index.php');
}

$errors = [];
$data   = $lama;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('UPDATE jasa_kurir SET nama_kurir = ? WHERE id_jasa_kurir = ?')
            ->execute([$data['nama_kurir'], $idKurir]);
        flash('success', 'Jasa Kurir berhasil diperbarui.');
        redirect('admin/jasa_kurir/index.php');
    }
}

$judul = 'Ubah Jasa Kurir';
admin_header('Kelola Jasa Kurir', 'jasa_kurir');
page_header('Ubah Jasa Kurir', $lama['nama_kurir']);
require __DIR__ . '/form.php';
admin_footer();
