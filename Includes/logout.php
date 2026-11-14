<?php
/**
 * Activity & Sequence "Logout": hapusSesi() -> redirectLogin()
 */
require_once __DIR__ . '/../config/koneksi.php';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
session_regenerate_id(true);   // id sesi baru, supaya pesan "Anda telah keluar" ikut terbawa ke halaman login
flash('info', 'Anda telah keluar.');
redirect('Includes/login.php');
