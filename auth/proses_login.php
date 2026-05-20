<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// JANGAN session_start() di sini dulu
require '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Untuk redirect saja, session belum perlu dibuka
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

// ── Tidak ditemukan ───────────────────────────────────
if (!$user) {
    header('Location: login.php?error=1');
    exit;
}

// ── Simpan session SETELAH tahu role ─────────────────
if ($role === 'penjual') {
    session_name('session_penjual');
    session_start();
    session_regenerate_id(true);

    $_SESSION['login']        = true;
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_role']    = 'penjual';
    $_SESSION['penjual_id']   = $user['id'];
    $_SESSION['penjual_nama'] = $user['nama'];
    $_SESSION['admin_nama']   = $user['nama'];

} else {
    session_name('session_pembeli');
    session_start();
    session_regenerate_id(true);

    $_SESSION['login']        = true;
    $_SESSION['user_id']      = $user['id'];
    $_SESSION['user_role']    = 'pembeli';
    $_SESSION['pembeli_id']   = $user['id'];
    $_SESSION['pembeli_nama'] = $user['nama'];
}

// ── Redirect ──────────────────────────────────────────
if ($role === 'penjual') {
    header('Location: ../penjual/dashboard.php');
} else {
    header('Location: ../pages/home.php');
}
exit;