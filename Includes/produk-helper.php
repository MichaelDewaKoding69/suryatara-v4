<?php
/**
 * Query & tampilan kartu produk yang dipakai di beberapa halaman.
 */

const PRODUK_SELECT = "
    SELECT p.*, jb.nama_jenis, jb.deskripsi AS deskripsi_jenis,
           m.id_mutu, m.nama_mutu, v.id_varietas, v.nama_varietas, t.nama_tekstur, w.nama_warna, b.nama_bulir,
           u.nama_ukuran, u.berat_kg, k.nama_kadar_patahan AS nama_kadar, k.deskripsi AS deskripsi_kadar,
           (SELECT COALESCE(SUM(pp.jumlah), 0) FROM pesanan_produk pp JOIN pesanan ps ON ps.id_pesanan = pp.id_pesanan
             WHERE pp.id_produk = p.id_produk AND ps.status_pesanan IN ('Diproses','Dikirim','Selesai')) AS terjual
    FROM produk p
    JOIN jenis_beras jb  ON jb.id_jenis_beras = p.id_jenis_beras
    JOIN mutu m          ON m.id_mutu = jb.id_mutu
    JOIN varietas v      ON v.id_varietas = jb.id_varietas
    JOIN tekstur t       ON t.id_tekstur = jb.id_tekstur
    JOIN warna w         ON w.id_warna = jb.id_warna
    JOIN bulir b         ON b.id_bulir = jb.id_bulir
    JOIN ukuran u        ON u.id_ukuran = p.id_ukuran
    JOIN kadar_patahan k ON k.id_kadar_patahan = p.id_kadar_patahan
";

/** Kunci pengelompokan: satu beras (merek + jenis + kadar patahan) dengan beberapa ukuran. */
function produk_kunci(array $p): string
{
    return $p['merek'] . '|' . $p['id_jenis_beras'] . '|' . $p['id_kadar_patahan'];
}

/** Nama beras tanpa keterangan ukuran, mis. "Pusako Rancak Pera IR-42 5 kg" -> "Pusako Rancak Pera IR-42". */
function produk_nama_dasar(array $p): string
{
    $nama = trim((string)$p['nama_produk']);
    $base = preg_replace('/\s*[\d.,]+\s*kg\s*$/i', '', $nama);
    return $base !== '' ? $base : $nama;
}

/**
 * Gabungkan baris produk (per ukuran) menjadi satu kartu per beras.
 * Urutan mengikuti kemunculan pertama, jadi urutan dari query tetap dipakai.
 */
function produk_grup(array $rows, ?int $limit = null): array
{
    $grup = [];
    foreach ($rows as $r) {
        $k = produk_kunci($r);
        if (!isset($grup[$k])) {
            $grup[$k] = $r + ['harga_min' => $r['harga'], 'harga_max' => $r['harga'], 'jumlah_variasi' => 0,
                              'stok_total' => 0, 'terjual_total' => 0, 'id_termurah' => $r['id_produk']];
        }
        $g = &$grup[$k];
        $g['jumlah_variasi']++;
        $g['stok_total']    += (int)$r['stok'];
        $g['terjual_total'] += (int)($r['terjual'] ?? 0);
        if ((float)$r['harga'] < (float)$g['harga_min']) {
            $g['harga_min']   = $r['harga'];
            $g['id_termurah'] = $r['id_produk'];
        }
        if ((float)$r['harga'] > (float)$g['harga_max']) {
            $g['harga_max'] = $r['harga'];
        }
        unset($g);
    }
    $grup = array_values($grup);
    return $limit ? array_slice($grup, 0, $limit) : $grup;
}

/** Kartu produk. Terima baris hasil produk_grup() (atau baris produk biasa). */
function produk_card(array $p): string
{
    $variasi = (int)($p['jumlah_variasi'] ?? 1);
    $stok    = (int)($p['stok_total'] ?? $p['stok']);
    $terjual = (int)($p['terjual_total'] ?? $p['terjual'] ?? 0);
    $hargaMin = $p['harga_min'] ?? $p['harga'];
    $beda    = isset($p['harga_max']) && (float)$p['harga_max'] > (float)$hargaMin;
    $nama    = produk_nama_dasar($p);
    $link    = url('Includes/detail-produk.php?id=' . (int)($p['id_termurah'] ?? $p['id_produk']));
    $habis   = $stok <= 0;
    ob_start(); ?>
    <article class="produk-card">
        <?php if ($habis): ?>
            <span class="produk-card__tag out">Stok habis</span>
        <?php elseif ($terjual >= 2): ?>
            <span class="produk-card__tag">Terlaris</span>
        <?php endif; ?>
        <a href="<?= $link ?>" class="produk-card__image">
            <img src="<?= upload_url('produk', $p['foto']) ?>" alt="<?= h($nama) ?>" loading="lazy">
        </a>
        <div class="produk-card__body">
            <span class="produk-card__category"><?= h($p['merek']) ?></span>
            <a href="<?= $link ?>" class="produk-card__name"><?= h($nama) ?></a>
            <div class="produk-card__meta"><?= h($p['nama_varietas']) ?>, <?= h(strtolower($p['nama_tekstur'])) ?></div>
            <div class="produk-card__bottom">
                <div>
                    <?php if ($beda): ?><div class="produk-card__from">Mulai dari</div><?php endif; ?>
                    <div class="produk-card__price"><?= rupiah($hargaMin) ?></div>
                    <div class="produk-card__stock"><?= $variasi > 1 ? $variasi . ' pilihan ukuran' : 'Stok ' . $stok ?></div>
                </div>
                <?php if ($variasi > 1): ?>
                    <a href="<?= $link ?>" class="produk-card__cart produk-card__cart--link">Pilih ukuran</a>
                <?php elseif (!$habis): ?>
                    <form method="POST" action="<?= url('Includes/cart-view.php') ?>">
                        <?= csrf_field() ?>
                        <input type="hidden" name="aksi" value="tambah">
                        <input type="hidden" name="id_produk" value="<?= (int)$p['id_produk'] ?>">
                        <input type="hidden" name="jumlah" value="1">
                        <button type="submit" class="produk-card__cart" title="Tambah ke keranjang">+ Keranjang</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
