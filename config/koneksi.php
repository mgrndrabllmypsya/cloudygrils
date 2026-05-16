<?php
$server   = "localhost";
$username = "root";
$password = "";
$db       = "cloudygirls";
 
$conn = mysqli_connect($server, $username, $password, $db);
 
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
 
mysqli_set_charset($conn, "utf8mb4");
?>
 