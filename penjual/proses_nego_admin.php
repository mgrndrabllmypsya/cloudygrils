<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: nego.php"); exit;
}

$nego_id    = (int)($_POST['nego_id'] ?? 0);
$aksi       = $_POST['aksi'] ?? '';
$pesan_admin = mysqli_real_escape_string($conn, trim($_POST['pesan_admin'] ?? ''));

if (!$nego_id) {
    header("Location: nego.php"); exit;
}

// Ambil data nego
$row = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM nego_harga WHERE id=$nego_id LIMIT 1"));
if (!$row) {
    header("Location: nego.php"); exit;
}

if ($aksi === 'setuju') {
    $harga_deal = (float)($_POST['harga_deal'] ?? $row['harga_tawar']);
    mysqli_query($conn, "
        UPDATE nego_harga SET
            status = 'disetujui',
            harga_deal = $harga_deal,
            pesan_admin = '$pesan_admin',
            updated_at = NOW()
        WHERE id = $nego_id
    ");
    header("Location: nego.php?msg=setuju"); exit;

} elseif ($aksi === 'counter') {
    $harga_counter = (float)($_POST['harga_counter'] ?? 0);
    if ($harga_counter <= 0) {
        header("Location: nego.php?msg=error"); exit;
    }
    mysqli_query($conn, "
        UPDATE nego_harga SET
            status = 'counter',
            harga_counter = $harga_counter,
            pesan_admin = '$pesan_admin',
            updated_at = NOW()
        WHERE id = $nego_id
    ");
    header("Location: nego.php?msg=counter"); exit;

} elseif ($aksi === 'tolak') {
    mysqli_query($conn, "
        UPDATE nego_harga SET
            status = 'ditolak',
            pesan_admin = '$pesan_admin',
            updated_at = NOW()
        WHERE id = $nego_id
    ");
    header("Location: nego.php?msg=tolak"); exit;

} else {
    header("Location: nego.php"); exit;
}