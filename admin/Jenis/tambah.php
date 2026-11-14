<?php
/** Tambah jenis beras. klikTambah -> tampilkanForm -> submit -> validasiData -> insertJenisBeras */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Jenis/jenis-beras.css';

$idJenis = 0;
$errors  = [];
$data    = ['nama_jenis' => '', 'id_mutu' => '', 'id_tekstur' => '', 'id_warna' => '', 'id_varietas' => '', 'id_bulir' => '', 'deskripsi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('INSERT INTO jenis_beras (nama_jenis, id_mutu, id_tekstur, id_warna, id_varietas, id_bulir, deskripsi) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([$data['nama_jenis'], $data['id_mutu'], $data['id_tekstur'], $data['id_warna'], $data['id_varietas'], $data['id_bulir'],
                       $data['deskripsi'] !== '' ? $data['deskripsi'] : null]);
        flash('success', 'Jenis beras baru berhasil ditambahkan.');
        redirect('admin/Jenis/index.php');
    }
}

$judul = 'Tambah Jenis Beras';
admin_header('Kelola Jenis Beras', 'jenis_beras');
page_header('Tambah Jenis Beras', 'Pilih kombinasi mutu, tekstur, warna, varietas, dan bulir.');
require __DIR__ . '/form.php';
admin_footer();
