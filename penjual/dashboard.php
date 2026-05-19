<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

// Statistik
$total_produk     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='aktif'"))[0] ?? 0;
$total_pesanan    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan"))[0] ?? 0;
$total_pendapatan = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(total_bayar) FROM pesanan WHERE status='selesai'"))[0] ?? 0;
$pesanan_pending  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='menunggu'"))[0] ?? 0;

// Pesanan terbaru
$q_pesanan = mysqli_query($conn, "
    SELECT ps.*, pb.nama AS nama_pembeli
    FROM pesanan ps
    JOIN pembeli pb ON pb.id = ps.pembeli_id
    ORDER BY ps.created_at DESC LIMIT 5
");

// Produk terbaru
$q_produk = mysqli_query($conn, "SELECT * FROM produk ORDER BY created_at DESC LIMIT 5");

// ── DATA GRAFIK ──

// Harian: 7 hari terakhir
$data_harian = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime("-$i days"));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND DATE(created_at)='$tgl'"));
    $data_harian[] = ['label' => $label, 'value' => (int)$r[0]];
}

// Mingguan: 8 minggu terakhir
$data_mingguan = [];
for ($i = 7; $i >= 0; $i--) {
    $start = date('Y-m-d', strtotime("monday -$i week"));
    $end   = date('Y-m-d', strtotime("sunday -$i week"));
    $label = 'W' . date('W', strtotime($start));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND DATE(created_at) BETWEEN '$start' AND '$end'"));
    $data_mingguan[] = ['label' => $label, 'value' => (int)$r[0]];
}

// Bulanan: 12 bulan terakhir
$data_bulanan = [];
for ($i = 11; $i >= 0; $i--) {
    $bln   = date('Y-m', strtotime("-$i months"));
    $label = date('M y', strtotime("-$i months"));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND DATE_FORMAT(created_at,'%Y-%m')='$bln'"));
    $data_bulanan[] = ['label' => $label, 'value' => (int)$r[0]];
}

// Tahunan: 5 tahun terakhir
$data_tahunan = [];
for ($i = 4; $i >= 0; $i--) {
    $thn   = date('Y', strtotime("-$i years"));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND YEAR(created_at)='$thn'"));
    $data_tahunan[] = ['label' => $thn, 'value' => (int)$r[0]];
}

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';

// Load logo toko
$settings = [];
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan_toko WHERE id=1");
if ($q_set) $settings = mysqli_fetch_assoc($q_set) ?? [];
$logo_path = !empty($settings['logo']) ? '../uploads/toko/' . $settings['logo'] : null;
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
    --bg:#0F0E17; --surface:#1A1825; --surface2:#232136; --border:#2E2B3D;
    --accent:#A78BFA; --accent2:#7C3AED; --pink:#F9A8D4; --pink2:#EC4899;
    --green:#34D399; --yellow:#FBBF24; --red:#F87171;
    --text:#E2E0F0; --muted:#6B6880; --white:#FFFFFF;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
a { text-decoration:none; color:inherit; }

/* ── SIDEBAR ── */
.sidebar { width:240px; background:var(--surface); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:50; }
.sidebar-logo { padding:24px 24px 20px; border-bottom:1px solid var(--border); }
.sidebar-logo .logo { font-family:'Playfair Display',serif; font-size:20px; font-weight:900; color:var(--white); }
.sidebar-logo .logo span { color:var(--accent); }
.sidebar-logo small { display:block; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:var(--muted); margin-top:2px; }
.sidebar-nav { flex:1; padding:16px 12px; display:flex; flex-direction:column; gap:2px; overflow-y:auto; }
.nav-item { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:500; color:var(--muted); transition:all .2s; }
.nav-item:hover { background:var(--surface2); color:var(--text); }
.nav-item.active { background:linear-gradient(135deg,rgba(124,58,237,.25),rgba(236,72,153,.15)); color:var(--accent); }
.nav-item i { font-size:16px; width:20px; }
.nav-section { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:var(--muted); padding:14px 14px 6px; font-weight:600; }

/* ── ADMIN CARD + DROPDOWN ── */
.sidebar-footer { padding:16px 12px; border-top:1px solid var(--border); }
.admin-card-wrap { position:relative; margin-bottom:10px; }
.admin-card { display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--surface2); border-radius:10px; cursor:pointer; user-select:none; border:1px solid transparent; transition:border-color .2s; }
.admin-card:hover { border-color:var(--accent); }
.admin-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,var(--accent2),var(--pink2)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#fff; flex-shrink:0; overflow:hidden; }
.admin-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.admin-info { flex:1; min-width:0; }
.admin-info .name { font-size:13px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.admin-info .role { font-size:10px; color:var(--muted); }
.admin-card .chevron { font-size:12px; color:var(--muted); transition:transform .2s; }
.admin-card.open .chevron { transform:rotate(180deg); }
.admin-dropdown { display:none; position:absolute; bottom:calc(100% + 8px); left:0; right:0; background:var(--surface2); border:1px solid var(--border); border-radius:12px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,.4); z-index:100; }
.admin-dropdown.show { display:block; }
.dropdown-header { padding:12px 14px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:10px; }
.dropdown-header .dh-avatar { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--accent2),var(--pink2)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:15px; color:#fff; flex-shrink:0; overflow:hidden; }
.dropdown-header .dh-avatar img { width:100%; height:100%; object-fit:cover; border-radius:50%; }
.dropdown-header .dh-name { font-size:13px; font-weight:600; color:var(--white); }
.dropdown-header .dh-role { font-size:11px; color:var(--muted); }
.dropdown-item { display:flex; align-items:center; gap:10px; padding:10px 14px; font-size:13px; color:var(--text); transition:background .15s; cursor:pointer; }
.dropdown-item:hover { background:rgba(167,139,250,.1); color:var(--accent); }
.dropdown-item i { font-size:15px; width:18px; }
.dropdown-item.danger { color:var(--red); }
.dropdown-item.danger:hover { background:rgba(248,113,113,.1); }
.dropdown-divider { height:1px; background:var(--border); margin:2px 0; }
.btn-logout { display:flex; align-items:center; gap:8px; padding:8px 14px; border-radius:8px; font-size:12px; color:var(--red); transition:background .2s; width:100%; }
.btn-logout:hover { background:rgba(248,113,113,.1); }

/* ── MAIN ── */
.main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
.topbar { background:var(--surface); border-bottom:1px solid var(--border); padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:40; }
.topbar-title { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; }
.topbar-right { display:flex; align-items:center; gap:12px; }
.topbar-date { font-size:12px; color:var(--muted); }
.btn-toko { display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; background:var(--surface2); border:1px solid var(--border); font-size:12px; font-weight:500; color:var(--text); transition:border-color .2s; }
.btn-toko:hover { border-color:var(--accent); color:var(--accent); }

.content { padding:28px 32px; flex:1; }

/* ── STATS ── */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:20px; position:relative; overflow:hidden; transition:transform .2s,border-color .2s; }
.stat-card:hover { transform:translateY(-2px); border-color:var(--accent); }
.stat-card::before { content:''; position:absolute; top:0; right:0; width:80px; height:80px; border-radius:50%; opacity:.07; transform:translate(20px,-20px); }
.stat-card.purple::before { background:var(--accent2); }
.stat-card.pink::before   { background:var(--pink2); }
.stat-card.green::before  { background:var(--green); }
.stat-card.yellow::before { background:var(--yellow); }
.stat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:14px; }
.stat-card.purple .stat-icon { background:rgba(124,58,237,.2); color:var(--accent); }
.stat-card.pink   .stat-icon { background:rgba(236,72,153,.2); color:var(--pink2); }
.stat-card.green  .stat-icon { background:rgba(52,211,153,.2); color:var(--green); }
.stat-card.yellow .stat-icon { background:rgba(251,191,36,.2); color:var(--yellow); }
.stat-value { font-size:26px; font-weight:700; color:var(--white); line-height:1; margin-bottom:4px; }
.stat-label { font-size:12px; color:var(--muted); }

/* ── CHART CARD ── */
.chart-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; margin-bottom:24px; }
.chart-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.chart-header h3 { font-size:14px; font-weight:600; color:var(--white); }
.chart-tabs { display:flex; gap:4px; }
.chart-tab { padding:5px 14px; border-radius:8px; font-size:12px; font-weight:500; color:var(--muted); border:1px solid transparent; cursor:pointer; transition:all .2s; background:var(--surface2); }
.chart-tab:hover { color:var(--text); }
.chart-tab.active { background:linear-gradient(135deg,var(--accent2),var(--pink2)); color:#fff; border-color:transparent; }
.chart-body { padding:20px; }
.chart-canvas-wrap { position:relative; height:220px; }

/* ── GRID 2 ── */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
.card-header h3 { font-size:14px; font-weight:600; color:var(--white); }
.card-header a { font-size:12px; color:var(--accent); }
.card-header a:hover { text-decoration:underline !important; }
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); padding:10px 20px; border-bottom:1px solid var(--border); font-weight:600; }
td { padding:12px 20px; font-size:13px; border-bottom:1px solid rgba(46,43,61,.5); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:var(--surface2); }
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-green  { background:rgba(52,211,153,.15); color:var(--green); }
.badge-yellow { background:rgba(251,191,36,.15);  color:var(--yellow); }
.badge-red    { background:rgba(248,113,113,.15);  color:var(--red); }
.badge-purple { background:rgba(167,139,250,.15);  color:var(--accent); }
.produk-row { display:flex; align-items:center; gap:12px; }
.produk-thumb { width:40px; height:40px; border-radius:8px; object-fit:cover; background:var(--surface2); flex-shrink:0; }
.produk-nama { font-size:13px; font-weight:500; color:var(--white); }
.produk-kat  { font-size:11px; color:var(--muted); }
.empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2rem; display:block; margin-bottom:8px; }

/* ── QUICK ACTIONS ── */
.actions-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; padding:16px; }
.action-btn { display:flex; align-items:center; gap:10px; padding:12px 14px; border-radius:10px; background:var(--surface2); border:1px solid var(--border); font-size:13px; font-weight:500; color:var(--text); transition:all .2s; cursor:pointer; }
.action-btn:hover { border-color:var(--accent); color:var(--accent); }
.action-btn i { font-size:16px; }

@media (max-width:900px) { .stats-grid { grid-template-columns:repeat(2,1fr); } .grid-2 { grid-template-columns:1fr; } }
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php" class="nav-item active"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="produk.php"    class="nav-item"><i class="bi bi-handbag"></i> Produk</a>
        <a href="pesanan.php"   class="nav-item"><i class="bi bi-bag-check"></i> Pesanan</a>
        <a href="chat.php"      class="nav-item"><i class="bi bi-chat-dots"></i> Chat</a>
        <a href="nego.php"      class="nav-item"><i class="bi bi-tags"></i> Nego Harga</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php"     class="nav-item"><i class="bi bi-star"></i> Ulasan</a>
        <a href="pengaturan.php" class="nav-item"><i class="bi bi-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card-wrap">
            <div class="admin-dropdown" id="adminDropdown">
                <div class="dropdown-header">
                    <div class="dh-avatar">
                        <?php if ($logo_path && file_exists($logo_path)): ?>
                            <img src="<?= escape($logo_path) ?>" alt="logo">
                        <?php else: ?>
                            <?= strtoupper(substr($admin_nama, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="dh-name"><?= escape($admin_nama) ?></div>
                        <div class="dh-role">Administrator</div>
                    </div>
                </div>
                <a href="pengaturan.php" class="dropdown-item"><i class="bi bi-gear"></i> Pengaturan Akun</a>
                <a href="../pages/home.php" class="dropdown-item" target="_blank"><i class="bi bi-shop"></i> Lihat Toko</a>
                <div class="dropdown-divider"></div>
                <a href="../auth/logout_admin.php" class="dropdown-item danger"><i class="bi bi-box-arrow-left"></i> Keluar</a>
            </div>
            <div class="admin-card" id="adminCardBtn" onclick="toggleDropdown()">
                <div class="admin-avatar">
                    <?php if ($logo_path && file_exists($logo_path)): ?>
                        <img src="<?= escape($logo_path) ?>" alt="logo">
                    <?php else: ?>
                        <?= strtoupper(substr($admin_nama, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="admin-info">
                    <div class="name"><?= escape($admin_nama) ?></div>
                    <div class="role">Administrator</div>
                </div>
                <i class="bi bi-chevron-up chevron"></i>
            </div>
        </div>
        <a href="../auth/logout_admin.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i> Keluar</a>
    </div>
</aside>

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <div class="topbar-title">Dashboard</div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../index.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
        </div>
    </div>

    <div class="content">

        <!-- STATS (tanpa Pembeli) -->
        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-handbag"></i></div>
                <div class="stat-value"><?= $total_produk ?></div>
                <div class="stat-label">Produk Aktif</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card pink">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value"><?= $pesanan_pending ?></div>
                <div class="stat-label">Menunggu Konfirmasi</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-value" style="font-size:18px;"><?= formatRupiah($total_pendapatan) ?></div>
                <div class="stat-label">Pendapatan</div>
            </div>
        </div>

        <!-- GRAFIK PENJUALAN -->
        <div class="chart-card">
            <div class="chart-header">
                <h3><i class="bi bi-bar-chart-line" style="color:var(--accent);margin-right:6px;"></i> Grafik Penjualan</h3>
                <div class="chart-tabs">
                    <button class="chart-tab active" onclick="switchChart('harian', this)">Harian</button>
                    <button class="chart-tab" onclick="switchChart('mingguan', this)">Mingguan</button>
                    <button class="chart-tab" onclick="switchChart('bulanan', this)">Bulanan</button>
                    <button class="chart-tab" onclick="switchChart('tahunan', this)">Tahunan</button>
                </div>
            </div>
            <div class="chart-body">
                <div class="chart-canvas-wrap">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>

        <!-- TABLE GRID -->
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
                        <thead><tr><th>Pembeli</th><th>Total</th><th>Status</th></tr></thead>
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
                                    $status = $row['status'] ?? 'menunggu';
                                    $badge  = match($status) {
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
                        <thead><tr><th>Produk</th><th>Harga</th><th>Status</th></tr></thead>
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
                                    <span class="badge <?= $st === 'aktif' ? 'badge-green' : 'badge-red' ?>"><?= ucfirst($st) ?></span>
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
                <a href="tambah_produk.php" class="action-btn"><i class="bi bi-plus-circle" style="color:var(--accent);"></i> Tambah Produk Baru</a>
                <a href="pesanan.php"       class="action-btn"><i class="bi bi-bag-check"  style="color:var(--green);"></i> Kelola Pesanan</a>
                <a href="pengaturan.php"    class="action-btn"><i class="bi bi-gear"        style="color:var(--yellow);"></i> Pengaturan Toko</a>
                <a href="ulasan.php"        class="action-btn"><i class="bi bi-star"        style="color:var(--pink2);"></i> Lihat Ulasan</a>
            </div>
        </div>

    </div><!-- end content -->
</div><!-- end main -->

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Data dari PHP
const chartData = {
    harian:   <?= json_encode($data_harian) ?>,
    mingguan: <?= json_encode($data_mingguan) ?>,
    bulanan:  <?= json_encode($data_bulanan) ?>,
    tahunan:  <?= json_encode($data_tahunan) ?>
};

const accent = '#A78BFA';
const pink2  = '#EC4899';

let salesChart = null;

function buildChart(period) {
    const raw    = chartData[period];
    const labels = raw.map(d => d.label);
    const values = raw.map(d => d.value);

    const ctx = document.getElementById('salesChart').getContext('2d');

    // Gradient fill
    const grad = ctx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(167,139,250,0.35)');
    grad.addColorStop(1, 'rgba(167,139,250,0.01)');

    if (salesChart) salesChart.destroy();

    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: values,
                borderColor: accent,
                backgroundColor: grad,
                borderWidth: 2.5,
                pointBackgroundColor: accent,
                pointBorderColor: '#0F0E17',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#232136',
                    borderColor: '#2E2B3D',
                    borderWidth: 1,
                    titleColor: '#E2E0F0',
                    bodyColor: '#A78BFA',
                    padding: 10,
                    callbacks: {
                        label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(46,43,61,0.6)' },
                    ticks: { color: '#6B6880', font: { size: 11 } }
                },
                y: {
                    grid: { color: 'rgba(46,43,61,0.6)' },
                    ticks: {
                        color: '#6B6880', font: { size: 11 },
                        callback: v => 'Rp ' + (v >= 1000000 ? (v/1000000).toFixed(1)+'jt' : v >= 1000 ? (v/1000).toFixed(0)+'rb' : v)
                    },
                    beginAtZero: true
                }
            }
        }
    });
}

function switchChart(period, btn) {
    document.querySelectorAll('.chart-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    buildChart(period);
}

// Init
buildChart('harian');

// Admin dropdown
function toggleDropdown() {
    document.getElementById('adminDropdown').classList.toggle('show');
    document.getElementById('adminCardBtn').classList.toggle('open');
}
document.addEventListener('click', function(e) {
    const wrap = document.querySelector('.admin-card-wrap');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('adminDropdown').classList.remove('show');
        document.getElementById('adminCardBtn').classList.remove('open');
    }
});
</script>

</body>
</html>