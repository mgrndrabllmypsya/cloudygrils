<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login_admin.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    mysqli_query($conn, "DELETE FROM pembeli WHERE id=$id");
    header("Location: pembeli.php?msg=deleted"); exit;
}

$search = $_GET['search'] ?? '';
$where  = $search ? "WHERE pb.nama LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR pb.email LIKE '%" . mysqli_real_escape_string($conn, $search) . "%'" : "";

$q_pembeli = mysqli_query($conn, "
    SELECT pb.*, COUNT(ps.id) AS total_pesanan, COALESCE(SUM(ps.total_bayar),0) AS total_belanja
    FROM pembeli pb
    LEFT JOIN pesanan ps ON ps.pembeli_id = pb.id
    $where
    GROUP BY pb.id
    ORDER BY pb.created_at DESC
");

$total_pembeli = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pembeli"))[0] ?? 0;
$total_aktif   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(DISTINCT pembeli_id) FROM pesanan"))[0] ?? 0;

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pembeli — Cloudy Girls Admin</title>
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

.main { margin-left:240px; flex:1; display:flex; flex-direction:column; }
.topbar { background:var(--surface); border-bottom:1px solid var(--border); padding:0 32px; height:64px; display:flex; align-items:center; justify-content:space-between; position:sticky; top:0; z-index:40; }
.topbar-title { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; }
.topbar-right { display:flex; align-items:center; gap:12px; }
.topbar-date { font-size:12px; color:var(--muted); }
.btn-toko { display:flex; align-items:center; gap:6px; padding:8px 16px; border-radius:8px; background:var(--surface2); border:1px solid var(--border); font-size:12px; font-weight:500; color:var(--text); transition:border-color .2s; }
.btn-toko:hover { border-color:var(--accent); color:var(--accent); }

.content { padding:28px 32px; flex:1; }

.stats-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:20px; position:relative; overflow:hidden; transition:transform .2s,border-color .2s; }
.stat-card:hover { transform:translateY(-2px); border-color:var(--accent); }
.stat-card::before { content:''; position:absolute; top:0; right:0; width:80px; height:80px; border-radius:50%; opacity:.07; transform:translate(20px,-20px); }
.stat-card.purple::before { background:var(--accent2); }
.stat-card.pink::before { background:var(--pink2); }
.stat-card.green::before { background:var(--green); }
.stat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:14px; }
.stat-card.purple .stat-icon { background:rgba(124,58,237,.2); color:var(--accent); }
.stat-card.pink .stat-icon { background:rgba(236,72,153,.2); color:var(--pink2); }
.stat-card.green .stat-icon { background:rgba(52,211,153,.2); color:var(--green); }
.stat-value { font-size:26px; font-weight:700; color:var(--white); line-height:1; margin-bottom:4px; }
.stat-label { font-size:12px; color:var(--muted); }

.card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
.card-header h3 { font-size:14px; font-weight:600; color:var(--white); }
.filter-row { display:flex; gap:8px; align-items:center; }
.search-input { background:var(--surface2); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:12px; padding:6px 12px; outline:none; font-family:'DM Sans',sans-serif; }
.search-input:focus { border-color:var(--accent); }
.btn-filter { padding:6px 14px; border-radius:8px; background:var(--accent2); color:#fff; font-size:12px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; }
.btn-filter:hover { opacity:.85; }

.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); padding:10px 20px; border-bottom:1px solid var(--border); font-weight:600; }
td { padding:12px 20px; font-size:13px; border-bottom:1px solid rgba(46,43,61,.5); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:var(--surface2); }

.buyer-avatar { width:34px; height:34px; border-radius:50%; background:linear-gradient(135deg,var(--accent2),var(--pink2)); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px; color:#fff; flex-shrink:0; }
.buyer-row { display:flex; align-items:center; gap:10px; }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-green { background:rgba(52,211,153,.15); color:var(--green); }
.badge-purple { background:rgba(167,139,250,.15); color:var(--accent); }

.empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2rem; display:block; margin-bottom:8px; }

.alert { padding:12px 20px; border-radius:10px; font-size:13px; margin-bottom:16px; background:rgba(248,113,113,.15); color:var(--red); border:1px solid rgba(248,113,113,.3); display:flex; align-items:center; gap:8px; }

.btn-del { background:none; border:none; color:var(--red); cursor:pointer; font-size:12px; padding:4px 8px; border-radius:6px; transition:background .2s; font-family:'DM Sans',sans-serif; }
.btn-del:hover { background:rgba(248,113,113,.1); }

.confirm-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; align-items:center; justify-content:center; }
.confirm-overlay.show { display:flex; }
.confirm-box { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:28px; max-width:360px; width:90%; }
.confirm-box h4 { font-size:15px; font-weight:700; color:var(--white); margin-bottom:8px; }
.confirm-box p { font-size:13px; color:var(--muted); margin-bottom:20px; }
.confirm-btns { display:flex; gap:8px; justify-content:flex-end; }
.btn-cancel-c { padding:8px 16px; border-radius:8px; background:var(--surface2); border:1px solid var(--border); color:var(--muted); font-size:13px; cursor:pointer; font-family:'DM Sans',sans-serif; }
.btn-del-c { padding:8px 16px; border-radius:8px; background:var(--red); color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; }
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
        <a href="pesanan.php" class="nav-item"><i class="bi bi-bag-check"></i> Pesanan</a>
        <a href="pembeli.php" class="nav-item active"><i class="bi bi-people"></i> Pembeli</a>
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
        <div class="topbar-title">Pembeli</div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../index.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert"><i class="bi bi-trash"></i> Data pembeli berhasil dihapus.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <div class="stat-value"><?= $total_pembeli ?></div>
                <div class="stat-label">Total Pembeli</div>
            </div>
            <div class="stat-card pink">
                <div class="stat-icon"><i class="bi bi-person-check"></i></div>
                <div class="stat-value"><?= $total_aktif ?></div>
                <div class="stat-label">Pernah Belanja</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="bi bi-person-plus"></i></div>
                <div class="stat-value"><?= mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pembeli WHERE DATE(created_at)=CURDATE()"))[0] ?? 0 ?></div>
                <div class="stat-label">Daftar Hari Ini</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-people" style="color:var(--pink2);margin-right:6px;"></i> Daftar Pembeli</h3>
                <form method="GET" class="filter-row">
                    <input type="text" name="search" class="search-input" placeholder="Cari nama / email..." value="<?= escape($search) ?>">
                    <button type="submit" class="btn-filter"><i class="bi bi-search"></i> Cari</button>
                </form>
            </div>
            <div class="table-wrap">
                <?php if ($q_pembeli && mysqli_num_rows($q_pembeli) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pembeli</th>
                            <th>No. HP</th>
                            <th>Total Pesanan</th>
                            <th>Total Belanja</th>
                            <th>Bergabung</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($q_pembeli)): ?>
                        <tr>
                            <td style="color:var(--muted);"><?= $no++ ?></td>
                            <td>
                                <div class="buyer-row">
                                    <div class="buyer-avatar"><?= strtoupper(substr($row['nama'], 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:500;color:var(--white);"><?= escape($row['nama']) ?></div>
                                        <div style="font-size:11px;color:var(--muted);"><?= escape($row['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--muted);font-size:12px;"><?= escape($row['no_hp'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $row['total_pesanan'] > 0 ? 'badge-green' : 'badge-purple' ?>">
                                    <?= $row['total_pesanan'] ?> pesanan
                                </span>
                            </td>
                            <td style="font-weight:600;color:var(--accent);"><?= formatRupiah($row['total_belanja']) ?></td>
                            <td style="color:var(--muted);font-size:12px;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <button class="btn-del" onclick="confirmDelete(<?= $row['id'] ?>, '<?= escape($row['nama']) ?>')">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty"><i class="bi bi-people"></i>Tidak ada pembeli ditemukan</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Confirm Delete -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <h4><i class="bi bi-exclamation-triangle" style="color:var(--red);margin-right:6px;"></i> Hapus Pembeli?</h4>
        <p id="confirmMsg">Data pembeli ini akan dihapus permanen.</p>
        <form method="POST">
            <input type="hidden" name="delete_id" id="deleteId">
            <div class="confirm-btns">
                <button type="button" class="btn-cancel-c" onclick="closeConfirm()">Batal</button>
                <button type="submit" class="btn-del-c"><i class="bi bi-trash"></i> Hapus</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmDelete(id, nama) {
    document.getElementById('deleteId').value = id;
    document.getElementById('confirmMsg').textContent = 'Pembeli "' + nama + '" akan dihapus permanen beserta datanya.';
    document.getElementById('confirmOverlay').classList.add('show');
}
function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('show');
}
document.getElementById('confirmOverlay').addEventListener('click', function(e){
    if (e.target === this) closeConfirm();
});
</script>
</body>
</html>