<?php
session_start();
include '../config/koneksi.php';

$kode = $_GET['kode'] ?? '';
if (!$kode) { header("Location: ../pages/home.php"); exit; }

$stmt = $conn->prepare("SELECT p.*, pr.nama_barang AS nama_produk FROM pesanan p JOIN produk pr ON pr.id = p.produk_id WHERE p.kode_pesanan = ?");
$stmt->bind_param("s", $kode);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();

if (!$pesanan) { echo "Pesanan tidak ditemukan."; exit; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Berhasil – CloudyGirls</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --purple: #7c3aed; --purple-light: #ede9fe;
            --pink: #db2777; --bg: #f5f3ff;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem 1rem; }
        .card { background: #fff; border-radius: 18px; box-shadow: 0 4px 24px rgba(124,58,237,.1); padding: 2.5rem; max-width: 480px; width: 100%; text-align: center; }
        .checkmark { font-size: 3.5rem; animation: pop .5s ease; }
        @keyframes pop { 0%{transform:scale(0)} 80%{transform:scale(1.2)} 100%{transform:scale(1)} }
        h1 { font-family: 'Playfair Display', serif; font-size: 1.6rem; margin: 1rem 0 .35rem; color: #1e1b2e; }
        .kode { display: inline-block; background: var(--purple-light); color: var(--purple); font-weight: 700; font-size: 1.1rem; padding: .4rem 1.2rem; border-radius: 99px; margin: .5rem 0 1.5rem; letter-spacing: .05em; }
        .info-box { background: var(--bg); border-radius: 12px; padding: 1rem 1.25rem; text-align: left; margin-bottom: 1.5rem; }
        .info-row { display: flex; justify-content: space-between; font-size: .88rem; margin-bottom: .45rem; }
        .info-row:last-child { margin-bottom: 0; }
        .info-row span:last-child { font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }
        .info-row.total span:last-child { color: var(--pink); font-size: 1rem; }
        .note { font-size: .82rem; color: #6b7280; margin-bottom: 1.5rem; }
        .btn { display: block; padding: .85rem; border-radius: 10px; font-family: inherit; font-weight: 600; font-size: .95rem; cursor: pointer; text-decoration: none; margin-bottom: .6rem; transition: all .2s; }
        .btn-primary { background: linear-gradient(135deg, var(--purple), var(--pink)); color: #fff; border: none; }
        .btn-outline { background: transparent; border: 2px solid var(--purple); color: var(--purple); }
    </style>
</head>
<body>
<div class="card">
    <div class="checkmark">🎉</div>
    <h1>Pesanan Berhasil Dibuat!</h1>
    <div class="kode"><?= htmlspecialchars($kode) ?></div>

    <div class="info-box">
        <div class="info-row"><span>Produk</span><span><?= htmlspecialchars($pesanan['nama_produk']) ?></span></div>
        <div class="info-row"><span>Metode</span><span><?= strtoupper($pesanan['metode']) ?></span></div>
        <?php if ($pesanan['metode'] === 'transfer'): ?>
        <div class="info-row"><span>Ekspedisi</span><span><?= htmlspecialchars($pesanan['kurir'] ?? '-') ?></span></div>
        <?php endif; ?>
        <div class="info-row total"><span>Total Bayar</span><span>Rp <?= number_format($pesanan['total_bayar'], 0, ',', '.') ?></span></div>
    </div>

    <?php if ($pesanan['metode'] === 'cod'): ?>
    <p class="note">✅ Pesanan masuk ke admin. Tunggu konfirmasi jadwal COD dari kami ya!</p>
    <?php else: ?>
    <p class="note">⏳ Bukti transfer kamu sedang dicek admin. Kami akan menghubungimu segera.</p>
    <?php endif; ?>

    <a href="../pages/home.php" class="btn btn-primary">Kembali Belanja</a>
    <a href="../pages/pesanan.php" class="btn btn-outline">Lihat Pesanan Saya</a>
</div>
</body>
</html>