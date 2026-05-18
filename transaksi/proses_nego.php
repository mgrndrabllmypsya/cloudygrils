<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php"); exit;
}

$pembeli_id = (int)$_SESSION['user_id'];
$aksi       = $_POST['aksi'] ?? '';

// ══════════════════════════════════════════
// AKSI 1: AJUKAN NEGO BARU
// ══════════════════════════════════════════
if ($aksi === 'ajukan') {
    $produk_id   = (int)($_POST['produk_id'] ?? 0);
    $harga_tawar = (float)($_POST['harga_tawar'] ?? 0);
    $pesan       = trim($_POST['pesan'] ?? '');

    if (!$produk_id || $harga_tawar <= 0) {
        header("Location: ../pages/detail.php?id=$produk_id&error=invalid"); exit;
    }

    $stmt = $conn->prepare("SELECT harga, status FROM produk WHERE id = ?");
    $stmt->bind_param("i", $produk_id);
    $stmt->execute();
    $produk = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$produk) { header("Location: ../pages/home.php"); exit; }
    if ($produk['status'] !== 'aktif') {
        header("Location: ../pages/detail.php?id=$produk_id&error=terjual"); exit;
    }

    $harga_asli = $produk['harga'];

    $cek = $conn->prepare("SELECT id FROM nego_harga WHERE produk_id = ? AND pembeli_id = ? AND status IN ('menunggu','counter') LIMIT 1");
    $cek->bind_param("ii", $produk_id, $pembeli_id);
    $cek->execute();
    $nego_lama = $cek->get_result()->fetch_assoc();
    $cek->close();

    if ($nego_lama) {
        header("Location: ../pages/detail.php?id=$produk_id&error=nego_aktif"); exit;
    }
    if ($harga_tawar >= $harga_asli) {
        header("Location: ../pages/detail.php?id=$produk_id&error=harga_tinggi"); exit;
    }
    if ($harga_tawar < ($harga_asli * 0.5)) {
        header("Location: ../pages/detail.php?id=$produk_id&error=harga_rendah"); exit;
    }

    $ins = $conn->prepare("INSERT INTO nego_harga (produk_id, pembeli_id, harga_asli, harga_tawar, pesan, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, 'menunggu', NOW(), NOW())");
    $ins->bind_param("iidds", $produk_id, $pembeli_id, $harga_asli, $harga_tawar, $pesan);

    if ($ins->execute()) {
        $nego_id = $conn->insert_id;
        $ins->close();
        header("Location: ../pages/detail.php?id=$produk_id&nego=sukses"); exit;
    } else {
        $ins->close();
        header("Location: ../pages/detail.php?id=$produk_id&error=gagal"); exit;
    }
}

// ══════════════════════════════════════════
// AKSI 2: TERIMA COUNTER DARI ADMIN
// ══════════════════════════════════════════
if ($aksi === 'terima_counter') {
    $nego_id = (int)($_POST['nego_id'] ?? 0);
    if (!$nego_id) { header("Location: ../pages/home.php"); exit; }

    $stmt = $conn->prepare("SELECT * FROM nego_harga WHERE id = ? AND pembeli_id = ? AND status = 'counter' LIMIT 1");
    $stmt->bind_param("ii", $nego_id, $pembeli_id);
    $stmt->execute();
    $nego = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$nego) { header("Location: ../pages/home.php"); exit; }

    $harga_deal = $nego['harga_counter'];
    $upd = $conn->prepare("UPDATE nego_harga SET status='disetujui', harga_deal=?, updated_at=NOW() WHERE id=?");
    $upd->bind_param("di", $harga_deal, $nego_id);
    $upd->execute();
    $upd->close();

    header("Location: ../pages/detail.php?id=" . $nego['produk_id'] . "&nego=counter_diterima"); exit;
}

// ══════════════════════════════════════════
// AKSI 3: TOLAK COUNTER DARI ADMIN
// ══════════════════════════════════════════
if ($aksi === 'tolak_counter') {
    $nego_id = (int)($_POST['nego_id'] ?? 0);
    if (!$nego_id) { header("Location: ../pages/home.php"); exit; }

    $stmt = $conn->prepare("SELECT * FROM nego_harga WHERE id = ? AND pembeli_id = ? AND status = 'counter' LIMIT 1");
    $stmt->bind_param("ii", $nego_id, $pembeli_id);
    $stmt->execute();
    $nego = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$nego) { header("Location: ../pages/home.php"); exit; }

    $upd = $conn->prepare("UPDATE nego_harga SET status='ditolak', updated_at=NOW() WHERE id=?");
    $upd->bind_param("i", $nego_id);
    $upd->execute();
    $upd->close();

    header("Location: ../pages/detail.php?id=" . $nego['produk_id'] . "&nego=counter_ditolak"); exit;
}

header("Location: ../pages/home.php"); exit;