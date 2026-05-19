<?php
session_start();
require_once '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: login_admin.php"); exit;
}

$username = trim($_POST['username']);
$password = trim($_POST['password']);

$stmt = mysqli_prepare($conn, "SELECT * FROM admin WHERE username=? LIMIT 1");
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data   = mysqli_fetch_assoc($result);

if (!$data || !password_verify($password, $data['password'])) {
    header("Location: login_admin.php?error=1"); exit;
}

$_SESSION['admin_login'] = true;
$_SESSION['admin_id']    = $data['id'];
$_SESSION['admin_nama']  = $data['nama'];

header("Location: ../admin/dashboard.php"); exit;
?>