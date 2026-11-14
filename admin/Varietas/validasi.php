<?php
/** Validasi data varietas dari $_POST (tambah.php dan edit.php). $idVarietas = 0 kalau tambah. */
$data = [
    'nama_varietas' => trim((string)($_POST['nama_varietas'] ?? '')),
    'deskripsi'     => trim((string)($_POST['deskripsi'] ?? '')),
];
$errors = [];

if ($data['nama_varietas'] === '') {
    $errors[] = 'Nama Varietas wajib diisi.';
} else {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM varietas WHERE LOWER(nama_varietas) = LOWER(?) AND id_varietas <> ?');
    $cek->execute([$data['nama_varietas'], $idVarietas]);
    if ($cek->fetchColumn() > 0) {
        $errors[] = 'Nama Varietas "' . $data['nama_varietas'] . '" sudah ada.';
    }
}
