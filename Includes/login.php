<?php
/**
 * Activity & Sequence "Login" (Pelanggan / Admin / Owner):
 *   getAkunByEmail -> validasiPassword -> buatSesi(role) -> arahkan sesuai peran
 */
require_once __DIR__ . '/../config/koneksi.php';

if (is_admin() || is_owner() || is_pelanggan()) {
    redirect(home_for_role());
}

$email = trim((string)($_POST['email'] ?? ''));
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $password = (string)($_POST['password'] ?? '');
    // getAkunByEmail(email) -> Data User + Role
    $stmt = $pdo->prepare('SELECT u.id_user AS id, u.nama, u.password, r.nama_role FROM `user` u JOIN role r ON r.id_role = u.id_role WHERE u.email = ?');
    $stmt->execute([$email]);
    $akun = $stmt->fetch();
    $role = $akun ? strtolower($akun['nama_role']) : null;

    if ($akun && password_verify($password, $akun['password'])) {
        session_regenerate_id(true);
        $_SESSION['role']    = $role;
        $_SESSION['user_id'] = (int)$akun['id'];
        $_SESSION['nama']    = $akun['nama'];
        flash('success', 'Selamat datang, ' . $akun['nama'] . '!');
        if ($role !== 'pelanggan') {
            redirect(home_for_role());     // Admin: dashboard, Owner: laporan keuangan
        }
        $next = (string)($_GET['next'] ?? '');
        if ($next !== '' && strpos($next, '/') === 0 && strpos($next, '//') !== 0) {
            header('Location: ' . $next);
            exit;
        }
        redirect('index.php');
    }
    $error = 'Email atau password salah.';
}

$pageTitle = 'Masuk';
$pageCss = 'Includes/css/login.css';
require __DIR__ . '/auth-head.php';
?>
<div class="auth-page">
    <div class="auth-form-area">
        <div class="auth-container">
            <a href="<?= url('index.php') ?>" class="back-home">&larr; Kembali ke toko</a>
            <div class="auth-brand"><img src="<?= asset('images/logo.png') ?>" alt="Logo"></div>
            <div class="auth-heading">
                <h1>Masuk</h1>
                <p>Masuk dengan email dan password akun Anda.</p>
            </div>
            <?= str_replace('flash-wrap', 'flash-wrap" style="width:100%;margin:0 0 10px', flash_render()) ?>
            <?php if ($error): ?>
                <div class="alert-box danger"><span><?= h($error) ?></span></div>
            <?php endif; ?>
            <form method="POST">
                <?= csrf_field() ?>
                <div style="margin-bottom:14px">
                    <label class="form-label-x" for="email">Email</label>
                    <input type="email" id="email" name="email" class="input-x" value="<?= h($email) ?>" required autofocus>
                </div>
                <div style="margin-bottom:18px">
                    <label class="form-label-x" for="password">Password</label>
                    <div class="pw-wrap"><input type="password" id="password" name="password" class="input-x" required>
                        <button type="button" class="pw-toggle">tampilkan</button></div>
                </div>
                <button class="btn-brown btn-block">Masuk</button>
            </form>
            <div class="auth-foot">Belum punya akun? <a href="<?= url('Includes/register.php') ?>">Daftar di sini</a></div>
        </div>
    </div>
</div>
<script src="<?= asset('Includes/js/script.js') ?>"></script>
</body>
</html>
