<?php
/**
 * Validasi data produk dari $_POST, dipakai oleh tambah.php dan edit.php.
 * Mengisi $data dan $errors. $idProduk = id produk yang sedang diubah (0 kalau tambah).
 */
$data = [
    'merek'            => trim((string)($_POST['merek'] ?? '')),
    'nama_produk'      => trim((string)($_POST['nama_produk'] ?? '')),
    'id_jenis_beras'   => trim((string)($_POST['id_jenis_beras'] ?? '')),
    'id_ukuran'        => trim((string)($_POST['id_ukuran'] ?? '')),
    'id_kadar_patahan' => trim((string)($_POST['id_kadar_patahan'] ?? '')),
    'harga'            => trim((string)($_POST['harga'] ?? '')),
    'stok'             => trim((string)($_POST['stok'] ?? '')),
    'deskripsi'        => trim((string)($_POST['deskripsi'] ?? '')),
];
$errors = [];

if ($data['merek'] === '') {
    $errors[] = 'Merek wajib diisi.';
}
if ($data['nama_produk'] === '') {
    $errors[] = 'Nama Produk wajib diisi.';
} else {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE LOWER(nama_produk) = LOWER(?) AND id_produk <> ?');
    $cek->execute([$data['nama_produk'], $idProduk]);
    if ($cek->fetchColumn() > 0) {
        $errors[] = 'Nama Produk "' . $data['nama_produk'] . '" sudah ada.';
    }
}

$pilihan = [
    'id_jenis_beras'   => ['Jenis Beras', 'SELECT COUNT(*) FROM jenis_beras WHERE id_jenis_beras = ?'],
    'id_ukuran'        => ['Ukuran', 'SELECT COUNT(*) FROM ukuran WHERE id_ukuran = ?'],
    'id_kadar_patahan' => ['Kadar Patahan', 'SELECT COUNT(*) FROM kadar_patahan WHERE id_kadar_patahan = ?'],
];
foreach ($pilihan as $kolom => [$label, $sqlCek]) {
    if ($data[$kolom] === '') {
        $errors[] = "$label wajib diisi.";
        continue;
    }
    $cek = $pdo->prepare($sqlCek);
    $cek->execute([(int)$data[$kolom]]);
    if (!$cek->fetchColumn()) {
        $errors[] = "Pilihan $label tidak valid.";
    }
}

foreach (['harga' => 'Harga (Rp)', 'stok' => 'Stok'] as $kolom => $label) {
    if ($data[$kolom] === '') {
        $errors[] = "$label wajib diisi.";
    } elseif (!ctype_digit($data[$kolom])) {
        $errors[] = "$label harus berupa angka bulat positif.";
    }
}
