<?php
/** Tambah varietas. klikTambah -> tampilkanForm -> submit -> validasiData -> insert */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Varietas/varietas.css';

$idVarietas = 0;
$errors = [];
$data   = ['nama_varietas' => '', 'deskripsi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('INSERT INTO varietas (nama_varietas, deskripsi) VALUES (?, ?)')
            ->execute([$data['nama_varietas'], $data['deskripsi'] !== '' ? $data['deskripsi'] : null]);
        flash('success', 'Varietas baru berhasil ditambahkan.');
        redirect('admin/Varietas/index.php');
    }
}

$judul = 'Tambah Varietas';
admin_header('Kelola Varietas', 'varietas');
page_header('Tambah Varietas');
require __DIR__ . '/form.php';
admin_footer();
