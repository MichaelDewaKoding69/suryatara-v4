<?php
/** Carousel beranda (dipanggil dari index.php). CSS: Includes/css/carousel.css */
$slides = [
    ['Beras pulen untuk<br>makan sehari-hari', 'Rojolele, pandan wangi, pera IR-42 sampai beras merah. Kemasan 2,5 kg sampai karung 20 kg.', 'Lihat produk'],
    ['Stok langsung dari<br>gudang Cipondoh', 'Pesanan diproses setelah pembayaran dicek admin, lalu dikirim lewat kurir pilihan toko.', 'Belanja sekarang'],
    ['Pusako Rancak,<br>beras khas Minang', 'Nasi berderai untuk rendang dan gulai. Tersedia kemasan 5 kg dan 20 kg.', 'Lihat Pusako Rancak'],
];
?>
<section class="hero-carousel">
    <div class="carousel-container">
        <?php foreach ($slides as $i => $s): ?>
            <div class="carousel-slide slide-<?= $i + 1 ?> <?= $i === 0 ? 'active' : '' ?>">
                <div class="carousel-overlay">
                    <div class="carousel-content">
                        <h1><?= $s[0] ?></h1>
                        <p><?= h($s[1]) ?></p>
                        <a href="<?= url($i === 2 ? 'Includes/produk.php?q=Pusako' : 'Includes/produk.php') ?>" class="carousel-button"><?= h($s[2]) ?></a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="button" class="carousel-arrow carousel-prev" onclick="changeSlide(-1)" aria-label="Sebelumnya">&#8249;</button>
        <button type="button" class="carousel-arrow carousel-next" onclick="changeSlide(1)" aria-label="Berikutnya">&#8250;</button>
        <div class="carousel-indicators-x">
            <?php foreach ($slides as $i => $s): ?>
                <button class="carousel-dot <?= $i === 0 ? 'active' : '' ?>" onclick="showSlide(<?= $i ?>)" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
var currentSlide = 0;
var slides = document.querySelectorAll('.carousel-slide');
var dots = document.querySelectorAll('.carousel-dot');
function showSlide(i) {
    currentSlide = (i + slides.length) % slides.length;
    slides.forEach(function (s, k) { s.classList.toggle('active', k === currentSlide); });
    dots.forEach(function (d, k) { d.classList.toggle('active', k === currentSlide); });
}
function changeSlide(dir) { showSlide(currentSlide + dir); }
setInterval(function () { changeSlide(1); }, 6000);
</script>
