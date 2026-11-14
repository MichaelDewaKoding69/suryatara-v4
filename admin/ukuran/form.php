<?php
/** Form ukuran, dipakai oleh tambah.php dan edit.php. */
?>
<div class="panel">
    <div class="panel-head">
        <div><h2><?= h($judul) ?></h2><p>Kolom bertanda <span style="color:var(--danger)">*</span> wajib diisi.</p></div>
        <a href="<?= url('admin/ukuran/index.php') ?>" class="btn-a outline sm">Kembali</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert-box danger"><span><strong>Data tidak valid.</strong> <?= h(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-grid-a">
                <div>
                    <label class="label-a">Nama Ukuran <span class="req">*</span></label>
                    <input name="nama_ukuran" class="input-a" value="<?= h($data['nama_ukuran']) ?>" required>
                    <div class="hint-a">Contoh: 5 kg</div>
                </div>
                <div>
                    <label class="label-a">Berat (kg) <span class="req">*</span></label>
                    <input name="berat_kg" type="number" min="0" step="0.01" class="input-a" value="<?= h($data['berat_kg']) ?>" required>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:18px">
                <button class="btn-a">Simpan</button>
                <a href="<?= url('admin/ukuran/index.php') ?>" class="btn-a outline">Batal</a>
            </div>
        </form>
    </div>
</div>
