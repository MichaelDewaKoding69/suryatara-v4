<?php
/**
 * Beranda: header + carousel + produk unggulan + footer.
 * Setiap bagian ada di folder Includes/ dan punya file css sendiri di Includes/css/.
 */
$pageTitle  = 'Toko Beras Online';
$activeMenu = 'beranda';
$pageCss    = ['Includes/css/carousel.css', 'Includes/css/produk-unggulan.css'];

require __DIR__ . '/Includes/header.php';
require __DIR__ . '/Includes/carousel.php';
require __DIR__ . '/Includes/produk-unggulan.php';
require __DIR__ . '/Includes/footer.php';
