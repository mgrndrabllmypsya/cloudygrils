<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
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

$total_perhatian = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status_transfer='menunggu' AND status='menunggu'"))[0] ?? 0;

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pesanan — Cloudy Girls Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --bg:       #FFF5F8;
    --surface:  #FFFFFF;
    --surface2: #FFF0F5;
    --border:   #FFB6D0;
    --accent:   #FF4081;
    --accent2:  #F50057;
    --pink:     #FF80AB;
    --pink2:    #FF4081;
    --green:    #00BFA5;
    --yellow:   #FFB300;
    --red:      #FF1744;
    --orange:   #FF6D00;
    --text:     #1A1A1A;
    --text2:    #444444;
    --muted:    #AAAAAA;
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
    background-image: radial-gradient(circle, #FFB6D0 1px, transparent 1px);
    background-size: 28px 28px;
    opacity:.15;
    pointer-events:none;
    z-index:0;
}
a { text-decoration:none; color:inherit; }

/* SIDEBAR */
.sidebar {
    width:230px;
    background: linear-gradient(180deg, #FF80AB 0%, #FF4081 45%, #F50057 100%);
    display:flex; flex-direction:column;
    position:fixed; top:0; left:0; bottom:0; z-index:50;
    box-shadow: 4px 0 28px rgba(255,64,129,.3);
}
.sidebar-logo {
    padding:22px 22px 18px;
    border-bottom:1.5px solid rgba(255,255,255,.2);
    background:rgba(255,255,255,.12);
}
.sidebar-logo .logo { font-family:'Playfair Display',serif; font-size:21px; font-weight:900; color:#fff; }
.sidebar-logo .logo span { color:#FFE4EE; }
.sidebar-logo small { display:block; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:rgba(255,255,255,.65); margin-top:3px; }
.sidebar-nav { flex:1; padding:14px 10px; display:flex; flex-direction:column; gap:2px; overflow-y:auto; }
.nav-section { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,.55); padding:12px 12px 5px; font-weight:600; }
.nav-item { display:flex; align-items:center; gap:11px; padding:9px 13px; border-radius:10px; font-size:13px; font-weight:500; color:rgba(255,255,255,.8); transition:all .18s; }
.nav-item:hover { background:rgba(255,255,255,.2); color:#fff; }
.nav-item.active { background:rgba(255,255,255,.28); color:#fff; font-weight:600; border-left:3px solid #fff; }
.nav-item i { font-size:15px; width:18px; flex-shrink:0; }
.sidebar-footer { padding:14px 10px; border-top:1.5px solid rgba(255,255,255,.2); background:rgba(0,0,0,.1); }
.admin-card { display:flex; align-items:center; gap:10px; padding:10px 12px; background:rgba(255,255,255,.18); border-radius:10px; margin-bottom:8px; border:1.5px solid rgba(255,255,255,.3); }
.admin-avatar { width:34px; height:34px; border-radius:50%; background:rgba(255,255,255,.3); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#fff; flex-shrink:0; border:2px solid rgba(255,255,255,.5); }
.admin-info .name { font-size:12px; font-weight:600; color:#fff; }
.admin-info .role { font-size:10px; color:rgba(255,255,255,.65); }
.btn-logout { display:flex; align-items:center; gap:8px; padding:8px 12px; border-radius:8px; font-size:12px; color:rgba(255,255,255,.85); transition:background .2s; width:100%; }
.btn-logout:hover { background:rgba(255,255,255,.2); color:#fff; }

/* MAIN */
.main { margin-left:230px; flex:1; display:flex; flex-direction:column; position:relative; z-index:1; }
.topbar {
    background:rgba(255,255,255,.95);
    backdrop-filter:blur(12px);
    border-bottom:1.5px solid var(--border);
    padding:0 28px; height:62px;
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; z-index:40;
    box-shadow:0 2px 12px rgba(255,64,129,.07);
}
.topbar-title { font-family:'Playfair Display',serif; font-size:19px; font-weight:700; color:var(--text); }
.topbar-right { display:flex; align-items:center; gap:10px; }
.topbar-date { font-size:12px; color:var(--muted); }
.btn-toko { display:flex; align-items:center; gap:6px; padding:7px 16px; border-radius:8px; background:linear-gradient(135deg,#FF80AB,#FF4081); font-size:12px; font-weight:600; color:#fff; box-shadow:0 3px 12px rgba(255,64,129,.35); transition:opacity .2s; }
.btn-toko:hover { opacity:.88; }

.content { padding:26px 28px; flex:1; }

/* STATS */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:22px; }
.stat-card { background:var(--white); border:1.5px solid var(--border); border-radius:16px; padding:20px; position:relative; overflow:hidden; transition:transform .2s, box-shadow .2s; box-shadow:0 2px 12px rgba(255,64,129,.08); }
.stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(255,64,129,.15); }
.stat-card::after { content:''; position:absolute; bottom:-20px; right:-20px; width:90px; height:90px; border-radius:50%; opacity:.07; }
.stat-card.c1::after { background:#FF4081; }
.stat-card.c2::after { background:#FFB300; }
.stat-card.c3::after { background:#FF6D00; }
.stat-card.c4::after { background:#00BFA5; }
.stat-icon { width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:19px; margin-bottom:14px; }
.stat-card.c1 .stat-icon { background:#FFE4EE; color:#FF4081; }
.stat-card.c2 .stat-icon { background:#FFF8E1; color:#FFB300; }
.stat-card.c3 .stat-icon { background:#FBE9E7; color:#FF6D00; }
.stat-card.c4 .stat-icon { background:#E0F2F1; color:#00BFA5; }
.stat-value { font-size:26px; font-weight:700; color:var(--text); line-height:1; margin-bottom:5px; }
.stat-label { font-size:12px; color:var(--muted); }

/* ALERT PERHATIAN */
.alert-perhatian {
    display:flex; align-items:center; gap:12px;
    background:#FFF8E1; border:1.5px solid #FFD54F;
    border-radius:12px; padding:14px 18px; margin-bottom:20px;
    font-size:13px; color:#F57F17;
}
.alert-perhatian a { color:#E65100; font-weight:600; text-decoration:underline; }

/* CARD */
.card { background:var(--white); border:1.5px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(255,64,129,.07); }
.card-header {
    padding:14px 20px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;
    background:linear-gradient(to right,#FFF5F8,#fff);
}
.card-header h3 { font-size:14px; font-weight:600; color:var(--text); }
.filter-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.filter-select, .search-input {
    background:var(--surface2); border:1.5px solid var(--border); border-radius:8px;
    color:var(--text); font-size:12px; padding:6px 12px; outline:none;
    font-family:'DM Sans',sans-serif;
}
.filter-select:focus, .search-input:focus { border-color:var(--accent); }
.btn-filter { padding:6px 14px; border-radius:8px; background:linear-gradient(135deg,#FF80AB,#FF4081); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; }
.btn-filter:hover { opacity:.85; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); padding:10px 20px; border-bottom:1.5px solid var(--border); font-weight:600; background:#FFF8FA; }
td { padding:12px 20px; font-size:13px; border-bottom:1px solid #FFE4EE; vertical-align:middle; color:var(--text2); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#FFF5F8; }

/* BADGE STATUS PESANAN */
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.badge-menunggu    { background:#FFE4EE; color:#FF4081; }
.badge-dikonfirmasi { background:#FFF8E1; color:#F9A825; }
.badge-diproses    { background:#FBE9E7; color:#E64A19; }
.badge-dikirim     { background:#E0F2F1; color:#00897B; }
.badge-selesai     { background:#E0F2F1; color:#00897B; }
.badge-dibatalkan  { background:#FFEBEE; color:#E53935; }

/* BADGE TRANSFER */
.tbadge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; }
.tbadge-menunggu     { background:#FFF8E1; color:#F9A825; }
.tbadge-dikonfirmasi { background:#E0F2F1; color:#00897B; }
.tbadge-ditolak      { background:#FFEBEE; color:#E53935; }

/* INDIKATOR */
.dot-alert { width:7px; height:7px; border-radius:50%; background:var(--yellow); display:inline-block; margin-right:4px; animation:pulse 1.5s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

/* AKSI */
.btn-detail { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; background:linear-gradient(135deg,#FF80AB,#FF4081); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; text-decoration:none; }
.btn-detail:hover { opacity:.85; }

.empty { text-align:center; padding:48px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2.5rem; display:block; margin-bottom:10px; color:var(--pink); }

.alert-msg { padding:12px 20px; border-radius:10px; font-size:13px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.alert-success { background:#E0F2F1; color:#00695C; border:1.5px solid #80CBC4; }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
        <small>Dashboard Penjual</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php" class="nav-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="produk.php"    class="nav-item"><i class="bi bi-handbag"></i> Produk</a>
        <a href="pesanan.php"   class="nav-item active"><i class="bi bi-bag-check"></i> Pesanan</a>
        <a href="chat.php"      class="nav-item"><i class="bi bi-chat-dots"></i> Chat</a>
        <a href="nego.php"      class="nav-item"><i class="bi bi-tags"></i> Nego Harga</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php"     class="nav-item"><i class="bi bi-star"></i> Ulasan</a>
        <a href="pengaturan.php" class="nav-item"><i class="bi bi-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar"><?= strtoupper(substr($admin_nama, 0, 1)) ?></div>
            <div class="admin-info">
                <div class="name"><?= escape($admin_nama) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <a href="../auth/logout_admin.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i> Keluar</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">Pesanan</div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../index.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
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
                        <option value="dikirim"      <?= $filter_status==='dikirim'      ? 'selected':'' ?>>Dikirim</option>
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
                        $status_label = match($status) {
                            'menunggu'     => 'Menunggu',
                            'dikonfirmasi' => 'Dikonfirmasi',
                            'diproses'     => 'Diproses',
                            'dikirim'      => 'Dikirim',
                            'selesai'      => 'Selesai',
                            'dibatalkan'   => 'Dibatalkan',
                            default        => ucfirst($status)
                        };
                        $st_transfer = $row['status_transfer'] ?? null;
                        $butuh_aksi  = ($row['metode'] === 'transfer' && $st_transfer === 'menunggu' && $row['bukti_transfer']);
                        ?>
                        <tr>
                            <td style="color:var(--muted);font-size:12px;"><?= $no++ ?></td>
                            <td>
                                <?php if ($butuh_aksi): ?><span class="dot-alert" title="Perlu konfirmasi transfer"></span><?php endif; ?>
                                <div style="font-weight:600;color:var(--text);font-size:12px;"><?= escape($row['kode_pesanan']) ?></div>
                                <div style="font-size:12px;color:var(--text2);"><?= escape($row['nama_pembeli']) ?></div>
                                <div style="font-size:10px;color:var(--muted);"><?= escape($row['email_pembeli']) ?></div>
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

</body>
</html>