<?php
/** Validasi data ukuran dari $_POST (tambah.php dan edit.php). $idUkuran = 0 kalau tambah. */
$data = [
    'nama_ukuran' => trim((string)($_POST['nama_ukuran'] ?? '')),
    'berat_kg'      => str_replace(',', '.', trim((string)($_POST['berat_kg'] ?? ''))),
];
$errors = [];

if ($data['nama_ukuran'] === '') {
    $errors[] = 'Nama Ukuran wajib diisi.';
} else {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM ukuran WHERE LOWER(nama_ukuran) = LOWER(?) AND id_ukuran <> ?');
    $cek->execute([$data['nama_ukuran'], $idUkuran]);
    if ($cek->fetchColumn() > 0) {
        $errors[] = 'Nama Ukuran "' . $data['nama_ukuran'] . '" sudah ada.';
    }
}

if ($data['berat_kg'] === '') {
    $errors[] = 'Berat (kg) wajib diisi.';
} elseif (!is_numeric($data['berat_kg']) || (float)$data['berat_kg'] < 0) {
    $errors[] = 'Berat (kg) harus berupa angka.';
}
