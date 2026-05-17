<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login_admin.php"); exit;
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

// Pesanan butuh perhatian: transfer menunggu konfirmasi
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
    --bg: #0F0E17; --surface: #1A1825; --surface2: #232136; --border: #2E2B3D;
    --accent: #A78BFA; --accent2: #7C3AED; --pink: #F9A8D4; --pink2: #EC4899;
    --green: #34D399; --yellow: #FBBF24; --red: #F87171; --orange: #FB923C;
    --text: #E2E0F0; --muted: #6B6880; --white: #FFFFFF;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; min-height:100vh; }
a { text-decoration:none; color:inherit; }

/* SIDEBAR */
.sidebar { width:240px; background:var(--surface); border-right:1px solid var(--border); display:flex; flex-direction:column; position:fixed; top:0; left:0; bottom:0; z-index:50; }
.sidebar-logo { padding:24px 24px 20px; border-bottom:1px solid var(--border); }
.sidebar-logo .logo { font-family:'Playfair Display',serif; font-size:20px; font-weight:900; color:var(--white); }
.sidebar-logo .logo span { color:var(--accent); }
.sidebar-logo small { display:block; font-size:10px; letter-spacing:2px; text-transform:uppercase; color:var(--muted); margin-top:2px; }
.sidebar-nav { flex:1; padding:16px 12px; display:flex; flex-direction:column; gap:2px; }
.nav-item { display:flex; align-items:center; gap:12px; padding:10px 14px; border-radius:10px; font-size:13px; font-weight:500; color:var(--muted); transition:all .2s; }
.nav-item:hover { background:var(--surface2); color:var(--text); }
.nav-item.active { background:linear-gradient(135deg,rgba(124,58,237,.25),rgba(236,72,153,.15)); color:var(--accent); }
.nav-item i { font-size:16px; width:20px; }
.nav-section { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:var(--muted); padding:14px 14px 6px; font-weight:600; }
.sidebar-footer { padding:16px 12px; border-top:1px solid var(--border); }
.admin-card { display:flex; align-items:center; gap:10px; padding:10px 12px; background:var(--surface2); border-radius:10px; margin-bottom:10px; }
.admin-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,var(--accent2),var(--pink2)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; color:#fff; flex-shrink:0; }
.admin-info .name { font-size:13px; font-weight:600; color:var(--text); }
.admin-info .role { font-size:10px; color:var(--muted); }
.btn-logout { display:flex; align-items:center; gap:8px; padding:8px 14px; border-radius:8px; font-size:12px; color:var(--red); transition:background .2s; width:100%; }
.btn-logout:hover { background:rgba(248,113,113,.1); }

/* MAIN */
.main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
.topbar { background:var(--surface); border-bottom:1px solid var(--border); padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:40; }
.topbar-title { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; }
.topbar-right { display:flex; align-items:center; gap:12px; }
.topbar-date { font-size:12px; color:var(--muted); }
.btn-toko { display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; background:var(--surface2); border:1px solid var(--border); font-size:12px; font-weight:500; color:var(--text); transition:border-color .2s; }
.btn-toko:hover { border-color:var(--accent); color:var(--accent); }

.content { padding:28px 32px; flex:1; }

/* STATS */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:20px; position:relative; overflow:hidden; transition:transform .2s,border-color .2s; }
.stat-card:hover { transform:translateY(-2px); border-color:var(--accent); }
.stat-card::before { content:''; position:absolute; top:0; right:0; width:80px; height:80px; border-radius:50%; opacity:.07; transform:translate(20px,-20px); }
.stat-card.purple::before { background:var(--accent2); }
.stat-card.yellow::before { background:var(--yellow); }
.stat-card.orange::before { background:var(--orange); }
.stat-card.green::before { background:var(--green); }
.stat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:14px; }
.stat-card.purple .stat-icon { background:rgba(124,58,237,.2); color:var(--accent); }
.stat-card.yellow .stat-icon { background:rgba(251,191,36,.2); color:var(--yellow); }
.stat-card.orange .stat-icon { background:rgba(251,146,60,.2); color:var(--orange); }
.stat-card.green .stat-icon { background:rgba(52,211,153,.2); color:var(--green); }
.stat-value { font-size:26px; font-weight:700; color:var(--white); line-height:1; margin-bottom:4px; }
.stat-label { font-size:12px; color:var(--muted); }

/* ALERT PERHATIAN */
.alert-perhatian {
    display:flex; align-items:center; gap:12px;
    background:rgba(251,191,36,.1); border:1px solid rgba(251,191,36,.3);
    border-radius:12px; padding:14px 18px; margin-bottom:20px;
    font-size:13px; color:var(--yellow);
}
.alert-perhatian a { color:var(--yellow); font-weight:600; text-decoration:underline; }

/* CARD */
.card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.card-header h3 { font-size:14px; font-weight:600; color:var(--white); }
.filter-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.filter-select, .search-input {
    background:var(--surface2); border:1px solid var(--border); border-radius:8px;
    color:var(--text); font-size:12px; padding:6px 12px; outline:none;
    font-family:'DM Sans',sans-serif;
}
.filter-select:focus, .search-input:focus { border-color:var(--accent); }
.btn-filter { padding:6px 14px; border-radius:8px; background:var(--accent2); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; }
.btn-filter:hover { opacity:.85; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); padding:10px 20px; border-bottom:1px solid var(--border); font-weight:600; }
td { padding:12px 20px; font-size:13px; border-bottom:1px solid rgba(46,43,61,.5); vertical-align:middle; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:rgba(35,33,54,.5); }

/* BADGE STATUS PESANAN */
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }
.badge-menunggu   { background:rgba(167,139,250,.15); color:var(--accent); }
.badge-dikonfirmasi { background:rgba(251,191,36,.15); color:var(--yellow); }
.badge-diproses   { background:rgba(251,146,60,.15); color:var(--orange); }
.badge-dikirim    { background:rgba(52,211,153,.15); color:var(--green); }
.badge-selesai    { background:rgba(52,211,153,.2); color:var(--green); }
.badge-dibatalkan { background:rgba(248,113,113,.15); color:var(--red); }

/* BADGE TRANSFER */
.tbadge { display:inline-flex; align-items:center; gap:4px; padding:2px 8px; border-radius:6px; font-size:10px; font-weight:600; }
.tbadge-menunggu     { background:rgba(251,191,36,.15); color:var(--yellow); }
.tbadge-dikonfirmasi { background:rgba(52,211,153,.15); color:var(--green); }
.tbadge-ditolak      { background:rgba(248,113,113,.15); color:var(--red); }

/* INDIKATOR BUTUH AKSI */
.dot-alert { width:7px; height:7px; border-radius:50%; background:var(--yellow); display:inline-block; margin-right:4px; animation:pulse 1.5s ease-in-out infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:.3;} }

/* AKSI */
.btn-detail { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:8px; background:linear-gradient(135deg,var(--accent2),var(--pink2)); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; text-decoration:none; }
.btn-detail:hover { opacity:.85; }

.empty { text-align:center; padding:48px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2.5rem; display:block; margin-bottom:10px; opacity:.4; }

.alert-msg { padding:12px 20px; border-radius:10px; font-size:13px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
.alert-success { background:rgba(52,211,153,.12); color:var(--green); border:1px solid rgba(52,211,153,.3); }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
        <small>Admin Panel</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php" class="nav-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="produk.php" class="nav-item"><i class="bi bi-handbag"></i> Produk</a>
        <a href="pesanan.php" class="nav-item active"><i class="bi bi-bag-check"></i> Pesanan</a>
        <a href="pembeli.php" class="nav-item"><i class="bi bi-people"></i> Pembeli</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php" class="nav-item"><i class="bi bi-star"></i> Ulasan</a>
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
            <div class="alert-msg" style="background:rgba(248,113,113,.12);color:var(--red);border:1px solid rgba(248,113,113,.3);"><i class="bi bi-x-circle-fill"></i> Transfer ditolak. Pembeli akan diberitahu.</div>
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
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-bag"></i></div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-value"><?= $total_menunggu ?></div>
                <div class="stat-label">Menunggu</div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="stat-value"><?= $total_diproses ?></div>
                <div class="stat-label">Diproses</div>
            </div>
            <div class="stat-card green">
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
                        <option value="menunggu"    <?= $filter_status==='menunggu'    ? 'selected':'' ?>>Menunggu</option>
                        <option value="dikonfirmasi" <?= $filter_status==='dikonfirmasi'? 'selected':'' ?>>Dikonfirmasi</option>
                        <option value="diproses"    <?= $filter_status==='diproses'    ? 'selected':'' ?>>Diproses</option>
                        <option value="dikirim"     <?= $filter_status==='dikirim'     ? 'selected':'' ?>>Dikirim</option>
                        <option value="selesai"     <?= $filter_status==='selesai'     ? 'selected':'' ?>>Selesai</option>
                        <option value="dibatalkan"  <?= $filter_status==='dibatalkan'  ? 'selected':'' ?>>Dibatalkan</option>
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
                                <div style="font-weight:600;color:var(--white);font-size:12px;"><?= escape($row['kode_pesanan']) ?></div>
                                <div style="font-size:12px;color:var(--text);"><?= escape($row['nama_pembeli']) ?></div>
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