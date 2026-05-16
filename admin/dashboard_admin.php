<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login_admin.php"); exit;
}

function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Statistik
$total_produk   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='aktif'"))[0] ?? 0;
$total_pembeli  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pembeli"))[0] ?? 0;
$total_pesanan  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan"))[0] ?? 0;
$total_pendapatan = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(total_bayar) FROM pesanan WHERE status='selesai'"))[0] ?? 0;

// Pesanan terbaru
$q_pesanan = mysqli_query($conn, "
    SELECT ps.*, pb.nama AS nama_pembeli
    FROM pesanan ps
    JOIN pembeli pb ON pb.id = ps.pembeli_id
    ORDER BY ps.created_at DESC LIMIT 5
");

// Produk terbaru
$q_produk = mysqli_query($conn, "SELECT * FROM produk ORDER BY created_at DESC LIMIT 5");

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --bg: #0F0E17;
    --surface: #1A1825;
    --surface2: #232136;
    --border: #2E2B3D;
    --accent: #A78BFA;
    --accent2: #7C3AED;
    --pink: #F9A8D4;
    --pink2: #EC4899;
    --green: #34D399;
    --yellow: #FBBF24;
    --red: #F87171;
    --text: #E2E0F0;
    --muted: #6B6880;
    --white: #FFFFFF;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
a { text-decoration: none; color: inherit; }

/* SIDEBAR */
.sidebar {
    width: 240px;
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 50;
}
.sidebar-logo {
    padding: 24px 24px 20px;
    border-bottom: 1px solid var(--border);
}
.sidebar-logo .logo {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 900;
    color: var(--white);
}
.sidebar-logo .logo span { color: var(--accent); }
.sidebar-logo small {
    display: block;
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--muted);
    margin-top: 2px;
}
.sidebar-nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 2px; }
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
    transition: all .2s;
    cursor: pointer;
}
.nav-item:hover { background: var(--surface2); color: var(--text); }
.nav-item.active { background: linear-gradient(135deg, rgba(124,58,237,.25), rgba(236,72,153,.15)); color: var(--accent); }
.nav-item i { font-size: 16px; width: 20px; }
.nav-section {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 14px 14px 6px;
    font-weight: 600;
}
.sidebar-footer {
    padding: 16px 12px;
    border-top: 1px solid var(--border);
}
.admin-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: var(--surface2);
    border-radius: 10px;
    margin-bottom: 10px;
}
.admin-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--accent2), var(--pink2));
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px; color: #fff;
    flex-shrink: 0;
}
.admin-info { min-width: 0; }
.admin-info .name { font-size: 13px; font-weight: 600; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.admin-info .role { font-size: 10px; color: var(--muted); }
.btn-logout {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 8px;
    font-size: 12px; color: var(--red);
    transition: background .2s;
    width: 100%;
}
.btn-logout:hover { background: rgba(248,113,113,.1); }

/* MAIN */
.main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

/* TOPBAR */
.topbar {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 0 32px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 40;
}
.topbar-title { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 700; }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.topbar-date { font-size: 12px; color: var(--muted); }
.btn-toko {
    display: flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: 8px;
    background: var(--surface2); border: 1px solid var(--border);
    font-size: 12px; font-weight: 500; color: var(--text);
    transition: border-color .2s;
}
.btn-toko:hover { border-color: var(--accent); color: var(--accent); }

/* CONTENT */
.content { padding: 28px 32px; flex: 1; }

/* STATS */
.stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 28px; }
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 20px;
    position: relative;
    overflow: hidden;
    transition: transform .2s, border-color .2s;
}
.stat-card:hover { transform: translateY(-2px); border-color: var(--accent); }
.stat-card::before {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 80px; height: 80px;
    border-radius: 50%;
    opacity: .07;
    transform: translate(20px, -20px);
}
.stat-card.purple::before { background: var(--accent2); }
.stat-card.pink::before { background: var(--pink2); }
.stat-card.green::before { background: var(--green); }
.stat-card.yellow::before { background: var(--yellow); }
.stat-icon {
    width: 38px; height: 38px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px;
    margin-bottom: 14px;
}
.stat-card.purple .stat-icon { background: rgba(124,58,237,.2); color: var(--accent); }
.stat-card.pink .stat-icon { background: rgba(236,72,153,.2); color: var(--pink2); }
.stat-card.green .stat-icon { background: rgba(52,211,153,.2); color: var(--green); }
.stat-card.yellow .stat-icon { background: rgba(251,191,36,.2); color: var(--yellow); }
.stat-value { font-size: 26px; font-weight: 700; color: var(--white); line-height: 1; margin-bottom: 4px; }
.stat-label { font-size: 12px; color: var(--muted); }

/* GRID 2COL */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* CARDS */
.card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px;
    overflow: hidden;
}
.card-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.card-header h3 { font-size: 14px; font-weight: 600; color: var(--white); }
.card-header a { font-size: 12px; color: var(--accent); }
.card-header a:hover { text-decoration: underline !important; }

/* TABLE */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th {
    text-align: left;
    font-size: 10px;
    letter-spacing: 1px;
    text-transform: uppercase;
    color: var(--muted);
    padding: 10px 20px;
    border-bottom: 1px solid var(--border);
    font-weight: 600;
}
td { padding: 12px 20px; font-size: 13px; border-bottom: 1px solid rgba(46,43,61,.5); }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--surface2); }

/* BADGE */
.badge {
    display: inline-flex; align-items: center;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.badge-green { background: rgba(52,211,153,.15); color: var(--green); }
.badge-yellow { background: rgba(251,191,36,.15); color: var(--yellow); }
.badge-red { background: rgba(248,113,113,.15); color: var(--red); }
.badge-purple { background: rgba(167,139,250,.15); color: var(--accent); }

/* PRODUK ROW */
.produk-row { display: flex; align-items: center; gap: 12px; }
.produk-thumb {
    width: 40px; height: 40px;
    border-radius: 8px;
    object-fit: cover;
    background: var(--surface2);
    flex-shrink: 0;
}
.produk-nama { font-size: 13px; font-weight: 500; color: var(--white); }
.produk-kat { font-size: 11px; color: var(--muted); }

/* EMPTY */
.empty { text-align: center; padding: 40px 20px; color: var(--muted); font-size: 13px; }
.empty i { font-size: 2rem; display: block; margin-bottom: 8px; }

/* QUICK ACTIONS */
.actions-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; padding: 16px; }
.action-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 14px; border-radius: 10px;
    background: var(--surface2); border: 1px solid var(--border);
    font-size: 13px; font-weight: 500; color: var(--text);
    transition: all .2s; cursor: pointer;
}
.action-btn:hover { border-color: var(--accent); color: var(--accent); }
.action-btn i { font-size: 16px; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
        <small>Admin Panel</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php" class="nav-item active">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>
        <a href="produk.php" class="nav-item">
            <i class="bi bi-handbag"></i> Produk
        </a>
        <a href="pesanan.php" class="nav-item">
            <i class="bi bi-bag-check"></i> Pesanan
        </a>
        <a href="pembeli.php" class="nav-item">
            <i class="bi bi-people"></i> Pembeli
        </a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php" class="nav-item">
            <i class="bi bi-star"></i> Ulasan
        </a>
        <a href="pengaturan.php" class="nav-item">
            <i class="bi bi-gear"></i> Pengaturan
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar"><?= strtoupper(substr($admin_nama, 0, 1)) ?></div>
            <div class="admin-info">
                <div class="name"><?= escape($admin_nama) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <a href="../auth/logout_admin.php" class="btn-logout">
            <i class="bi bi-box-arrow-left"></i> Keluar
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../index.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-handbag"></i></div>
                <div class="stat-value"><?= $total_produk ?></div>
                <div class="stat-label">Produk Aktif</div>
            </div>
            <div class="stat-card pink">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <div class="stat-value"><?= $total_pembeli ?></div>
                <div class="stat-label">Total Pembeli</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-value" style="font-size:18px;"><?= formatRupiah($total_pendapatan) ?></div>
                <div class="stat-label">Pendapatan</div>
            </div>
        </div>

        <!-- GRID 2 COL -->
        <div class="grid-2">

            <!-- PESANAN TERBARU -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-bag-check" style="color:var(--accent);margin-right:6px;"></i> Pesanan Terbaru</h3>
                    <a href="pesanan.php">Lihat semua →</a>
                </div>
                <div class="table-wrap">
                    <?php if ($q_pesanan && mysqli_num_rows($q_pesanan) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Pembeli</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($q_pesanan)): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:500;color:var(--white);"><?= escape($row['nama_pembeli']) ?></div>
                                    <div style="font-size:11px;color:var(--muted);"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td style="font-weight:600;color:var(--accent);"><?= formatRupiah($row['total_bayar']) ?></td>
                                <td>
                                    <?php
                                    $status = $row['status'] ?? 'pending';
                                    $badge = match($status) {
                                        'selesai'  => 'badge-green',
                                        'proses'   => 'badge-yellow',
                                        'batal'    => 'badge-red',
                                        default    => 'badge-purple'
                                    };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty"><i class="bi bi-bag-x"></i>Belum ada pesanan</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PRODUK TERBARU -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-handbag" style="color:var(--pink2);margin-right:6px;"></i> Produk Terbaru</h3>
                    <a href="produk.php">Lihat semua →</a>
                </div>
                <div class="table-wrap">
                    <?php if ($q_produk && mysqli_num_rows($q_produk) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Harga</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($q_produk)): ?>
                            <tr>
                                <td>
                                    <div class="produk-row">
                                        <img src="../uploads/produk/<?= escape($row['foto_utama'] ?? '') ?>"
                                             class="produk-thumb"
                                             onerror="this.src='https://placehold.co/40x40/232136/A78BFA?text=CG'">
                                        <div>
                                            <div class="produk-nama"><?= escape($row['nama_barang'] ?? '') ?></div>
                                            <div class="produk-kat"><?= escape($row['kategori'] ?? '') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-weight:600;color:var(--accent);"><?= formatRupiah($row['harga']) ?></td>
                                <td>
                                    <?php $st = $row['status'] ?? 'nonaktif'; ?>
                                    <span class="badge <?= $st === 'aktif' ? 'badge-green' : 'badge-red' ?>">
                                        <?= ucfirst($st) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty"><i class="bi bi-handbag"></i>Belum ada produk</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- QUICK ACTIONS -->
        <div class="card" style="margin-top:20px;">
            <div class="card-header">
                <h3><i class="bi bi-lightning-charge" style="color:var(--yellow);margin-right:6px;"></i> Aksi Cepat</h3>
            </div>
            <div class="actions-grid">
                <a href="tambah_produk.php" class="action-btn">
                    <i class="bi bi-plus-circle" style="color:var(--accent);"></i> Tambah Produk Baru
                </a>
                <a href="pesanan.php" class="action-btn">
                    <i class="bi bi-bag-check" style="color:var(--green);"></i> Kelola Pesanan
                </a>
                <a href="pembeli.php" class="action-btn">
                    <i class="bi bi-people" style="color:var(--pink2);"></i> Data Pembeli
                </a>
                <a href="pengaturan.php" class="action-btn">
                    <i class="bi bi-gear" style="color:var(--yellow);"></i> Pengaturan Toko
                </a>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>