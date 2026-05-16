<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}

$user_id   = (int)$_SESSION['user_id'];
$produk_id = (int)($_GET['produk_id'] ?? 0);
$kembali   = $_GET['kembali'] ?? 'home.php';

if ($produk_id) {
    $cek = mysqli_query($conn, "SELECT id FROM wishlist WHERE pembeli_id=$user_id AND produk_id=$produk_id LIMIT 1");
    if ($cek && mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "DELETE FROM wishlist WHERE pembeli_id=$user_id AND produk_id=$produk_id");
    } else {
        mysqli_query($conn, "INSERT IGNORE INTO wishlist (pembeli_id, produk_id) VALUES ($user_id, $produk_id)");
    }
}

header("Location: $kembali"); exit;