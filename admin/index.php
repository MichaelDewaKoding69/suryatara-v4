<?php
require_once __DIR__ . '/../config/koneksi.php';
redirect(is_admin() ? 'admin/dashboard.php' : 'Includes/login.php');
