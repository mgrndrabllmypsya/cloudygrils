<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$msg = '';
$msg_type = '';

// Handle update pengaturan toko
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_toko'])) {
    $nama_toko  = mysqli_real_escape_string($conn, $_POST['nama_toko']  ?? '');
    $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']  ?? '');
    $no_hp      = mysqli_real_escape_string($conn, $_POST['no_hp']      ?? '');
    $alamat     = mysqli_real_escape_string($conn, $_POST['alamat']     ?? '');
    $instagram  = mysqli_real_escape_string($conn, $_POST['instagram']  ?? '');
    $maps_url   = mysqli_real_escape_string($conn, $_POST['maps_url']   ?? '');

    $logo_sql = '';
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $ftype   = $_FILES['logo']['type'];
        if (in_array($ftype, $allowed)) {
            $ext      = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = 'logo_toko_' . time() . '.' . $ext;
            $upload_dir = '../uploads/toko/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $filename)) {
                $logo_sql = ", logo='$filename'";
            }
        } else {
            $msg = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            $msg_type = 'error';
        }
    }

    if ($msg_type !== 'error') {
        $exist = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pengaturan_toko"));
        if ($exist && $exist[0] > 0) {
            mysqli_query($conn, "UPDATE pengaturan_toko SET
                nama_toko='$nama_toko', deskripsi='$deskripsi', no_hp='$no_hp',
                alamat='$alamat', instagram='$instagram', maps_url='$maps_url'
                $logo_sql WHERE id=1");
        } else {
            mysqli_query($conn, "INSERT INTO pengaturan_toko (nama_toko, deskripsi, no_hp, alamat, instagram, maps_url)
                VALUES ('$nama_toko','$deskripsi','$no_hp','$alamat','$instagram','$maps_url')");
        }
        $msg = 'Pengaturan toko berhasil disimpan.';
        $msg_type = 'success';
    }
}

// Handle ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $pw_lama  = $_POST['pw_lama']  ?? '';
    $pw_baru  = $_POST['pw_baru']  ?? '';
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
$q_set = mysqli_query($conn, "SELECT * FROM pengaturan_toko WHERE id=1");
if ($q_set) $settings = mysqli_fetch_assoc($q_set) ?? [];

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$logo_path  = !empty($settings['logo']) ? '../uploads/toko/' . $settings['logo'] : null;
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
    --text:     #1A1A1A;
    --text2:    #444444;
    --muted:    #AAAAAA;
    --white:    #FFFFFF;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--bg);
    color: var(--text);
    display: flex;
    min-height: 100vh;
}
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, #FFB6D0 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: .15;
    pointer-events: none;
    z-index: 0;
}
a { text-decoration: none; color: inherit; }

/* ── SIDEBAR ── */
.sidebar {
    width: 240px;
    background: linear-gradient(180deg, #FF80AB 0%, #FF4081 45%, #F50057 100%);
    display: flex; flex-direction: column;
    position: fixed; top: 0; left: 0; bottom: 0; z-index: 50;
    box-shadow: 4px 0 28px rgba(255,64,129,.3);
}
.sidebar-logo {
    padding: 24px 24px 20px;
    border-bottom: 1.5px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.12);
}
.sidebar-logo .logo { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 900; color: #fff; }
.sidebar-logo .logo span { color: #FFE4EE; }
.sidebar-logo small { display: block; font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.65); margin-top: 2px; }
.sidebar-nav { flex: 1; padding: 16px 12px; display: flex; flex-direction: column; gap: 2px; }
.nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,.8); transition: all .2s; }
.nav-item:hover { background: rgba(255,255,255,.2); color: #fff; }
.nav-item.active { background: rgba(255,255,255,.28); color: #fff; font-weight: 600; border-left: 3px solid #fff; }
.nav-item i { font-size: 16px; width: 20px; }
.nav-section { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.55); padding: 14px 14px 6px; font-weight: 600; }
.sidebar-footer { padding: 16px 12px; border-top: 1.5px solid rgba(255,255,255,.2); background: rgba(0,0,0,.1); }
.admin-card { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: rgba(255,255,255,.18); border-radius: 10px; margin-bottom: 10px; border: 1.5px solid rgba(255,255,255,.3); }
.admin-avatar { width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.3); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; color: #fff; flex-shrink: 0; border: 2px solid rgba(255,255,255,.5); overflow: hidden; }
.admin-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.admin-info .name { font-size: 13px; font-weight: 600; color: #fff; }
.admin-info .role { font-size: 10px; color: rgba(255,255,255,.65); }
.btn-logout { display: flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 8px; font-size: 12px; color: rgba(255,255,255,.85); transition: background .2s; width: 100%; }
.btn-logout:hover { background: rgba(255,255,255,.2); color: #fff; }

/* ── MAIN ── */
.main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; position: relative; z-index: 1; }

/* ── TOPBAR ── */
.topbar {
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(12px);
    border-bottom: 1.5px solid var(--border);
    padding: 0 32px; height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 40;
    box-shadow: 0 2px 12px rgba(255,64,129,.07);
}
.topbar-title { font-family: 'Playfair Display', serif; font-size: 18px; font-weight: 700; color: var(--text); }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-date { font-size: 12px; color: var(--muted); }
.btn-toko { display: flex; align-items: center; gap: 6px; padding: 8px 14px; border-radius: 8px; background: linear-gradient(135deg,#FF80AB,#FF4081); font-size: 12px; font-weight: 600; color: #fff; box-shadow: 0 3px 12px rgba(255,64,129,.35); transition: opacity .2s; }
.btn-toko:hover { opacity: .88; }

/* ── CONTENT ── */
.content { padding: 28px 32px; flex: 1; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* ── CARD ── */
.card { background: var(--surface); border: 1.5px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: 0 2px 16px rgba(255,64,129,.06); }
.card-header { padding: 16px 20px; border-bottom: 1.5px solid var(--border); background: var(--surface2); }
.card-header h3 { font-size: 14px; font-weight: 600; color: var(--text); }
.card-header p { font-size: 12px; color: var(--muted); margin-top: 2px; }
.card-body { padding: 20px; }

/* ── FORM ── */
.form-group { margin-bottom: 16px; }
.form-label { display: block; font-size: 12px; font-weight: 600; color: var(--text2); margin-bottom: 6px; letter-spacing: .3px; }
.form-input, .form-textarea {
    width: 100%;
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 8px;
    color: var(--text);
    font-size: 13px;
    padding: 10px 12px;
    outline: none;
    font-family: 'DM Sans', sans-serif;
    transition: border-color .2s, box-shadow .2s;
}
.form-input:focus, .form-textarea:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(255,64,129,.1);
}
.form-input::placeholder, .form-textarea::placeholder { color: var(--muted); }
.form-textarea { resize: vertical; min-height: 80px; }

/* ── LOGO UPLOAD ── */
.logo-upload-wrap { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
.logo-preview {
    width: 80px; height: 80px; border-radius: 12px; flex-shrink: 0;
    background: var(--surface2); border: 2px dashed var(--border);
    display: flex; align-items: center; justify-content: center;
    overflow: hidden; cursor: pointer; transition: border-color .2s;
}
.logo-preview:hover { border-color: var(--accent); }
.logo-preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
.logo-placeholder { font-size: 28px; color: var(--muted); }
.logo-upload-info p { font-size: 12px; color: var(--muted); line-height: 1.5; }
.btn-upload-logo {
    display: inline-flex; align-items: center; gap: 6px; margin-top: 8px;
    padding: 7px 14px; border-radius: 7px;
    background: var(--surface2); border: 1.5px solid var(--border);
    font-size: 12px; color: var(--accent); cursor: pointer;
    font-family: 'DM Sans', sans-serif; transition: border-color .2s, background .2s;
}
.btn-upload-logo:hover { border-color: var(--accent); background: rgba(255,64,129,.06); }
#inputLogo { display: none; }

/* ── MAPS INFO BOX ── */
.maps-info {
    background: rgba(255,64,129,.05);
    border: 1px solid rgba(255,64,129,.2);
    border-radius: 8px;
    padding: 10px 12px;
    margin-top: 6px;
}
.maps-info-title { font-size: 11px; font-weight: 600; color: var(--accent); margin-bottom: 4px; }
.maps-info ol { font-size: 11px; color: var(--muted); padding-left: 14px; line-height: 1.9; margin: 0; }

/* ── BUTTONS ── */
.btn-save {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 10px 22px; border-radius: 8px;
    background: linear-gradient(135deg, #FF80AB, #FF4081);
    color: #fff; font-size: 13px; font-weight: 600;
    border: none; cursor: pointer; font-family: 'DM Sans', sans-serif;
    box-shadow: 0 4px 14px rgba(255,64,129,.35);
    transition: opacity .2s, transform .15s;
}
.btn-save:hover { opacity: .88; transform: translateY(-1px); }

/* ── ALERTS ── */
.alert {
    padding: 12px 16px; border-radius: 10px; font-size: 13px;
    margin-bottom: 20px; display: flex; align-items: center; gap: 8px;
}
.alert.success { background: rgba(0,191,165,.12); color: var(--green); border: 1px solid rgba(0,191,165,.3); }
.alert.error   { background: rgba(255,23,68,.1);  color: var(--red);   border: 1px solid rgba(255,23,68,.25); }

/* ── INFO BOX ── */
.info-box {
    background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 10px; padding: 14px 16px;
    font-size: 12px; color: var(--text2); line-height: 1.6;
}
.info-box .info-row { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.info-box .info-row:last-child { margin-bottom: 0; }
.info-box i { color: var(--accent); font-size: 14px; }

/* ── PASSWORD STRENGTH ── */
.pw-strength { height: 4px; border-radius: 2px; margin-top: 6px; background: var(--border); overflow: hidden; }
.pw-strength-bar { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0; }

@media (max-width: 900px) { .grid-2 { grid-template-columns: 1fr; } }
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php"  class="nav-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="produk.php"     class="nav-item"><i class="bi bi-handbag"></i> Produk</a>
        <a href="pesanan.php"    class="nav-item"><i class="bi bi-bag-check"></i> Pesanan</a>
        <a href="chat.php"       class="nav-item"><i class="bi bi-chat-dots"></i> Chat</a>
        <a href="nego.php"       class="nav-item"><i class="bi bi-tags"></i> Nego Harga</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php"     class="nav-item"><i class="bi bi-star"></i> Ulasan</a>
        <a href="pengaturan.php" class="nav-item active"><i class="bi bi-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
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
            <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
            <?= escape($msg) ?>
        </div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- ── INFORMASI TOKO ── -->
            <div class="card">
                <div class="card-header">
                    <h3><i class="bi bi-shop" style="color:var(--accent);margin-right:6px;"></i> Informasi Toko</h3>
                    <p>Atur tampilan dan info toko Anda</p>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="update_toko" value="1">

                        <!-- Logo -->
                        <div class="form-group">
                            <label class="form-label">Logo / Foto Toko</label>
                            <div class="logo-upload-wrap">
                                <div class="logo-preview" onclick="document.getElementById('inputLogo').click()" title="Klik untuk ganti logo">
                                    <?php if ($logo_path && file_exists($logo_path)): ?>
                                        <img src="<?= escape($logo_path) ?>" alt="Logo Toko" id="logoImg">
                                    <?php else: ?>
                                        <i class="bi bi-shop logo-placeholder" id="logoPlaceholder"></i>
                                        <img src="" alt="" id="logoImg" style="display:none;">
                                    <?php endif; ?>
                                </div>
                                <div class="logo-upload-info">
                                    <p>Ukuran disarankan <strong>300×300px</strong>.<br>Format: JPG, PNG, atau WEBP.<br>Maks. 2MB.</p>
                                    <label class="btn-upload-logo" for="inputLogo">
                                        <i class="bi bi-upload"></i> Pilih Gambar
                                    </label>
                                </div>
                            </div>
                            <input type="file" name="logo" id="inputLogo" accept="image/*" onchange="previewLogo(this)">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nama Toko</label>
                            <input type="text" name="nama_toko" class="form-input" placeholder="Cloudy Girls"
                                value="<?= escape($settings['nama_toko'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Deskripsi Toko</label>
                            <textarea name="deskripsi" class="form-textarea" placeholder="Deskripsi singkat toko Anda..."><?= escape($settings['deskripsi'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nomor WhatsApp / HP</label>
                            <input type="text" name="no_hp" class="form-input" placeholder="08xxxxxxxxxx"
                                value="<?= escape($settings['no_hp'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Alamat Toko</label>
                            <textarea name="alamat" class="form-textarea" placeholder="Alamat lengkap toko..."><?= escape($settings['alamat'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Instagram</label>
                            <input type="text" name="instagram" class="form-input" placeholder="@cloudygirls"
                                value="<?= escape($settings['instagram'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Link Google Maps Toko</label>
                            <input type="url" name="maps_url" class="form-input"
                                   placeholder="https://maps.app.goo.gl/xxxxx"
                                   value="<?= escape($settings['maps_url'] ?? '') ?>">
                            <div class="maps-info">
                                <div class="maps-info-title"><i class="bi bi-question-circle"></i> Cara dapat link Google Maps:</div>
                                <ol>
                                    <li>Buka <strong>Google Maps</strong> di HP</li>
                                    <li>Cari atau tandai lokasi toko/rumahmu</li>
                                    <li>Tap <strong>Bagikan</strong> → <strong>Salin link</strong></li>
                                    <li>Tempel link di kolom di atas</li>
                                </ol>
                            </div>
                        </div>

                        <button type="submit" class="btn-save">
                            <i class="bi bi-floppy"></i> Simpan Pengaturan
                        </button>
                    </form>
                </div>
            </div>

            <!-- ── KANAN ── -->
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
                                <input type="password" name="pw_baru" class="form-input" placeholder="Minimal 6 karakter" id="pwBaru" required oninput="checkStrength(this.value)">
                                <div class="pw-strength"><div class="pw-strength-bar" id="pwBar"></div></div>
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
                            <div class="info-row"><i class="bi bi-code-slash"></i> <span><strong>Versi Sistem:</strong> 1.0.0</span></div>
                            <div class="info-row"><i class="bi bi-calendar3"></i> <span><strong>Tanggal:</strong> <?= date('d M Y, H:i') ?></span></div>
                            <div class="info-row"><i class="bi bi-person-badge"></i> <span><strong>Admin:</strong> <?= escape($admin_nama) ?></span></div>
                            <div class="info-row"><i class="bi bi-server"></i> <span><strong>PHP:</strong> <?= phpversion() ?></span></div>
                        </div>
                    </div>
                </div>

            </div><!-- end kanan -->

        </div><!-- end grid-2 -->
    </div><!-- end content -->
</div><!-- end main -->

<script>
function previewLogo(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.getElementById('logoImg');
            const ph  = document.getElementById('logoPlaceholder');
            img.src = e.target.result;
            img.style.display = 'block';
            if (ph) ph.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function checkStrength(val) {
    const bar = document.getElementById('pwBar');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const pct = (score / 5) * 100;
    const colors = ['#FF1744','#FF6D00','#FFB300','#00BFA5','#00BFA5'];
    bar.style.width  = pct + '%';
    bar.style.background = colors[score - 1] || '#FFB6D0';
}
</script>
</body>
</html>