<?php
/** Tambah ukuran. klikTambah -> tampilkanForm -> submit -> validasiData -> insert */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/ukuran/ukuran.css';

$idUkuran = 0;
$errors = [];
$data   = ['nama_ukuran' => '', 'berat_kg' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('INSERT INTO ukuran (nama_ukuran, berat_kg) VALUES (?, ?)')
            ->execute([$data['nama_ukuran'], $data['berat_kg']]);
        flash('success', 'Ukuran baru berhasil ditambahkan.');
        redirect('admin/ukuran/index.php');
    }
}

$judul = 'Tambah Ukuran';
admin_header('Kelola Ukuran', 'ukuran');
page_header('Tambah Ukuran');
require __DIR__ . '/form.php';
admin_footer();
