<?php
$aksi = $_POST['aksi'] ?? '';

// Aksi dari PEMBELI
$aksi_pembeli = ['ajukan', 'terima_counter', 'tolak_counter'];
// Aksi dari PENJUAL
$aksi_penjual = ['setuju', 'counter', 'tolak'];

if (in_array($aksi, $aksi_pembeli)) {
    session_name('session_pembeli');
    session_start();
    include '../config/koneksi.php';

    if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'pembeli') {
        header("Location: ../auth/login.php"); exit;
    }
    $pembeli_id = (int)$_SESSION['user_id'];

} elseif (in_array($aksi, $aksi_penjual)) {
    session_name('session_penjual');
    session_start();
    include '../config/koneksi.php';

    if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
        header("Location: ../auth/login.php"); exit;
    }

} else {
    header("Location: ../pages/home.php"); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/home.php"); exit;
}

// ══════════════════════════════════════════
// AKSI 1: AJUKAN NEGO BARU (PEMBELI)
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
        $ins->close();
        header("Location: ../pages/detail.php?id=$produk_id&nego=sukses"); exit;
    } else {
        $ins->close();
        header("Location: ../pages/detail.php?id=$produk_id&error=gagal"); exit;
    }
}

// ══════════════════════════════════════════
// AKSI 2: TERIMA COUNTER (PEMBELI)
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
// AKSI 3: TOLAK COUNTER (PEMBELI)
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

    $upd = $conn->prepare("UPDATE nego_harga SET status='counter_ditolak', updated_at=NOW() WHERE id=?");
    $upd->bind_param("i", $nego_id);
    $upd->execute();
    $upd->close();

    header("Location: ../pages/detail.php?id=" . $nego['produk_id'] . "&nego=counter_ditolak"); exit;
}

// ══════════════════════════════════════════
// AKSI 4: SETUJU (PENJUAL)
// ══════════════════════════════════════════
if ($aksi === 'setuju') {
    $nego_id    = (int)($_POST['nego_id'] ?? 0);
    $harga_deal = (float)($_POST['harga_deal'] ?? 0);
    $pesan      = trim($_POST['pesan_admin'] ?? '');

    $upd = $conn->prepare("UPDATE nego_harga SET status='disetujui', harga_deal=?, pesan_admin=?, updated_at=NOW() WHERE id=?");
    $upd->bind_param("dsi", $harga_deal, $pesan, $nego_id);
    $upd->execute();
    $upd->close();
    header("Location: ../penjual/nego.php?msg=setuju"); exit;
}

// ══════════════════════════════════════════
// AKSI 5: COUNTER (PENJUAL)
// ══════════════════════════════════════════
if ($aksi === 'counter') {
    $nego_id       = (int)($_POST['nego_id'] ?? 0);
    $harga_counter = (float)($_POST['harga_counter'] ?? 0);
    $pesan         = trim($_POST['pesan_admin'] ?? '');

    $upd = $conn->prepare("UPDATE nego_harga SET status='counter', harga_counter=?, pesan_admin=?, updated_at=NOW() WHERE id=?");
    $upd->bind_param("dsi", $harga_counter, $pesan, $nego_id);
    $upd->execute();
    $upd->close();
    header("Location: ../penjual/nego.php?msg=counter"); exit;
}

// ══════════════════════════════════════════
// AKSI 6: TOLAK (PENJUAL)
// ══════════════════════════════════════════
if ($aksi === 'tolak') {
    $nego_id = (int)($_POST['nego_id'] ?? 0);
    $pesan   = trim($_POST['pesan_admin'] ?? '');

    $upd = $conn->prepare("UPDATE nego_harga SET status='ditolak', pesan_admin=?, updated_at=NOW() WHERE id=?");
    $upd->bind_param("si", $pesan, $nego_id);
    $upd->execute();
    $upd->close();
    header("Location: ../penjual/nego.php?msg=tolak"); exit;
}

header("Location: ../pages/home.php"); exit;