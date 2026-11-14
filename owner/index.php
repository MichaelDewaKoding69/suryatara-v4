<?php
require_once __DIR__ . '/../config/koneksi.php';
redirect(is_owner() ? 'owner/laporan.php' : 'Includes/login.php');
