<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: register.php"); exit;
}

$nama     = trim($_POST['nama']);
$username = trim($_POST['username']);
$email    = trim($_POST['email']);
$password = trim($_POST['password']);

// Cek email
$stmt = mysqli_prepare($conn, "SELECT id FROM pembeli WHERE email=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) > 0) {
    header("Location: register.php?error=email_exists"); exit;
}
mysqli_stmt_close($stmt);

// Cek username
$stmt2 = mysqli_prepare($conn, "SELECT id FROM pembeli WHERE username=? LIMIT 1");
mysqli_stmt_bind_param($stmt2, "s", $username);
mysqli_stmt_execute($stmt2);
mysqli_stmt_store_result($stmt2);
if (mysqli_stmt_num_rows($stmt2) > 0) {
    header("Location: register.php?error=username_exists"); exit;
}
mysqli_stmt_close($stmt2);

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt3 = mysqli_prepare($conn, "INSERT INTO pembeli (nama, username, email, password, created_at) VALUES (?,?,?,?,NOW())");
mysqli_stmt_bind_param($stmt3, "ssss", $nama, $username, $email, $hash);

if (mysqli_stmt_execute($stmt3)) {
    $user_id = mysqli_insert_id($conn);
    $_SESSION['login']   = true;
    $_SESSION['user_id'] = $user_id;
    $_SESSION['nama']    = $nama;
    $_SESSION['email']   = $email;
    header("Location: login.php"); exit;
} else {
    header("Location: register.php?error=gagal"); exit;
}
?>