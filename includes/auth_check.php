<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$auth = require_once __DIR__ . '/../config/auth.php';
$current_role = $_SESSION['role'];
$current_page = basename($_SERVER['PHP_SELF']);

// Periksa apakah role memiliki akses ke halaman ini
if (!isset($auth['permissions'][$current_role]) || !in_array($current_page, $auth['permissions'][$current_role])) {
    die("Akses ditolak! Anda tidak memiliki izin untuk membuka halaman ini.");
}
?>
