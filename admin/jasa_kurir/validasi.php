<?php
/** Validasi data jasa kurir dari $_POST (tambah.php dan edit.php). $idKurir = 0 kalau tambah. */
$data = [
    'nama_kurir' => trim((string)($_POST['nama_kurir'] ?? '')),
];
$errors = [];

if ($data['nama_kurir'] === '') {
    $errors[] = 'Nama Kurir wajib diisi.';
} else {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM jasa_kurir WHERE LOWER(nama_kurir) = LOWER(?) AND id_jasa_kurir <> ?');
    $cek->execute([$data['nama_kurir'], $idKurir]);
    if ($cek->fetchColumn() > 0) {
        $errors[] = 'Nama Kurir "' . $data['nama_kurir'] . '" sudah ada.';
    }
}
