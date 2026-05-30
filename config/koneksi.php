<?php
$host = "localhost";
$user = "mifmyho2_B2";
$pass = "@MIF2025";
$db   = "mifmyho2_B2";
 
$conn = mysqli_connect($host, $user, $pass, $db);
 
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
 
mysqli_set_charset($conn, "utf8mb4");
?>
  