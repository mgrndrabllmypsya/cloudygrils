<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login.php"); exit;
}

$email    = trim($_POST['email']);
$password = trim($_POST['password']);

$stmt = mysqli_prepare($conn, "SELECT * FROM pembeli WHERE email = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data   = mysqli_fetch_assoc($result);

if (!$data || !password_verify($password, $data['password'])) {
    header("Location: login.php?error=1"); exit;
}

$_SESSION['login']      = true;
$_SESSION['user_id']    = $data['id'];
$_SESSION['nama']       = $data['nama'];
$_SESSION['email']      = $data['email'];

header("Location: ../pages/home.php"); exit;
?>