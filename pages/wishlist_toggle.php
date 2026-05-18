<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

$user_id   = (int)$_SESSION['user_id'];
$produk_id = isset($_GET['produk_id']) ? (int)$_GET['produk_id'] : 0;
$kembali   = isset($_GET['kembali'])   ? $_GET['kembali'] : 'home.php';

if ($produk_id > 0) {
    $cek = mysqli_query($conn, "SELECT id FROM wishlist WHERE pembeli_id=$user_id AND produk_id=$produk_id LIMIT 1");
    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "DELETE FROM wishlist WHERE pembeli_id=$user_id AND produk_id=$produk_id");
    } else {
        mysqli_query($conn, "INSERT INTO wishlist (pembeli_id, produk_id, created_at) VALUES ($user_id, $produk_id, NOW())");
    }
}

header("Location: " . $kembali);
exit;