<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

$total_produk     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='aktif'"))[0] ?? 0;
$total_pesanan    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan"))[0] ?? 0;
$total_pendapatan = mysqli_fetch_row(mysqli_query($conn, "SELECT SUM(total_bayar) FROM pesanan WHERE status='selesai'"))[0] ?? 0;
$pesanan_pending  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='menunggu'"))[0] ?? 0;
$total_unread = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
$nego_menunggu = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;

$q_pesanan = mysqli_query($conn, "
    SELECT ps.*, pb.nama AS nama_pembeli
    FROM pesanan ps
    JOIN pembeli pb ON pb.id = ps.pembeli_id
    ORDER BY ps.created_at DESC LIMIT 5
");
$q_produk = mysqli_query($conn, "SELECT * FROM produk ORDER BY created_at DESC LIMIT 5");

$data_harian = [];
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i days"));
    $label = date('d M', strtotime("-$i days"));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND DATE(created_at)='$tgl'"));
    $data_harian[] = ['label' => $label, 'value' => (int)$r[0]];
}
$data_mingguan = [];
for ($i = 7; $i >= 0; $i--) {
    $start = date('Y-m-d', strtotime("monday -$i week"));
    $end   = date('Y-m-d', strtotime("sunday -$i week"));
    $label = 'W' . date('W', strtotime($start));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND DATE(created_at) BETWEEN '$start' AND '$end'"));
    $data_mingguan[] = ['label' => $label, 'value' => (int)$r[0]];
}
$data_bulanan = [];
for ($i = 11; $i >= 0; $i--) {
    $bln   = date('Y-m', strtotime("-$i months"));
    $label = date('M y', strtotime("-$i months"));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND DATE_FORMAT(created_at,'%Y-%m')='$bln'"));
    $data_bulanan[] = ['label' => $label, 'value' => (int)$r[0]];
}
$data_tahunan = [];
for ($i = 4; $i >= 0; $i--) {
    $thn = date('Y', strtotime("-$i years"));
    $r = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE status='selesai' AND YEAR(created_at)='$thn'"));
    $data_tahunan[] = ['label' => $thn, 'value' => (int)$r[0]];
}

$q_kategori = mysqli_query($conn, "
    SELECT pr.kategori, COUNT(*) AS jumlah
    FROM pesanan ps
    JOIN produk pr ON pr.id = ps.produk_id
    WHERE ps.status = 'selesai'
    GROUP BY pr.kategori
    ORDER BY jumlah DESC
    LIMIT 5
");
$data_kategori = [];
$total_terjual = 0;
if ($q_kategori) {
    while ($r = mysqli_fetch_assoc($q_kategori)) {
        $data_kategori[] = $r;
        $total_terjual  += $r['jumlah'];
    }
}

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
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
<title>Dashboard — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,500;0,700;0,900;1,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
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
    --text:     #1A1A1A;
    --text2:    #444444;
    --muted:    #BBA0B0;
    --white:    #FFFFFF;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'DM Sans',sans-serif;
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

/* ── SIDEBAR ── */
.sidebar {
    width: 300px; /* ✅ Diperlebar dari 270px */
    background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 50;
    border-radius: 0 28px 28px 0;
    box-shadow: 6px 0 32px rgba(212,84,127,.28);
    overflow: hidden;
}
.sidebar-logo {
    padding: 28px 28px 22px;
    border-bottom: 1.5px solid rgba(255,255,255,.2);
    background: rgba(255, 254, 254, 0.12);
}

/* ✅ PERBAIKAN: Khusus untuk mengatur gambar logonya saja */
.sidebar-logo .logo-img {
    width: 38px;
    height: 38px;
    object-fit: contain;     /* Mengatur isi gambar di dalam lingkaran */
    background: #ffffff;      /* Memberikan latar belakang bulat putih bersih di belakang logo */
    border-radius: 50%;       /* MEMBUAT BULAT SEMPURNA */
    flex-shrink: 0;           /* Mencegah gambar menyusut/gepeng */
    padding: 4px;             /* Memberi jarak manis antara logo dengan tepi lingkaran putih */
    box-sizing: border-box;
    border: 1.5px solid rgba(255, 255, 255, 0.4);
}

/* ✅ PERBAIKAN: Mengembalikan teks Cloudy Girls menjadi putih indah */
/* Mengubah tulisan "Cloudy" menjadi warna Hijau Toska */
.sidebar-logo .logo {
    font-family: 'Playfair Display', serif;
    font-size: 24px; 
    font-weight: 900;
    color: #1db899b1 !important; /* Warna Hijau Toska yang fresh */
    letter-spacing: -.3px;
    margin: 0;
    line-height: 1;
}

/* Mengubah tulisan "Girls" menjadi warna Pink Terang */
.sidebar-logo .logo span { 
    color: #ff009db1; !important; /* Warna Pink Terang menyala */
}

.sidebar-logo small {
    display: block; 
    font-size: 10px;
    letter-spacing: 2px; 
    text-transform: uppercase;
    color: rgba(255,255,255,.65); 
    margin-top: 8px;
}
/* ── SIDEBAR NAV ── */
.sidebar-nav {
    flex: 1;
    padding: 20px 18px; /* ✅ Padding lebih lebar */
    display: flex;
    flex-direction: column;
    gap: 4px; /* ✅ Gap antar item lebih besar */
    overflow-y: auto;
}
.nav-section {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,.55);
    padding: 18px 16px 8px; /* ✅ Padding atas lebih besar */
    font-weight: 600;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 14px; /* ✅ Gap icon-teks lebih besar */
    padding: 13px 18px; /* ✅ Padding vertikal & horizontal lebih besar */
    border-radius: 12px;
    font-size: 14px; /* ✅ Font lebih besar */
    font-weight: 500;
    color: rgba(255,255,255,.85);
    transition: all .2s;
    letter-spacing: 0.01em;
}
.nav-item:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
    transform: translateX(3px); /* ✅ Efek geser halus saat hover */
}
.nav-item.active {
    background: rgba(255,255,255,.28);
    color: #fff;
    font-weight: 600;
    border-left: 3px solid #fff;
    padding-left: 15px; /* kompensasi border */
}
.nav-item i {
    font-size: 17px; /* ✅ Icon lebih besar */
    width: 22px;
    flex-shrink: 0;
}
.badge-notif {
    background: #fff;
    color: var(--accent);
    font-size: 10px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    margin-left: auto;
}
.nav-item-toko {
    margin-top: 0;
    background: transparent;
    border: none;
    color: rgba(255,255,255,.85) !important;
    font-weight: 500 !important;
    justify-content: flex-start;
    border-radius: 12px;
    box-shadow: none;
    letter-spacing: 0.01em;
}
.nav-item-toko:hover {
    background: rgba(255,255,255,.2) !important;
    border-color: transparent !important;
    box-shadow: none;
    transform: translateX(3px) !important;
    color: #fff !important;
}
.nav-ext-icon {
    font-size: 11px !important;
    width: auto !important;
    margin-left: auto;
    opacity: .6;
}

/* ── SIDEBAR FOOTER (hanya logout) ── */
.sidebar-footer {
    padding: 16px 18px 20px; /* ✅ Padding disesuaikan */
    border-top: 1.5px solid rgba(255,255,255,.2);
    background: rgba(0,0,0,.1);
}
.btn-logout {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 16px; /* ✅ Lebih besar */
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,.85);
    transition: background .2s;
    width: 100%;
    letter-spacing: 0.01em;
}
.btn-logout i { font-size: 16px; }
.btn-logout:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
}

/* ── MAIN ── */
.main { margin-left: 300px; /* ✅ Sesuai lebar sidebar baru */ flex:1; display:flex; flex-direction:column; position:relative; z-index:1; }
.topbar {
    background:rgba(255,255,255,.95);
    backdrop-filter:blur(12px);
    border-bottom:1.5px solid var(--border);
    padding:0 32px; height:64px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:40;
    box-shadow:0 2px 12px rgba(212,84,127,.07);
}
.topbar-title { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; color:var(--text); }
.topbar-right { display:flex; align-items:center; gap:10px; }
.topbar-date { font-size:12px; color:var(--muted); }
.btn-toko {
    display:flex; align-items:center; gap:6px;
    padding:8px 14px; border-radius:8px;
    background:linear-gradient(135deg,#F4A7C3,#E8719A);
    font-size:12px; font-weight:600; color:#fff;
    box-shadow:0 3px 12px rgba(212,84,127,.35);
    transition:opacity .2s;
}
.btn-toko:hover { opacity:.88; }

.content { padding:26px 28px; flex:1; }

/* ── STATS ── */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.stat-card {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; padding:20px;
    position:relative; overflow:hidden;
    transition:transform .2s, box-shadow .2s;
    box-shadow:0 2px 12px rgba(212,84,127,.08);
}
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(212,84,127,.15); }
.stat-card::after {
    content:''; position:absolute; bottom:-20px; right:-20px;
    width:90px; height:90px; border-radius:50%; opacity:.07;
}
.stat-card.c1::after { background:#E8719A; }
.stat-card.c2::after { background:#00BFA5; }
.stat-card.c3::after { background:#D4547F; }
.stat-card.c4::after { background:#E8719A; }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:19px; margin-bottom:14px; }
.stat-card.c1 .stat-icon { background:#FFE0EF; color:#E8719A; }
.stat-card.c2 .stat-icon { background:#E0F2F1; color:#00BFA5; }
.stat-card.c3 .stat-icon { background:#FADADD; color:#D4547F; }
.stat-card.c4 .stat-icon { background:#FFF8E1; color:#FFB300; }
.stat-value { font-size:clamp(17px,2.2vw,26px); font-weight:700; color:var(--text); line-height:1; margin-bottom:5px; }
.stat-label { font-size:12px; color:var(--muted); }

/* ── CHART ── */
.chart-card {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden; margin-bottom:22px;
    box-shadow:0 2px 12px rgba(212,84,127,.08);
}
.chart-header {
    padding:15px 20px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:10px;
    background:linear-gradient(to right,#FFF0F5,#fff);
}
.chart-header h3 { font-size:14px; font-weight:600; color:var(--text); }
.chart-tabs { display:flex; gap:4px; }
.chart-tab {
    padding:5px 13px; border-radius:8px; font-size:12px; font-weight:500;
    color:var(--muted); border:1.5px solid var(--border); cursor:pointer;
    transition:all .2s; background:var(--white);
}
.chart-tab:hover { color:var(--accent); border-color:var(--pink); }
.chart-tab.active {
    background:linear-gradient(135deg,#F4A7C3,#E8719A);
    color:#fff; border-color:transparent;
    box-shadow:0 3px 10px rgba(212,84,127,.3);
}
.chart-body { padding:20px; }
.chart-canvas-wrap { position:relative; height:220px; }

/* ── GRID + CARDS ── */
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
.card {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden;
    box-shadow:0 2px 12px rgba(212,84,127,.07);
}
.card-header {
    padding:14px 20px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    background:linear-gradient(to right,#FFF0F5,#fff);
}
.card-header h3 { font-size:14px; font-weight:600; color:var(--text); }
.card-header a { font-size:12px; color:var(--accent); font-weight:500; }
.card-header a:hover { text-decoration:underline !important; }
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th {
    text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase;
    color:var(--muted); padding:10px 18px; border-bottom:1.5px solid var(--border);
    font-weight:600; background:#FFF2F7;
}
td { padding:11px 18px; font-size:13px; border-bottom:1px solid #FFE0EF; color:var(--text2); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#FFF0F5; }
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-green  { background:#E0F2F1; color:#00897B; }
.badge-yellow { background:#FFF2F7; color:#F9A825; }
.badge-red    { background:#FFEBEE; color:#E53935; }
.badge-pink   { background:#FFE0EF; color:#E8719A; }
.produk-row { display:flex; align-items:center; gap:10px; }
.produk-thumb { width:38px; height:38px; border-radius:8px; object-fit:cover; flex-shrink:0; border:1.5px solid var(--border); }
.produk-nama { font-size:13px; font-weight:500; color:var(--text); }
.produk-kat  { font-size:11px; color:var(--muted); }
.empty { text-align:center; padding:36px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2rem; display:block; margin-bottom:8px; color:var(--pink); }

/* ── QUICK ACTIONS ── */
.actions-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:10px; padding:16px; }
.action-btn {
    display:flex; align-items:center; gap:10px;
    padding:12px 14px; border-radius:10px;
    background:#FFF0F5; border:1.5px solid var(--border);
    font-size:13px; font-weight:500; color:var(--text2); transition:all .2s;
    cursor:pointer; font-family:'DM Sans',sans-serif; text-align:left;
    width:100%;
}
.action-btn:hover { border-color:var(--accent); color:var(--accent); background:#FFE0EF; }
.action-btn i { font-size:15px; }

/* ── MODAL TAMBAH PRODUK ── */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.55); backdrop-filter:blur(4px);
    z-index:200; align-items:center; justify-content:center; padding:20px;
}
.modal-overlay.show { display:flex; }
.modal-box {
    background:#fff; border:1.5px solid var(--border);
    border-radius:18px; width:100%; max-width:640px;
    max-height:90vh; overflow-y:auto;
    box-shadow:0 25px 60px rgba(212,84,127,.2);
    animation:modalIn .25s ease;
}
@keyframes modalIn { from{opacity:0;transform:translateY(20px) scale(.97)} to{opacity:1;transform:none} }
.modal-box::-webkit-scrollbar { width:4px; }
.modal-box::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }
.modal-head {
    padding:18px 22px 14px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; background:#fff; z-index:1;
    background:linear-gradient(to right,#FFF0F5,#fff);
}
.modal-head-left { display:flex; align-items:center; gap:10px; }
.modal-head-icon {
    width:36px; height:36px; border-radius:10px;
    background:linear-gradient(135deg,#FFE0EF,#F4A7C3);
    display:flex; align-items:center; justify-content:center;
    font-size:16px; color:var(--accent);
}
.modal-head-title { font-family:'Playfair Display',serif; font-size:16px; font-weight:700; color:var(--text); }
.modal-head-sub { font-size:11px; color:var(--muted); margin-top:1px; }
.btn-close-modal {
    width:32px; height:32px; border-radius:8px;
    background:var(--surface2); border:1.5px solid var(--border);
    color:var(--muted); cursor:pointer; font-size:15px;
    display:flex; align-items:center; justify-content:center; transition:all .2s;
}
.btn-close-modal:hover { border-color:var(--red); color:var(--red); }
.modal-body { padding:20px 22px 22px; }
.form-section { margin-bottom:18px; }
.form-section-title {
    font-size:10px; letter-spacing:1.5px; text-transform:uppercase;
    color:var(--muted); font-weight:600; margin-bottom:11px;
    display:flex; align-items:center; gap:6px;
}
.form-section-title::after { content:''; flex:1; height:1px; background:var(--border); }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.form-full { grid-column:1/-1; }
.form-group { display:flex; flex-direction:column; gap:5px; }
.form-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); }
.form-label .req { color:var(--accent); margin-left:2px; }
.form-ctrl {
    background:#FFF0F5; border:1.5px solid var(--border);
    border-radius:8px; color:var(--text); padding:9px 12px;
    font-family:'DM Sans',sans-serif; font-size:13px; outline:none; width:100%;
    transition:border-color .2s, box-shadow .2s;
}
.form-ctrl:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(212,84,127,.1); }
.form-ctrl::placeholder { color:var(--muted); }
textarea.form-ctrl { resize:vertical; min-height:78px; }
select.form-ctrl option { background:#fff; }
.upload-area {
    border:2px dashed var(--border); border-radius:10px;
    padding:20px 16px; text-align:center; cursor:pointer;
    transition:all .2s; position:relative; overflow:hidden;
    background:#FFF2F7; display:block;
}
.upload-area:hover { border-color:var(--accent); background:#FFE0EF; }
.upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.ua-icon { font-size:26px; color:var(--accent); opacity:.7; }
.ua-text { font-size:13px; color:var(--accent); font-weight:600; margin-top:5px; }
.ua-hint { font-size:11px; color:var(--muted); margin-top:2px; }
#dash-preview { display:none; max-height:100px; max-width:100%; object-fit:cover; border-radius:8px; border:1.5px solid var(--border); margin:10px auto 0; }
.modal-foot {
    padding:14px 22px; border-top:1.5px solid var(--border);
    display:flex; justify-content:flex-end; gap:10px;
    position:sticky; bottom:0;
    background:linear-gradient(to right,#FFF0F5,#fff);
}
.btn-cancel-m {
    padding:9px 20px; border-radius:8px;
    background:#FFF0F5; border:1.5px solid var(--border);
    color:var(--muted); font-size:13px; cursor:pointer;
    font-family:'DM Sans',sans-serif; transition:all .2s;
}
.btn-cancel-m:hover { color:var(--text); border-color:var(--accent); }
.btn-save-m {
    padding:9px 22px; border-radius:8px;
    background:linear-gradient(135deg,#F4A7C3,#E8719A);
    color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer;
    font-family:'DM Sans',sans-serif; transition:opacity .2s;
    display:flex; align-items:center; gap:7px;
    box-shadow:0 4px 14px rgba(212,84,127,.3);
}
.btn-save-m:hover { opacity:.87; }

/* ── KATEGORI TERLARIS ── */
.donut-wrap { display:flex; align-items:center; justify-content:center; padding:20px 16px 8px; }
.donut-canvas-wrap { position:relative; width:150px; height:150px; flex-shrink:0; }
.donut-center { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; pointer-events:none; }
.donut-center .total-num  { font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.donut-center .total-label{ font-size:10px; color:var(--muted); text-transform:uppercase; letter-spacing:1px; margin-top:2px; }
.donut-legend { flex:1; padding-left:20px; display:flex; flex-direction:column; gap:8px; }
.legend-item  { display:flex; align-items:center; justify-content:space-between; gap:8px; font-size:13px; }
.legend-left  { display:flex; align-items:center; gap:7px; }
.legend-dot   { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.legend-name  { color:var(--text2); }
.legend-pct   { font-weight:600; color:var(--muted); font-size:12px; }

@media (max-width:900px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .grid-2 { grid-template-columns: 1fr !important; }
    
    /* Modifikasi layout grid chart & donut agar tidak ringsek */
    div[style*="grid-template-columns:1fr 300px"] { 
        grid-template-columns: 1fr !important; 
    }

    /* Sembunyikan sidebar ke kiri secara default di HP */
    .sidebar { 
        border-radius: 0; 
        width: 280px; 
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    /* Class tambahan saat sidebar aktif/muncul di HP */
    .sidebar.active {
        transform: translateX(0);
    }
    
    /* Main content memenuhi layar di HP */
    .main { margin-left: 0; }
    
    /* Tampilkan tombol menu di topbar khusus HP */
    .btn-toggle-sidebar {
        display: flex !important;
    }

    .stats-grid .stat-card { padding: 14px; }
    .chart-tabs { flex-wrap: wrap; }
    table { font-size: 12px; }
    td, th { padding: 8px 6px; }
}

@media (max-width:480px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .content { padding: 14px 12px; }
    .topbar { padding: 10px 14px; }
    .card { padding: 14px; }
    .card-header { flex-wrap: wrap; gap: 6px; }
    .donut-wrap { flex-direction: column; }
    .donut-legend { padding-left: 0; padding-top: 12px; width: 100%; }
    .actions-grid { grid-template-columns: 1fr 1fr; }
    div[style*="grid-template-columns:1fr 300px"] { 
        grid-template-columns: 1fr !important; 
    }
}
</style>
</head>

<?php include '../includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
    <div style="display: flex; align-items: center; gap: 15px;">
        <button class="btn-toggle-sidebar" id="toggleSidebar" style="display: none; background: var(--surface2); border: 1.5px solid var(--border); padding: 6px 10px; border-radius: 8px; color: var(--accent); cursor: pointer; font-size: 18px;">
            <i class="bi bi-list"></i>
        </button>
        <div class="topbar-title">Dashboard</div>
    </div>
    
    <div class="topbar-right">
        <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
    </div>
</div>

    <div class="content">

        <div class="stats-grid">
            <div class="stat-card c1">
                <div class="stat-icon"><i class="bi bi-handbag"></i></div>
                <div class="stat-value"><?= $total_produk ?></div>
                <div class="stat-label">Produk Aktif</div>
            </div>
            <div class="stat-card c2">
                <div class="stat-icon"><i class="bi bi-bag-check"></i></div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card c3">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value"><?= $pesanan_pending ?></div>
                <div class="stat-label">Menunggu Konfirmasi</div>
            </div>
            <div class="stat-card c4">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-value"><?= formatRupiah($total_pendapatan) ?></div>
                <div class="stat-label">Pendapatan</div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 300px;gap:18px;margin-bottom:22px;">

            <div class="chart-card" style="margin-bottom:0;">
                <div class="chart-header">
                    <h3><i class="bi bi-bar-chart-line" style="color:var(--accent);margin-right:6px;"></i> Grafik Penjualan</h3>
                    <div class="chart-tabs">
                        <button class="chart-tab active" onclick="switchChart('harian',this)">Harian</button>
                        <button class="chart-tab" onclick="switchChart('mingguan',this)">Mingguan</button>
                        <button class="chart-tab" onclick="switchChart('bulanan',this)">Bulanan</button>
                        <button class="chart-tab" onclick="switchChart('tahunan',this)">Tahunan</button>
                    </div>
                </div>
                <div class="chart-body">
                    <div class="chart-canvas-wrap"><canvas id="salesChart"></canvas></div>
                </div>
            </div>

            <div class="card" style="margin-bottom:0;">
                <div class="card-header">
                    <h3><i class="bi bi-pie-chart" style="color:var(--yellow);margin-right:6px;"></i> Kategori Terlaris</h3>
                    <a href="produk.php">Detail →</a>
                </div>
                <?php if (!empty($data_kategori)): ?>
                <div style="display:flex;flex-direction:column;align-items:center;padding:20px 16px 16px;">
                    <div class="donut-canvas-wrap">
                        <canvas id="donutChart"></canvas>
                        <div class="donut-center">
                            <div class="total-num"><?= $total_terjual ?></div>
                            <div class="total-label">Total</div>
                        </div>
                    </div>
                    <div style="width:100%;margin-top:16px;display:flex;flex-direction:column;gap:9px;">
                        <?php
                        $donut_colors = ['#E8719A','#2196F3','#FF9800','#00BFA5','#9C27B0'];
                        foreach ($data_kategori as $i => $kat):
                            $pct = $total_terjual > 0 ? round($kat['jumlah'] / $total_terjual * 100) : 0;
                            $color = $donut_colors[$i % count($donut_colors)];
                        ?>
                        <div class="legend-item">
                            <div class="legend-left">
                                <span class="legend-dot" style="background:<?= $color ?>;"></span>
                                <span class="legend-name"><?= escape($kat['kategori']) ?></span>
                            </div>
                            <span class="legend-pct"><?= $pct ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php else: ?>
                <div class="empty" style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:220px;"><i class="bi bi-pie-chart"></i>Belum ada data penjualan</div>
                <?php endif; ?>
            </div>

        </div>

        <div class="grid-2">
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
                                    <div style="font-weight:500;color:var(--text);"><?= escape($row['nama_pembeli']) ?></div>
                                    <div style="font-size:11px;color:var(--muted);"><?= date('d M Y', strtotime($row['created_at'])) ?></div>
                                </td>
                                <td style="font-weight:600;color:var(--accent);"><?= formatRupiah($row['total_bayar']) ?></td>
                                <td>
                                    <?php
                                    $status = $row['status'] ?? 'menunggu';
                                    $badge  = match($status) {
                                        'selesai' => 'badge-green',
                                        'proses'  => 'badge-yellow',
                                        'batal'   => 'badge-red',
                                        default   => 'badge-pink'
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
                                             onerror="this.src='https://placehold.co/40x40/FFE4EE/FF4081?text=CG'">
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

        <div class="card" style="margin-top:18px;">
            <div class="card-header">
                <h3><i class="bi bi-lightning-charge" style="color:var(--yellow);margin-right:6px;"></i> Aksi Cepat</h3>
            </div>
            <div class="actions-grid">
                <button onclick="openTambahModal()" class="action-btn">
                    <i class="bi bi-plus-circle" style="color:var(--accent);"></i> Tambah Produk Baru
                </button>
                <a href="pesanan.php"    class="action-btn"><i class="bi bi-bag-check"  style="color:var(--green);"></i> Kelola Pesanan</a>
                <a href="pengaturan.php" class="action-btn"><i class="bi bi-gear"        style="color:var(--yellow);"></i> Pengaturan Toko</a>
                <a href="ulasan.php"     class="action-btn"><i class="bi bi-star"        style="color:var(--pink2);"></i> Lihat Ulasan</a>
            </div>
        </div>

    </div>
</div>


<!-- MODAL TAMBAH PRODUK -->
<div class="modal-overlay" id="dashModalOverlay">
    <div class="modal-box">

        <div class="modal-head">
            <div class="modal-head-left">
                <div class="modal-head-icon"><i class="bi bi-handbag"></i></div>
                <div>
                    <div class="modal-head-title">Tambah Produk</div>
                    <div class="modal-head-sub">Isi detail produk baru</div>
                </div>
            </div>
            <button class="btn-close-modal" onclick="closeTambahModal()"><i class="bi bi-x-lg"></i></button>
        </div>

        <form method="POST" action="produk.php" enctype="multipart/form-data">
            <input type="hidden" name="id_edit" value="0">

            <div class="modal-body">

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-image" style="color:var(--pink2)"></i> Foto Produk
                    </div>
                    <label class="upload-area" for="dash_foto">
                        <input type="file" id="dash_foto" name="foto_utama" accept="image/*" onchange="dashPreview(this)">
                        <div class="ua-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="ua-text">Klik untuk upload foto</div>
                        <div class="ua-hint">JPG, PNG, WEBP — maks. 5MB</div>
                    </label>
                    <img id="dash-preview" src="" alt="Preview">
                </div>

                <div class="form-section">
                    <div class="form-section-title">
                        <i class="bi bi-info-circle" style="color:var(--accent)"></i> Informasi Dasar
                    </div>
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Nama Barang <span class="req">*</span></label>
                            <input type="text" name="nama_barang" class="form-ctrl" placeholder="cth. Kemeja Floral Vintage" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategori <span class="req">*</span></label>
                            <select name="kategori" class="form-ctrl" required>
                                <option value="Atasan">Atasan</option>
                                <option value="Bawahan">Bawahan</option>
                                <option value="Dress/Gamis">Dress/Gamis</option>
                                <option value="Outer">Outer</option>
                                <option value="Hijab &amp; Aksesoris">Hijab &amp; Aksesoris</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kondisi <span class="req">*</span></label>
                            <select name="kondisi" class="form-ctrl" required>
                                <option value="Mulus">Mulus</option>
                                <option value="Bekas Pakai">Bekas Pakai</option>
                                <option value="Perlu Perbaikan">Perlu Perbaikan</option>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" class="form-ctrl" placeholder="Ceritakan detail, kondisi, ukuran, warna, dll..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="form-section" style="margin-bottom:0;">
                    <div class="form-section-title">
                        <i class="bi bi-tag" style="color:var(--green)"></i> Harga &amp; Detail
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Harga (Rp) <span class="req">*</span></label>
                            <input type="number" name="harga" class="form-ctrl" placeholder="50000" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ukuran</label>
                            <select name="ukuran" class="form-ctrl">
                                <option value="">Pilih Ukuran</option>
                                <option>XS</option>
                                <option>S</option>
                                <option>M</option>
                                <option>L</option>
                                <option>XL</option>
                                <option>XXL</option>
                                <option>XXXL</option>
                                <option>All Size</option>
                            </select>
                        </div>
                    </div>
                </div>

            </div>

            <div class="modal-foot">
                <button type="button" class="btn-cancel-m" onclick="closeTambahModal()">
                    <i class="bi bi-x"></i> Batal
                </button>
                <button type="submit" class="btn-save-m">
                    <i class="bi bi-floppy"></i> Simpan Produk
                </button>
            </div>
        </form>

    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    
    document.addEventListener("DOMContentLoaded", function() {
    const toggleBtn = document.getElementById('toggleSidebar');
    const sidebar = document.querySelector('.sidebar');

    if(toggleBtn && sidebar) {
        // Aksi ketika tombol hamburger diklik
        toggleBtn.addEventListener('click', function(e) {
            sidebar.classList.toggle('active');
            e.stopPropagation();
        });


        // Klik di luar sidebar untuk menutup kembali (khusus HP)
        document.addEventListener('click', function(e) {
            if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                sidebar.classList.remove('active');
            }
        });
    }
});
const chartData = {
    harian:   <?= json_encode($data_harian) ?>,
    mingguan: <?= json_encode($data_mingguan) ?>,
    bulanan:  <?= json_encode($data_bulanan) ?>,
    tahunan:  <?= json_encode($data_tahunan) ?>
};
let salesChart = null;

function buildChart(period) {
    const raw    = chartData[period];
    const labels = raw.map(d => d.label);
    const values = raw.map(d => d.value);
    const ctx    = document.getElementById('salesChart').getContext('2d');
    const grad   = ctx.createLinearGradient(0, 0, 0, 220);
    grad.addColorStop(0, 'rgba(212,84,127,0.22)');
    grad.addColorStop(1, 'rgba(212,84,127,0.01)');
    if (salesChart) salesChart.destroy();
    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: values,
                borderColor: '#E8719A',
                backgroundColor: grad,
                borderWidth: 2.5,
                pointBackgroundColor: '#E8719A',
                pointBorderColor: '#fff',
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
                    backgroundColor: '#fff',
                    borderColor: '#F4A7C3',
                    borderWidth: 1.5,
                    titleColor: '#1A1A1A',
                    bodyColor: '#E8719A',
                    padding: 10,
                    callbacks: { label: ctx => ' Rp ' + ctx.parsed.y.toLocaleString('id-ID') }
                }
            },
            scales: {
                x: { grid: { color: 'rgba(212,167,195,0.3)' }, ticks: { color: '#AAAAAA', font: { size: 11 } } },
                y: {
                    grid: { color: 'rgba(212,167,195,0.3)' },
                    ticks: {
                        color: '#AAAAAA', font: { size: 11 },
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
buildChart('harian');

<?php if (!empty($data_kategori)): ?>
(function() {
    const labels = <?= json_encode(array_column($data_kategori, 'kategori')) ?>;
    const values = <?= json_encode(array_column($data_kategori, 'jumlah')) ?>;
    const colors = ['#E8719A','#2196F3','#FF9800','#00BFA5','#9C27B0'];
    const ctx = document.getElementById('donutChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data: values,
                backgroundColor: colors.slice(0, values.length),
                borderWidth: 3,
                borderColor: '#fff',
                hoverBorderWidth: 3,
                hoverOffset: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '68%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#fff',
                    borderColor: '#F4A7C3',
                    borderWidth: 1.5,
                    titleColor: '#1A1A1A',
                    bodyColor: '#E8719A',
                    padding: 10,
                    callbacks: {
                        label: ctx => ' ' + ctx.label + ': ' + ctx.parsed + ' terjual'
                    }
                }
            }
        }
    });
})();
<?php endif; ?>

function openTambahModal() {
    document.getElementById('dashModalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeTambahModal() {
    document.getElementById('dashModalOverlay').classList.remove('show');
    document.body.style.overflow = '';
    document.querySelector('#dashModalOverlay form').reset();
    const prev = document.getElementById('dash-preview');
    prev.src = '';
    prev.style.display = 'none';
}
function dashPreview(input) {
    const img = document.getElementById('dash-preview');
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => { img.src = e.target.result; img.style.display = 'block'; };
        r.readAsDataURL(input.files[0]);
    }
}
document.getElementById('dashModalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeTambahModal();
});
</script>
</body>
</html>