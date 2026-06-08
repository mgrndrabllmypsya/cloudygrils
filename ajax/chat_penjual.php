<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    echo json_encode([]); exit;
}

$pembeli_id = (int)($_GET['pembeli_id'] ?? 0);
$produk_id  = (int)($_GET['produk_id'] ?? 0);
$last_id    = (int)($_GET['last_id'] ?? 0);

if (!$pembeli_id || !$produk_id) { echo json_encode([]); exit; }

// Tandai pesan pembeli sebagai sudah dibaca
$conn->query("UPDATE chat SET sudah_dibaca=1 
              WHERE pembeli_id=$pembeli_id AND produk_id=$produk_id AND pengirim='pembeli'");

$result = mysqli_query($conn, "
    SELECT id, pengirim, pesan, created_at
    FROM chat
    WHERE pembeli_id=$pembeli_id AND produk_id=$produk_id AND id > $last_id
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