<?php
/** Validasi data jenis beras dari $_POST (tambah.php dan edit.php). $idJenis = 0 kalau tambah. */
$data = [
    'nama_jenis'  => trim((string)($_POST['nama_jenis'] ?? '')),
    'id_mutu'     => trim((string)($_POST['id_mutu'] ?? '')),
    'id_tekstur'  => trim((string)($_POST['id_tekstur'] ?? '')),
    'id_warna'    => trim((string)($_POST['id_warna'] ?? '')),
    'id_varietas' => trim((string)($_POST['id_varietas'] ?? '')),
    'id_bulir'    => trim((string)($_POST['id_bulir'] ?? '')),
    'deskripsi'   => trim((string)($_POST['deskripsi'] ?? '')),
];
$errors = [];

if ($data['nama_jenis'] === '') {
    $errors[] = 'Nama Jenis Beras wajib diisi.';
} else {
    $cek = $pdo->prepare('SELECT COUNT(*) FROM jenis_beras WHERE LOWER(nama_jenis) = LOWER(?) AND id_jenis_beras <> ?');
    $cek->execute([$data['nama_jenis'], $idJenis]);
    if ($cek->fetchColumn() > 0) {
        $errors[] = 'Nama Jenis Beras "' . $data['nama_jenis'] . '" sudah ada.';
    }
}

$tabel = ['id_mutu' => ['Mutu', 'mutu'], 'id_tekstur' => ['Tekstur', 'tekstur'], 'id_warna' => ['Warna', 'warna'],
          'id_varietas' => ['Varietas', 'varietas'], 'id_bulir' => ['Bulir', 'bulir']];
foreach ($tabel as $kolom => [$label, $nama]) {
    if ($data[$kolom] === '') {
        $errors[] = "$label wajib diisi.";
        continue;
    }
    $cek = $pdo->prepare("SELECT COUNT(*) FROM $nama WHERE $kolom = ?");
    $cek->execute([(int)$data[$kolom]]);
    if (!$cek->fetchColumn()) {
        $errors[] = "Pilihan $label tidak valid.";
    }
}
