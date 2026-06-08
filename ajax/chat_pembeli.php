<?php
session_name('session_pembeli');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    echo json_encode([]); exit;
}

$user_id   = (int)$_SESSION['user_id'];
$produk_id = (int)($_GET['produk_id'] ?? 0);
$last_id   = (int)($_GET['last_id'] ?? 0);

if (!$produk_id) { echo json_encode([]); exit; }

// Tandai pesan admin sebagai sudah dibaca
$conn->query("UPDATE chat SET sudah_dibaca=1 
              WHERE produk_id=$produk_id AND pembeli_id=$user_id AND pengirim='admin'");

$result = mysqli_query($conn, "
    SELECT id, pengirim, pesan, created_at
    FROM chat
    WHERE produk_id=$produk_id AND pembeli_id=$user_id AND id > $last_id
    ORDER BY created_at ASC
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'id'       => $row['id'],
        'pengirim' => $row['pengirim'],
        'pesan'    => htmlspecialchars($row['pesan'] ?? '', ENT_QUOTES, 'UTF-8'),
        'waktu'    => date('H:i', strtotime($row['created_at'])),
    ];
}

header('Content-Type: application/json');
echo json_encode($data);