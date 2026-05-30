<?php
require_once 'config/koneksi.php';
$result = mysqli_query($conn, "SELECT username, password FROM admin WHERE id=1");
$row = mysqli_fetch_assoc($result);
echo "Username: " . $row['username'] . "<br>";
echo "Password hash: " . $row['password'] . "<br>";
echo "<br>";
echo "Test admin123: " . (password_verify('admin123', $row['password']) ? 'COCOK ✅' : 'TIDAK COCOK ❌');
?>