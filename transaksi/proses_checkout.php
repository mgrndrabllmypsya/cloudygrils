<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_name('session_pembeli');
session_start();
include '../config/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php");
    exit;
}

// Paksa dari session, abaikan $_POST['pembeli_id']
$pembeli_id = (int)$_SESSION['pembeli_id'];

if (!$pembeli_id) {
    header("Location: ../auth/login.php");
    exit;
}

$produk_id    = (int)$_POST['produk_id'];
$nego_id      = !empty($_POST['nego_id']) ? (int)$_POST['nego_id'] : null;
$metode       = $_POST['metode'];
$harga_produk = (float)$_POST['harga_produk'];
$catatan = $metode === 'cod'
    ? trim($_POST['catatan_cod'] ?? '')
    : trim($_POST['catatan'] ?? '');

$ongkir  = $metode === 'transfer' ? (float)($_POST['ongkir'] ?? 0) : 0;
$diskon  = ($metode === 'transfer' && $harga_produk > 50000) ? 10000 : 0;
$total   = $harga_produk - $diskon + $ongkir;

// Generate kode pesanan (format: CG-YYYYMMDD + 4 digit urut)
$tgl_kode  = date('Ymd');
$stmt_kode = $conn->query("SELECT COUNT(*) AS c FROM pesanan WHERE kode_pesanan LIKE 'CG-{$tgl_kode}%'");
$row_kode  = $stmt_kode->fetch_assoc();
$urut      = str_pad($row_kode['c'] + 1, 4, '0', STR_PAD_LEFT);
$kode_pesanan = "CG-{$tgl_kode}{$urut}";
// Pastikan tidak duplikat
$cek = $conn->query("SELECT id FROM pesanan WHERE kode_pesanan = '$kode_pesanan' LIMIT 1");
while ($cek && $cek->num_rows > 0) {
    $urut = str_pad((int)$urut + 1, 4, '0', STR_PAD_LEFT);
    $kode_pesanan = "CG-{$tgl_kode}{$urut}";
    $cek = $conn->query("SELECT id FROM pesanan WHERE kode_pesanan = '$kode_pesanan' LIMIT 1");
}

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

// Siapkan kolom jenis_cod & alamat_cod secara terpisah
$jenis_cod_val  = null;
$alamat_cod_val = null;

// Ambil kurir
$kurir = trim($_POST['ekspedisi'] ?? '');
if ($metode === 'cod') {
    $kurir = 'cod';
}

// Ambil nama produk
$res_produk  = $conn->query("SELECT nama_barang FROM produk WHERE id = $produk_id LIMIT 1");
$nama_produk = $res_produk ? ($res_produk->fetch_assoc()['nama_barang'] ?? 'produk') : 'produk';

if ($metode === 'cod') {
    $jenis_cod_val  = $cod_jenis ?: null;   // 'antar' atau 'ambil'
    $alamat_cod_val = $lokasi_cod ?: null;  // alamat pembeli / lokasi toko
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
if (!function_exists('e')) {
    function e($conn, $val) {
        return $val === null ? 'NULL' : "'" . $conn->real_escape_string($val) . "'";
    }
}

$nego_sql = $nego_id ? $nego_id : 'NULL';

// INSERT pesanan
$sql = "INSERT INTO pesanan
    (kode_pesanan, produk_id, pembeli_id, nego_id, metode,
     harga_produk, diskon, ongkir, total_bayar, catatan, status,
     nama_penerima, no_hp_penerima, provinsi, kota_tujuan,
     kecamatan, kecamatan_id, detail_alamat, kode_pos,
     metode_transfer, jumlah_transfer, bukti_transfer, status_transfer,
     kurir, jenis_cod, alamat_cod, created_at, updated_at)
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
    " . e($conn, $jenis_cod_val) . ",
    " . e($conn, $alamat_cod_val) . ",
    NOW(), NOW()
)";

if ($conn->query($sql)) {
    $pesanan_id = $conn->insert_id;

    // Tandai produk sebagai ditahan (bukan terjual dulu, tunggu konfirmasi selesai)
    $conn->query("UPDATE produk SET status = 'ditahan' WHERE id = $produk_id");

    // INSERT ke tabel cod (khusus metode COD)
    if ($metode === 'cod' && $jenis_cod_val) {
        $sql_cod = "INSERT INTO cod
            (pesanan_id, jenis, lokasi, catatan_pembeli, status, created_at, updated_at)
        VALUES (
            $pesanan_id,
            " . e($conn, $jenis_cod_val) . ",
            " . e($conn, $alamat_cod_val) . ",
            " . e($conn, $catatan) . ",
            'menunggu',
            NOW(), NOW()
        )";
        $conn->query($sql_cod);
    }

    // ── NOTIFIKASI ──────────────────────────────────────────────────
    require_once '../includes/notifikasi.php';

    // Notifikasi ke pembeli
    if ($metode === 'transfer') {
        kirimNotifikasiPembeli(
            $conn, $pembeli_id,
            "Pesanan Berhasil Dibuat",
            "Pesanan #{$kode_pesanan} ({$nama_produk}) berhasil dibuat. Silakan tunggu konfirmasi pembayaran dari admin.",
            'pesanan', $pesanan_id
        );
    } else {
        kirimNotifikasiPembeli(
            $conn, $pembeli_id,
            "Pesanan COD Berhasil Dibuat",
            "Pesanan #{$kode_pesanan} ({$nama_produk}) dengan metode COD berhasil dibuat. Seller akan segera memproses pesananmu.",
            'pesanan', $pesanan_id
        );
    }

    // Notifikasi ke admin
    kirimNotifikasiAdmin(
        $conn,
        "Pesanan Baru Masuk",
        "Ada pesanan baru #{$kode_pesanan} ({$nama_produk}) dengan metode " . strtoupper($metode) . ". Segera cek dan proses.",
        'pesanan', $pesanan_id
    );
    // ────────────────────────────────────────────────────────────────

    header("Location: ../transaksi/sukses.php?kode={$kode_pesanan}");
    exit;
} else {
    die("Gagal membuat pesanan: " . $conn->error);
}