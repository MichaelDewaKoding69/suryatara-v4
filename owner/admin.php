<?php
/**
 * Activity & Sequence "Menambahkan Admin" (aktor: Owner):
 *   bukaMenuKelolaAdmin -> getUserByRole('admin') -> tampilkanDaftarAdmin -> klikTambahAdmin -> tampilkanForm
 *   -> submit(nama, email, password) -> validasiData + getUserByEmail(email) -> getRoleByNama('admin')
 *   -> insertUser(data, idRole) -> tampilkanPesanBerhasil / tampilkanPesanError
 */
require __DIR__ . '/sidebar.php';
$pageCss = 'owner/CSS/admin.css';

$f = ['nama' => '', 'email' => '', 'no_telepon' => ''];
$errors = [];
$tambah = isset($_GET['tambah']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $tambah = true;
    foreach ($f as $k => $v) {
        $f[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $pw  = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password2'] ?? '');

    // validasiData()
    if (mb_strlen($f['nama']) < 3) $errors[] = 'Nama minimal 3 karakter.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Format email tidak valid.';
    if ($f['no_telepon'] !== '' && !preg_match('/^[0-9+\-\s]{8,20}$/', $f['no_telepon'])) $errors[] = 'Nomor telepon tidak valid.';
    if (strlen($pw) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($pw !== $pw2) $errors[] = 'Konfirmasi password tidak sama.';
    // getUserByEmail(email)
    if (!$errors) {
        $st = $pdo->prepare('SELECT COUNT(*) FROM `user` WHERE email = ?');
        $st->execute([$f['email']]);
        if ($st->fetchColumn() > 0) {
            $errors[] = 'Email sudah terdaftar.';
        }
    }
    if (!$errors) {
        // getRoleByNama('admin') -> insertUser(data, idRole)
        $pdo->prepare('INSERT INTO `user` (id_role, nama, email, password, no_telepon) VALUES (?, ?, ?, ?, ?)')
            ->execute([role_id('Admin'), $f['nama'], $f['email'], password_hash($pw, PASSWORD_DEFAULT), $f['no_telepon'] ?: null]);
        flash('success', 'Admin "' . $f['nama'] . '" berhasil ditambahkan. Admin dapat login dengan email ' . $f['email'] . '.');
        redirect('owner/admin.php');
    }
}

// getUserByRole('admin')
$st = $pdo->prepare('SELECT u.* FROM `user` u JOIN role r ON r.id_role = u.id_role WHERE r.nama_role = ? ORDER BY u.id_user');
$st->execute(['Admin']);
$admins = $st->fetchAll();

admin_header('Kelola Admin', 'kelola_admin');
page_header('Kelola Admin', 'Daftar akun admin toko. Owner dapat menambahkan admin baru.',
    $tambah ? '' : '<a href="' . url('owner/admin.php?tambah=1') . '" class="btn-a">Tambah Admin</a>');
?>

<?php if ($tambah): ?>
    <div class="panel" style="margin-bottom:18px;max-width:760px">
        <div class="panel-head">
            <div><h2>Tambah Admin</h2><p>Akun baru otomatis mendapat role <strong>Admin</strong>.</p></div>
            <a href="<?= url('owner/admin.php') ?>" class="btn-a outline sm">Batal</a>
        </div>
        <div class="panel-body">
            <?php if ($errors): ?>
                <div class="alert-box danger"><span><strong>Data tidak valid / email sudah terdaftar.</strong> <?= h(implode(' ', $errors)) ?></span></div>
            <?php endif; ?>
            <form method="POST" action="<?= url('owner/admin.php') ?>" class="form-grid-a">
                <?= csrf_field() ?>
                <div class="full"><label class="label-a">Nama <span class="req">*</span></label><input name="nama" class="input-a" value="<?= h($f['nama']) ?>" required></div>
                <div><label class="label-a">Email <span class="req">*</span></label><input type="email" name="email" class="input-a" value="<?= h($f['email']) ?>" required></div>
                <div><label class="label-a">No. Telepon</label><input name="no_telepon" class="input-a" value="<?= h($f['no_telepon']) ?>"></div>
                <div><label class="label-a">Password <span class="req">*</span></label><input type="password" name="password" class="input-a" minlength="6" required></div>
                <div><label class="label-a">Ulangi Password <span class="req">*</span></label><input type="password" name="password2" class="input-a" minlength="6" required></div>
                <div class="full"><button class="btn-a">Simpan</button></div>
            </form>
        </div>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-head"><div><h2>Daftar Admin</h2><p><?= count($admins) ?> admin</p></div></div>
    <div class="table-wrap"><table class="table-admin">
        <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>No. Telepon</th><th>Role</th></tr></thead>
        <tbody>
        <?php if (!$admins): ?><tr><td colspan="5"><div class="empty-a">Belum ada admin.</div></td></tr><?php endif; ?>
        <?php foreach ($admins as $i => $a): ?>
            <tr>
                <td style="color:var(--text-muted)"><?= $i + 1 ?></td>
                <td><div style="display:flex;gap:10px;align-items:center"><div class="admin-avatar" style="width:34px;height:34px;font-weight:800"><?= h(mb_strtoupper(mb_substr($a['nama'], 0, 1))) ?></div><strong><?= h($a['nama']) ?></strong></div></td>
                <td><?= h($a['email']) ?></td>
                <td><?= h($a['no_telepon'] ?: '-') ?></td>
                <td><?= badge('Admin') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table></div>
</div>

<?php admin_footer(); ?>
