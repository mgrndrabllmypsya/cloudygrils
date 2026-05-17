<?php
session_start();
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php");
    exit;
}

$produk_id    = (int)$_POST['produk_id'];
$pembeli_id   = (int)$_POST['pembeli_id'];
$nego_id      = !empty($_POST['nego_id']) ? (int)$_POST['nego_id'] : null;
$metode       = $_POST['metode'];
$harga_produk = (float)$_POST['harga_produk'];
$catatan      = trim($_POST['catatan'] ?? '');

$ongkir  = $metode === 'transfer' ? (float)($_POST['ongkir'] ?? 0) : 0;
$diskon  = ($metode === 'transfer' && $harga_produk > 50000) ? 10000 : 0;
$total   = $harga_produk - $diskon + $ongkir;

// Generate kode pesanan
$tahun_bulan  = date('ym');
$stmt_kode    = $conn->query("SELECT COUNT(*) AS c FROM pesanan WHERE kode_pesanan LIKE 'CG-{$tahun_bulan}%'");
$row_kode     = $stmt_kode->fetch_assoc();
$urut         = str_pad($row_kode['c'] + 1, 4, '0', STR_PAD_LEFT);
$kode_pesanan = "CG-{$tahun_bulan}{$urut}";

$nama_penerima  = trim($_POST['nama_penerima'] ?? '');
$no_hp_penerima = trim($_POST['no_hp_penerima'] ?? '');
$provinsi       = trim($_POST['provinsi'] ?? '');
$kota_tujuan    = trim($_POST['kota_tujuan'] ?? '');
$kecamatan      = trim($_POST['kecamatan'] ?? '');
$kecamatan_id   = trim($_POST['kecamatan_id'] ?? '');
$detail_alamat  = trim($_POST['detail_alamat'] ?? '');
$kode_pos       = trim($_POST['kode_pos'] ?? '');

$lokasi_cod = trim($_POST['lokasi_cod'] ?? '');
$cod_jenis  = trim($_POST['cod_jenis'] ?? '');

// Ambil kurir
$kurir = trim($_POST['ekspedisi'] ?? '');
if ($metode === 'cod') {
    $kurir = 'cod';
}

if ($metode === 'cod') {
    $detail_alamat = $lokasi_cod;
    $catatan = ($cod_jenis ? "Jenis COD: {$cod_jenis}. " : '') . $catatan;
}

$metode_transfer = null;
$jumlah_transfer = 0;
$bukti_transfer  = null;
$status_transfer = null;

if ($metode === 'transfer') {
    $metode_transfer = $_POST['metode_transfer'] ?? null;
    $jumlah_transfer = (float)($_POST['jumlah_transfer'] ?? 0);
    $status_transfer = 'menunggu';

    if (!empty($_FILES['bukti_transfer']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['bukti_transfer']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) die("Format file tidak didukung.");
        if ($_FILES['bukti_transfer']['size'] > 2 * 1024 * 1024) die("Ukuran file maks 2MB.");
        $namaFile     = 'bukti_' . time() . '_' . $pembeli_id . '.' . $ext;
        $folderUpload = '../uploads/bukti_transfer/';
        if (!is_dir($folderUpload)) mkdir($folderUpload, 0755, true);
        move_uploaded_file($_FILES['bukti_transfer']['tmp_name'], $folderUpload . $namaFile);
        $bukti_transfer = $namaFile;
    }
}

// Helper escape
function e($conn, $val) {
    return $val === null ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
}

$nego_sql = $nego_id ? $nego_id : 'NULL';

$sql = "INSERT INTO pesanan
    (kode_pesanan, produk_id, pembeli_id, nego_id, metode,
     harga_produk, diskon, ongkir, total_bayar, catatan, status,
     nama_penerima, no_hp_penerima, provinsi, kota_tujuan,
     kecamatan, kecamatan_id, detail_alamat, kode_pos,
     metode_transfer, jumlah_transfer, bukti_transfer, status_transfer,
     kurir, created_at, updated_at)
VALUES (
    " . e($conn, $kode_pesanan) . ",
    $produk_id,
    $pembeli_id,
    $nego_sql,
    " . e($conn, $metode) . ",
    $harga_produk,
    $diskon,
    $ongkir,
    $total,
    " . e($conn, $catatan) . ",
    'menunggu',
    " . e($conn, $nama_penerima) . ",
    " . e($conn, $no_hp_penerima) . ",
    " . e($conn, $provinsi) . ",
    " . e($conn, $kota_tujuan) . ",
    " . e($conn, $kecamatan) . ",
    " . e($conn, $kecamatan_id) . ",
    " . e($conn, $detail_alamat) . ",
    " . e($conn, $kode_pos) . ",
    " . e($conn, $metode_transfer) . ",
    $jumlah_transfer,
    " . e($conn, $bukti_transfer) . ",
    " . e($conn, $status_transfer) . ",
    " . e($conn, $kurir) . ",
    NOW(), NOW()
)";

if ($conn->query($sql)) {
    // Tandai produk sebagai terjual
    $conn->query("UPDATE produk SET status = 'terjual' WHERE id = $produk_id");
    
    header("Location: ../transaksi/sukses.php?kode={$kode_pesanan}");
    exit;
} else {
    die("Gagal membuat pesanan: " . $conn->error);
}