<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require '../config/koneksi.php';

// Kalau bukan POST, balik ke login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header('Location: login.php?error=1');
    exit;
}

$user = null;
$role = null;

// ── Cek tabel pembeli ────────────────────────────────
$stmt = mysqli_prepare($conn, "SELECT id, nama, email, password FROM pembeli WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row    = mysqli_fetch_assoc($result);

if ($row && password_verify($password, $row['password'])) {
    $user = $row;
    $role = 'pembeli';
}

// ── Cek tabel penjual ────────────────────────────────
if (!$user) {
    $stmt = mysqli_prepare($conn, "SELECT id, nama, email, password FROM penjual WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row    = mysqli_fetch_assoc($result);

    if ($row && password_verify($password, $row['password'])) {
        $user = $row;
        $role = 'penjual';
    }
}

// ── Tidak ditemukan di kedua tabel ───────────────────
if (!$user) {
    header('Location: login.php?error=1');
    exit;
}

// ── Simpan session ────────────────────────────────────
session_regenerate_id(true);
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_nama']  = $user['nama'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role']  = $role;
$_SESSION['login']      = true;

if ($role === 'penjual') {
    $_SESSION['admin_login']   = true;
    $_SESSION['penjual_id']    = $user['id'];
    $_SESSION['penjual_nama']  = $user['nama'];
}

// ── Redirect sesuai role ──────────────────────────────
if ($role === 'penjual') {
    header('Location: ../penjual/dashboard.php');
} else {
    header('Location: ../pages/home.php');
}
exit;