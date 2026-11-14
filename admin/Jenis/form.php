<?php
/**
 * Form jenis beras, dipakai oleh tambah.php dan edit.php.
 * Pilihan mutu, tekstur, warna, dan bulir diambil dari atribut beras (nilainya tetap).
 */
$pilihan = [
    'id_mutu'     => ['Mutu', $pdo->query('SELECT id_mutu AS id, nama_mutu AS nama FROM mutu ORDER BY id_mutu')->fetchAll()],
    'id_tekstur'  => ['Tekstur', $pdo->query('SELECT id_tekstur AS id, nama_tekstur AS nama FROM tekstur ORDER BY id_tekstur')->fetchAll()],
    'id_warna'    => ['Warna', $pdo->query('SELECT id_warna AS id, nama_warna AS nama FROM warna ORDER BY id_warna')->fetchAll()],
    'id_varietas' => ['Varietas', $pdo->query('SELECT id_varietas AS id, nama_varietas AS nama FROM varietas ORDER BY nama_varietas')->fetchAll()],
    'id_bulir'    => ['Bulir', $pdo->query('SELECT id_bulir AS id, nama_bulir AS nama FROM bulir ORDER BY id_bulir')->fetchAll()],
];
?>
<div class="panel">
    <div class="panel-head">
        <div><h2><?= h($judul) ?></h2><p>Kolom bertanda <span style="color:var(--danger)">*</span> wajib diisi.</p></div>
        <a href="<?= url('admin/Jenis/index.php') ?>" class="btn-a outline sm">Kembali</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert-box danger"><span><strong>Data tidak valid.</strong> <?= h(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-grid-a">
                <div class="full">
                    <label class="label-a">Nama Jenis Beras <span class="req">*</span></label>
                    <input name="nama_jenis" class="input-a" value="<?= h($data['nama_jenis']) ?>" required>
                </div>
                <?php foreach ($pilihan as $kolom => [$label, $opsi]): ?>
                    <div>
                        <label class="label-a"><?= $label ?> <span class="req">*</span></label>
                        <select name="<?= $kolom ?>" class="select-a" required>
                            <option value="">&mdash; Pilih <?= $label ?> &mdash;</option>
                            <?php foreach ($opsi as $o): ?>
                                <option value="<?= (int)$o['id'] ?>" <?= (string)$data[$kolom] === (string)$o['id'] ? 'selected' : '' ?>><?= h($o['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
                <div class="full">
                    <label class="label-a">Deskripsi</label>
                    <textarea name="deskripsi" class="textarea-a"><?= h($data['deskripsi']) ?></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:18px">
                <button class="btn-a">Simpan</button>
                <a href="<?= url('admin/Jenis/index.php') ?>" class="btn-a outline">Batal</a>
            </div>
        </form>
    </div>
</div>
