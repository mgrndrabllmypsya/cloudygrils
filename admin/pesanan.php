<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login_admin.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

// Handle update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id     = (int)$_POST['pesanan_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE pesanan SET status='$status' WHERE id=$id");
    header("Location: pesanan.php?msg=updated"); exit;
}

// Filter
$filter_status = $_GET['status'] ?? '';
$search        = $_GET['search'] ?? '';
$where = "WHERE 1=1";
if ($filter_status) $where .= " AND ps.status='" . mysqli_real_escape_string($conn, $filter_status) . "'";
if ($search)        $where .= " AND (pb.nama LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR ps.id LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";

$q_pesanan = mysqli_query($conn, "
    SELECT ps.*, pb.nama AS nama_pembeli, pb.email AS email_pembeli
    FROM pesanan ps
    JOIN pembeli pb ON pb.id = ps.pembeli_id
    $where
    ORDER BY ps.created_at DESC
");

$total_pesanan  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan"))[0] ?? 0;
$total_pending  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='pending'"))[0] ?? 0;
$total_proses   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='proses'"))[0] ?? 0;
$total_selesai  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='selesai'"))[0] ?? 0;

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
    --green: #34D399; --yellow: #FBBF24; --red: #F87171;
    --text: #E2E0F0; --muted: #6B6880; --white: #FFFFFF;
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

.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:28px; }
.stat-card { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:20px; position:relative; overflow:hidden; transition:transform .2s,border-color .2s; }
.stat-card:hover { transform:translateY(-2px); border-color:var(--accent); }
.stat-card::before { content:''; position:absolute; top:0; right:0; width:80px; height:80px; border-radius:50%; opacity:.07; transform:translate(20px,-20px); }
.stat-card.purple::before { background:var(--accent2); }
.stat-card.yellow::before { background:var(--yellow); }
.stat-card.green::before { background:var(--green); }
.stat-card.red::before { background:var(--red); }
.stat-icon { width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:17px; margin-bottom:14px; }
.stat-card.purple .stat-icon { background:rgba(124,58,237,.2); color:var(--accent); }
.stat-card.yellow .stat-icon { background:rgba(251,191,36,.2); color:var(--yellow); }
.stat-card.green .stat-icon { background:rgba(52,211,153,.2); color:var(--green); }
.stat-card.red .stat-icon { background:rgba(248,113,113,.2); color:var(--red); }
.stat-value { font-size:26px; font-weight:700; color:var(--white); line-height:1; margin-bottom:4px; }
.stat-label { font-size:12px; color:var(--muted); }

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

.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; }
th { text-align:left; font-size:10px; letter-spacing:1px; text-transform:uppercase; color:var(--muted); padding:10px 20px; border-bottom:1px solid var(--border); font-weight:600; }
td { padding:12px 20px; font-size:13px; border-bottom:1px solid rgba(46,43,61,.5); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:var(--surface2); }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-green { background:rgba(52,211,153,.15); color:var(--green); }
.badge-yellow { background:rgba(251,191,36,.15); color:var(--yellow); }
.badge-red { background:rgba(248,113,113,.15); color:var(--red); }
.badge-purple { background:rgba(167,139,250,.15); color:var(--accent); }

.empty { text-align:center; padding:40px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2rem; display:block; margin-bottom:8px; }

.alert { padding:12px 20px; border-radius:10px; font-size:13px; margin-bottom:16px; background:rgba(52,211,153,.15); color:var(--green); border:1px solid rgba(52,211,153,.3); display:flex; align-items:center; gap:8px; }

/* Modal */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.7); z-index:200; align-items:center; justify-content:center; }
.modal-overlay.show { display:flex; }
.modal { background:var(--surface); border:1px solid var(--border); border-radius:14px; padding:28px; min-width:320px; max-width:400px; width:90%; }
.modal h4 { font-size:15px; font-weight:700; color:var(--white); margin-bottom:16px; }
.modal select { width:100%; background:var(--surface2); border:1px solid var(--border); border-radius:8px; color:var(--text); font-size:13px; padding:10px 12px; margin-bottom:16px; outline:none; font-family:'DM Sans',sans-serif; }
.modal select:focus { border-color:var(--accent); }
.modal-btns { display:flex; gap:8px; justify-content:flex-end; }
.btn-cancel { padding:8px 16px; border-radius:8px; background:var(--surface2); border:1px solid var(--border); color:var(--muted); font-size:13px; cursor:pointer; font-family:'DM Sans',sans-serif; }
.btn-save { padding:8px 16px; border-radius:8px; background:var(--accent2); color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; }
.btn-edit { background:none; border:none; color:var(--accent); cursor:pointer; font-size:13px; padding:4px 8px; border-radius:6px; transition:background .2s; }
.btn-edit:hover { background:rgba(167,139,250,.15); }
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

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div class="alert"><i class="bi bi-check-circle-fill"></i> Status pesanan berhasil diperbarui.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-bag"></i></div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-value"><?= $total_pending ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="bi bi-arrow-repeat"></i></div>
                <div class="stat-value"><?= $total_proses ?></div>
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
                    <input type="text" name="search" class="search-input" placeholder="Cari pembeli / ID..." value="<?= escape($search) ?>">
                    <select name="status" class="filter-select">
                        <option value="">Semua Status</option>
                        <option value="pending"  <?= $filter_status==='pending'  ? 'selected':'' ?>>Pending</option>
                        <option value="proses"   <?= $filter_status==='proses'   ? 'selected':'' ?>>Proses</option>
                        <option value="selesai"  <?= $filter_status==='selesai'  ? 'selected':'' ?>>Selesai</option>
                        <option value="batal"    <?= $filter_status==='batal'    ? 'selected':'' ?>>Batal</option>
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
                            <th>Pembeli</th>
                            <th>Total Bayar</th>
                            <th>Metode</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($q_pesanan)): ?>
                        <?php
                        $status = $row['status'] ?? 'pending';
                        $badge  = match($status) {
                            'selesai' => 'badge-green', 'proses' => 'badge-yellow',
                            'batal'   => 'badge-red',   default  => 'badge-purple'
                        };
                        ?>
                        <tr>
                            <td style="color:var(--muted);"><?= $no++ ?></td>
                            <td>
                                <div style="font-weight:500;color:var(--white);"><?= escape($row['nama_pembeli']) ?></div>
                                <div style="font-size:11px;color:var(--muted);"><?= escape($row['email_pembeli']) ?></div>
                            </td>
                            <td style="font-weight:600;color:var(--accent);"><?= formatRupiah($row['total_bayar']) ?></td>
                            <td style="color:var(--muted);font-size:12px;"><?= escape($row['metode_bayar'] ?? '-') ?></td>
                            <td><span class="badge <?= $badge ?>"><?= ucfirst($status) ?></span></td>
                            <td style="color:var(--muted);font-size:12px;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <button class="btn-edit" onclick="openModal(<?= $row['id'] ?>, '<?= $status ?>')">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty"><i class="bi bi-bag-x"></i>Tidak ada pesanan ditemukan</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Modal Update Status -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <h4><i class="bi bi-pencil-square" style="color:var(--accent);margin-right:6px;"></i> Update Status Pesanan</h4>
        <form method="POST">
            <input type="hidden" name="pesanan_id" id="modalPesananId">
            <input type="hidden" name="update_status" value="1">
            <select name="status" id="modalStatus">
                <option value="pending">Pending</option>
                <option value="proses">Proses</option>
                <option value="selesai">Selesai</option>
                <option value="batal">Batal</option>
            </select>
            <div class="modal-btns">
                <button type="button" class="btn-cancel" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn-save">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id, status) {
    document.getElementById('modalPesananId').value = id;
    document.getElementById('modalStatus').value = status;
    document.getElementById('modalOverlay').classList.add('show');
}
function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
}
document.getElementById('modalOverlay').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});
</script>
</body>
</html>