<?php
/**
 * Activity & Sequence "Registrasi Akun":
 *   isiData(nama, email, password, noHp, alamat, kota) -> validasiData -> getPelangganByEmail -> insertPelanggan
 */
require_once __DIR__ . '/../config/koneksi.php';

if (is_pelanggan() || is_admin() || is_owner()) {
    redirect(home_for_role());
}

$f = [];
foreach (['nama', 'email', 'no_hp', 'alamat', 'kota'] as $k) {
    $f[$k] = trim((string)($_POST[$k] ?? ''));
}
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pw  = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password2'] ?? '');

    if (mb_strlen($f['nama']) < 3) $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if (!preg_match('/^(\+62|62|0)8[0-9]{7,12}$/', preg_replace('/[\s-]/', '', $f['no_hp']))) $errors[] = 'Nomor HP tidak valid (contoh: 081234567890).';
    if (mb_strlen($f['alamat']) < 10) $errors[] = 'Alamat minimal 10 karakter.';
    if ($f['kota'] === '') $errors[] = 'Kota wajib diisi.';
    if (strlen($pw) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($pw !== $pw2) $errors[] = 'Konfirmasi password tidak sama.';

    if (!$errors) {
        // getPelangganByEmail(email)
        $stmt = $pdo->prepare('SELECT 1 FROM `user` WHERE email = ?');
        $stmt->execute([$f['email']]);
        if ($stmt->fetch()) {
            $errors[] = 'Email sudah terdaftar. Silakan login.';
        }
    }

    if (!$errors) {
        // insertPelanggan(data) -> tabel user dengan role Pelanggan
        $pdo->prepare('INSERT INTO `user` (id_role, nama, email, password, no_telepon, alamat, kota) VALUES (?, ?, ?, ?, ?, ?, ?)')
            ->execute([role_id('Pelanggan'), $f['nama'], $f['email'], password_hash($pw, PASSWORD_DEFAULT), preg_replace('/[\s-]/', '', $f['no_hp']), $f['alamat'], $f['kota']]);
        flash('success', 'Registrasi berhasil! Silakan login menggunakan akun Anda.');
        redirect('Includes/login.php');
    }
}

$pageTitle = 'Daftar';
$pageCss = 'Includes/css/register.css';
require __DIR__ . '/auth-head.php';
?>
<div class="auth-page">
    <div class="auth-form-area">
        <div class="auth-container" style="max-width:500px">
            <a href="<?= url('index.php') ?>" class="back-home">&larr; Kembali ke toko</a>
            <div class="auth-heading">
                <h1>Daftar akun</h1>
                <p>Alamat dipakai sebagai alamat kirim default dan bisa diganti saat checkout.</p>
            </div>
            <?php if ($errors): ?>
                <div class="alert-box danger"><span><?= implode('<br>', array_map('h', $errors)) ?></span></div>
            <?php endif; ?>
            <form method="POST" class="form-grid">
                <?= csrf_field() ?>
                <div class="full"><label class="form-label-x">Nama lengkap</label><input name="nama" class="input-x" value="<?= h($f['nama']) ?>" required></div>
                <div><label class="form-label-x">Email</label><input type="email" name="email" class="input-x" value="<?= h($f['email']) ?>" required></div>
                <div><label class="form-label-x">No. HP</label><input name="no_hp" class="input-x" value="<?= h($f['no_hp']) ?>" placeholder="08xxxxxxxxxx" required></div>
                <div class="full"><label class="form-label-x">Alamat</label><textarea name="alamat" class="textarea-x" style="min-height:70px" required><?= h($f['alamat']) ?></textarea></div>
                <div class="full"><label class="form-label-x">Kota</label><input name="kota" class="input-x" value="<?= h($f['kota']) ?>" required></div>
                <div><label class="form-label-x">Password</label><div class="pw-wrap"><input type="password" name="password" class="input-x" minlength="6" required><button type="button" class="pw-toggle">tampilkan</button></div></div>
                <div><label class="form-label-x">Ulangi password</label><input type="password" name="password2" class="input-x" minlength="6" required></div>
                <div class="full"><button class="btn-brown btn-block">Daftar</button></div>
            </form>
            <div class="auth-foot">Sudah punya akun? <a href="<?= url('Includes/login.php') ?>">Masuk</a></div>
        </div>
    </div>
</div>
<script src="<?= asset('Includes/js/script.js') ?>"></script>
</body>
</html>
