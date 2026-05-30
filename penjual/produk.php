<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

$message      = "";
$message_type = "";

// ── HAPUS PRODUK ──
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $res = $conn->query("SELECT foto_utama FROM produk WHERE id=$id");
    if ($r = $res->fetch_assoc()) {
        $path = "../uploads/produk/" . $r['foto_utama'];
        if ($r['foto_utama'] && file_exists($path)) unlink($path);
    }
    $conn->query("DELETE FROM pesanan WHERE produk_id=$id");
    $conn->query("DELETE FROM produk WHERE id=$id");
    header("Location: produk.php?msg=deleted"); exit;
}

// ── LOAD EDIT ──
$edit = null;
if (isset($_GET['edit'])) {
    $id  = (int)$_GET['edit'];
    $res = $conn->query("SELECT * FROM produk WHERE id=$id");
    if ($res) $edit = $res->fetch_assoc();
}

// ── SIMPAN / UPDATE ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_barang = $conn->real_escape_string(trim($_POST['nama_barang']));
    $kategori    = $conn->real_escape_string($_POST['kategori']);
    $deskripsi   = $conn->real_escape_string(trim($_POST['deskripsi']));
    $harga       = (float)$_POST['harga'];
    $kondisi     = $conn->real_escape_string($_POST['kondisi']);
    $ukuran      = $conn->real_escape_string(trim($_POST['ukuran']));
    $id_edit     = isset($_POST['id_edit']) ? (int)$_POST['id_edit'] : 0;

    $foto_utama_name = "";
    if (!empty($_FILES['foto_utama']['name'])) {
        $upload_dir = "../uploads/produk/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext     = strtolower(pathinfo($_FILES['foto_utama']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $message = "Format tidak didukung. Gunakan JPG, PNG, atau WEBP.";
            $message_type = "error";
        } elseif ($_FILES['foto_utama']['size'] > 5 * 1024 * 1024) {
            $message = "Ukuran foto maksimal 5MB.";
            $message_type = "error";
        } else {
            $foto_utama_name = 'prod_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['foto_utama']['tmp_name'], $upload_dir . $foto_utama_name);
        }
    }

    if (empty($message)) {
        if ($id_edit > 0) {
            if ($foto_utama_name) {
                $res = $conn->query("SELECT foto_utama FROM produk WHERE id=$id_edit");
                if ($r = $res->fetch_assoc()) {
                    $old = "../uploads/produk/" . $r['foto_utama'];
                    if ($r['foto_utama'] && file_exists($old)) unlink($old);
                }
            }
            $foto_query = $foto_utama_name ? ", foto_utama='$foto_utama_name'" : "";
            $sql = "UPDATE produk SET
                        kategori='$kategori', nama_barang='$nama_barang', deskripsi='$deskripsi',
                        harga=$harga, kondisi='$kondisi', ukuran='$ukuran' $foto_query
                    WHERE id=$id_edit";
            $conn->query($sql);
            $message      = "Produk berhasil diperbarui!";
            $message_type = "success";
            $edit = null;
        } else {
            $sql = "INSERT INTO produk
                        (kategori, nama_barang, deskripsi, harga, kondisi, ukuran, foto_utama, status)
                    VALUES
                        ('$kategori','$nama_barang','$deskripsi',$harga,'$kondisi','$ukuran','$foto_utama_name','aktif')";
            $conn->query($sql);
            $message      = "Produk berhasil ditambahkan!";
            $message_type = "success";
        }
    }
}

// ── DAFTAR PRODUK ──
$search   = $conn->real_escape_string($_GET['q'] ?? '');
$filter_k = $conn->real_escape_string($_GET['kategori'] ?? '');
$where    = "WHERE status != 'dihapus'";
if ($search)   $where .= " AND nama_barang LIKE '%$search%'";
if ($filter_k) $where .= " AND kategori='$filter_k'";

$produk_list   = $conn->query("SELECT * FROM produk $where ORDER BY id DESC");
$total_produk  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='aktif'"))[0] ?? 0;
$total_terjual = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='terjual'"))[0] ?? 0;
$total_ditahan = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='ditahan'"))[0] ?? 0;
$total_semua   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status != 'dihapus'"))[0] ?? 0;
$total_unread = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
$nego_menunggu = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;

$kategori_list = ['Atasan','Bawahan','Dress/Gamis','Outer','Hijab & Aksesoris'];
$kondisi_list  = ['Mulus','Bekas Pakai','Perlu Perbaikan'];
$ukuran_list   = ['XS','S','M','L','XL','XXL','XXXL','All Size'];

$total_unread = mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"
))[0] ?? 0;

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$edit_json  = $edit ? json_encode($edit) : 'null';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Produk — Cloudy Girls Admin</title>
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

/* SIDEBAR */
.sidebar {
    width:300px;
    background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%);
    display:flex; flex-direction:column;
    position:fixed; top:0; left:0; bottom:0; z-index:50;
    border-radius: 0 28px 28px 0;
    box-shadow: 6px 0 32px rgba(212,84,127,.28);
    overflow: hidden;
}
.sidebar-logo { padding:28px 28px 22px; border-bottom:1.5px solid rgba(255,255,255,.2); background:rgba(255,255,255,.12); }
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
.sidebar-footer { padding:16px 18px 20px; border-top:1.5px solid rgba(255,255,255,.2); background:rgba(0,0,0,.1); }
.btn-logout { display:flex; align-items:center; gap:10px; padding:11px 16px; border-radius:10px; font-size:13px; font-weight:500; color:rgba(255,255,255,.85); transition:background .2s; width:100%; letter-spacing:0.01em; }
.btn-logout i { font-size:16px; }
.btn-logout:hover { background:rgba(255,255,255,.2); color:#fff; }
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

/* MAIN */
.main { margin-left:300px; flex:1; display:flex; flex-direction:column; min-height:100vh; position:relative; z-index:1; }

/* TOPBAR */
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
.btn-toko { display:flex; align-items:center; gap:6px; padding:7px 14px; border-radius:8px; background:var(--surface2); border:1.5px solid var(--border); font-size:12px; font-weight:500; color:var(--text2); transition:all .2s; }
.btn-toko:hover { border-color:var(--accent); color:var(--accent); }

/* TAMBAH PRODUK BUTTON */
.btn-tambah {
    display:flex; align-items:center; gap:8px;
    padding:9px 18px; border-radius:10px;
    background:linear-gradient(135deg,#F4A7C3,#E8719A);
    color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer;
    font-family:'DM Sans',sans-serif;
    box-shadow:0 4px 14px rgba(212,84,127,.35);
    transition:opacity .2s, transform .1s;
    white-space:nowrap;
}
.btn-tambah:hover { opacity:.8; transform:translateY(-1px); }
.btn-tambah:active { transform:scale(.97); }
.btn-tambah .plus-icon { width:22px; height:22px; border-radius:6px; background:rgba(255,255,255,.25); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; line-height:1; }

/* CONTENT */
.content { padding:24px 28px; flex:1; }

/* STATS */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
.stat-card { background:var(--white); border:1.5px solid var(--border); border-radius:14px; padding:16px 18px; display:flex; align-items:center; gap:14px; transition:transform .2s, box-shadow .2s; cursor:pointer; box-shadow:0 2px 12px rgba(212,84,127,.07); }
.stat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(212,84,127,.15); }
.stat-icon-wrap { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
.stat-card.all     .stat-icon-wrap { background:#FFE0EF; color:#E8719A; }
.stat-card.aktif   .stat-icon-wrap { background:#E0F2F1; color:#00BFA5; }
.stat-card.terjual .stat-icon-wrap { background:#FCE4EC; color:#D4547F; }
.stat-card.ditahan .stat-icon-wrap { background:#FFF8E1; color:#FFB300; }
.stat-info .val { font-size:22px; font-weight:700; color:var(--text); line-height:1; }
.stat-info .lbl { font-size:11px; color:var(--muted); margin-top:2px; }

/* ALERT */
.alert { padding:12px 16px; border-radius:10px; margin-bottom:18px; font-size:13px; display:flex; align-items:center; gap:8px; animation:fadeIn .3s ease; }
.alert-success { background:#E0F2F1; color:#00695C; border:1.5px solid #80CBC4; }
.alert-error   { background:#FFEBEE; color:#C62828; border:1.5px solid #EF9A9A; }
.alert-del     { background:#FFEBEE; color:#C62828; border:1.5px solid #EF9A9A; }
@keyframes fadeIn { from{opacity:0;transform:translateY(-6px);} to{opacity:1;transform:none;} }

/* CARD */
.card { background:var(--white); border:1.5px solid var(--border); border-radius:16px; overflow:hidden; box-shadow:0 2px 12px rgba(212,84,127,.07); }
.card-header { padding:14px 20px; border-bottom:1.5px solid var(--border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; background:linear-gradient(to right,#FFF0F5,#fff); }
.card-header h3 { font-size:14px; font-weight:600; color:var(--text); display:flex; align-items:center; gap:6px; }
.card-count { font-size:11px; color:var(--muted); background:var(--surface2); padding:3px 10px; border-radius:20px; border:1.5px solid var(--border); }

/* FILTER BAR */
.filter-bar { display:flex; gap:10px; padding:14px 20px; flex-wrap:wrap; border-bottom:1.5px solid var(--border); align-items:center; }
.filter-input { flex:1; min-width:160px; background:var(--surface2); border:1.5px solid var(--border); border-radius:8px; color:var(--text); padding:8px 12px; font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:border-color .2s; }
.filter-input:focus { border-color:var(--accent); }
.filter-select { background:var(--surface2); border:1.5px solid var(--border); border-radius:8px; color:var(--text); padding:8px 12px; font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:border-color .2s; }
.filter-select:focus { border-color:var(--accent); }
.btn-sm { padding:7px 14px; border-radius:8px; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:5px; transition:opacity .2s; }
.btn-primary-sm { background:linear-gradient(135deg,#F4A7C3,#E8719A); color:#fff; box-shadow:0 3px 10px rgba(212,84,127,.3); }
.btn-primary-sm:hover { opacity:.85; }
.btn-ghost-sm { background:var(--surface2); color:var(--muted); border:1.5px solid var(--border) !important; }
.btn-ghost-sm:hover { color:var(--text); border-color:var(--accent) !important; }

/* TABLE */
.table-wrap { overflow-x:auto; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { text-align:left; padding:10px 18px; font-size:10px; text-transform:uppercase; letter-spacing:1px; color:var(--muted); border-bottom:1.5px solid var(--border); font-weight:600; white-space:nowrap; background:#FFF2F7; }
td { padding:11px 18px; border-bottom:1px solid #FFE0EF; vertical-align:middle; color:var(--text2); }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#FFF0F5; }

.prod-img { width:48px; height:48px; object-fit:cover; border-radius:10px; border:1.5px solid var(--border); background:var(--surface2); }
.no-img { width:48px; height:48px; border-radius:10px; background:var(--surface2); border:1.5px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:20px; color:var(--muted); }
.prod-name { font-size:13px; font-weight:600; color:var(--text); }
.prod-cat  { font-size:11px; color:var(--muted); margin-top:2px; }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; }
.badge-green  { background:#E0F2F1; color:#00897B; }
.badge-yellow { background:#FFF8E1; color:#F9A825; }
.badge-red    { background:#FFEBEE; color:#E53935; }
.badge-purple { background:#FFE0EF; color:#E8719A; }
.badge-pink   { background:#FCE4EC; color:#E91E63; }

.btn-action { display:inline-flex; align-items:center; gap:5px; padding:5px 12px; border-radius:7px; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; font-size:12px; font-weight:500; transition:all .2s; }
.btn-edit-a { background:#FFE0EF; color:#E8719A; border:1.5px solid #F4A7C3 !important; }
.btn-edit-a:hover { background:#FFD0E5; }
.btn-del-a  { background:#FFEBEE; color:#E53935; border:1.5px solid #FFCDD2 !important; }
.btn-del-a:hover  { background:#FFCDD2; }
.btn-grp { display:flex; gap:6px; }

.empty { text-align:center; padding:50px 20px; color:var(--muted); font-size:13px; }
.empty i { font-size:2.5rem; display:block; margin-bottom:10px; color:var(--pink); }
.empty p { margin-bottom:12px; }

/* MODAL */
.modal-overlay {
    display:none; position:fixed; inset:0;
    background:rgba(0,0,0,.55);
    backdrop-filter:blur(4px);
    z-index:200; align-items:center; justify-content:center; padding:20px;
}
.modal-overlay.show { display:flex; }
.modal {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:18px; width:100%; max-width:660px; max-height:90vh; overflow-y:auto;
    box-shadow:0 25px 60px rgba(212,84,127,.2);
    animation:modalIn .25s ease;
}
@keyframes modalIn { from{opacity:0;transform:translateY(20px) scale(.97);} to{opacity:1;transform:none;} }
.modal::-webkit-scrollbar { width:4px; }
.modal::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

.modal-header {
    padding:20px 24px 16px; border-bottom:1.5px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    position:sticky; top:0; background:var(--white); z-index:1;
    background:linear-gradient(to right,#FFF0F5,#fff);
}
.modal-header-left { display:flex; align-items:center; gap:10px; }
.modal-icon { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#FFE0EF,#F4A7C3); display:flex; align-items:center; justify-content:center; font-size:16px; color:var(--accent); }
.modal-title { font-family:'Playfair Display',serif; font-size:17px; font-weight:700; color:var(--text); }
.modal-sub   { font-size:11px; color:var(--muted); margin-top:1px; }
.btn-close-modal { width:32px; height:32px; border-radius:8px; background:var(--surface2); border:1.5px solid var(--border); color:var(--muted); cursor:pointer; font-size:16px; display:flex; align-items:center; justify-content:center; transition:all .2s; }
.btn-close-modal:hover { color:var(--red); border-color:var(--red); }

.modal-body { padding:20px 24px 24px; }

/* FORM */
.form-section { margin-bottom:20px; }
.form-section-title { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:var(--muted); font-weight:600; margin-bottom:12px; display:flex; align-items:center; gap:6px; }
.form-section-title::after { content:''; flex:1; height:1px; background:var(--border); }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.form-full { grid-column:1/-1; }
.form-group { display:flex; flex-direction:column; gap:5px; }
.form-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.07em; color:var(--muted); }
.form-label span.req { color:var(--pink2); margin-left:2px; }
.form-control { background:var(--surface2); border:1.5px solid var(--border); border-radius:8px; color:var(--text); padding:9px 12px; font-family:'DM Sans',sans-serif; font-size:13px; outline:none; width:100%; transition:border-color .2s, box-shadow .2s; }
.form-control:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(212,84,127,.1); }
.form-control::placeholder { color:var(--muted); }
textarea.form-control { resize:vertical; min-height:80px; }
select.form-control option { background:var(--surface2); }

/* UPLOAD FOTO */
.upload-area { display:block; width:100%; border:2px dashed var(--border); border-radius:10px; padding:20px 16px; text-align:center; cursor:pointer; transition:all .2s; position:relative; overflow:hidden; background:#FFF2F7; }
.upload-area:hover { border-color:var(--accent); background:#FFE0EF; }
.upload-area input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; width:100%; height:100%; }
.upload-area .ua-icon { font-size:28px; color:var(--accent); opacity:.7; }
.upload-area .ua-text { font-size:13px; color:var(--accent); font-weight:600; margin-top:6px; }
.upload-area .ua-hint { font-size:11px; color:var(--muted); margin-top:2px; }
#preview-utama { display:none; max-height:110px; max-width:100%; object-fit:cover; border-radius:8px; border:1.5px solid var(--border); margin:10px auto 0; }
#preview-utama.hidden { display:none; }

/* MODAL FOOTER */
.modal-footer { padding:16px 24px; border-top:1.5px solid var(--border); display:flex; justify-content:flex-end; gap:10px; position:sticky; bottom:0; background:linear-gradient(to right,#FFF0F5,#fff); }
.btn-cancel-m { padding:9px 20px; border-radius:8px; background:var(--surface2); border:1.5px solid var(--border); color:var(--muted); font-size:13px; cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .2s; }
.btn-cancel-m:hover { color:var(--text); border-color:var(--accent); }
.btn-save-m { padding:9px 22px; border-radius:8px; background:linear-gradient(135deg,#F4A7C3,#E8719A); color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; display:flex; align-items:center; gap:7px; box-shadow:0 4px 14px rgba(212,84,127,.3); }
.btn-save-m:hover { opacity:.87; }

/* CONFIRM DELETE MODAL */
.confirm-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.55); backdrop-filter:blur(4px); z-index:300; align-items:center; justify-content:center; }
.confirm-overlay.show { display:flex; }
.confirm-box { background:var(--white); border:1.5px solid var(--border); border-radius:16px; padding:28px; max-width:380px; width:90%; box-shadow:0 20px 50px rgba(212,84,127,.2); animation:modalIn .2s ease; }
.confirm-icon { width:52px; height:52px; border-radius:14px; background:#FFEBEE; border:1.5px solid #FFCDD2; display:flex; align-items:center; justify-content:center; font-size:24px; color:var(--red); margin:0 auto 16px; }
.confirm-box h4 { font-size:16px; font-weight:700; color:var(--text); text-align:center; margin-bottom:10px; }
.confirm-box p  { font-size:13px; color:var(--muted); text-align:center; margin-bottom:22px; line-height:1.5; }
.confirm-btns { display:flex; gap:10px; }
.confirm-btns .btn-c { flex:1; padding:10px; border-radius:8px; background:var(--surface2); border:1.5px solid var(--border); color:var(--muted); font-size:13px; cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .2s; }
.confirm-btns .btn-c:hover { color:var(--text); border-color:var(--accent); }
.confirm-btns .btn-d { flex:1; padding:10px; border-radius:8px; background:var(--red); color:#fff; font-size:13px; font-weight:600; border:none; cursor:pointer; font-family:'DM Sans',sans-serif; transition:opacity .2s; }
.confirm-btns .btn-d:hover { opacity:.85; }
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>
<!-- MAIN -->
<div class="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-title">Manajemen Produk</div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
            <button class="btn-tambah" onclick="openModal()">
                <span class="plus-icon"><i class="bi bi-plus"></i></span>
                Tambah Produk
            </button>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">

        <!-- Alert -->
        <?php if ($message): ?>
        <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'error' ?>">
            <i class="bi bi-<?= $message_type === 'success' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
            <?= escape($message) ?>
        </div>
        <?php endif; ?>
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert alert-del"><i class="bi bi-trash-fill"></i> Produk berhasil dihapus.</div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card all" onclick="window.location='produk.php'">
                <div class="stat-icon-wrap"><i class="bi bi-handbag"></i></div>
                <div class="stat-info"><div class="val"><?= $total_semua ?></div><div class="lbl">Semua Produk</div></div>
            </div>
            <div class="stat-card aktif" onclick="window.location='produk.php?kategori='">
                <div class="stat-icon-wrap"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info"><div class="val"><?= $total_produk ?></div><div class="lbl">Aktif</div></div>
            </div>
            <div class="stat-card terjual">
                <div class="stat-icon-wrap"><i class="bi bi-bag-check"></i></div>
                <div class="stat-info"><div class="val"><?= $total_terjual ?></div><div class="lbl">Terjual</div></div>
            </div>
            <div class="stat-card ditahan">
                <div class="stat-icon-wrap"><i class="bi bi-pause-circle"></i></div>
                <div class="stat-info"><div class="val"><?= $total_ditahan ?></div><div class="lbl">Ditahan</div></div>
            </div>
        </div>

        <!-- Produk Table Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-list-ul" style="color:var(--pink2)"></i> Daftar Produk</h3>
                <span class="card-count"><?= $total_semua ?> produk</span>
            </div>

            <!-- Filter -->
            <form method="GET" class="filter-bar">
                <input type="text" name="q" class="filter-input" placeholder="🔍  Cari nama barang..." value="<?= escape($_GET['q'] ?? '') ?>">
                <select name="kategori" class="filter-select" onchange="this.form.submit()">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($kategori_list as $k): ?>
                    <option value="<?= $k ?>" <?= ($_GET['kategori'] ?? '') === $k ? 'selected' : '' ?>><?= $k ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn-sm btn-primary-sm"><i class="bi bi-search"></i> Cari</button>
                <?php if (!empty($_GET['q']) || !empty($_GET['kategori'])): ?>
                <a href="produk.php" class="btn-sm btn-ghost-sm"><i class="bi bi-x"></i> Reset</a>
                <?php endif; ?>
            </form>

            <!-- Table -->
            <div class="table-wrap">
                <?php if ($produk_list && $produk_list->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Produk</th>
                            <th>Kondisi</th>
                            <th>Ukuran</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $produk_list->fetch_assoc()): ?>
                        <?php $fp = "../uploads/produk/" . $row['foto_utama']; ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['foto_utama']) && file_exists($fp)): ?>
                                    <img class="prod-img" src="../uploads/produk/<?= escape($row['foto_utama']) ?>">
                                <?php else: ?>
                                    <div class="no-img"><i class="bi bi-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="prod-name"><?= escape($row['nama_barang']) ?></div>
                                <div class="prod-cat"><?= escape($row['kategori']) ?></div>
                            </td>
                            <td>
                                <?php
                                $kmap = ['Mulus'=>'badge-green','Bekas Pakai'=>'badge-yellow','Perlu Perbaikan'=>'badge-red'];
                                $k = $row['kondisi'];
                                ?>
                                <span class="badge <?= $kmap[$k] ?? 'badge-purple' ?>"><?= escape($k) ?></span>
                            </td>
                            <td style="color:var(--muted);font-size:12px;"><?= $row['ukuran'] ?: '—' ?></td>
                            <td style="font-weight:700;color:var(--accent);white-space:nowrap;"><?= formatRupiah($row['harga']) ?></td>
                            <td>
                                <?php
                                $smap = ['aktif'=>'badge-green','terjual'=>'badge-pink','ditahan'=>'badge-yellow'];
                                $s = $row['status'];
                                ?>
                                <span class="badge <?= $smap[$s] ?? 'badge-purple' ?>"><?= ucfirst($s) ?></span>
                            </td>
                            <td>
                                <div class="btn-grp">
                                    <button class="btn-action btn-edit-a"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode($row), ENT_QUOTES) ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn-action btn-del-a"
                                        onclick="openConfirm(<?= $row['id'] ?>, '<?= addslashes(escape($row['nama_barang'])) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty">
                    <i class="bi bi-handbag"></i>
                    <p>Belum ada produk<?= $search ? " dengan kata \"" . escape($search) . "\"" : '' ?>.</p>
                    <button class="btn-tambah" onclick="openModal()" style="margin:0 auto;">
                        <span class="plus-icon"><i class="bi bi-plus"></i></span>
                        Tambah Produk Pertama
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /content -->
</div><!-- /main -->


<!-- MODAL TAMBAH / EDIT PRODUK -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-header-left">
                <div class="modal-icon"><i class="bi bi-handbag"></i></div>
                <div>
                    <div class="modal-title" id="modalTitle">Tambah Produk</div>
                    <div class="modal-sub" id="modalSub">Isi detail produk baru</div>
                </div>
            </div>
            <button class="btn-close-modal" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formProduk">
            <input type="hidden" name="id_edit" id="id_edit" value="0">

            <div class="modal-body">

                <!-- FOTO -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-image" style="color:var(--pink2)"></i> Foto Produk</div>
                    <label class="upload-area" for="foto_utama_input">
                        <input type="file" id="foto_utama_input" name="foto_utama" accept="image/*" onchange="previewUtama(this)">
                        <div class="ua-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                        <div class="ua-text">Klik untuk upload foto</div>
                        <div class="ua-hint">JPG, PNG, WEBP — maks. 5MB</div>
                    </label>
                    <img id="preview-utama" class="hidden" src="" alt="Preview">
                    <div id="current-foto" style="display:none;margin-top:8px;">
                        <img id="current-foto-img" src="" style="max-height:90px;border-radius:8px;border:1.5px solid var(--border);">
                        <div style="font-size:11px;color:var(--muted);margin-top:4px;">Foto saat ini — upload baru untuk mengganti</div>
                    </div>
                </div>

                <!-- INFO DASAR -->
                <div class="form-section">
                    <div class="form-section-title"><i class="bi bi-info-circle" style="color:var(--accent)"></i> Informasi Dasar</div>
                    <div class="form-grid">
                        <div class="form-group form-full">
                            <label class="form-label">Nama Barang <span class="req">*</span></label>
                            <input type="text" name="nama_barang" id="f_nama_barang" class="form-control" placeholder="cth. Kemeja Floral Vintage" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kategori <span class="req">*</span></label>
                            <select name="kategori" id="f_kategori" class="form-control" required>
                                <?php foreach ($kategori_list as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Kondisi <span class="req">*</span></label>
                            <select name="kondisi" id="f_kondisi" class="form-control" required>
                                <?php foreach ($kondisi_list as $k): ?>
                                <option value="<?= $k ?>"><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group form-full">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="deskripsi" id="f_deskripsi" class="form-control" placeholder="Ceritakan detail, kondisi, ukuran, warna, dll..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- HARGA & DETAIL -->
                <div class="form-section" style="margin-bottom:0;">
                    <div class="form-section-title"><i class="bi bi-tag" style="color:var(--green)"></i> Harga & Detail</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Harga (Rp) <span class="req">*</span></label>
                            <input type="number" name="harga" id="f_harga" class="form-control" placeholder="50.000" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Ukuran</label>
                            <select name="ukuran" id="f_ukuran" class="form-control">
                                <option value="">Pilih Ukuran</option>
                                <?php foreach ($ukuran_list as $u): ?>
                                <option value="<?= $u ?>"><?= $u ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

            </div><!-- /modal-body -->

            <div class="modal-footer">
                <button type="button" class="btn-cancel-m" onclick="closeModal()"><i class="bi bi-x"></i> Batal</button>
                <button type="submit" class="btn-save-m"><i class="bi bi-floppy"></i> <span id="btnSaveLabel">Simpan Produk</span></button>
            </div>
        </form>
    </div>
</div>


<!-- CONFIRM DELETE MODAL -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon"><i class="bi bi-trash3"></i></div>
        <h4>Hapus Produk?</h4>
        <p id="confirmMsg">Produk ini akan dihapus permanen dan tidak bisa dikembalikan.</p>
        <div class="confirm-btns">
            <button class="btn-c" onclick="closeConfirm()">Batal</button>
            <a id="confirmLink" href="#" class="btn-d"><i class="bi bi-trash"></i> Hapus</a>
        </div>
    </div>
</div>


<script>
function openModal() {
    document.getElementById('id_edit').value = '0';
    document.getElementById('formProduk').reset();
    document.getElementById('modalTitle').textContent = 'Tambah Produk';
    document.getElementById('modalSub').textContent = 'Isi detail produk baru';
    document.getElementById('btnSaveLabel').textContent = 'Simpan Produk';
    document.getElementById('preview-utama').classList.add('hidden');
    document.getElementById('preview-utama').style.display = 'none';
    document.getElementById('current-foto').style.display = 'none';
    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function openEditModal(data) {
    document.getElementById('id_edit').value = data.id;
    document.getElementById('f_nama_barang').value = data.nama_barang || '';
    document.getElementById('f_kategori').value    = data.kategori   || '';
    document.getElementById('f_kondisi').value     = data.kondisi    || '';
    document.getElementById('f_deskripsi').value   = data.deskripsi  || '';
    document.getElementById('f_harga').value       = data.harga      || '';
    document.getElementById('f_ukuran').value      = data.ukuran     || '';

    const currentFoto = document.getElementById('current-foto');
    const currentImg  = document.getElementById('current-foto-img');
    if (data.foto_utama) {
        currentImg.src = '../uploads/produk/' + data.foto_utama;
        currentFoto.style.display = 'block';
    } else {
        currentFoto.style.display = 'none';
    }
    document.getElementById('preview-utama').classList.add('hidden');
    document.getElementById('preview-utama').style.display = 'none';

    document.getElementById('modalTitle').textContent = 'Edit Produk';
    document.getElementById('modalSub').textContent = 'Perbarui data produk';
    document.getElementById('btnSaveLabel').textContent = 'Perbarui Produk';

    document.getElementById('modalOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

function previewUtama(input) {
    const img = document.getElementById('preview-utama');
    if (input.files && input.files[0]) {
        const r = new FileReader();
        r.onload = e => {
            img.src = e.target.result;
            img.classList.remove('hidden');
            img.style.display = 'block';
            document.getElementById('current-foto').style.display = 'none';
        };
        r.readAsDataURL(input.files[0]);
    }
}

function openConfirm(id, nama) {
    document.getElementById('confirmMsg').textContent = 'Produk "' + nama + '" akan dihapus permanen.';
    document.getElementById('confirmLink').href = 'produk.php?delete=' + id;
    document.getElementById('confirmOverlay').classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('show');
    document.body.style.overflow = '';
}

document.getElementById('modalOverlay').addEventListener('click', function(e){
    if (e.target === this) closeModal();
});
document.getElementById('confirmOverlay').addEventListener('click', function(e){
    if (e.target === this) closeConfirm();
});

<?php if ($edit): ?>
window.addEventListener('DOMContentLoaded', function(){
    openEditModal(<?= $edit_json ?>);
});
<?php endif; ?>

setTimeout(() => {
    document.querySelectorAll('.alert').forEach(el => {
        el.style.transition = 'opacity .4s';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    });
}, 4000);
</script>
</body>
</html>