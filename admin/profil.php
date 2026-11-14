<?php
require __DIR__ . '/sidebar.php';
$pageCss = 'admin/CSS/profil.css';

$st = $pdo->prepare('SELECT * FROM `user` WHERE id_user = ?');
$st->execute([user_id()]);
$admin = $st->fetch();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $nama  = trim((string)($_POST['nama'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $hp    = trim((string)($_POST['no_hp'] ?? ''));
    $lama  = (string)($_POST['password_lama'] ?? '');
    $baru  = (string)($_POST['password_baru'] ?? '');
    $ulang = (string)($_POST['password_konfirmasi'] ?? '');
    if (mb_strlen($nama) < 3) $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid.';
    $cek = $pdo->prepare('SELECT COUNT(*) FROM `user` WHERE email = ? AND id_user <> ?');
    $cek->execute([$email, user_id()]);
    if ($cek->fetchColumn()) $errors[] = 'Email sudah dipakai akun lain.';
    if ($baru !== '' || $ulang !== '') {
        if (!password_verify($lama, $admin['password'])) $errors[] = 'Password lama salah.';
        if (strlen($baru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
        if ($baru !== $ulang) $errors[] = 'Konfirmasi password baru tidak sama.';
    }
    if (!$errors) {
        $pdo->prepare('UPDATE `user` SET nama = ?, email = ?, no_telepon = ? WHERE id_user = ?')->execute([$nama, $email, $hp ?: null, user_id()]);
        if ($baru !== '') {
            $pdo->prepare('UPDATE `user` SET password = ? WHERE id_user = ?')->execute([password_hash($baru, PASSWORD_DEFAULT), user_id()]);
        }
        $_SESSION['nama'] = $nama;
        flash('success', 'Profil berhasil diperbarui.');
        redirect('admin/profil.php');
    }
    $admin = array_merge($admin, ['nama' => $nama, 'email' => $email, 'no_telepon' => $hp]);
}

admin_header('Profil Saya', 'profil');
page_header('Profil Saya', 'Ubah data akun dan password.');
?>
<div class="panel" style="max-width:720px">
    <div class="panel-body">
        <?php if ($errors): ?><div class="alert-box danger"><span><?= h(implode(' ', $errors)) ?></span></div><?php endif; ?>
        <form method="POST" class="form-grid-a">
            <?= csrf_field() ?>
            <div class="full"><label class="label-a">Nama</label><input name="nama" class="input-a" value="<?= h($admin['nama']) ?>" required></div>
            <div><label class="label-a">Email</label><input type="email" name="email" class="input-a" value="<?= h($admin['email']) ?>" required></div>
            <div><label class="label-a">No. HP</label><input name="no_hp" class="input-a" value="<?= h($admin['no_telepon']) ?>"></div>
            <div class="full" style="border-top:1px dashed var(--border);padding-top:12px;color:var(--text-secondary)">Ganti password (kosongkan jika tidak diganti)</div>
            <div class="full"><label class="label-a" for="password_lama">Password lama</label><input type="password" name="password_lama" id="password_lama" class="input-a" autocomplete="current-password"></div>
            <div><label class="label-a" for="password_baru">Password baru</label><input type="password" name="password_baru" id="password_baru" class="input-a" minlength="6" autocomplete="new-password"></div>
            <div><label class="label-a" for="password_konfirmasi">Konfirmasi password baru</label><input type="password" name="password_konfirmasi" id="password_konfirmasi" class="input-a" autocomplete="new-password"></div>
            <div class="full"><button class="btn-a">Simpan</button></div>
        </form>
    </div>
</div>
<script>
// Cek langsung di browser: konfirmasi harus sama, dan password lama wajib diisi kalau ganti password
(function () {
    var lama = document.getElementById('password_lama');
    var baru = document.getElementById('password_baru');
    var ulang = document.getElementById('password_konfirmasi');
    function cek() {
        var ganti = baru.value !== '' || ulang.value !== '';
        lama.required = ganti;
        baru.required = ganti;
        ulang.setCustomValidity(ulang.value !== baru.value ? 'Konfirmasi password baru tidak sama.' : '');
    }
    baru.addEventListener('input', cek);
    ulang.addEventListener('input', cek);
})();
</script>
<?php admin_footer(); ?>
