<?php
/** Ubah varietas. klikUbah -> tampilkanForm(data lama) -> submit -> validasiData -> update */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Varietas/varietas.css';

$idVarietas = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM varietas WHERE id_varietas = ?');
$stmt->execute([$idVarietas]);
$lama = $stmt->fetch();
if (!$lama) {
    flash('warning', 'Data varietas tidak ditemukan.');
    redirect('admin/Varietas/index.php');
}

$errors = [];
$data   = $lama;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('UPDATE varietas SET nama_varietas = ?, deskripsi = ? WHERE id_varietas = ?')
            ->execute([$data['nama_varietas'], $data['deskripsi'] !== '' ? $data['deskripsi'] : null, $idVarietas]);
        flash('success', 'Varietas berhasil diperbarui.');
        redirect('admin/Varietas/index.php');
    }
}
$data['deskripsi'] = (string)($data['deskripsi'] ?? '');

$judul = 'Ubah Varietas';
admin_header('Kelola Varietas', 'varietas');
page_header('Ubah Varietas', $lama['nama_varietas']);
require __DIR__ . '/form.php';
admin_footer();
