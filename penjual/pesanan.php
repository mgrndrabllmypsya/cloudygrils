<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

// Filter
$filter_status = $_GET['status'] ?? '';
$search        = $_GET['search'] ?? '';
$where = "WHERE 1=1";
if ($filter_status) $where .= " AND ps.status='" . mysqli_real_escape_string($conn, $filter_status) . "'";
if ($search)        $where .= " AND (pb.nama LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR ps.kode_pesanan LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";

$q_pesanan = mysqli_query($conn, "
    SELECT ps.*, pb.nama AS nama_pembeli, pb.email AS email_pembeli,
           pr.nama_barang AS nama_produk
    FROM pesanan ps
    JOIN pembeli pb ON pb.id = ps.pembeli_id
    LEFT JOIN produk pr ON pr.id = ps.produk_id
    $where
    ORDER BY ps.created_at DESC
");

$total_pesanan   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan"))[0] ?? 0;
$total_menunggu  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='menunggu'"))[0] ?? 0;
$total_diproses  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='diproses'"))[0] ?? 0;
$total_selesai   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='selesai'"))[0] ?? 0;
$total_unread = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
$nego_menunggu = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;

$total_perhatian = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status_transfer='menunggu' AND status='menunggu'"))[0] ?? 0;

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan — Cloudy Girls Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Lato:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --bg:       #FFF0F5;
    --surface:  #FFFFFF;
    --surface2: #FFE8F2;
    --border:   #F4A7C3;
    --accent:   #E8719A;
    --accent2:  #D4547F;
    --pink:     #F4A7C3;
    --pink2:    #E8719A;
    --green:    #00BFA5;
    --yellow:   #FFB300;
    --red:      #FF1744;
    --orange:   #FF6D00;
    --text:     #1A1A1A;
    --text2:    #444444;
    --muted:    #BBA0B0;
    --white:    #FFFFFF;

    --font-heading: 'Poppins', sans-serif;
    --font-body:    'Lato', sans-serif;
    --font-ui:      'Poppins', sans-serif;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: var(--font-body);
    background:var(--bg);
    color:var(--text);
    display:flex;
    min-height:100vh;
}
body::before {
    content:'';
    position:fixed; inset:0;
    background-image: radial-gradient(circle, #F4A7C3 1px, transparent 1px);
    background-size: 28px 28px;
    opacity:.15;
    pointer-events:none;
    z-index:0;
}
a { text-decoration:none; color:inherit; }

/* SIDEBAR */
.sidebar {
    width:300px;
    background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%);
    display:flex; flex-direction:column;
    position:fixed; top:0; left:0; bottom:0; z-index:50;
    border-radius: 0 28px 28px 0;
    box-shadow: 6px 0 32px rgba(212,84,127,.28);
    overflow: hidden;
}
.sidebar-logo {
    padding:28px 28px 22px;
    border-bottom:1.5px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.12);
}
.sidebar-logo .logo-img {
    width: 38px; height: 38px; object-fit: contain;
    background: #ffffff; border-radius: 50%; flex-shrink: 0;
    padding: 4px; box-sizing: border-box;
    border: 1.5px solid rgba(255, 255, 255, 0.4);
}
.sidebar-logo .logo {
    font-family: var(--font-heading);
    font-size: 24px; font-weight: 900;
    color: #1db899b1 !important;
    letter-spacing: -.3px; margin: 0; line-height: 1;
}
.sidebar-logo .logo span { color: #ff009db1 !important; }
.sidebar-logo small {
    display: block; font-family: var(--font-body);
    font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
    color: rgba(255,255,255,.65); margin-top: 8px;
}
.sidebar-nav { flex:1; padding:20px 18px; display:flex; flex-direction:column; gap:4px; overflow-y:auto; }
.nav-section { font-family: var(--font-ui); font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,.55); padding:18px 16px 8px; font-weight:600; }
.nav-item { font-family: var(--font-ui); display:flex; align-items:center; gap:14px; padding:13px 18px; border-radius:12px; font-size:14px; font-weight:500; color:rgba(255,255,255,.85); transition:all .2s; letter-spacing:0.01em; }
.nav-item:hover { background:rgba(255,255,255,.2); color:#fff; transform:translateX(3px); }
.nav-item.active { background:rgba(255,255,255,.28); color:#fff; font-weight:600; border-left:3px solid #fff; padding-left:15px; }
.nav-item i { font-size:17px; width:22px; flex-shrink:0; }
.badge-notif { font-family: var(--font-ui); background:#fff; color:var(--accent); font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; margin-left:auto; }
.sidebar-footer { padding:16px 18px 20px; border-top:1.5px solid rgba(255,255,255,.2); background:rgba(0,0,0,.1); }
.btn-logout { font-family: var(--font-ui); display:flex; align-items:center; gap:10px; padding:11px 16px; border-radius:10px; font-size:13px; font-weight:500; color:rgba(255,255,255,.85); transition:background .2s; width:100%; letter-spacing:0.01em; }
.btn-logout i { font-size:16px; }
.btn-logout:hover { background:rgba(255,255,255,.2); color:#fff; }
.nav-item-toko {
    margin-top: 0; background: transparent; border: none;
    color: rgba(255,255,255,.85) !important; font-weight: 500 !important;
    justify-content: flex-start; border-radius: 12px; box-shadow: none; letter-spacing: 0.01em;
}
.nav-item-toko:hover {
    background: rgba(255,255,255,.2) !important; border-color: transparent !important;
    box-shadow: none; transform: translateX(3px) !important; color: #fff !important;
}
.nav-ext-icon { font-size: 11px !important; width: auto !important; margin-left: auto; opacity: .6; }

/* MAIN */
.main { margin-left:300px; flex:1; display:flex; flex-direction:column; position:relative; z-index:1; }
.topbar {
    background:rgba(255,255,255,.95); backdrop-filter:blur(12px);
    border-bottom:1.5px solid var(--border);
    padding:0 32px; height:64px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:40;
    box-shadow:0 2px 12px rgba(212,84,127,.07);
}
.topbar-left { display:flex; align-items:center; gap:12px; }
.topbar-title { font-family: var(--font-heading); font-size:18px; font-weight:700; color:var(--text); }
.topbar-right { display:flex; align-items:center; gap:10px; }
.topbar-date { font-family: var(--font-body); font-size:12px; color:var(--muted); }
.btn-toko { font-family: var(--font-ui); display:flex; align-items:center; gap:6px; padding:7px 16px; border-radius:8px; background:linear-gradient(135deg,#F4A7C3,#E8719A); font-size:12px; font-weight:600; color:#fff; box-shadow:0 3px 12px rgba(212,84,127,.35); transition:opacity .2s; }
.btn-toko:hover { opacity:.88; }

.content { padding:26px 28px; flex:1; }

/* STATS */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.stat-card { background:var(--white); border:1.5px solid var(--border); border-radius:16px; padding:20px; position:relative; overflow:hidden; transition:transform .2s, box-shadow .2s; box-shadow:0 2px 12px rgba(212,84,127,.08); }
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(212,84,127,.15); }
.stat-card::after { content:''; position:absolute; bottom:-20px; right:-20px; width:90px; height:90px; border-radius:50%; opacity:.07; }
.stat-card.c1::after { background:#E8719A; }
.stat-card.c2::after { background:#FFB300; }
.stat-card.c3::after { background:#FF6D00; }
.stat-card.c4::after { background:#00BFA5; }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:19px; margin-bottom:14px; }
.stat-card.c1 .stat-icon { background:#FFE0EF; color:#E8719A; }
.stat-card.c2 .stat-icon { background:#FFF8E1; color:#FFB300; }
.stat-card.c3 .stat-icon { background:#FBE9E7; color:#FF6D00; }
.stat-card.c4 .stat-icon { background:#E0F2F1; color:#00BFA5; }
.stat-value { font-family: var(--font-heading); font-size:26px; font-weight:700; color:var(--text); line-height:1; margin-bottom:5px; }
.stat-label { font-family: var(--font-body); font-size:12px; color:var(--muted); }

/* ALERT PERHATIAN */
.alert-perhatian {
    font-family: var(--font-body);
    display:flex; align-items:center; gap:12px;
    background:#FFF8E1; border:1.5px solid #FFD54F;
    border-radius:12px; padding:14px 18px; margin-bottom:20px;
    font-size:13px; color:#F57F17;
}
.alert-perhatian a { font-family: var(--font-ui); color:#E65100; font-weight:600; text-decoration:underline; }

/* CARD */
.card { background:var(--white); border:1.5px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(212,84,127,.07); }
.card-header {
    padding:14px 20px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    background:linear-gradient(to right,#FFF0F5,#fff);
}
.card-header h3 { font-family: var(--font-heading); font-size:14px; font-weight:600; color:var(--text); }
.filter-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.filter-select, .search-input {
    font-family: var(--font-body);
    background:var(--surface2); border:1.5px solid var(--border); border-radius:8px;
    color:var(--text); font-size:12px; padding:6px 12px; outline:none;
}
.filter-select:focus, .search-input:focus { border-color:var(--accent); }
.btn-filter { font-family: var(--font-ui); padding:6px 14px; border-radius:8px; background:linear-gradient(135deg,#F4A7C3,#E8719A); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; transition:opacity .2s; }
.btn-filter:hover { opacity:.85; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { font-family: var(--font-ui); text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); padding:10px 20px; border-bottom:1.5px solid var(--border); font-weight:600; background:#FFF2F7; }
td { font-family: var(--font-body); padding:12px 20px; font-size:13px; border-bottom:1px solid #FFE0EF; vertical-align:middle; color:var(--text2); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#FFF0F5; }

/* BADGE STATUS PESANAN */
.badge { font-family: var(--font-ui); display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.badge-menunggu     { background:#FFE0EF; color:#E8719A; }
.badge-dikonfirmasi { background:#FFF8E1; color:#F9A825; }
.badge-diproses     { background:#FBE9E7; color:#E64A19; }
.badge-dikirim      { background:#E0F2F1; color:#00897B; }
.badge-pengantaran  { background:#E3F2FD; color:#1565C0; }   /* biru – sedang diantar COD */
.badge-menunggu-pembeli { background:#FFF3E0; color:#E65100; } /* oranye – COD ambil di toko */
.badge-selesai      { background:#E0F2F1; color:#00897B; }
.badge-dibatalkan   { background:#FFEBEE; color:#E53935; }

/* BADGE TRANSFER */
.tbadge { font-family: var(--font-ui); display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; }
.tbadge-menunggu     { background:#FFF8E1; color:#F9A825; }
.tbadge-dikonfirmasi { background:#E0F2F1; color:#00897B; }
.tbadge-ditolak      { background:#FFEBEE; color:#E53935; }

/* INDIKATOR */
.dot-alert { width:7px; height:7px; border-radius:50%; background:var(--yellow); display:inline-block; margin-right:4px; animation:pulse 1.5s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

/* AKSI */
.btn-detail { font-family: var(--font-ui); display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; background:linear-gradient(135deg,#F4A7C3,#E8719A); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; transition:opacity .2s; text-decoration:none; }
.btn-detail:hover { opacity:.85; }

.empty { font-family: var(--font-body); text-align:center; padding:48px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2.5rem; display:block; margin-bottom:10px; color:var(--pink); }

.alert-msg { font-family: var(--font-body); padding:12px 20px; border-radius:10px; font-size:13px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.alert-success { background:#E0F2F1; color:#00695C; border:1.5px solid #80CBC4; }

/* RESPONSIVE MOBILE */
.btn-toggle-sidebar { display:none; background:var(--surface2); border:1.5px solid var(--border); border-radius:10px; width:38px; height:38px; align-items:center; justify-content:center; cursor:pointer; font-size:18px; color:var(--text); }
.sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:98; }
.sidebar-overlay.active { display:block; }

@media (max-width:1024px) {
    .main { margin-left:0 !important; }
    .sidebar { position:fixed; left:0; top:0; height:100vh; width:280px; border-radius:0; transform:translateX(-100%); transition:transform 0.3s ease; z-index:99; }
    .sidebar.active { transform:translateX(0); }
    .btn-toggle-sidebar { display:flex !important; }
    .stats-grid { grid-template-columns:repeat(2,1fr) !important; gap:10px; }
    .topbar { padding:0 16px; }
}
@media (max-width:768px) {
    .topbar { padding:0 14px; height:auto; min-height:56px; }
    .content { padding:14px 12px; }
    .table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
    .stats-grid { grid-template-columns:repeat(2,1fr) !important; gap:8px; }
    .stat-card { padding:14px; }
    .card-header { flex-wrap:wrap; gap:8px; }
    .filter-row { width:100%; flex-wrap:wrap; }
    .filter-row .search-input, .filter-row .filter-select { flex:1; min-width:120px; }
    table th:nth-child(5), table td:nth-child(5),
    table th:nth-child(6), table td:nth-child(6),
    table th:nth-child(8), table td:nth-child(8) { display:none; }
}
@media (max-width:480px) {
    .stats-grid { grid-template-columns:repeat(2,1fr) !important; gap:8px; }
    .content { padding:12px 10px; }
    .topbar-date { display:none; }
    .topbar-title { font-size:15px; }
    td, th { padding:6px 8px; font-size:11px; }
    .badge { font-size:10px; padding:2px 7px; }
    table th:nth-child(3), table td:nth-child(3),
    table th:nth-child(4), table td:nth-child(4),
    table th:nth-child(5), table td:nth-child(5),
    table th:nth-child(6), table td:nth-child(6),
    table th:nth-child(8), table td:nth-child(8) { display:none; }
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<!-- OVERLAY SIDEBAR (untuk mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()" id="sidebarToggleBtn">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title">Pesanan</div>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'konfirmasi'): ?>
            <div class="alert-msg alert-success"><i class="bi bi-check-circle-fill"></i> Transfer berhasil dikonfirmasi. Status pesanan diperbarui.</div>
            <?php elseif ($_GET['msg'] === 'tolak'): ?>
            <div class="alert-msg" style="background:#FFEBEE;color:#C62828;border:1.5px solid #EF9A9A;"><i class="bi bi-x-circle-fill"></i> Transfer ditolak. Pembeli akan diberitahu.</div>
            <?php elseif ($_GET['msg'] === 'resi'): ?>
            <div class="alert-msg alert-success"><i class="bi bi-truck"></i> Nomor resi berhasil disimpan. Status diperbarui ke Dikirim.</div>
            <?php elseif ($_GET['msg'] === 'status'): ?>
            <div class="alert-msg alert-success"><i class="bi bi-check-circle-fill"></i> Status pesanan berhasil diperbarui.</div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($total_perhatian > 0): ?>
        <div class="alert-perhatian">
            <span class="dot-alert"></span>
            <span><strong><?= $total_perhatian ?> pesanan</strong> menunggu konfirmasi transfer —
            <a href="pesanan.php?status=menunggu">lihat sekarang</a></span>
        </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card c1">
                <div class="stat-icon"><i class="bi bi-bag"></i></div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card c2">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-value"><?= $total_menunggu ?></div>
                <div class="stat-label">Menunggu</div>
            </div>
            <div class="stat-card c3">
                <div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="stat-value"><?= $total_diproses ?></div>
                <div class="stat-label">Diproses</div>
            </div>
            <div class="stat-card c4">
                <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value"><?= $total_selesai ?></div>
                <div class="stat-label">Selesai</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-bag-check" style="color:var(--accent);margin-right:6px;"></i> Daftar Pesanan</h3>
                <form method="GET" class="filter-row">
                    <input type="text" name="search" class="search-input" placeholder="Cari pembeli / kode..." value="<?= escape($search) ?>">
                    <select name="status" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="menunggu"     <?= $filter_status==='menunggu'     ? 'selected':'' ?>>Menunggu</option>
                        <option value="dikonfirmasi" <?= $filter_status==='dikonfirmasi' ? 'selected':'' ?>>Dikonfirmasi</option>
                        <option value="diproses"     <?= $filter_status==='diproses'     ? 'selected':'' ?>>Diproses</option>
                        <option value="dikirim"      <?= $filter_status==='dikirim'      ? 'selected':'' ?>>Dikirim / Dalam Pengantaran / Menunggu Pembeli</option>
                        <option value="selesai"      <?= $filter_status==='selesai'      ? 'selected':'' ?>>Selesai</option>
                        <option value="dibatalkan"   <?= $filter_status==='dibatalkan'   ? 'selected':'' ?>>Dibatalkan</option>
                    </select>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel"></i> Filter</button>
                </form>
            </div>
            <div class="table-wrap">
                <?php if ($q_pesanan && mysqli_num_rows($q_pesanan) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Kode / Pembeli</th>
                            <th>Produk</th>
                            <th>Total</th>
                            <th>Metode</th>
                            <th>Transfer</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($q_pesanan)): ?>
                        <?php
                        $status = $row['status'] ?? 'menunggu';
                        $badge_class = 'badge-' . $status;

                        $is_cod_row = ($row['metode'] === 'cod');
                        $jenis_cod_row = '';
                        if (!empty($row['jenis_cod'])) {
                            $jenis_cod_row = $row['jenis_cod'];
                        } elseif (preg_match('/Jenis COD:\s*(\w+)/i', $row['catatan'] ?? '', $m_row)) {
                            $jenis_cod_row = $m_row[1];
                        }

                        // FIX: 'ambil' = pembeli ambil ke toko penjual → "Menunggu Pembeli"
                        //      'antar' = penjual antar ke rumah pembeli → "Dalam Pengantaran"
                        $is_beli_ke_penjual_row = ($is_cod_row && $jenis_cod_row === 'ambil');

                        $status_label = match($status) {
                            'menunggu'     => 'Menunggu',
                            'dikonfirmasi' => 'Dikonfirmasi',
                            'diproses'     => 'Diproses',
                            'dikirim'      => $is_beli_ke_penjual_row ? 'Menunggu Pembeli' : ($is_cod_row ? 'Dalam Pengantaran' : 'Dikirim'),
                            'selesai'      => 'Selesai',
                            'dibatalkan'   => 'Dibatalkan',
                            default        => ucfirst($status)
                        };

                        // Badge class berbeda untuk tiap kondisi status dikirim
                        if ($status === 'dikirim') {
                            if ($is_beli_ke_penjual_row) {
                                $badge_class = 'badge-menunggu-pembeli';   // kuning – pembeli belum ambil
                            } elseif ($is_cod_row) {
                                $badge_class = 'badge-pengantaran';        // biru – sedang diantar
                            } else {
                                $badge_class = 'badge-dikirim';            // hijau – ekspedisi
                            }
                        }
                        $st_transfer = $row['status_transfer'] ?? null;
                        $butuh_aksi  = ($row['metode'] === 'transfer' && $st_transfer === 'menunggu' && $row['bukti_transfer']);
                        ?>
                        <tr>
                            <td style="color:var(--muted);font-size:12px;"><?= $no++ ?></td>
                            <td>
                                <?php if ($butuh_aksi): ?><span class="dot-alert" title="Perlu konfirmasi transfer"></span><?php endif; ?>
                                <div style="font-weight:600;color:var(--text);font-size:12px;"><?= escape($row['kode_pesanan']) ?></div>
                                <div style="font-size:12px;color:var(--text2);"><?= escape($row['nama_pembeli']) ?></div>
                            </td>
                            <td style="font-size:12px;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= escape($row['nama_produk'] ?? '-') ?></td>
                            <td style="font-weight:600;color:var(--accent);"><?= formatRupiah($row['total_bayar']) ?></td>
                            <td>
                                <?php if ($row['metode'] === 'cod'): ?>
                                    <span style="font-size:12px;color:var(--green);"><i class="bi bi-cash"></i> COD</span>
                                <?php else: ?>
                                    <span style="font-size:12px;color:var(--accent);"><i class="bi bi-credit-card"></i> Transfer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['metode'] === 'transfer' && $st_transfer): ?>
                                    <span class="tbadge tbadge-<?= $st_transfer ?>">
                                        <?= match($st_transfer) {
                                            'menunggu'     => '⏳ Menunggu',
                                            'dikonfirmasi' => '✓ OK',
                                            'ditolak'      => '✗ Ditolak',
                                            default        => $st_transfer
                                        } ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--muted);font-size:11px;">—</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= $badge_class ?>"><?= $status_label ?></span></td>
                            <td style="color:var(--muted);font-size:11px;white-space:nowrap;"><?= date('d M Y<\b\r>H:i', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="detail_pesanan.php?id=<?= $row['id'] ?>" class="btn-detail">
                                    <i class="bi bi-eye"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty">
                    <i class="bi bi-bag-x"></i>
                    Tidak ada pesanan ditemukan
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
</script>

</body>
</html>