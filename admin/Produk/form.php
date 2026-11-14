<?php
/**
 * Form produk, dipakai bersama oleh tambah.php dan edit.php.
 * Variabel yang harus ada: $data (nilai form), $errors, $judul, $idProduk (0 = tambah)
 */
$jenisList   = $pdo->query('SELECT id_jenis_beras, nama_jenis FROM jenis_beras ORDER BY nama_jenis')->fetchAll();
$ukuranList  = $pdo->query('SELECT id_ukuran, nama_ukuran FROM ukuran ORDER BY berat_kg')->fetchAll();
$patahanList = $pdo->query('SELECT id_kadar_patahan, nama_kadar_patahan FROM kadar_patahan ORDER BY id_kadar_patahan')->fetchAll();
?>
<div class="panel">
    <div class="panel-head">
        <div><h2><?= h($judul) ?></h2><p>Kolom bertanda <span style="color:var(--danger)">*</span> wajib diisi.</p></div>
        <a href="<?= url('admin/Produk/index.php') ?>" class="btn-a outline sm">Kembali</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert-box danger"><span><strong>Data tidak valid.</strong> <?= h(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="form-grid-a">
                <div>
                    <label class="label-a">Merek <span class="req">*</span></label>
                    <input name="merek" class="input-a" value="<?= h($data['merek']) ?>" required>
                    <div class="hint-a">Contoh: Rojo Lele, Pusako Rancak</div>
                </div>
                <div>
                    <label class="label-a">Nama Produk <span class="req">*</span></label>
                    <input name="nama_produk" class="input-a" value="<?= h($data['nama_produk']) ?>" required>
                </div>
                <div>
                    <label class="label-a">Jenis Beras <span class="req">*</span></label>
                    <select name="id_jenis_beras" class="select-a" required>
                        <option value="">&mdash; Pilih Jenis Beras &mdash;</option>
                        <?php foreach ($jenisList as $o): ?>
                            <option value="<?= (int)$o['id_jenis_beras'] ?>" <?= (string)$data['id_jenis_beras'] === (string)$o['id_jenis_beras'] ? 'selected' : '' ?>><?= h($o['nama_jenis']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label-a">Ukuran <span class="req">*</span></label>
                    <select name="id_ukuran" class="select-a" required>
                        <option value="">&mdash; Pilih Ukuran &mdash;</option>
                        <?php foreach ($ukuranList as $o): ?>
                            <option value="<?= (int)$o['id_ukuran'] ?>" <?= (string)$data['id_ukuran'] === (string)$o['id_ukuran'] ? 'selected' : '' ?>><?= h($o['nama_ukuran']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label-a">Kadar Patahan <span class="req">*</span></label>
                    <select name="id_kadar_patahan" class="select-a" required>
                        <option value="">&mdash; Pilih Kadar Patahan &mdash;</option>
                        <?php foreach ($patahanList as $o): ?>
                            <option value="<?= (int)$o['id_kadar_patahan'] ?>" <?= (string)$data['id_kadar_patahan'] === (string)$o['id_kadar_patahan'] ? 'selected' : '' ?>><?= h($o['nama_kadar_patahan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="label-a">Harga (Rp) <span class="req">*</span></label>
                    <input name="harga" type="number" min="0" step="1" class="input-a" value="<?= h($data['harga'] === '' ? '' : (int)$data['harga']) ?>" required>
                </div>
                <div>
                    <label class="label-a">Stok <span class="req">*</span></label>
                    <input name="stok" type="number" min="0" step="1" class="input-a" value="<?= h($data['stok'] === '' ? '' : (int)$data['stok']) ?>" required>
                </div>
                <div>
                    <label class="label-a">Foto Produk</label>
                    <div style="display:flex;gap:14px;align-items:center">
                        <img id="imgPrev" src="<?= upload_url('produk', $data['foto'] ?: null) ?>" class="img-preview-a" alt="" data-zoom>
                        <div style="flex:1">
                            <input type="file" name="foto" class="input-a" accept=".jpg,.jpeg,.png,.webp,image/*" style="padding-top:8px"
                                   onchange="if(this.files[0]){document.getElementById('imgPrev').src=window.URL.createObjectURL(this.files[0])}">
                            <div class="hint-a">JPG/PNG/WEBP, maks <?= MAKS_UPLOAD_MB ?> MB.<?= $idProduk ? ' Kosongkan jika tidak diganti.' : '' ?></div>
                        </div>
                    </div>
                </div>
                <div class="full">
                    <label class="label-a">Deskripsi</label>
                    <textarea name="deskripsi" class="textarea-a"><?= h($data['deskripsi']) ?></textarea>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:18px">
                <button class="btn-a">Simpan</button>
                <a href="<?= url('admin/Produk/index.php') ?>" class="btn-a outline">Batal</a>
            </div>
        </form>
    </div>
</div>
