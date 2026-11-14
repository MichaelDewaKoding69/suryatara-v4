<?php
/** Ubah ukuran. klikUbah -> tampilkanForm(data lama) -> submit -> validasiData -> update */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/ukuran/ukuran.css';

$idUkuran = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM ukuran WHERE id_ukuran = ?');
$stmt->execute([$idUkuran]);
$lama = $stmt->fetch();
if (!$lama) {
    flash('warning', 'Data ukuran tidak ditemukan.');
    redirect('admin/ukuran/index.php');
}

$errors = [];
$data   = $lama;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('UPDATE ukuran SET nama_ukuran = ?, berat_kg = ? WHERE id_ukuran = ?')
            ->execute([$data['nama_ukuran'], $data['berat_kg'], $idUkuran]);
        flash('success', 'Ukuran berhasil diperbarui.');
        redirect('admin/ukuran/index.php');
    }
}

$judul = 'Ubah Ukuran';
admin_header('Kelola Ukuran', 'ukuran');
page_header('Ubah Ukuran', $lama['nama_ukuran']);
require __DIR__ . '/form.php';
admin_footer();
