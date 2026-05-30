<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

$penjual_nama = $_SESSION['penjual_nama'] ?? 'Penjual';

$filter = $_GET['status'] ?? 'semua';
$where  = $filter !== 'semua' ? "WHERE nh.status = '" . mysqli_real_escape_string($conn, $filter) . "'" : "";

$q = mysqli_query($conn, "
    SELECT nh.*, pb.nama AS nama_pembeli, pb.no_hp AS hp_pembeli,
           pr.nama_barang, pr.foto_utama, pr.harga AS harga_produk
    FROM nego_harga nh
    JOIN pembeli pb ON pb.id = nh.pembeli_id
    JOIN produk pr ON pr.id = nh.produk_id
    $where
    ORDER BY nh.created_at DESC
");

$total_menunggu = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;
$total_counter  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='counter'"))[0] ?? 0;
$total_setuju   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='disetujui'"))[0] ?? 0;
$total_tolak    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='ditolak'"))[0] ?? 0;
$total_unread = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nego Harga — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
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
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
body::before{content:'';position:fixed;inset:0;background-image:radial-gradient(circle,#F4A7C3 1px,transparent 1px);background-size:28px 28px;opacity:.15;pointer-events:none;z-index:0;}
a{text-decoration:none;color:inherit;}
.sidebar {
    width: 300px;
    background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    border-radius: 0 28px 28px 0;
    box-shadow: 6px 0 32px rgba(212,84,127,.28);
    overflow-y: auto;
    z-index: 50;
    height: 100vh;
    position: sticky;
    top: 0;
}
.sidebar-logo {
    padding: 28px 28px 22px;
    border-bottom: 1.5px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.12);
}
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
.sidebar-logo .logo {
    font-family: 'Playfair Display', serif;
    font-size: 24px; 
    font-weight: 900;
    color: #1db899b1 !important; /* Warna Hijau Toska yang fresh */
    letter-spacing: -.3px;
    margin: 0;
    line-height: 1;
}
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
.sidebar-nav { flex:1; padding:20px 18px; display:flex; flex-direction:column; gap:4px; overflow-y:auto; }
.nav-section { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,.55); padding:18px 16px 8px; font-weight:600; }
.nav-item { display:flex; align-items:center; gap:14px; padding:13px 18px; border-radius:12px; font-size:14px; font-weight:500; color:rgba(255,255,255,.85); transition:all .2s; letter-spacing:0.01em; }
.nav-item:hover { background:rgba(255,255,255,.2); color:#fff; transform:translateX(3px); }
.nav-item.active { background:rgba(255,255,255,.28); color:#fff; font-weight:600; border-left:3px solid #fff; padding-left:15px; }
.nav-item i { font-size:17px; width:22px; flex-shrink:0; }
.badge-notif { background:#fff; color:var(--accent); font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; margin-left:auto; }
.nav-item-toko { margin-top:0; background:transparent; border:none; color:rgba(255,255,255,.85) !important; font-weight:500 !important; justify-content:flex-start; border-radius:12px; box-shadow:none; letter-spacing:0.01em; }
.nav-item-toko:hover { background:rgba(255,255,255,.2) !important; border-color:transparent !important; box-shadow:none; transform:translateX(3px) !important; color:#fff !important; }
.nav-ext-icon { font-size:11px !important; width:auto !important; margin-left:auto; opacity:.6; }
.sidebar-footer { padding:16px 18px 20px; border-top:1.5px solid rgba(255,255,255,.2); background:rgba(0,0,0,.1); }
.admin-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,.18);border-radius:10px;margin-bottom:10px;border:1.5px solid rgba(255,255,255,.3);}
.admin-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;border:2px solid rgba(255,255,255,.5);}
.admin-info .name{font-size:13px;font-weight:600;color:#fff;}
.admin-info .role{font-size:10px;color:rgba(255,255,255,.65);}
.btn-logout { display:flex; align-items:center; gap:10px; padding:11px 16px; border-radius:10px; font-size:13px; font-weight:500; color:rgba(255,255,255,.85); transition:background .2s; width:100%; letter-spacing:0.01em; }
.btn-logout i { font-size:16px; }
.btn-logout:hover { background:rgba(255,255,255,.2); color:#fff; }
.main{flex:1;display:flex;flex-direction:column;position:relative;z-index:1;min-width:0;}
.topbar{background:rgba(255,255,255,.95);backdrop-filter:blur(12px);border-bottom:1.5px solid var(--border);padding:0 28px;height:62px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;box-shadow:0 2px 12px rgba(212,84,127,.07);}
.topbar-title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;color:var(--text);}
.topbar-right{display:flex;align-items:center;gap:10px;}
.btn-toko{display:flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;background:linear-gradient(135deg,#F4A7C3,#E8719A);font-size:12px;font-weight:600;color:#fff;box-shadow:0 3px 12px rgba(212,84,127,.35);transition:opacity .2s;}
.btn-toko:hover{opacity:.88;}
.content{padding:26px 28px;flex:1;}
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
.stat-card{background:var(--white);border:1.5px solid var(--border);border-radius:16px;padding:18px;cursor:pointer;transition:all .2s;display:block;text-decoration:none;box-shadow:0 2px 12px rgba(212,84,127,.08);}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 8px 24px rgba(212,84,127,.15);}
.stat-card.active-filter{border-color:var(--accent);background:#FFE0EF;}
.stat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:12px;}
.stat-value{font-size:24px;font-weight:700;color:var(--text);margin-bottom:3px;}
.stat-label{font-size:12px;color:var(--muted);}
.filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.filter-tab{padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;border:1.5px solid var(--border);color:var(--muted);cursor:pointer;transition:all .2s;text-decoration:none;background:var(--white);}
.filter-tab:hover{border-color:var(--accent);color:var(--accent);}
.filter-tab.active{background:linear-gradient(135deg,#F4A7C3,#E8719A);border-color:transparent;color:#fff;box-shadow:0 3px 10px rgba(212,84,127,.3);}
.nego-list{display:flex;flex-direction:column;gap:14px;}
.nego-card{background:var(--white);border:1.5px solid var(--border);border-radius:16px;overflow:hidden;transition:border-color .2s,box-shadow .2s;box-shadow:0 2px 12px rgba(212,84,127,.07);}
.nego-card:hover{border-color:var(--pink);box-shadow:0 6px 20px rgba(212,84,127,.12);}
.nego-card-head{padding:14px 20px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;background:linear-gradient(to right,#FFF0F5,#fff);}
.nego-card-body{padding:16px 20px;display:flex;gap:16px;align-items:flex-start;}
.nego-card-foot{padding:12px 20px;border-top:1.5px solid var(--border);background:#FFF2F7;display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
.produk-thumb{width:56px;height:64px;border-radius:8px;object-fit:cover;border:1.5px solid var(--border);flex-shrink:0;}
.nego-info{flex:1;}
.nego-produk{font-weight:600;font-size:14px;color:var(--text);margin-bottom:4px;}
.nego-pembeli{font-size:12px;color:var(--muted);margin-bottom:10px;}
.harga-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:10px;}
.harga-box{background:var(--surface2);border-radius:8px;padding:10px 12px;border:1px solid var(--border);}
.harga-box .lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
.harga-box .val{font-size:14px;font-weight:700;}
.harga-asli .val{color:var(--text);}
.harga-tawar .val{color:var(--yellow);}
.harga-deal .val{color:var(--green);}
.harga-counter .val{color:var(--orange);}
.nego-pesan{font-size:13px;color:var(--text2);font-style:italic;padding:10px 12px;background:var(--surface2);border-radius:8px;border-left:3px solid var(--border);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-menunggu{background:#FFF8E1;color:#F9A825;}
.badge-disetujui{background:#E0F2F1;color:#00897B;}
.badge-ditolak{background:#FFEBEE;color:#E53935;}
.badge-counter{background:#FBE9E7;color:#E64A19;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;}
.btn-green{background:#E0F2F1;color:#00695C;border:1.5px solid #80CBC4 !important;}
.btn-green:hover{background:#B2DFDB;}
.btn-red{background:#FFEBEE;color:#C62828;border:1.5px solid #EF9A9A !important;}
.btn-red:hover{background:#FFCDD2;}
.btn-orange{background:#FBE9E7;color:#BF360C;border:1.5px solid #FFAB91 !important;}
.btn-orange:hover{background:#FFCCBC;}
.form-inline{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.form-input{background:var(--surface2);border:1.5px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;padding:7px 12px;outline:none;font-family:'DM Sans',sans-serif;transition:border-color .2s;width:160px;}
.form-input:focus{border-color:var(--accent);}
.empty-box{text-align:center;padding:60px 20px;color:var(--muted);}
.empty-box i{font-size:3rem;display:block;margin-bottom:12px;color:var(--pink);}
.empty-box p{font-size:14px;}
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:#E0F2F1;color:#00695C;border:1.5px solid #80CBC4;}
.alert-danger{background:#FFEBEE;color:#C62828;border:1.5px solid #EF9A9A;}
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);backdrop-filter:blur(4px);z-index:200;align-items:center;justify-content:center;}
.overlay.show{display:flex;}
.modal-box{background:var(--white);border:1.5px solid var(--border);border-radius:16px;padding:28px;width:420px;max-width:92%;box-shadow:0 25px 60px rgba(212,84,127,.2);}
.modal-box h4{font-size:15px;font-weight:700;color:var(--text);margin-bottom:6px;}
.modal-box p{font-size:13px;color:var(--muted);margin-bottom:16px;}
.modal-btns{display:flex;gap:8px;justify-content:flex-end;}
.btn-cancel{padding:8px 16px;border-radius:8px;background:var(--surface2);border:1.5px solid var(--border);color:var(--muted);font-size:13px;cursor:pointer;font-family:'DM Sans',sans-serif;}
.btn-cancel:hover{color:var(--text);border-color:var(--accent);}
/* RESPONSIVE MOBILE */
.btn-toggle-sidebar { display:none; background:var(--surface2,#FFF0F5); border:1.5px solid var(--border,#FFD6E7); border-radius:10px; width:38px; height:38px; align-items:center; justify-content:center; cursor:pointer; font-size:18px; color:var(--text,#2d2d2d); }
.sidebar-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.4); z-index:98; }
.sidebar-overlay.active { display:block; }
@media (max-width:900px) {
    .sidebar { position:fixed; left:0; top:0; height:100vh; width:280px; border-radius:0; transform:translateX(-100%); transition:transform 0.3s ease; z-index:99; }
    .sidebar.active { transform:translateX(0); }
    .main { margin-left:0 !important; }
    .btn-toggle-sidebar { display:flex !important; }
    .topbar { padding:0 16px !important; }
    .content { padding:16px 14px !important; }
    table { font-size:12px; }
    td, th { padding:8px 10px; }
}
@media (max-width:480px) {
    .topbar-date { display:none; }
}

@media (max-width:900px) {
    .grid-2, [style*="grid-template-columns:1fr 1fr"], [style*="grid-template-columns: 1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
    .stats-grid { grid-template-columns: repeat(2,1fr) !important; }
    .card { padding: 14px !important; }
    .card-header { flex-wrap: wrap; gap: 8px; }
    .modal-box, .modal-content { max-width: 95vw !important; margin: 10px !important; }
    .form-grid { grid-template-columns: 1fr !important; }
    .topbar { height: auto !important; min-height: 56px; flex-wrap: wrap; gap: 8px; padding: 10px 16px !important; }
    .chat-layout, [style*="display:flex"][style*="height"] { flex-direction: column !important; height: auto !important; }
    .chat-list { width: 100% !important; max-height: 250px; border-right: none !important; border-bottom: 1.5px solid var(--border,#FFD6E7); }
    .chat-main { width: 100% !important; }
    .nego-table, .pesanan-table { overflow-x: auto; display: block; }
    .filter-bar, .search-bar { flex-wrap: wrap; gap: 8px; }
    .filter-bar select, .filter-bar input { width: 100% !important; }
}

@media (max-width:480px) {
    .stats-grid { grid-template-columns: repeat(2,1fr) !important; gap: 8px !important; }
    .content { padding: 12px 10px !important; }
    table { font-size: 11px !important; }
    td, th { padding: 6px 8px !important; }
    .badge { font-size: 10px !important; padding: 2px 7px !important; }
    .btn-tambah, .btn-primary { font-size: 12px !important; padding: 8px 12px !important; }
    h2, h3 { font-size: 14px !important; }
    .topbar-title { font-size: 15px !important; }
    img.produk-thumb { width: 32px !important; height: 32px !important; }
}

</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>
<div class="main">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px;"><button class="btn-toggle-sidebar" onclick="toggleSidebar()"><i class="bi bi-list"></i></button><div class="topbar-title">Nego Harga</div></div>
        <div class="topbar-right">
            <span style="font-size:12px;color:var(--muted);"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../pages/home.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'setuju'): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Nego disetujui!</div>
            <?php elseif ($_GET['msg'] === 'tolak'): ?>
            <div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Nego ditolak.</div>
            <?php elseif ($_GET['msg'] === 'counter'): ?>
            <div class="alert alert-success"><i class="bi bi-arrow-left-right"></i> Counter harga berhasil dikirim.</div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="stats-grid">
            <a href="nego.php?status=menunggu" class="stat-card <?= $filter==='menunggu'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:#FFF8E1;color:#F9A825;"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value"><?= $total_menunggu ?></div>
                <div class="stat-label">Menunggu Respon</div>
            </a>
            <a href="nego.php?status=counter" class="stat-card <?= $filter==='counter'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:#FBE9E7;color:#E64A19;"><i class="bi bi-arrow-left-right"></i></div>
                <div class="stat-value"><?= $total_counter ?></div>
                <div class="stat-label">Counter Dikirim</div>
            </a>
            <a href="nego.php?status=disetujui" class="stat-card <?= $filter==='disetujui'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:#E0F2F1;color:#00897B;"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value"><?= $total_setuju ?></div>
                <div class="stat-label">Disetujui</div>
            </a>
            <a href="nego.php?status=ditolak" class="stat-card <?= $filter==='ditolak'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:#FFEBEE;color:#E53935;"><i class="bi bi-x-circle"></i></div>
                <div class="stat-value"><?= $total_tolak ?></div>
                <div class="stat-label">Ditolak</div>
            </a>
        </div>

        <div class="filter-tabs">
            <a href="nego.php?status=semua"     class="filter-tab <?= $filter==='semua'    ?'active':'' ?>">Semua</a>
            <a href="nego.php?status=menunggu"  class="filter-tab <?= $filter==='menunggu' ?'active':'' ?>">⏳ Menunggu</a>
            <a href="nego.php?status=counter"   class="filter-tab <?= $filter==='counter'  ?'active':'' ?>">↔ Counter</a>
            <a href="nego.php?status=disetujui" class="filter-tab <?= $filter==='disetujui'?'active':'' ?>">✅ Disetujui</a>
            <a href="nego.php?status=ditolak"   class="filter-tab <?= $filter==='ditolak'  ?'active':'' ?>">❌ Ditolak</a>
        </div>

        <?php if ($q && mysqli_num_rows($q) > 0): ?>
        <div class="nego-list">
        <?php while ($row = mysqli_fetch_assoc($q)): ?>
        <?php $persen_tawar = round((1 - $row['harga_tawar'] / $row['harga_asli']) * 100); ?>
        <div class="nego-card">
            <div class="nego-card-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,#F4A7C3,#E8719A);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;">
                        <?= strtoupper(substr($row['nama_pembeli'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:13px;color:var(--text);"><?= escape($row['nama_pembeli']) ?></div>
                        <div style="font-size:11px;color:var(--muted);"><?= escape($row['hp_pembeli'] ?? '-') ?> · <?= date('d M Y, H:i', strtotime($row['created_at'])) ?></div>
                    </div>
                </div>
                <?php
                $badge_class = match($row['status']) {
                    'menunggu'  => 'badge-menunggu',
                    'disetujui' => 'badge-disetujui',
                    'ditolak'   => 'badge-ditolak',
                    'counter'   => 'badge-counter',
                    default     => ''
                };
                $badge_label = match($row['status']) {
                    'menunggu'  => '⏳ Menunggu',
                    'disetujui' => '✅ Disetujui',
                    'ditolak'   => '❌ Ditolak',
                    'counter'   => '↔ Counter',
                    default     => $row['status']
                };
                ?>
                <span class="badge <?= $badge_class ?>"><?= $badge_label ?></span>
            </div>

            <div class="nego-card-body">
                <img src="../uploads/produk/<?= escape($row['foto_utama'] ?? '') ?>"
                     class="produk-thumb"
                     onerror="this.src='https://placehold.co/56x64/FFF0F5/E8719A?text=CG'">
                <div class="nego-info">
                    <div class="nego-produk"><?= escape($row['nama_barang']) ?></div>
                    <div class="nego-pembeli">Penawaran turun <strong style="color:var(--yellow);"><?= $persen_tawar ?>%</strong> dari harga asli</div>
                    <div class="harga-row">
                        <div class="harga-box harga-asli">
                            <div class="lbl">Harga Asli</div>
                            <div class="val"><?= formatRupiah($row['harga_asli']) ?></div>
                        </div>
                        <div class="harga-box harga-tawar">
                            <div class="lbl">Ditawar</div>
                            <div class="val"><?= formatRupiah($row['harga_tawar']) ?></div>
                        </div>
                        <?php if ($row['harga_deal']): ?>
                        <div class="harga-box harga-deal">
                            <div class="lbl">Harga Deal</div>
                            <div class="val"><?= formatRupiah($row['harga_deal']) ?></div>
                        </div>
                        <?php elseif ($row['harga_counter']): ?>
                        <div class="harga-box harga-counter">
                            <div class="lbl">Counter Penjual</div>
                            <div class="val"><?= formatRupiah($row['harga_counter']) ?></div>
                        </div>
                        <?php else: ?>
                        <div class="harga-box">
                            <div class="lbl">Selisih</div>
                            <div class="val" style="color:var(--red);">- <?= formatRupiah($row['harga_asli'] - $row['harga_tawar']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($row['pesan']): ?>
                    <div class="nego-pesan">"<?= escape($row['pesan']) ?>"</div>
                    <?php endif; ?>
                    <?php if ($row['pesan_admin']): ?>
                    <div class="nego-pesan" style="border-left-color:var(--accent);margin-top:8px;">
                        <span style="font-size:11px;color:var(--accent);font-style:normal;font-weight:600;">Balasan Penjual:</span><br>
                        "<?= escape($row['pesan_admin']) ?>"
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($row['status'] === 'menunggu'): ?>
            <div class="nego-card-foot">
                <form method="POST" action="../transaksi/proses_nego.php">
                    <input type="hidden" name="nego_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="aksi" value="setuju">
                    <input type="hidden" name="harga_deal" value="<?= $row['harga_tawar'] ?>">
                    <button type="submit" class="btn btn-green"><i class="bi bi-check-circle-fill"></i> Setuju</button>
                </form>
                <form method="POST" action="../transaksi/proses_nego.php" class="form-inline">
                    <input type="hidden" name="nego_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="aksi" value="counter">
                    <input type="number" name="harga_counter" class="form-input"
                           placeholder="Harga counter..." min="1"
                           value="<?= round(($row['harga_asli'] + $row['harga_tawar']) / 2) ?>">
                    <input type="text" name="pesan_admin" class="form-input" placeholder="Pesan (opsional)">
                    <button type="submit" class="btn btn-orange"><i class="bi bi-arrow-left-right"></i> Counter</button>
                </form>
                <button class="btn btn-red" onclick="openTolak(<?= $row['id'] ?>)">
                    <i class="bi bi-x-circle-fill"></i> Tolak
                </button>
            </div>
            <?php elseif ($row['status'] === 'counter'): ?>
            <div class="nego-card-foot">
                <span style="font-size:12px;color:var(--muted);"><i class="bi bi-clock"></i> Menunggu respon pembeli atas counter harga</span>
            </div>
            <?php elseif ($row['status'] === 'disetujui'): ?>
            <div class="nego-card-foot">
                <span style="font-size:12px;color:var(--green);"><i class="bi bi-check-circle-fill"></i> Deal pada <?= formatRupiah($row['harga_deal']) ?></span>
            </div>
            <?php elseif ($row['status'] === 'ditolak'): ?>
            <div class="nego-card-foot">
                <span style="font-size:12px;color:var(--red);"><i class="bi bi-x-circle-fill"></i> Nego ditolak</span>
            </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
        </div>

        <?php else: ?>
        <div class="empty-box">
            <i class="bi bi-tags"></i>
            <p>Belum ada pengajuan nego<?= $filter !== 'semua' ? ' dengan status ini' : '' ?>.</p>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- MODAL TOLAK -->
<div class="overlay" id="modalTolak">
    <div class="modal-box">
        <h4><i class="bi bi-x-circle" style="color:var(--red);margin-right:6px;"></i> Tolak Nego</h4>
        <p>Berikan alasan penolakan kepada pembeli (opsional).</p>
        <form method="POST" action="../transaksi/proses_nego.php">
            <input type="hidden" name="nego_id" id="tolakNegoId">
            <input type="hidden" name="aksi" value="tolak">
            <div style="margin-bottom:14px;">
                <textarea name="pesan_admin" class="form-input" style="width:100%;min-height:80px;resize:vertical;"
                    placeholder="Contoh: Harga terlalu rendah, minimal nego 10% dari harga asli."></textarea>
            </div>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeTolak()">Batal</button>
                <button type="submit" class="btn btn-red"><i class="bi bi-x-circle-fill"></i> Tolak Nego</button>
            </div>
        </form>
    </div>
</div>

<script>
function openTolak(id) {
    document.getElementById('tolakNegoId').value = id;
    document.getElementById('modalTolak').classList.add('show');
}
function closeTolak() {
    document.getElementById('modalTolak').classList.remove('show');
}
document.getElementById('modalTolak').addEventListener('click', function(e){
    if (e.target === this) closeTolak();
});
</script>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar .nav-item, .sidebar .btn-logout').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 900) closeSidebar();
        });
    });
});
</script>
</body>
</html>