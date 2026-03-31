<?php
// logout.php - Halaman untuk keluar dari akun
// File ini diletakkan di folder auth
session_start();

// Hapus semua data session
session_unset();
session_destroy();

// Hapus cookie jika ada (opsional)
if (isset($_COOKIE['user_login'])) {
    setcookie('user_login', '', time() - 3600, '/');
}

// Redirect ke halaman login yang berada di folder yang sama
header("Location: login.php");
exit();
?>