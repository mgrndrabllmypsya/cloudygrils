<?php
session_name('session_pembeli');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'pembeli') {
    header("Location: ../auth/login.php"); exit;
}
$user_id = $_SESSION['user_id'];

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM pembeli WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sukses = '';
$error  = '';
$tab    = $_GET['tab'] ?? 'profil';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'])) {

    if ($_POST['aksi'] === 'update_profil') {
        $nama   = trim($_POST['nama'] ?? '');
        $no_hp  = trim($_POST['no_hp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        if (empty($nama)) {
            $error = 'Nama tidak boleh kosong.';
        } else {
            $foto_profil = $user['foto_profil'] ?? '';
if (isset($_POST['hapus_foto']) && $_POST['hapus_foto'] === '1') {
    $folderUpload = '../uploads/foto_profil/';
    if ($foto_profil && file_exists($folderUpload . $foto_profil)) {
        unlink($folderUpload . $foto_profil);
    }
    $foto_profil = '';
}
            if (!empty($_FILES['foto_profil']['name']) && $_FILES['foto_profil']['error'] === UPLOAD_ERR_OK) {
                $folderUpload = '../uploads/foto_profil/';
                if (!is_dir($folderUpload)) mkdir($folderUpload, 0755, true);

                // Cek MIME type berdasarkan isi file
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $_FILES['foto_profil']['tmp_name']);
                finfo_close($finfo);

                $mime_map = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                    'image/gif'  => 'gif',
                ];

                if (!isset($mime_map[$mime])) {
                    $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
                } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                    $error = 'Ukuran foto maksimal 2MB.';
                } else {
                    $ext      = $mime_map[$mime];
                    $namaFile = 'profil_' . $user_id . '_' . time() . '.' . $ext;

                    // Hapus foto lama

                    move_uploaded_file($_FILES['foto_profil']['tmp_name'], $folderUpload . $namaFile);
                    $foto_profil = $namaFile;
                }
            }

            if (!$error) {
                $nama_esc   = $conn->real_escape_string($nama);
                $hp_esc     = $conn->real_escape_string($no_hp);
                $alamat_esc = $conn->real_escape_string($alamat);
                $foto_esc   = $conn->real_escape_string($foto_profil);
                $conn->query("UPDATE pembeli SET nama='$nama_esc', no_hp='$hp_esc', foto_profil='$foto_esc' WHERE id=$user_id");
                $_SESSION['nama'] = $nama;
                $sukses = 'Profil berhasil diperbarui!';

                $stmt2 = $conn->prepare("SELECT * FROM pembeli WHERE id = ?");
                $stmt2->bind_param("i", $user_id);
                $stmt2->execute();
                $user = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
            }
        }
        $tab = 'profil';
    }

    if ($_POST['aksi'] === 'ganti_password') {
        $pw_lama  = $_POST['password_lama'] ?? '';
        $pw_baru  = $_POST['password_baru'] ?? '';
        $pw_ulang = $_POST['password_ulang'] ?? '';

        if (empty($pw_lama) || empty($pw_baru) || empty($pw_ulang)) {
            $error = 'Semua field password wajib diisi.';
        } elseif (!password_verify($pw_lama, $user['password'])) {
            $error = 'Password lama tidak sesuai.';
        } elseif (strlen($pw_baru) < 6) {
            $error = 'Password baru minimal 6 karakter.';
        } elseif ($pw_baru !== $pw_ulang) {
            $error = 'Konfirmasi password tidak cocok.';
        } else {
            $pw_hash = password_hash($pw_baru, PASSWORD_DEFAULT);
            $conn->query("UPDATE pembeli SET password='$pw_hash' WHERE id=$user_id");
            $sukses = 'Password berhasil diperbarui!';
        }
        $tab = 'account';
    }
}

$total_pesanan = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE pembeli_id=$user_id"))[0] ?? 0;
$total_selesai = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE pembeli_id=$user_id AND status='selesai'"))[0] ?? 0;
$total_belanja = mysqli_fetch_row(mysqli_query($conn, "SELECT COALESCE(SUM(total_bayar),0) FROM pesanan WHERE pembeli_id=$user_id AND status='selesai'"))[0] ?? 0;
$total_ulasan  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ulasan WHERE pembeli_id=$user_id"))[0] ?? 0;

$foto_src = !empty($user['foto_profil'])
    ? '../uploads/foto_profil/' . $user['foto_profil']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['nama']) . '&background=D63384&color=fff&size=128';

$page_title = 'Profil Saya — Cloudy Girls';
include '../includes/header.php';
?>

<style>
:root {
    --pink-deep:  #D63384;
    --pink-mid:   #F06292;
    --pink-soft:  #F8BBD9;
    --pink-pale:  #FDE8F2;
    --pink-blush: #FFF0F7;
    --cream:      #FFF8FC;
    --white:      #FFFFFF;
    --dark:       #2D1B25;
    --muted:      #A07090;
    --border:     #F2D0E5;
    --green:      #10b981;
    --red:        #ef4444;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; color:var(--dark); background:var(--cream); }
a { text-decoration:none; color:inherit; }

.page-wrap { max-width:960px; margin:0 auto; padding:32px 20px 80px; }

.profil-layout { display:grid; grid-template-columns:240px 1fr; gap:24px; align-items:start; }

/* SIDEBAR */
.sidebar-profil {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden; position:sticky; top:80px;
}
.sidebar-user {
    padding:20px; text-align:center;
    border-bottom:1px solid var(--border);
    background:var(--pink-blush);
}
.sidebar-avatar { width:72px; height:72px; border-radius:50%; object-fit:cover; border:3px solid var(--pink-soft); margin-bottom:10px; }
.sidebar-nama { font-weight:700; font-size:14px; color:var(--dark); margin-bottom:2px; }
.sidebar-email { font-size:11px; color:var(--muted); }
.sidebar-nav { padding:8px; }
.nav-link {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:10px;
    font-size:13px; font-weight:500; color:var(--muted);
    cursor:pointer; transition:all .2s; margin-bottom:2px; 
}
.nav-link i { font-size:16px; width:18px; }
.nav-link:hover { background:var(--pink-pale); color:var(--pink-deep); }
.nav-link.active { background:#1db899b1; color:#fff; }
.nav-link.active i { color:#fff; }
.nav-link.danger { color:var(--red); }
.nav-link.danger:hover { background:#1db899b1;; }

/* CARD */
.card { background:var(--white); border:1.5px solid var(--border); border-radius:16px; overflow:hidden; margin-bottom:20px; }
.card-head { padding:18px 24px; border-bottom:1px solid var(--border); }
.card-head h2 { font-size:17px; font-weight:700; color:var(--dark); margin-bottom:2px; }
.card-head p { font-size:12px; color:var(--muted); }
.card-body { padding:24px; }

/* FOTO */
.foto-row { display:flex; align-items:center; gap:16px; margin-bottom:24px; }
.foto-avatar { width:72px; height:72px; border-radius:50%; object-fit:cover; border:3px solid var(--border); flex-shrink:0; }
.foto-btns { display:flex; gap:8px; flex-wrap:wrap; }
.btn-foto {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 16px; border-radius:8px;
    font-size:12px; font-weight:600; cursor:pointer;
    font-family:'DM Sans',sans-serif; transition:all .2s; border:none;
}
.btn-foto-ganti { background:#1db899b1; color:#fff; position:relative; overflow:hidden; }
.btn-foto-ganti input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.btn-foto-hapus { background:var(--white); color:var(--dark); border:1.5px solid var(--border) !important; }
.btn-foto-hapus:hover { border-color:var(--red) !important; color:var(--red); }

/* FORM */
.form-group { margin-bottom:16px; }
.form-group label {
    display:block; font-size:11px; font-weight:700;
    color:var(--muted); text-transform:uppercase;
    letter-spacing:.8px; margin-bottom:6px;
}
.form-group input, .form-group textarea {
    width:100%; padding:10px 14px;
    border:1.5px solid var(--border); border-radius:10px;
    font-family:'DM Sans',sans-serif; font-size:14px;
    color:var(--dark); outline:none; transition:border-color .2s; background:#fff;
}
.form-group input:focus, .form-group textarea:focus { border-color:var(--dark); }
.form-group input[readonly] { background:#f9fafb; color:var(--muted); cursor:not-allowed; }
.form-group textarea { resize:vertical; min-height:80px; }

.form-foot {
    display:flex; justify-content:flex-end; gap:10px;
    margin-top:20px; padding-top:20px; border-top:1px solid var(--border);
}
.btn-batal {
    padding:10px 20px; border-radius:10px;
    background:var(--white); color:var(--dark);
    border:1.5px solid var(--border);
    font-size:13px; font-weight:600; cursor:pointer;
    font-family:'DM Sans',sans-serif; text-decoration:none;
    display:inline-flex; align-items:center;
}
.btn-simpan {
    padding:10px 24px; border-radius:10px;
    background:#1db899b1; color:#fff; border:none;
    font-size:13px; font-weight:600; cursor:pointer;
    font-family:'DM Sans',sans-serif; transition:opacity .2s;
}
.btn-simpan:hover { opacity:.85; }

/* STATS */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
.stat-box { background:var(--white); border:1.5px solid var(--border); border-radius:12px; padding:14px; text-align:center; }
.stat-box .val { font-size:20px; font-weight:700; color:var(--pink-deep); }
.stat-box .lbl { font-size:11px; color:var(--muted); margin-top:2px; }

/* PW STRENGTH */
.pw-strength { height:4px; border-radius:2px; margin-top:6px; transition:all .3s; }
.pw-weak { background:#ef4444; width:33%; }
.pw-medium { background:#f59e0b; width:66%; }
.pw-strong { background:#10b981; width:100%; }

/* ALERT */
.alert { padding:12px 16px; border-radius:10px; font-size:13px; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
.alert-error { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }

@media(max-width:700px) {
    .profil-layout { grid-template-columns:1fr; }
    .sidebar-profil { position:static; }
    .stats-grid { grid-template-columns:repeat(2,1fr); }
}
</style>

<div class="page-wrap">

    <?php if ($sukses): ?>
    <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?= escape($sukses) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="bi bi-exclamation-circle-fill"></i> <?= escape($error) ?></div>
    <?php endif; ?>

    <div class="profil-layout">

        <!-- SIDEBAR -->
        <div class="sidebar-profil">
            <div class="sidebar-user">
                <img src="<?= escape($foto_src) ?>" class="sidebar-avatar" id="sidebarAvatar" alt="foto">
                <div class="sidebar-nama"><?= escape($user['nama']) ?></div>
                <div class="sidebar-email"><?= escape($user['email']) ?></div>
            </div>
            <div class="sidebar-nav">
                <a href="?tab=profil" class="nav-link <?= $tab==='profil'?'active':'' ?>">
                    <i class="bi bi-person-circle"></i> Profil
                </a>
                <a href="?tab=account" class="nav-link <?= $tab==='account'?'active':'' ?>">
                    <i class="bi bi-gear"></i> Account
                </a>
                <a href="?tab=aktivitas" class="nav-link <?= $tab==='aktivitas'?'active':'' ?>">
                    <i class="bi bi-clock-history"></i> Aktivitas
                </a>
                <div style="border-top:1px solid var(--border);margin:8px 0;"></div>
                <a href="../auth/logout.php" class="nav-link danger">
                    <i class="bi bi-box-arrow-left"></i> Keluar
                </a>
            </div>
        </div>

        <!-- KONTEN -->
        <div>

            <?php if ($tab === 'profil'): ?>
            <div class="card">
                <div class="card-head">
                    <h2>Profil</h2>
                    <p>Atur dan perbarui profil kamu di sini.</p>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="aksi" value="update_profil">
                        <input type="hidden" name="hapus_foto" id="hapusFotoFlag" value="0">

                        <div class="foto-row">
                            <img src="<?= escape($foto_src) ?>" class="foto-avatar" id="fotoPreview" alt="foto">
                            <div class="foto-btns">
                                <label class="btn-foto btn-foto-ganti">
                                    <i class="bi bi-camera"></i> Ganti gambar
                                    <input type="file" name="foto_profil" accept="image/jpeg,image/png,image/webp" onchange="previewFoto(this)">
                                </label>
                                <button type="button" class="btn-foto btn-foto-hapus" onclick="hapusFoto()">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" name="nama" value="<?= escape($user['nama']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" value="<?= escape($user['email']) ?>" readonly>
                        </div>
                        

                        <div class="form-foot">
                            <a href="home.php" class="btn-batal">Batal</a>
                            <button type="submit" class="btn-simpan">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'account'): ?>
            <div class="card">
                <div class="card-head">
                    <h2>Account</h2>
                    <p>Kelola keamanan akun kamu.</p>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="aksi" value="ganti_password">
                        <div class="form-group">
                            <label>Password Lama</label>
                            <input type="password" name="password_lama" placeholder="Masukkan password lama" required>
                        </div>
                        <div class="form-group">
                            <label>Password Baru</label>
                            <input type="password" name="password_baru" placeholder="Minimal 6 karakter" oninput="cekKekuatan(this.value)" required>
                            <div class="pw-strength" id="pwStrength"></div>
                        </div>
                        <div class="form-group">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="password_ulang" placeholder="Ulangi password baru" required>
                        </div>
                        <div class="form-foot">
                            <a href="?tab=account" class="btn-batal">Batal</a>
                            <button type="submit" class="btn-simpan">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>

            <?php elseif ($tab === 'aktivitas'): ?>
            <div class="card">
                <div class="card-head">
                    <h2>Aktivitas</h2>
                    <p>Ringkasan aktivitas belanja kamu.</p>
                </div>
                <div class="card-body">
                    <div class="stats-grid">
                        <div class="stat-box">
                            <div class="val"><?= $total_pesanan ?></div>
                            <div class="lbl">Total Pesanan</div>
                        </div>
                        <div class="stat-box">
                            <div class="val"><?= $total_selesai ?></div>
                            <div class="lbl">Selesai</div>
                        </div>
                        <div class="stat-box">
                            <div class="val" style="font-size:14px;">Rp <?= number_format($total_belanja,0,',','.') ?></div>
                            <div class="lbl">Total Belanja</div>
                        </div>
                        <div class="stat-box">
                            <div class="val"><?= $total_ulasan ?></div>
                            <div class="lbl">Ulasan</div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px;">
                        <a href="pesanan.php" style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--pink-blush);border-radius:12px;border:1.5px solid var(--border);color:var(--dark);transition:all .2s;"
                           onmouseover="this.style.borderColor='var(--pink-deep)'" onmouseout="this.style.borderColor='var(--border)'">
                            <i class="bi bi-bag-check" style="font-size:20px;color:var(--pink-deep);"></i>
                            <div>
                                <div style="font-weight:600;font-size:13px;">Pesanan Saya</div>
                                <div style="font-size:12px;color:var(--muted);"><?= $total_pesanan ?> pesanan</div>
                            </div>
                            <i class="bi bi-chevron-right" style="margin-left:auto;color:var(--muted);"></i>
                        </a>
                        <a href="home.php" style="display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--pink-blush);border-radius:12px;border:1.5px solid var(--border);color:var(--dark);transition:all .2s;"
                           onmouseover="this.style.borderColor='var(--pink-deep)'" onmouseout="this.style.borderColor='var(--border)'">
                            <i class="bi bi-shop" style="font-size:20px;color:var(--pink-deep);"></i>
                            <div>
                                <div style="font-weight:600;font-size:13px;">Belanja Lagi</div>
                                <div style="font-size:12px;color:var(--muted);">Lihat koleksi terbaru</div>
                            </div>
                            <i class="bi bi-chevron-right" style="margin-left:auto;color:var(--muted);"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
function previewFoto(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('fotoPreview').src = e.target.result;
        document.getElementById('sidebarAvatar').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function hapusFoto() {
    const defaultSrc = 'https://ui-avatars.com/api/?name=<?= urlencode($user['nama']) ?>&background=D63384&color=fff&size=128';
    document.getElementById('fotoPreview').src = defaultSrc;
    document.getElementById('sidebarAvatar').src = defaultSrc;
    document.getElementById('hapusFotoFlag').value = '1';
    const fileInput = document.querySelector('input[name="foto_profil"]');
    if (fileInput) fileInput.value = '';
}

function cekKekuatan(pw) {
    const bar = document.getElementById('pwStrength');
    if (!bar) return;
    bar.className = 'pw-strength';
    if (pw.length === 0) return;
    if (pw.length < 6) bar.classList.add('pw-weak');
    else if (pw.length < 10 || !/[0-9]/.test(pw)) bar.classList.add('pw-medium');
    else bar.classList.add('pw-strong');
}
</script>