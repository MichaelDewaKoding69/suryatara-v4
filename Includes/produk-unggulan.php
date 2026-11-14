<?php
/**
 * Produk unggulan beranda (dipanggil dari index.php). CSS: Includes/css/produk-unggulan.css
 * "Unggulan" diambil dari produk yang paling banyak terjual (ERD tidak punya kolom unggulan).
 */
require_once __DIR__ . '/produk-helper.php';

$unggulan = produk_grup($pdo->query(PRODUK_SELECT . " WHERE p.stok > 0 ORDER BY terjual DESC, p.id_produk ASC")->fetchAll(), 4);
?>
<section class="section-block" id="produk">
    <div class="section-head">
        <h2 class="section-title">Paling banyak dibeli</h2>
        <a href="<?= url('Includes/produk.php') ?>" class="link-more">Semua produk</a>
    </div>
    <div class="produk-grid">
        <?php foreach ($unggulan as $p) echo produk_card($p); ?>
    </div>
</section>
