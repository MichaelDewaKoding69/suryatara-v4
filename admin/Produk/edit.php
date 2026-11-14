<?php
/**
 * Ubah data produk.
 * klikUbah -> tampilkanForm(data lama) -> submit -> validasiData -> updateProduk -> pesan berhasil / error
 */
require __DIR__ . '/../sidebar.php';
$pageCss = 'admin/Produk/produk.css';

$idProduk = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM produk WHERE id_produk = ?');
$stmt->execute([$idProduk]);
$lama = $stmt->fetch();
if (!$lama) {
    flash('warning', 'Data produk tidak ditemukan.');
    redirect('admin/Produk/index.php');
}

$errors = [];
$data   = $lama;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    require __DIR__ . '/validasi.php';
    $data['foto'] = $lama['foto'];

    if (!$errors) {
        try {
            $fotoBaru = upload_image('foto', 'produk', 'produk', false);
            $pdo->prepare('UPDATE produk SET merek = ?, nama_produk = ?, id_jenis_beras = ?, id_ukuran = ?, id_kadar_patahan = ?,
                           harga = ?, stok = ?, foto = ?, deskripsi = ? WHERE id_produk = ?')
                ->execute([$data['merek'], $data['nama_produk'], $data['id_jenis_beras'], $data['id_ukuran'], $data['id_kadar_patahan'],
                           $data['harga'], $data['stok'], $fotoBaru ?: $lama['foto'], $data['deskripsi'] !== '' ? $data['deskripsi'] : null, $idProduk]);
            flash('success', 'Produk berhasil diperbarui.');
            redirect('admin/Produk/index.php');
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }
}
$data['deskripsi'] = (string)($data['deskripsi'] ?? '');

$judul = 'Ubah Produk';
admin_header('Kelola Produk', 'produk');
page_header('Ubah Produk', $lama['nama_produk']);
require __DIR__ . '/form.php';
admin_footer();
