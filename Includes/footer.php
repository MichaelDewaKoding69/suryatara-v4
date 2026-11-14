<?php
$waLink = 'https://wa.me/' . TOKO_WHATSAPP . '?text=' . rawurlencode('Halo ' . TOKO_NAMA . ', saya mau tanya soal beras.');
?>
<footer class="footer-roemah" id="kontak">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 mb-4">
                <div class="footer-brand">Roemah Beras</div>
                <p class="mt-2" style="font-size:13px;color:#5f5148;max-width:320px">
                    Toko beras online dari <?= h(TOKO_PERUSAHAAN) ?>, Cipondoh, Tangerang. Melayani pembelian eceran sampai karungan.
                </p>
            </div>
            <div class="col-lg-2 col-md-4 mb-4">
                <div class="footer-title">Belanja</div>
                <a href="<?= url('Includes/produk.php') ?>" class="footer-link">Semua produk</a>
                <a href="<?= url('Includes/cart-view.php') ?>" class="footer-link">Keranjang</a>
                <a href="<?= url('Includes/pesanan-saya.php') ?>" class="footer-link">Pesanan saya</a>
                <a href="<?= url('Includes/retur-saya.php') ?>" class="footer-link">Retur</a>
            </div>
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="footer-title">Mutu beras</div>
                <?php
                try {
                    foreach ($pdo->query('SELECT id_mutu, nama_mutu FROM mutu ORDER BY id_mutu')->fetchAll() as $m) {
                        echo '<a href="' . url('Includes/produk.php?mutu[]=' . (int)$m['id_mutu']) . '" class="footer-link">Beras ' . h($m['nama_mutu']) . '</a>';
                    }
                } catch (Throwable $e) {
                }
                ?>
            </div>
            <div class="col-lg-3 col-md-4 mb-4">
                <div class="footer-title">Alamat toko</div>
                <p class="footer-link mb-1"><?= h(TOKO_ALAMAT) ?></p>
                <p class="footer-link mb-1">Telp/WA <?= h(TOKO_TELEPON) ?></p>
                <p class="footer-link mb-1"><?= h(TOKO_EMAIL) ?></p>
            </div>
        </div>
        <div class="footer-bottom">&copy; <?= date('Y') ?> Roemah Beras &middot; <?= h(TOKO_PERUSAHAAN) ?></div>
    </div>
</footer>

<a href="<?= h($waLink) ?>" class="wa-bubble" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <i class="fab fa-whatsapp"></i><span>Tanya via WhatsApp</span>
</a>

<div class="lightbox" id="lightbox" onclick="this.classList.remove('show')"><img src="" alt="Pratinjau"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= asset('Includes/js/script.js') ?>"></script>
</body>
</html>
