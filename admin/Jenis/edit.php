<?php
/** Ubah jenis beras. klikUbah -> tampilkanForm(data lama) -> submit -> validasiData -> updateJenisBeras */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Jenis/jenis-beras.css';

$idJenis = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM jenis_beras WHERE id_jenis_beras = ?');
$stmt->execute([$idJenis]);
$lama = $stmt->fetch();
if (!$lama) {
    flash('warning', 'Data jenis beras tidak ditemukan.');
    redirect('admin/Jenis/index.php');
}

$errors = [];
$data   = $lama;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('UPDATE jenis_beras SET nama_jenis = ?, id_mutu = ?, id_tekstur = ?, id_warna = ?, id_varietas = ?, id_bulir = ?, deskripsi = ?
                       WHERE id_jenis_beras = ?')
            ->execute([$data['nama_jenis'], $data['id_mutu'], $data['id_tekstur'], $data['id_warna'], $data['id_varietas'], $data['id_bulir'],
                       $data['deskripsi'] !== '' ? $data['deskripsi'] : null, $idJenis]);
        flash('success', 'Jenis beras berhasil diperbarui.');
        redirect('admin/Jenis/index.php');
    }
}
$data['deskripsi'] = (string)($data['deskripsi'] ?? '');

$judul = 'Ubah Jenis Beras';
admin_header('Kelola Jenis Beras', 'jenis_beras');
page_header('Ubah Jenis Beras', $lama['nama_jenis']);
require __DIR__ . '/form.php';
admin_footer();
