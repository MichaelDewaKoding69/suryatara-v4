<?php
/**
 * Tambah produk baru.
 * klikTambah -> tampilkanForm -> submit -> validasiData -> insertProduk -> pesan berhasil / error
 */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Produk/produk.css';

$idProduk = 0;
$errors   = [];
$data     = ['merek' => '', 'nama_produk' => '', 'id_jenis_beras' => '', 'id_ukuran' => '', 'id_kadar_patahan' => '',
             'harga' => '', 'stok' => '', 'foto' => '', 'deskripsi' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';

    if (!$errors) {
        try {
            $foto = upload_image('foto', 'produk', 'produk', false);
            $pdo->prepare('INSERT INTO produk (merek, nama_produk, id_jenis_beras, id_ukuran, id_kadar_patahan, harga, stok, foto, deskripsi)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$data['merek'], $data['nama_produk'], $data['id_jenis_beras'], $data['id_ukuran'], $data['id_kadar_patahan'],
                           $data['harga'], $data['stok'], $foto, $data['deskripsi'] !== '' ? $data['deskripsi'] : null]);
            flash('success', 'Produk baru berhasil ditambahkan.');
            redirect('admin/Produk/index.php');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
    $data['foto'] = '';
}

$judul = 'Tambah Produk';
admin_header('Kelola Produk', 'produk');
page_header('Tambah Produk', 'Isi data produk beras yang akan dijual.');
require __DIR__ . '/form.php';
admin_footer();
