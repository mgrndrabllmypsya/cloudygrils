<?php
require_once 'config/koneksi.php';
$hash = password_hash('admin123', PASSWORD_DEFAULT);
mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id=1");
echo "Password berhasil direset! Sekarang login dengan password: admin123";
?>