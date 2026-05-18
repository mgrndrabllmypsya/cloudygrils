<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login_admin.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';

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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nego Harga — Cloudy Girls Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --bg:#0F0E17; --surface:#1A1825; --surface2:#232136; --border:#2E2B3D;
    --accent:#A78BFA; --accent2:#7C3AED; --pink:#F9A8D4; --pink2:#EC4899;
    --green:#34D399; --yellow:#FBBF24; --red:#F87171; --orange:#FB923C;
    --text:#E2E0F0; --muted:#6B6880; --white:#FFFFFF;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;}
a{text-decoration:none;color:inherit;}

.sidebar{width:240px;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;}
.sidebar-logo{padding:24px 24px 20px;border-bottom:1px solid var(--border);}
.sidebar-logo .logo{font-family:'Playfair Display',serif;font-size:20px;font-weight:900;color:var(--white);}
.sidebar-logo .logo span{color:var(--accent);}
.sidebar-logo small{display:block;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:2px;}
.sidebar-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:2px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:500;color:var(--muted);transition:all .2s;}
.nav-item:hover{background:var(--surface2);color:var(--text);}
.nav-item.active{background:linear-gradient(135deg,rgba(124,58,237,.25),rgba(236,72,153,.15));color:var(--accent);}
.nav-item i{font-size:16px;width:20px;}
.badge-notif{background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:auto;}
.nav-section{font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);padding:14px 14px 6px;font-weight:600;}
.sidebar-footer{padding:16px 12px;border-top:1px solid var(--border);}
.admin-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--surface2);border-radius:10px;margin-bottom:10px;}
.admin-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--pink2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;}
.admin-info .name{font-size:13px;font-weight:600;color:var(--text);}
.admin-info .role{font-size:10px;color:var(--muted);}
.btn-logout{display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;font-size:12px;color:var(--red);transition:background .2s;width:100%;}
.btn-logout:hover{background:rgba(248,113,113,.1);}

.main{margin-left:240px;flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;}
.topbar-title{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.btn-toko{display:flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;background:var(--surface2);border:1px solid var(--border);font-size:12px;font-weight:500;color:var(--text);transition:border-color .2s;}
.btn-toko:hover{border-color:var(--accent);color:var(--accent);}
.content{padding:28px 32px;flex:1;}

/* STATS */
.stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:18px;cursor:pointer;transition:all .2s;display:block;text-decoration:none;}
.stat-card:hover{transform:translateY(-2px);}
.stat-card.active-filter{border-color:var(--accent);background:rgba(167,139,250,.05);}
.stat-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;margin-bottom:12px;}
.stat-value{font-size:24px;font-weight:700;color:var(--white);margin-bottom:3px;}
.stat-label{font-size:12px;color:var(--muted);}

/* FILTER TABS */
.filter-tabs{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap;}
.filter-tab{padding:7px 16px;border-radius:20px;font-size:12px;font-weight:600;border:1px solid var(--border);color:var(--muted);cursor:pointer;transition:all .2s;text-decoration:none;}
.filter-tab:hover{border-color:var(--accent);color:var(--accent);}
.filter-tab.active{background:var(--accent2);border-color:var(--accent2);color:#fff;}

/* NEGO CARDS */
.nego-list{display:flex;flex-direction:column;gap:14px;}
.nego-card{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:border-color .2s;}
.nego-card:hover{border-color:rgba(167,139,250,.3);}
.nego-card-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.nego-card-body{padding:16px 20px;display:flex;gap:16px;align-items:flex-start;}
.nego-card-foot{padding:12px 20px;border-top:1px solid var(--border);background:rgba(46,43,61,.3);display:flex;gap:8px;flex-wrap:wrap;align-items:center;}

.produk-thumb{width:56px;height:64px;border-radius:8px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;}
.nego-info{flex:1;}
.nego-produk{font-weight:600;font-size:14px;color:var(--white);margin-bottom:4px;}
.nego-pembeli{font-size:12px;color:var(--muted);margin-bottom:10px;}

.harga-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:10px;}
.harga-box{background:var(--surface2);border-radius:8px;padding:10px 12px;}
.harga-box .lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;}
.harga-box .val{font-size:14px;font-weight:700;}
.harga-asli .val{color:var(--text);}
.harga-tawar .val{color:var(--yellow);}
.harga-deal .val{color:var(--green);}
.harga-counter .val{color:var(--orange);}

.nego-pesan{font-size:13px;color:var(--muted);font-style:italic;padding:10px 12px;background:var(--surface2);border-radius:8px;border-left:3px solid var(--border);}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-menunggu{background:rgba(251,191,36,.15);color:var(--yellow);}
.badge-disetujui{background:rgba(52,211,153,.15);color:var(--green);}
.badge-ditolak{background:rgba(248,113,113,.15);color:var(--red);}
.badge-counter{background:rgba(251,146,60,.15);color:var(--orange);}

/* BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;}
.btn-green{background:rgba(52,211,153,.15);color:var(--green);border:1px solid rgba(52,211,153,.3);}
.btn-green:hover{background:rgba(52,211,153,.25);}
.btn-red{background:rgba(248,113,113,.15);color:var(--red);border:1px solid rgba(248,113,113,.3);}
.btn-red:hover{background:rgba(248,113,113,.25);}
.btn-orange{background:rgba(251,146,60,.15);color:var(--orange);border:1px solid rgba(251,146,60,.3);}
.btn-orange:hover{background:rgba(251,146,60,.25);}

/* FORM INPUT */
.form-inline{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.form-input{background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;padding:7px 12px;outline:none;font-family:'DM Sans',sans-serif;transition:border-color .2s;width:160px;}
.form-input:focus{border-color:var(--accent);}

/* EMPTY */
.empty-box{text-align:center;padding:60px 20px;color:var(--muted);}
.empty-box i{font-size:3rem;display:block;margin-bottom:12px;opacity:.4;}
.empty-box p{font-size:14px;}

/* ALERT */
.alert{padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px;}
.alert-success{background:rgba(52,211,153,.12);color:var(--green);border:1px solid rgba(52,211,153,.3);}
.alert-danger{background:rgba(248,113,113,.12);color:var(--red);border:1px solid rgba(248,113,113,.3);}

/* MODAL */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.8);z-index:200;align-items:center;justify-content:center;}
.overlay.show{display:flex;}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px;width:420px;max-width:92%;}
.modal-box h4{font-size:15px;font-weight:700;color:var(--white);margin-bottom:6px;}
.modal-box p{font-size:13px;color:var(--muted);margin-bottom:16px;}
.modal-btns{display:flex;gap:8px;justify-content:flex-end;}
.btn-cancel{padding:8px 16px;border-radius:8px;background:var(--surface2);border:1px solid var(--border);color:var(--muted);font-size:13px;cursor:pointer;font-family:'DM Sans',sans-serif;}
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
        <a href="chat.php" class="nav-item"><i class="bi bi-chat-dots"></i> Chat</a>
        <a href="nego.php" class="nav-item active">
            <i class="bi bi-tags"></i> Nego Harga
            <?php if ($total_menunggu > 0): ?>
            <span class="badge-notif"><?= $total_menunggu ?></span>
            <?php endif; ?>
        </a>
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
        <div class="topbar-title">Nego Harga</div>
        <div class="topbar-right">
            <span style="font-size:12px;color:var(--muted);"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../pages/home.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'setuju'): ?>
        <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> Nego disetujui! Pembeli bisa langsung checkout dengan harga deal.</div>
        <?php elseif ($_GET['msg'] === 'tolak'): ?>
        <div class="alert alert-danger"><i class="bi bi-x-circle-fill"></i> Nego ditolak.</div>
        <?php elseif ($_GET['msg'] === 'counter'): ?>
        <div class="alert alert-success"><i class="bi bi-arrow-left-right"></i> Counter harga berhasil dikirim ke pembeli.</div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- STATS -->
        <div class="stats-grid">
            <a href="nego.php?status=menunggu" class="stat-card <?= $filter==='menunggu'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:rgba(251,191,36,.2);color:var(--yellow);"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-value"><?= $total_menunggu ?></div>
                <div class="stat-label">Menunggu Respon</div>
            </a>
            <a href="nego.php?status=counter" class="stat-card <?= $filter==='counter'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:rgba(251,146,60,.2);color:var(--orange);"><i class="bi bi-arrow-left-right"></i></div>
                <div class="stat-value"><?= $total_counter ?></div>
                <div class="stat-label">Counter Dikirim</div>
            </a>
            <a href="nego.php?status=disetujui" class="stat-card <?= $filter==='disetujui'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:rgba(52,211,153,.2);color:var(--green);"><i class="bi bi-check-circle"></i></div>
                <div class="stat-value"><?= $total_setuju ?></div>
                <div class="stat-label">Disetujui</div>
            </a>
            <a href="nego.php?status=ditolak" class="stat-card <?= $filter==='ditolak'?'active-filter':'' ?>">
                <div class="stat-icon" style="background:rgba(248,113,113,.2);color:var(--red);"><i class="bi bi-x-circle"></i></div>
                <div class="stat-value"><?= $total_tolak ?></div>
                <div class="stat-label">Ditolak</div>
            </a>
        </div>

        <!-- FILTER TABS -->
        <div class="filter-tabs">
            <a href="nego.php?status=semua" class="filter-tab <?= $filter==='semua'?'active':'' ?>">Semua</a>
            <a href="nego.php?status=menunggu" class="filter-tab <?= $filter==='menunggu'?'active':'' ?>">⏳ Menunggu</a>
            <a href="nego.php?status=counter" class="filter-tab <?= $filter==='counter'?'active':'' ?>">↔ Counter</a>
            <a href="nego.php?status=disetujui" class="filter-tab <?= $filter==='disetujui'?'active':'' ?>">✅ Disetujui</a>
            <a href="nego.php?status=ditolak" class="filter-tab <?= $filter==='ditolak'?'active':'' ?>">❌ Ditolak</a>
        </div>

        <!-- NEGO LIST -->
        <?php if ($q && mysqli_num_rows($q) > 0): ?>
        <div class="nego-list">
        <?php while ($row = mysqli_fetch_assoc($q)): ?>
        <?php
            $persen_tawar = round((1 - $row['harga_tawar'] / $row['harga_asli']) * 100);
        ?>
        <div class="nego-card">
            <!-- HEAD -->
            <div class="nego-card-head">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--pink2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;color:#fff;">
                        <?= strtoupper(substr($row['nama_pembeli'], 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:13px;color:var(--white);"><?= escape($row['nama_pembeli']) ?></div>
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

            <!-- BODY -->
            <div class="nego-card-body">
                <img src="../uploads/produk/<?= escape($row['foto_utama'] ?? '') ?>"
                     class="produk-thumb"
                     onerror="this.src='https://placehold.co/56x64/232136/A78BFA?text=CG'">
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
                            <div class="lbl">Counter Admin</div>
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
                        <span style="font-size:11px;color:var(--accent);font-style:normal;font-weight:600;">Balasan Admin:</span><br>
                        "<?= escape($row['pesan_admin']) ?>"
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FOOT — AKSI -->
            <?php if ($row['status'] === 'menunggu'): ?>
            <div class="nego-card-foot">
                <!-- Setuju dengan harga tawar -->
                <form method="POST" action="proses_nego_admin.php">
                    <input type="hidden" name="nego_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="aksi" value="setuju">
                    <input type="hidden" name="harga_deal" value="<?= $row['harga_tawar'] ?>">
                    <button type="submit" class="btn btn-green"><i class="bi bi-check-circle-fill"></i> Setuju</button>
                </form>

                <!-- Counter harga -->
                <form method="POST" action="proses_nego_admin.php" class="form-inline">
                    <input type="hidden" name="nego_id" value="<?= $row['id'] ?>">
                    <input type="hidden" name="aksi" value="counter">
                    <input type="number" name="harga_counter" class="form-input"
                           placeholder="Harga counter..." min="1"
                           value="<?= round(($row['harga_asli'] + $row['harga_tawar']) / 2) ?>">
                    <input type="text" name="pesan_admin" class="form-input" placeholder="Pesan (opsional)">
                    <button type="submit" class="btn btn-orange"><i class="bi bi-arrow-left-right"></i> Counter</button>
                </form>

                <!-- Tolak -->
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
        <form method="POST" action="proses_nego_admin.php">
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
</body>
</html>