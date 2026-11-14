<?php
/**
 * INSTALASI DATABASE - membuat ulang database `db_suryatara_v3` dari database/db_suryatara_v3.sql
 * Bisa dijalankan dari komputer sendiri (localhost), atau di hosting (Railway) dengan
 * install.php?token=... sesuai variabel INSTALL_TOKEN. Semua data lama di database ini akan diganti.
 */
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
$token  = getenv('INSTALL_TOKEN') ?: '';
$lokal  = in_array($remote, ['127.0.0.1', '::1'], true) && !getenv('MYSQLHOST');
$bolehToken = $token !== '' && hash_equals($token, (string)($_GET['token'] ?? $_POST['token'] ?? ''));
if (!$lokal && !$bolehToken) {
    http_response_code(403);
    die('Instalasi hanya dapat dijalankan dari localhost, atau dengan token instalasi yang benar.');
}

define('RB_INSTALLER', true);
$hasil = [];
$error = '';
$selesai = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['konfirmasi'] ?? '') === 'ya') {
    try {
        require __DIR__ . '/config/koneksi.php';   // menghasilkan $pdo tanpa database terpilih
        $sql = file_get_contents(__DIR__ . '/database/db_suryatara_v3.sql');
        if ($sql === false) {
            throw new RuntimeException('File database/db_suryatara_v3.sql tidak ditemukan.');
        }
        // di hosting nama database bisa berbeda (mis. "railway"), jadi nama di file SQL disesuaikan
        $sql = str_replace('`db_suryatara_v3`', '`' . $DB_NAME . '`', $sql);
        // buang baris komentar, lalu pecah per pernyataan (setiap pernyataan diakhiri ";" di akhir baris)
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = array_filter(array_map('trim', preg_split('/;\s*(\r?\n|$)/', $sql)), 'strlen');
        foreach ($statements as $i => $st) {
            try {
                $pdo->exec($st);
            } catch (PDOException $e) {
                throw new RuntimeException('Gagal pada pernyataan ke-' . ($i + 1) . ":\n" . mb_substr($st, 0, 400) . "\n\nPesan MySQL: " . $e->getMessage());
            }
        }
        $pdo->exec('USE `' . $DB_NAME . '`');
        foreach (['role', 'user', 'produk', 'pesanan', 'pesanan_produk', 'pembayaran', 'pengiriman', 'retur', 'foto_retur'] as $t) {
            $hasil[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        }
        $selesai = true;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Instalasi Database</title>
    <style>
        body { margin: 0; background: #f5f0e5; font-family: Arial, sans-serif; color: #3f3028; }
        .box { max-width: 680px; margin: 50px auto; background: #fbf8f1; border: 1px solid #ded4c2; border-radius: 14px; padding: 28px; }
        h1 { margin: 0 0 8px; color: #5c3b2e; font-family: Georgia, serif; }
        .btn { display: inline-block; border: 0; background: #5c3b2e; color: #fff; padding: 12px 20px; border-radius: 8px; font-weight: bold; font-size: 14px; cursor: pointer; text-decoration: none; }
        .warn { background: #f7efdf; border: 1px solid #e7d3ae; color: #85602a; padding: 12px 14px; border-radius: 10px; }
        .ok { background: #edf3e9; border: 1px solid #cadabd; color: #4e6b40; padding: 12px 14px; border-radius: 10px; }
        .err { background: #f8e9e5; border: 1px solid #e6c4bb; color: #914d40; padding: 12px 14px; border-radius: 10px; white-space: pre-wrap; font-family: Consolas, monospace; font-size: 12px; }
        table { border-collapse: collapse; margin: 12px 0; } td { padding: 4px 14px 4px 0; }
    </style>
</head>
<body>
<div class="box">
    <h1>Instalasi Database</h1>
    <p>Halaman ini menjalankan <code>database/db_suryatara_v3.sql</code> untuk membuat ulang database <strong>db_suryatara_v3</strong> sesuai ERD terbaru beserta data contoh.</p>

    <?php if ($selesai): ?>
        <div class="ok"><strong>Berhasil!</strong> Database sudah dibuat ulang.</div>
        <table><?php foreach ($hasil as $t => $n): ?><tr><td><?= htmlspecialchars($t) ?></td><td><strong><?= $n ?></strong> baris</td></tr><?php endforeach; ?></table>
        <p>Akun contoh: <strong>owner@suryatara.com / owner123</strong>, <strong>admin@suryatara.com / admin123</strong>, <strong>budi@gmail.com / pelanggan123</strong></p>
        <a class="btn" href="index.php">Buka Website</a>
    <?php else: ?>
        <?php if ($error): ?>
            <p><strong>Instalasi gagal.</strong> Kirimkan pesan berikut jika butuh bantuan:</p>
            <div class="err"><?= htmlspecialchars($error) ?></div><br>
        <?php endif; ?>
        <div class="warn"><strong>Perhatian:</strong> semua tabel dan data di database <code>db_suryatara_v3</code> akan dihapus dan diganti dengan data contoh.</div>
        <form method="POST" style="margin-top:18px">
            <input type="hidden" name="konfirmasi" value="ya">
            <button class="btn" onclick="return confirm('Buat ulang database db_suryatara_v3? Data lama akan diganti.')">Buat Ulang Database</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
