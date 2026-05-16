<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['admin_login']) || !$_SESSION['admin_login']) {
    header("Location: ../auth/login_admin.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$msg = '';
$msg_type = '';

// Handle update pengaturan toko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_toko'])) {
    $nama_toko    = mysqli_real_escape_string($conn, $_POST['nama_toko'] ?? '');
    $deskripsi    = mysqli_real_escape_string($conn, $_POST['deskripsi'] ?? '');
    $no_hp        = mysqli_real_escape_string($conn, $_POST['no_hp'] ?? '');
    $alamat       = mysqli_real_escape_string($conn, $_POST['alamat'] ?? '');
    $instagram    = mysqli_real_escape_string($conn, $_POST['instagram'] ?? '');

    // Cek apakah row settings sudah ada
    $exist = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pengaturan"));
    if ($exist && $exist[0] > 0) {
        mysqli_query($conn, "UPDATE pengaturan SET nama_toko='$nama_toko', deskripsi='$deskripsi', no_hp='$no_hp', alamat='$alamat', instagram='$instagram' WHERE id=1");
    } else {
        mysqli_query($conn, "INSERT INTO pengaturan (nama_toko,deskripsi,no_hp,alamat,instagram) VALUES ('$nama_toko','$deskripsi','$no_hp','$alamat','$instagram')");
    }
    $msg = 'Pengaturan toko berhasil disimpan.';
    $msg_type = 'success';
}

// Handle ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $pw_lama  = $_POST['pw_lama'] ?? '';
    $pw_baru  = $_POST['pw_baru'] ?? '';
    $pw_ulang = $_POST['pw_ulang'] ?? '';

    $admin_id = $_SESSION['admin_id'] ?? 0;
    $q = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM admin WHERE id=$admin_id"));

    if (!$q || !password_verify($pw_lama, $q['password'])) {
        $msg = 'Password lama tidak sesuai.'; $msg_type = 'error';
    } elseif ($pw_baru !== $pw_ulang) {
        $msg = 'Konfirmasi password tidak cocok.'; $msg_type = 'error';
    } elseif (strlen($pw_baru) < 6) {
        $msg = 'Password baru minimal 6 karakter.'; $msg_type = 'error';
    } else {
        $hash = password_hash($pw_baru, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE admin SET password='$hash' WHERE id=$admin_id");
        $msg = 'Password berhasil diperbarui.'; $msg_type = 'success';
    }
}

// Load settings
$settings = [];
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan WHERE id=1");
if ($q_set) $settings = mysqli_fetch_assoc($q_set) ?? [];

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pengaturan — Cloudy Girls Admin</title>
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
.grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; }

.card { background:var(--surface); border:1px solid var(--border); border-radius:14px; overflow:hidden; }
.card-header { padding:16px 20px; border-bottom:1px solid var(--border); }
.card-header h3 { font-size:14px; font-weight:600; color:var(--white); }
.card-header p { font-size:12px; color:var(--muted); margin-top:2px; }
.card-body { padding:20px; }

.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:12px; font-weight:500; color:var(--muted); margin-bottom:6px; letter-spacing:.3px; }
.form-input, .form-textarea {
    width:100%; background:var(--surface2); border:1px solid var(--border); border-radius:8px;
    color:var(--text); font-size:13px; padding:10px 12px; outline:none;
    font-family:'DM Sans',sans-serif; transition:border-color .2s;
}
.form-input:focus, .form-textarea:focus { border-color:var(--accent); }
.form-textarea { resize:vertical; min-height:80px; }
.form-input::placeholder, .form-textarea::placeholder { color:var(--muted); }

.btn-save {
    display:inline-flex; align-items:center; gap:8px;
    padding:10px 22px; border-radius:8px;
    background:linear-gradient(135deg,var(--accent2),var(--pink2));
    color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer;
    font-family:'DM Sans',sans-serif; transition:opacity .2s;
}
.btn-save:hover { opacity:.88; }

.alert {
    padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px;
    display:flex; align-items:center; gap:8px;
}
.alert.success { background:rgba(52,211,153,.15); color:var(--green); border:1px solid rgba(52,211,153,.3); }
.alert.error   { background:rgba(248,113,113,.15); color:var(--red);   border:1px solid rgba(248,113,113,.3); }

.divider { height:1px; background:var(--border); margin:20px 0; }

.info-box { background:var(--surface2); border:1px solid var(--border); border-radius:10px; padding:14px 16px; font-size:12px; color:var(--muted); line-height:1.6; }
.info-box i { color:var(--accent); margin-right:4px; }

@media (max-width: 900px) { .grid-2 { grid-template-columns:1fr; } }
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
        <a href="pembeli.php" class="nav-item"><i class="bi bi-people"></i> Pembeli</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php" class="nav-item"><i class="bi bi-star"></i> Ulasan</a>
        <a href="pengaturan.php" class="nav-item active"><i class="bi bi-gear"></i> Pengaturan</a>
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
        <div class="topbar-title">Pengaturan</div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <a href="../index.php" class="btn-toko"><i class="bi bi-shop"></i> Lihat Toko</a>
        </div>
    </div>

    <div class="content">

        <?php if ($msg): ?>
        <div class="alert <?= $msg_type ?>">
            <i class="bi bi-<?= $msg_type==='success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
            <?= escape($msg) ?>
        </div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- INFO TOKO -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-shop" style="color:var(--accent);margin-right:6px;"></i> Informasi Toko</h3>
                    <p>Atur tampilan dan info toko Anda</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="update_toko" value="1">
                        <div class="form-group">
                            <label class="form-label">Nama Toko</label>
                            <input type="text" name="nama_toko" class="form-input" placeholder="Cloudy Girls" value="<?= escape($settings['nama_toko'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi Toko</label>
                            <textarea name="deskripsi" class="form-textarea" placeholder="Deskripsi singkat toko Anda..."><?= escape($settings['deskripsi'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp / HP</label>
                            <input type="text" name="no_hp" class="form-input" placeholder="08xxxxxxxxxx" value="<?= escape($settings['no_hp'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat Toko</label>
                            <textarea name="alamat" class="form-textarea" placeholder="Alamat lengkap toko..."><?= escape($settings['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Instagram</label>
                            <input type="text" name="instagram" class="form-input" placeholder="@cloudygirls" value="<?= escape($settings['instagram'] ?? '') ?>">
                        </div>
                        <button type="submit" class="btn-save">
                            <i class="bi bi-floppy"></i> Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>

            <!-- KANAN: PASSWORD + INFO -->
            <div style="display:flex;flex-direction:column;gap:20px;">

                <!-- GANTI PASSWORD -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="bi bi-shield-lock" style="color:var(--pink2);margin-right:6px;"></i> Ganti Password Admin</h3>
                        <p>Perbarui kata sandi akun Anda</p>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="ganti_password" value="1">
                            <div class="form-group">
                                <label class="form-label">Password Lama</label>
                                <input type="password" name="pw_lama" class="form-input" placeholder="Masukkan password lama" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Password Baru</label>
                                <input type="password" name="pw_baru" class="form-input" placeholder="Minimal 6 karakter" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Konfirmasi Password Baru</label>
                                <input type="password" name="pw_ulang" class="form-input" placeholder="Ulangi password baru" required>
                            </div>
                            <button type="submit" class="btn-save">
                                <i class="bi bi-shield-check"></i> Perbarui Password
                            </button>
                        </form>
                    </div>
                </div>

                <!-- INFO SISTEM -->
                <div class="card">
                    <div class="card-header">
                        <h3><i class="bi bi-info-circle" style="color:var(--yellow);margin-right:6px;"></i> Info Sistem</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <div><i class="bi bi-code-slash"></i> <strong>Versi Sistem:</strong> 1.0.0</div>
                            <div style="margin-top:6px;"><i class="bi bi-calendar3"></i> <strong>Tanggal:</strong> <?= date('d M Y, H:i') ?></div>
                            <div style="margin-top:6px;"><i class="bi bi-person-badge"></i> <strong>Admin:</strong> <?= escape($admin_nama) ?></div>
                            <div style="margin-top:6px;"><i class="bi bi-server"></i> <strong>PHP:</strong> <?= phpversion() ?></div>
                        </div>
                        <div class="divider"></div>
                        <div class="info-box" style="color:var(--yellow);background:rgba(251,191,36,.07);border-color:rgba(251,191,36,.2);">
                            <i class="bi bi-exclamation-triangle"></i>
                            Pastikan tabel <strong>pengaturan</strong> memiliki kolom: <code>id, nama_toko, deskripsi, no_hp, alamat, instagram</code>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

</body>
</html>