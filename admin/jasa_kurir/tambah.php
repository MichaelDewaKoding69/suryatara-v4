<?php
/** Tambah jasa kurir. klikTambah -> tampilkanForm -> submit -> validasiData -> insert */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/jasa_kurir/jasakurir.css';

$idKurir = 0;
$errors = [];
$data   = ['nama_kurir' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    if (!$errors) {
        $pdo->prepare('INSERT INTO jasa_kurir (nama_kurir) VALUES (?)')
            ->execute([$data['nama_kurir']]);
        flash('success', 'Jasa Kurir baru berhasil ditambahkan.');
        redirect('admin/jasa_kurir/index.php');
    }
}

$judul = 'Tambah Jasa Kurir';
admin_header('Kelola Jasa Kurir', 'jasa_kurir');
page_header('Tambah Jasa Kurir');
require __DIR__ . '/form.php';
admin_footer();
