<?php
/** Form jasa kurir, dipakai oleh tambah.php dan edit.php. */
?>
<div class="panel">
    <div class="panel-head">
        <div><h2><?= h($judul) ?></h2><p>Kolom bertanda <span style="color:var(--danger)">*</span> wajib diisi.</p></div>
        <a href="<?= url('admin/jasa_kurir/index.php') ?>" class="btn-a outline sm">Kembali</a>
    </div>
    <div class="panel-body">
        <?php if ($errors): ?>
            <div class="alert-box danger"><span><strong>Data tidak valid.</strong> <?= h(implode(' ', $errors)) ?></span></div>
        <?php endif; ?>
        <form method="POST">
            <?= csrf_field() ?>
            <div class="form-grid-a">
                <div class="full">
                    <label class="label-a">Nama Kurir <span class="req">*</span></label>
                    <input name="nama_kurir" class="input-a" value="<?= h($data['nama_kurir']) ?>" required>
                    <div class="hint-a">Contoh: JNE, J&amp;T Express, SiCepat</div>
                </div>
            </div>
            <div style="display:flex;gap:8px;margin-top:18px">
                <button class="btn-a">Simpan</button>
                <a href="<?= url('admin/jasa_kurir/index.php') ?>" class="btn-a outline">Batal</a>
            </div>
        </form>
    </div>
</div>
