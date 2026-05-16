<?php
// ajax/input_resi.php
// Dipanggil oleh admin untuk menyimpan nomor resi

header('Content-Type: application/json');
session_start();
include '../config/koneksi.php';

// Cek login admin
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$pesanan_id = $_POST['pesanan_id'] ?? '';
$no_resi    = trim($_POST['no_resi'] ?? '');
$kurir      = trim($_POST['kurir'] ?? '');

if (!$pesanan_id || !$no_resi || !$kurir) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi']);
    exit;
}

$stmt = $conn->prepare("UPDATE pesanan SET no_resi = ?, kurir = ?, status = 'dikirim' WHERE id = ?");
$stmt->bind_param("ssi", $no_resi, $kurir, $pesanan_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Resi berhasil disimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan resi']);
}
$stmt->close();