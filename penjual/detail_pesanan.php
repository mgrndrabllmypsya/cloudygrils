<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: pesanan.php"); exit; }

// ── BINDERBYTE API KEY ──────────────────────────────────────────────────────
define('BINDERBYTE_KEY', '490283e20418abefb57d61aac39b1d4f7753f97d3f7dd3c3aba9c9c98bdcd7a5');

// ── FUNGSI RESOLVE NAMA WILAYAH dari BinderByte ─────────────────────────────
function getWilayahName($type, $id, $parent_id = null) {
    if (!$id) return '-';

    $key = BINDERBYTE_KEY;
    $url = match($type) {
        'provinsi'  => "https://api.binderbyte.com/wilayah/provinsi?api_key=$key",
        'kabupaten' => "https://api.binderbyte.com/wilayah/kabupaten?api_key=$key&id_provinsi=" . urlencode($parent_id),
        'kecamatan' => "https://api.binderbyte.com/wilayah/kecamatan?api_key=$key&id_kabupaten=" . urlencode($parent_id),
        default     => null
    };

    if (!$url) return $id;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) return $id; // fallback ke ID jika koneksi gagal

    $json = json_decode($response, true);
    if (!isset($json['value']) || !is_array($json['value'])) return $id;

    foreach ($json['value'] as $item) {
        if ((string)$item['id'] === (string)$id) {
            return $item['name'];
        }
    }

    return $id; // fallback jika tidak ditemukan
}

// Mapping nama kurir ke kode BinderByte
$kurir_map = [
    'jne'      => 'jne',
    'jnt'      => 'j&t',
    'j&t'      => 'j&t',
    'sicepat'  => 'sicepat',
    'anteraja' => 'anteraja',
    'pos'      => 'pos',
    'tiki'     => 'tiki',
    'ninja'    => 'ninja',
    'lion'     => 'lion',
    'sap'      => 'sap',
];

// ── HANDLE POST ACTIONS ─────────────────────────────────────────────────────

// 1. Konfirmasi transfer → status pesanan jadi 'dikonfirmasi'
if (isset($_POST['aksi']) && $_POST['aksi'] === 'konfirmasi_transfer') {
    mysqli_query($conn, "UPDATE pesanan SET
        status_transfer='dikonfirmasi',
        status='dikonfirmasi',
        dikonfirmasi_at=NOW()
        WHERE id=$id");
    header("Location: pesanan.php?msg=konfirmasi"); exit;
}

// 2. Tolak transfer
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tolak_transfer') {
    $catatan = mysqli_real_escape_string($conn, $_POST['catatan_tolak'] ?? '');
    mysqli_query($conn, "UPDATE pesanan SET
        status_transfer='ditolak',
        catatan_transfer='$catatan',
        status='menunggu'
        WHERE id=$id");

mysqli_query($conn, "UPDATE produk SET status='aktif'
        WHERE id = (SELECT produk_id FROM pesanan WHERE id=$id)");
    

    header("Location: detail_pesanan.php?id=$id&msg=tolak"); exit;
}

// 3. Simpan nomor resi → status jadi 'dikirim'
if (isset($_POST['aksi']) && $_POST['aksi'] === 'simpan_resi') {
    $no_resi = mysqli_real_escape_string($conn, trim($_POST['no_resi'] ?? ''));
    $kurir   = mysqli_real_escape_string($conn, trim($_POST['kurir'] ?? ''));
    if ($no_resi) {
        mysqli_query($conn, "UPDATE pesanan SET
            no_resi='$no_resi',
            kurir='$kurir',
            status='dikirim'
            WHERE id=$id");
    }
    header("Location: detail_pesanan.php?id=$id&msg=resi"); exit;
}

// 4. Update status manual
if (isset($_POST['aksi']) && $_POST['aksi'] === 'update_status') {
    $status_baru = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
    mysqli_query($conn, "UPDATE pesanan SET status='$status_baru' WHERE id=$id");
    header("Location: detail_pesanan.php?id=$id&msg=status"); exit;
}

// ── 5. TANDAI SELESAI oleh Admin ────────────────────────────────────────────
if (isset($_POST['aksi']) && $_POST['aksi'] === 'tandai_selesai_admin') {
    mysqli_query($conn, "UPDATE pesanan SET
        status='selesai',
        selesai_at=NOW(),
        diselesaikan_oleh='admin'
        WHERE id=$id AND status='dikirim'");
    header("Location: detail_pesanan.php?id=$id&msg=selesai"); exit;
}

// ── FETCH DATA ──────────────────────────────────────────────────────────────
$row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT ps.*,
           pb.nama AS nama_pembeli, pb.email AS email_pembeli, pb.no_hp AS hp_pembeli,
           pr.nama_barang AS nama_produk, pr.foto_utama AS gambar_produk, pr.harga AS harga_normal
    FROM pesanan ps
    JOIN pembeli pb ON pb.id = ps.pembeli_id
    LEFT JOIN produk pr ON pr.id = ps.produk_id
    WHERE ps.id = $id
    LIMIT 1
"));

if (!$row) { header("Location: pesanan.php"); exit; }

// ── RESOLVE NAMA WILAYAH via BinderByte ────────────────────────────────────
// Hanya resolve jika nilainya tampak seperti ID numerik (bukan sudah berupa nama)
function isWilayahId($val) {
    return $val && preg_match('/^\d[\d\.]*$/', trim($val));
}

$nama_provinsi  = '-';
$nama_kota      = '-';
$nama_kecamatan = '-';

if (!empty($row['provinsi'])) {
    $nama_provinsi = isWilayahId($row['provinsi'])
        ? getWilayahName('provinsi', $row['provinsi'])
        : $row['provinsi'];
}

if (!empty($row['kota_tujuan'])) {
    $nama_kota = isWilayahId($row['kota_tujuan'])
        ? getWilayahName('kabupaten', $row['kota_tujuan'], $row['provinsi'])
        : $row['kota_tujuan'];
}

if (!empty($row['kecamatan'])) {
    $nama_kecamatan = isWilayahId($row['kecamatan'])
        ? getWilayahName('kecamatan', $row['kecamatan'], $row['kota_tujuan'])
        : $row['kecamatan'];
}

// ── VARIABEL HALAMAN ────────────────────────────────────────────────────────
$admin_nama  = $_SESSION['admin_nama'] ?? 'Admin';
$status      = $row['status'];
$st_transfer = $row['status_transfer'];
$is_cod      = ($row['metode'] === 'cod');

$status_label = match($status) {
    'menunggu'     => 'Menunggu',
    'dikonfirmasi' => 'Dikonfirmasi',
    'diproses'     => 'Diproses',
    'dikirim'      => 'Dikirim',
    'selesai'      => 'Selesai',
    'dibatalkan'   => 'Dibatalkan',
    default        => ucfirst($status)
};

// ── STATUS LIST sesuai metode pembayaran ───────────────────────────────────
if ($is_cod) {
    $all_status = ['menunggu','diproses','dikirim','selesai','dibatalkan'];
} else {
    $all_status = ['menunggu','dikonfirmasi','diproses','dikirim','selesai','dibatalkan'];
}

// ── TRACKING RESI via BinderByte (hanya non-COD) ───────────────────────────
$tracking_data  = null;
$tracking_error = null;
if (!$is_cod && $row['no_resi'] && $row['kurir'] && in_array($status, ['dikirim','selesai'])) {
    $kurir_kode = strtolower($row['kurir']);
    $kurir_api  = $kurir_map[$kurir_kode] ?? $kurir_kode;
    $api_url    = "https://api.binderbyte.com/v1/track?api_key=" . BINDERBYTE_KEY
                  . "&courier=" . urlencode($kurir_api)
                  . "&awb=" . urlencode($row['no_resi']);
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $json = json_decode($response, true);
        if (isset($json['status']) && $json['status'] == 200) {
            $tracking_data = $json['data'];
        } else {
            $tracking_error = $json['message'] ?? 'Gagal mengambil data tracking.';
        }
    } else {
        $tracking_error = 'Tidak dapat terhubung ke API BinderByte.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Detail Pesanan <?= escape($row['kode_pesanan']) ?> — Cloudy Girls Admin</title>
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

/* SIDEBAR */
.sidebar{width:240px;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:50;}
.sidebar-logo{padding:24px 24px 20px;border-bottom:1px solid var(--border);}
.sidebar-logo .logo{font-family:'Playfair Display',serif;font-size:20px;font-weight:900;color:var(--white);}
.sidebar-logo .logo span{color:var(--accent);}
.sidebar-logo small{display:block;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:var(--muted);margin-top:2px;}
.sidebar-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:2px;}
.nav-item{display:flex;align-items:center;gap:12px;padding:10px 14px;border-radius:10px;font-size:13px;font-weight:500;color:var(--muted);transition:all .2s;}
.nav-item:hover{background:var(--surface2);color:var(--text);}
.nav-item.active{background:linear-gradient(135deg,rgba(124,58,237,.25),rgba(212,84,127,.15));color:var(--accent);}
.nav-item i{font-size:16px;width:20px;}
.nav-section{font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:var(--muted);padding:14px 14px 6px;font-weight:600;}
.sidebar-footer{padding:16px 12px;border-top:1px solid var(--border);}
.admin-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:var(--surface2);border-radius:10px;margin-bottom:10px;}
.admin-avatar{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--pink2));display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;}
.admin-info .name{font-size:13px;font-weight:600;color:var(--text);}
.admin-info .role{font-size:10px;color:var(--muted);}
.btn-logout{display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;font-size:12px;color:var(--red);transition:background .2s;width:100%;}
.btn-logout:hover{background:rgba(248,113,113,.1);}

/* MAIN */
.main{margin-left:240px;flex:1;display:flex;flex-direction:column;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 32px;height:64px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:40;}
.topbar-left{display:flex;align-items:center;gap:12px;}
.topbar-title{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;}
.btn-back{display:flex;align-items:center;gap:6px;padding:7px 14px;border-radius:8px;background:var(--surface2);border:1px solid var(--border);font-size:12px;color:var(--muted);transition:all .2s;}
.btn-back:hover{border-color:var(--accent);color:var(--accent);}
.content{padding:28px 32px;flex:1;}

/* LAYOUT 2 COL */
.detail-grid{display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;}

/* CARD */
.card{background:var(--surface);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:20px;}
.card-head{padding:14px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px;}
.card-head h3{font-size:13px;font-weight:700;color:var(--white);}
.card-head .icon{width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0;}
.card-body{padding:20px;}

/* INFO ROW */
.info-row{display:flex;justify-content:space-between;align-items:flex-start;padding:8px 0;border-bottom:1px solid rgba(46,43,61,.4);font-size:13px;gap:12px;}
.info-row:last-child{border-bottom:none;}
.info-label{color:var(--muted);font-size:12px;flex-shrink:0;}
.info-val{color:var(--white);font-weight:500;text-align:right;}

/* BADGE */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-menunggu{background:rgba(167,139,250,.15);color:var(--accent);}
.badge-dikonfirmasi{background:rgba(251,191,36,.15);color:var(--yellow);}
.badge-diproses{background:rgba(251,146,60,.15);color:var(--orange);}
.badge-dikirim{background:rgba(52,211,153,.15);color:var(--green);}
.badge-selesai{background:rgba(52,211,153,.2);color:var(--green);}
.badge-dibatalkan{background:rgba(248,113,113,.15);color:var(--red);}
.badge-transfer-menunggu{background:rgba(251,191,36,.15);color:var(--yellow);}
.badge-transfer-dikonfirmasi{background:rgba(52,211,153,.2);color:var(--green);}
.badge-transfer-ditolak{background:rgba(248,113,113,.15);color:var(--red);}

/* PRODUK CARD */
.produk-item{display:flex;gap:14px;align-items:center;padding:14px 0;}
.produk-img{width:64px;height:64px;border-radius:10px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;}
.produk-img-placeholder{width:64px;height:64px;border-radius:10px;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:22px;color:var(--muted);flex-shrink:0;}
.produk-nama{font-weight:600;color:var(--white);font-size:14px;margin-bottom:4px;}
.produk-harga{font-size:13px;color:var(--accent);font-weight:600;}

/* BUKTI TRANSFER */
.bukti-wrap{margin-top:12px;}
.bukti-wrap img{width:100%;max-height:300px;object-fit:contain;border-radius:10px;border:1px solid var(--border);background:var(--surface2);cursor:zoom-in;}
.no-bukti{text-align:center;padding:24px;color:var(--muted);font-size:12px;background:var(--surface2);border-radius:10px;border:1px dashed var(--border);}

/* ACTION BUTTONS */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:9px;font-size:13px;font-weight:600;border:none;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .2s;}
.btn-green{background:rgba(52,211,153,.15);color:var(--green);border:1px solid rgba(52,211,153,.3);}
.btn-green:hover{background:rgba(52,211,153,.25);}
.btn-red{background:rgba(248,113,113,.15);color:var(--red);border:1px solid rgba(248,113,113,.3);}
.btn-red:hover{background:rgba(248,113,113,.25);}
.btn-accent{background:linear-gradient(135deg,var(--accent2),var(--pink2));color:#fff;}
.btn-accent:hover{opacity:.85;}
.btn-secondary{background:var(--surface2);color:var(--muted);border:1px solid var(--border);}
.btn-secondary:hover{border-color:var(--accent);color:var(--accent);}
.btn-group{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;}

/* FORM INPUT */
.form-group{margin-bottom:12px;}
.form-group label{display:block;font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:6px;}
.form-input,.form-select,.form-textarea{width:100%;background:var(--surface2);border:1px solid var(--border);border-radius:8px;color:var(--text);font-size:13px;padding:9px 12px;outline:none;font-family:'DM Sans',sans-serif;transition:border-color .2s;}
.form-input:focus,.form-select:focus,.form-textarea:focus{border-color:var(--accent);}
.form-textarea{resize:vertical;min-height:70px;}

/* ALERT */
.alert-msg{padding:11px 16px;border-radius:10px;font-size:13px;display:flex;align-items:center;gap:8px;margin-bottom:16px;}
.alert-success{background:rgba(52,211,153,.12);color:var(--green);border:1px solid rgba(52,211,153,.3);}
.alert-danger{background:rgba(248,113,113,.12);color:var(--red);border:1px solid rgba(248,113,113,.3);}
.alert-info{background:rgba(167,139,250,.1);color:var(--accent);border:1px solid rgba(167,139,250,.25);}

/* DIVIDER */
hr{border:none;border-top:1px solid var(--border);margin:16px 0;}

/* COD INFO BOX */
.cod-box{background:rgba(52,211,153,.07);border:1px solid rgba(52,211,153,.25);border-radius:10px;padding:14px;margin-bottom:14px;}
.cod-box-title{font-size:13px;color:var(--green);font-weight:700;margin-bottom:8px;}
.cod-box-row{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted);margin-bottom:6px;line-height:1.5;}
.cod-box-row:last-child{margin-bottom:0;}
.cod-box-row strong{color:var(--white);}
.cod-box-row i{color:var(--green);font-size:14px;flex-shrink:0;}

/* TIMELINE ALUR PESANAN */
.tl-item{display:flex;gap:14px;padding:10px 0;position:relative;}
.tl-item:not(:last-child)::after{content:'';position:absolute;left:14px;top:34px;bottom:0;width:1px;background:var(--border);}
.tl-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0;margin-top:2px;}
.tl-dot.done{background:rgba(52,211,153,.2);color:var(--green);}
.tl-dot.active{background:rgba(167,139,250,.2);color:var(--accent);}
.tl-dot.pending{background:var(--surface2);color:var(--muted);}
.tl-text .title{font-size:13px;font-weight:600;}
.tl-text .sub{font-size:11px;color:var(--muted);margin-top:2px;}

/* TRACKING HISTORY */
.track-item{display:flex;gap:14px;padding:10px 0;position:relative;}
.track-item:not(:last-child)::after{content:'';position:absolute;left:11px;top:28px;bottom:0;width:1px;background:var(--border);}
.track-dot{width:22px;height:22px;border-radius:50%;flex-shrink:0;margin-top:2px;display:flex;align-items:center;justify-content:center;font-size:10px;}
.track-dot.first{background:rgba(52,211,153,.25);border:2px solid var(--green);color:var(--green);}
.track-dot.rest{background:var(--surface2);border:1px solid var(--border);color:var(--muted);}
.track-text .track-desc{font-size:12px;color:var(--text);line-height:1.5;}
.track-text .track-meta{font-size:11px;color:var(--muted);margin-top:3px;}
.track-status-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:6px;font-size:10px;font-weight:700;margin-bottom:10px;}
.track-status-delivered{background:rgba(52,211,153,.15);color:var(--green);}
.track-status-transit{background:rgba(251,191,36,.15);color:var(--yellow);}
.track-status-pending{background:rgba(167,139,250,.15);color:var(--accent);}

/* MODAL TOLAK */
.overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.75);z-index:200;align-items:center;justify-content:center;}
.overlay.show{display:flex;}
.modal-box{background:var(--surface);border:1px solid var(--border);border-radius:14px;padding:28px;width:420px;max-width:92%;}
.modal-box h4{font-size:15px;font-weight:700;color:var(--white);margin-bottom:6px;}
.modal-box p{font-size:13px;color:var(--muted);margin-bottom:16px;}

/* LIGHTBOX */
.lightbox{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:300;align-items:center;justify-content:center;cursor:zoom-out;}
.lightbox.show{display:flex;}
.lightbox img{max-width:90vw;max-height:90vh;border-radius:10px;object-fit:contain;}

/* TANDAI SELESAI BOX */
.selesai-box{background:rgba(52,211,153,.07);border:1.5px solid rgba(52,211,153,.4);border-radius:10px;padding:16px;margin-bottom:4px;}
.selesai-box-title{font-size:13px;color:var(--green);font-weight:700;margin-bottom:6px;display:flex;align-items:center;gap:6px;}
.selesai-box-desc{font-size:12px;color:var(--muted);margin-bottom:14px;line-height:1.6;}

/* WILAYAH LOADING */
.wilayah-loading{display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--muted);font-style:italic;}
.wilayah-loading i{animation:spin .8s linear infinite;}
@keyframes spin{to{transform:rotate(360deg);}}
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
        <div class="topbar-left">
            <a href="pesanan.php" class="btn-back"><i class="bi bi-arrow-left"></i> Kembali</a>
            <div class="topbar-title">Detail Pesanan</div>
        </div>
        <span class="badge badge-<?= $status ?>" style="font-size:12px;"><?= $status_label ?></span>
    </div>

    <div class="content">

        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'tolak'): ?>
            <div class="alert-msg alert-danger"><i class="bi bi-x-circle-fill"></i> Transfer ditolak. Pembeli perlu upload ulang bukti transfer.</div>
            <?php elseif ($_GET['msg'] === 'resi'): ?>
            <div class="alert-msg alert-success"><i class="bi bi-truck"></i> Nomor resi berhasil disimpan. Status diperbarui ke <strong>Dikirim</strong>.</div>
            <?php elseif ($_GET['msg'] === 'status'): ?>
            <div class="alert-msg alert-success"><i class="bi bi-check-circle-fill"></i> Status berhasil diperbarui.</div>
            <?php elseif ($_GET['msg'] === 'selesai'): ?>
            <div class="alert-msg alert-success"><i class="bi bi-check2-all"></i> Pesanan ditandai <strong>Selesai</strong> oleh admin. Pembeli kini bisa memberi ulasan.</div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- AKSI: Bukti Transfer Menunggu Konfirmasi (hanya non-COD) -->
        <?php if (!$is_cod && $st_transfer === 'menunggu' && $row['bukti_transfer']): ?>
        <div class="card" style="border-color:rgba(251,191,36,.4);">
            <div class="card-head" style="background:rgba(251,191,36,.07);">
                <div class="icon" style="background:rgba(251,191,36,.2);color:var(--yellow);"><i class="bi bi-exclamation-triangle"></i></div>
                <h3 style="color:var(--yellow);">Bukti Transfer Menunggu Konfirmasi</h3>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:16px;">
                    Pembeli sudah upload bukti transfer. Cek foto di bawah, lalu konfirmasi atau tolak.
                </p>
                <div class="bukti-wrap">
                    <img src="../uploads/bukti_transfer/<?= escape($row['bukti_transfer']) ?>"
                         alt="Bukti Transfer" onclick="openLightbox(this.src)" title="Klik untuk zoom">
                </div>
                <div style="margin-top:10px;font-size:12px;color:var(--muted);">
                    <?php if ($row['jumlah_transfer']): ?>
                    Jumlah transfer: <strong style="color:var(--white);"><?= formatRupiah($row['jumlah_transfer']) ?></strong>
                    — Total seharusnya: <strong style="color:var(--accent);"><?= formatRupiah($row['total_bayar']) ?></strong>
                    <?php if ($row['jumlah_transfer'] < $row['total_bayar']): ?>
                        <span style="color:var(--red);"> ⚠ Kurang <?= formatRupiah($row['total_bayar'] - $row['jumlah_transfer']) ?></span>
                    <?php elseif ($row['jumlah_transfer'] > $row['total_bayar']): ?>
                        <span style="color:var(--yellow);"> ⚠ Lebih <?= formatRupiah($row['jumlah_transfer'] - $row['total_bayar']) ?></span>
                    <?php else: ?>
                        <span style="color:var(--green);"> ✓ Sesuai</span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="btn-group">
                    <form method="POST">
                        <input type="hidden" name="aksi" value="konfirmasi_transfer">
                        <button type="submit" class="btn btn-green"><i class="bi bi-check-circle-fill"></i> Konfirmasi Transfer</button>
                    </form>
                    <button class="btn btn-red" onclick="document.getElementById('modalTolak').classList.add('show')">
                        <i class="bi bi-x-circle-fill"></i> Tolak Transfer
                    </button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- AKSI: Tandai Diproses (hanya non-COD setelah dikonfirmasi) -->
        <?php if (!$is_cod && $status === 'dikonfirmasi'): ?>
        <div class="card" style="border-color:rgba(251,146,60,.4);">
            <div class="card-head" style="background:rgba(251,146,60,.07);">
                <div class="icon" style="background:rgba(251,146,60,.2);color:var(--orange);"><i class="bi bi-arrow-repeat"></i></div>
                <h3 style="color:var(--orange);">Transfer Sudah Dikonfirmasi — Siapkan Barang</h3>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:14px;">
                    Klik tombol di bawah setelah barang mulai disiapkan. Setelah itu kamu bisa input nomor resi.
                </p>
                <form method="POST">
                    <input type="hidden" name="aksi" value="update_status">
                    <input type="hidden" name="status" value="diproses">
                    <button type="submit" class="btn btn-accent"><i class="bi bi-arrow-repeat"></i> Tandai Sedang Diproses</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- AKSI: COD menunggu — siapkan barang langsung -->
        <?php if ($is_cod && $status === 'menunggu'): ?>
        <div class="card" style="border-color:rgba(52,211,153,.4);">
            <div class="card-head" style="background:rgba(52,211,153,.07);">
                <div class="icon" style="background:rgba(52,211,153,.2);color:var(--green);"><i class="bi bi-cash-coin"></i></div>
                <h3 style="color:var(--green);">Pesanan COD Masuk — Siapkan Barang</h3>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--muted);margin-bottom:14px;">
                    Pesanan ini menggunakan metode <strong style="color:var(--green);">COD (🛵 Antar ke Rumah)</strong>.
                    Tidak ada konfirmasi transfer. Langsung siapkan barang dan hubungi pembeli untuk koordinasi pengantaran.
                </p>
                <form method="POST">
                    <input type="hidden" name="aksi" value="update_status">
                    <input type="hidden" name="status" value="diproses">
                    <button type="submit" class="btn btn-accent"><i class="bi bi-arrow-repeat"></i> Tandai Sedang Diproses</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- AKSI: TANDAI SELESAI oleh Admin (muncul saat status = dikirim) -->
        <?php if ($status === 'dikirim'): ?>
        <div class="card" style="border-color:rgba(52,211,153,.5);">
            <div class="card-head" style="background:rgba(52,211,153,.08);">
                <div class="icon" style="background:rgba(52,211,153,.2);color:var(--green);"><i class="bi bi-check2-all"></i></div>
                <h3 style="color:var(--green);">
                    <?= $is_cod ? 'Konfirmasi Penerimaan COD' : 'Konfirmasi Barang Diterima' ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="selesai-box">
                    <div class="selesai-box-title">
                        <i class="bi bi-info-circle-fill"></i>
                        <?= $is_cod ? 'Barang sudah diantar & uang COD diterima?' : 'Barang sudah sampai ke pembeli?' ?>
                    </div>
                    <div class="selesai-box-desc">
                        <?php if ($is_cod): ?>
                            Klik <strong style="color:var(--white);">Tandai Selesai</strong> setelah kamu mengantar barang dan menerima pembayaran COD dari pembeli.
                            Atau tunggu pembeli menekan tombol <em>"Pesanan Diterima"</em> di halaman pesanan mereka.
                        <?php else: ?>
                            Klik <strong style="color:var(--white);">Tandai Selesai</strong> jika kamu sudah memastikan barang diterima pembeli.
                            Atau tunggu pembeli menekan tombol <em>"Pesanan Diterima"</em> di halaman pesanan mereka.
                        <?php endif; ?>
                    </div>
                    <form method="POST" onsubmit="return confirm('Tandai pesanan ini sebagai Selesai?');">
                        <input type="hidden" name="aksi" value="tandai_selesai_admin">
                        <button type="submit" class="btn btn-green" style="font-size:13px;padding:10px 22px;">
                            <i class="bi bi-check2-all"></i> Tandai Selesai
                        </button>
                    </form>
                </div>
                <div style="font-size:11px;color:var(--muted);margin-top:10px;">
                    <i class="bi bi-shield-check" style="color:var(--accent);"></i>
                    Pesanan juga otomatis selesai jika pembeli mengkonfirmasi penerimaan sendiri.
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- MAIN GRID -->
        <div class="detail-grid">

            <!-- KOLOM KIRI -->
            <div>

                <!-- INFO PESANAN -->
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(167,139,250,.15);color:var(--accent);"><i class="bi bi-bag-check"></i></div>
                        <h3>Informasi Pesanan</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Kode Pesanan</span>
                            <span class="info-val" style="font-family:monospace;letter-spacing:1px;color:var(--accent);"><?= escape($row['kode_pesanan']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Tanggal Order</span>
                            <span class="info-val"><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Status</span>
                            <span class="info-val"><span class="badge badge-<?= $status ?>"><?= $status_label ?></span></span>
                        </div>
                        <?php if (!empty($row['diselesaikan_oleh']) && $status === 'selesai'): ?>
                        <div class="info-row">
                            <span class="info-label">Diselesaikan Oleh</span>
                            <span class="info-val" style="color:var(--green);">
                                <?= $row['diselesaikan_oleh'] === 'admin' ? '👤 Admin' : '🙋 Pembeli' ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($row['selesai_at']) && $status === 'selesai'): ?>
                        <div class="info-row">
                            <span class="info-label">Selesai Pada</span>
                            <span class="info-val"><?= date('d M Y, H:i', strtotime($row['selesai_at'])) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row">
                            <span class="info-label">Metode Bayar</span>
                            <span class="info-val">
                                <?php if ($is_cod): ?>
                                    <i class="bi bi-cash" style="color:var(--green);"></i> COD (🛵 Antar ke Rumah)
                                <?php else: ?>
                                    <i class="bi bi-credit-card" style="color:var(--accent);"></i>
                                    Transfer <?= strtoupper(escape($row['metode_transfer'] ?? '')) ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if (!$is_cod): ?>
                        <div class="info-row">
                            <span class="info-label">Ekspedisi</span>
                            <span class="info-val">
                                <?php if (!empty($row['kurir'])): ?>
                                    <i class="bi bi-truck" style="color:var(--green);"></i>
                                    <strong style="color:var(--white);"><?= strtoupper(escape($row['kurir'])) ?></strong>
                                <?php else: ?>
                                    <span style="color:var(--yellow);">⚠ Belum dipilih</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>

                        <?php if (!$is_cod && $st_transfer): ?>
                        <div class="info-row">
                            <span class="info-label">Status Transfer</span>
                            <span class="info-val">
                                <span class="badge badge-transfer-<?= $st_transfer ?>">
                                    <?= match($st_transfer) {
                                        'menunggu'     => '⏳ Menunggu Konfirmasi',
                                        'dikonfirmasi' => '✓ Dikonfirmasi',
                                        'ditolak'      => '✗ Ditolak',
                                        default        => $st_transfer
                                    } ?>
                                </span>
                            </span>
                        </div>
                        <?php if ($st_transfer === 'ditolak' && $row['catatan_transfer']): ?>
                        <div class="info-row">
                            <span class="info-label">Alasan Tolak</span>
                            <span class="info-val" style="color:var(--red);"><?= escape($row['catatan_transfer']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php if (!$is_cod && $row['dikonfirmasi_at']): ?>
                        <div class="info-row">
                            <span class="info-label">Dikonfirmasi Pada</span>
                            <span class="info-val"><?= date('d M Y, H:i', strtotime($row['dikonfirmasi_at'])) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (!$is_cod && $row['no_resi']): ?>
                        <div class="info-row">
                            <span class="info-label">No. Resi</span>
                            <span class="info-val" style="font-family:monospace;color:var(--green);"><?= escape($row['no_resi']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($is_cod): ?>
                            <?php
                            // Deteksi jenis COD dari kolom catatan
                            $cod_jenis_val = '';
                            if (preg_match('/Jenis COD:\s*(\w+)/i', $row['catatan'] ?? '', $m)) {
                                $cod_jenis_val = $m[1]; // antar_pembeli atau antar
                            }
                            ?>
                            <?php if ($cod_jenis_val === 'antar_pembeli' && $row['detail_alamat']): ?>
                            <?php elseif ($cod_jenis_val === 'antar'): ?>
                            <div class="info-row">
                                <span class="info-label">Jenis COD</span>
                                <span class="info-val" style="color:var(--accent);">
                                    🏪 Pembeli datang ke toko
                                </span>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($row['catatan']): ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- PRODUK -->
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(249,168,212,.15);color:var(--pink);"><i class="bi bi-handbag"></i></div>
                        <h3>Produk Dipesan</h3>
                    </div>
                    <div class="card-body">
                        <div class="produk-item">
                            <?php if ($row['gambar_produk']): ?>
                            <img src="../uploads/produk/<?= escape($row['gambar_produk']) ?>" class="produk-img" alt="<?= escape($row['nama_produk']) ?>">
                            <?php else: ?>
                            <div class="produk-img-placeholder"><i class="bi bi-handbag"></i></div>
                            <?php endif; ?>
                            <div>
                                <div class="produk-nama"><?= escape($row['nama_produk'] ?? 'Produk dihapus') ?></div>
                                <div class="produk-harga"><?= formatRupiah($row['harga_produk']) ?></div>
                                <?php if ($row['nego_id']): ?>
                                <div style="font-size:11px;color:var(--yellow);margin-top:3px;"><i class="bi bi-chat-dots"></i> Harga nego</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <hr>
                        <div class="info-row">
                            <span class="info-label">Harga Produk</span>
                            <span class="info-val"><?= formatRupiah($row['harga_produk']) ?></span>
                        </div>
                        <?php if ($row['diskon'] > 0): ?>
                        <div class="info-row">
                            <span class="info-label">Diskon</span>
                            <span class="info-val" style="color:var(--green);">- <?= formatRupiah($row['diskon']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!$is_cod && $row['ongkir'] > 0): ?>
                        <div class="info-row">
                            <span class="info-label">Ongkos Kirim</span>
                            <span class="info-val">+ <?= formatRupiah($row['ongkir']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="info-row" style="border-top:2px solid var(--border);padding-top:12px;margin-top:4px;">
                            <span class="info-label" style="font-weight:700;color:var(--text);">Total Bayar</span>
                            <span class="info-val" style="font-size:16px;color:var(--accent);"><?= formatRupiah($row['total_bayar']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- ── ALAMAT PENGIRIMAN (hanya non-COD) ── -->
                <?php if (!$is_cod && ($row['nama_penerima'] || $row['kota_tujuan'])): ?>
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(52,211,153,.15);color:var(--green);"><i class="bi bi-geo-alt"></i></div>
                        <h3>Alamat Pengiriman</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Penerima</span>
                            <span class="info-val"><?= escape($row['nama_penerima'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">No. HP</span>
                            <span class="info-val"><?= escape($row['no_hp_penerima'] ?? '-') ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Provinsi</span>
                            <span class="info-val">
                                <?php if ($nama_provinsi && $nama_provinsi !== $row['provinsi']): ?>
                                    <?= escape($nama_provinsi) ?>
                                <?php elseif ($nama_provinsi === $row['provinsi'] && isWilayahId($row['provinsi'])): ?>
                                    <span style="color:var(--yellow);font-size:11px;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        ID: <?= escape($row['provinsi']) ?> (gagal resolve)
                                    </span>
                                <?php else: ?>
                                    <?= escape($nama_provinsi) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kota / Kabupaten</span>
                            <span class="info-val">
                                <?php if ($nama_kota && $nama_kota !== $row['kota_tujuan']): ?>
                                    <?= escape($nama_kota) ?>
                                <?php elseif ($nama_kota === $row['kota_tujuan'] && isWilayahId($row['kota_tujuan'])): ?>
                                    <span style="color:var(--yellow);font-size:11px;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        ID: <?= escape($row['kota_tujuan']) ?> (gagal resolve)
                                    </span>
                                <?php else: ?>
                                    <?= escape($nama_kota) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Kecamatan</span>
                            <span class="info-val">
                                <?php if ($nama_kecamatan && $nama_kecamatan !== $row['kecamatan']): ?>
                                    <?= escape($nama_kecamatan) ?>
                                <?php elseif ($nama_kecamatan === $row['kecamatan'] && isWilayahId($row['kecamatan'])): ?>
                                    <span style="color:var(--yellow);font-size:11px;">
                                        <i class="bi bi-exclamation-circle"></i>
                                        ID: <?= escape($row['kecamatan']) ?> (gagal resolve)
                                    </span>
                                <?php else: ?>
                                    <?= escape($nama_kecamatan) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php if ($row['detail_alamat']): ?>
                        <div class="info-row">
                            <span class="info-label">Detail Alamat</span>
                            <span class="info-val" style="max-width:240px;"><?= escape($row['detail_alamat']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ($row['kode_pos']): ?>
                        <div class="info-row">
                            <span class="info-label">Kode Pos</span>
                            <span class="info-val"><?= escape($row['kode_pos']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- PENGIRIMAN & RESI / INFO COD -->
                 <?php if ($is_cod && !empty($row['alamat_cod'])): ?>
<div class="card">
    <div class="card-head">
        <div class="icon" style="background:rgba(52,211,153,.15);color:var(--green);"><i class="bi bi-geo-alt"></i></div>
        <h3>Alamat Pengantaran</h3>
    </div>
    <div class="card-body">
        <div class="info-row">
            <span class="info-label">Jenis COD</span>
            <span class="info-val" style="color:var(--green);">
                <?= escape($row['jenis_cod'] ?? '-') === 'antar_pembeli' ? '🛵 Antar ke Rumah' : '🏪 Beli ke Rumah Penjual' ?>
            </span>
        </div>
        <div class="info-row">
            <span class="info-label">Alamat Lengkap</span>
            <span class="info-val"><?= escape($row['alamat_cod']) ?></span>
        </div>
    </div>
</div>
<?php endif; ?>
                <?php if (in_array($status, ['diproses','dikirim','selesai']) || ($is_cod && $status === 'menunggu')): ?>
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(52,211,153,.15);color:var(--green);">
                            <i class="bi bi-<?= $is_cod ? 'cash-coin' : 'truck' ?>"></i>
                        </div>
                        <h3><?= $is_cod ? 'Info Pengantaran COD' : 'Pengiriman & Resi' ?></h3>
                    </div>
                    <div class="card-body">

                        <?php if ($is_cod): ?>
                            <div class="info-row">
                                <span class="info-label">No. HP Pembeli</span>
                                <span class="info-val">
                                    <a href="https://wa.me/62<?= ltrim(escape($row['hp_pembeli'] ?? ''), '0') ?>" target="_blank" style="color:var(--green);display:flex;align-items:center;gap:5px;">
                                        <i class="bi bi-whatsapp"></i> <?= escape($row['hp_pembeli'] ?? '-') ?>
                                    </a>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nama Pembeli</span>
                                <span class="info-val"><?= escape($row['nama_pembeli']) ?></span>
                            </div>
                             <div class="info-row">
                                <span class="info-label">Alamat Pembeli</span>
                                <span class="info-val" style="color:var(--green);max-width:260px;">
                                    <i class="bi bi-geo-alt-fill"></i> <?= escape($row['detail_alamat']) ?>
                                </span>
                            </div>
                     <?php
$catatan_murni = trim(preg_replace('/Jenis COD:\s*\w+\.?\s*/i', '', $row['catatan'] ?? ''));
?>
<div class="info-row">
    <span class="info-label">Catatan Pembeli</span>
    <span class="info-val" style="color:var(--yellow);font-style:italic;">
        <?= $catatan_murni ? '"' . escape($catatan_murni) . '"' : '<span style="color:var(--muted);">Tidak ada catatan</span>' ?>
    </span>
</div>

                        <?php else: ?>
                            <div class="info-row">
                                <span class="info-label">Kurir Dipilih Pembeli</span>
                                <span class="info-val" style="color:var(--accent);font-weight:700;">
                                    <?= !empty($row['kurir']) ? strtoupper(escape($row['kurir'])) : '<span style="color:var(--muted);">Belum dipilih</span>' ?>
                                </span>
                            </div>
                            <?php if ($row['no_resi']): ?>
                                <div class="info-row">
                                    <span class="info-label">No. Resi</span>
                                    <span class="info-val" style="font-family:monospace;letter-spacing:1px;color:var(--green);"><?= escape($row['no_resi']) ?></span>
                                </div>
                                <hr>
                                <form method="POST">
                                    <input type="hidden" name="aksi" value="simpan_resi">
                                    <input type="hidden" name="kurir" value="<?= escape($row['kurir']) ?>">
                                    <div class="form-group">
                                        <label>Update No. Resi</label>
                                        <input type="text" name="no_resi" class="form-input" value="<?= escape($row['no_resi']) ?>" placeholder="Nomor resi pengiriman">
                                    </div>
                                    <button type="submit" class="btn btn-accent"><i class="bi bi-save"></i> Update Resi</button>
                                </form>
                            <?php else: ?>
                                <p style="font-size:13px;color:var(--muted);margin:12px 0;">Input nomor resi untuk mengubah status menjadi <strong style="color:var(--green);">Dikirim</strong>.</p>
                                <form method="POST">
                                    <input type="hidden" name="aksi" value="simpan_resi">
                                    <input type="hidden" name="kurir" value="<?= escape($row['kurir']) ?>">
                                    <div class="form-group">
                                        <label>Nomor Resi</label>
                                        <input type="text" name="no_resi" class="form-input" placeholder="Masukkan nomor resi pengiriman" required>
                                    </div>
                                    <button type="submit" class="btn btn-accent"><i class="bi bi-truck"></i> Simpan & Kirim</button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
                <?php endif; ?>

                <!-- TRACKING RESI via BinderByte (hanya non-COD) -->
                <?php if (!$is_cod && in_array($status, ['dikirim','selesai']) && $row['no_resi']): ?>
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(167,139,250,.15);color:var(--accent);"><i class="bi bi-radar"></i></div>
                        <h3>Lacak Paket — <?= strtoupper(escape($row['kurir'])) ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if ($tracking_data): ?>
                            <?php
                            $summary = $tracking_data['summary'];
                            $detail  = $tracking_data['detail'];
                            $history = $tracking_data['history'] ?? [];
                            $track_status = strtoupper($summary['status'] ?? '');
                            $status_class = match($track_status) {
                                'DELIVERED' => 'track-status-delivered',
                                'ON TRANSIT', 'IN TRANSIT' => 'track-status-transit',
                                default => 'track-status-pending'
                            };
                            ?>
                            <span class="track-status-badge <?= $status_class ?>">
                                <i class="bi bi-circle-fill" style="font-size:6px;"></i>
                                <?= escape($track_status) ?>
                            </span>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:16px;">
                                <div style="background:var(--surface2);border-radius:8px;padding:10px;">
                                    <div style="font-size:10px;color:var(--muted);margin-bottom:3px;">PENGIRIM</div>
                                    <div style="font-size:12px;font-weight:600;color:var(--white);"><?= escape($detail['shipper'] ?? '-') ?></div>
                                    <div style="font-size:11px;color:var(--muted);margin-top:2px;"><?= escape($detail['origin'] ?? '-') ?></div>
                                </div>
                                <div style="background:var(--surface2);border-radius:8px;padding:10px;">
                                    <div style="font-size:10px;color:var(--muted);margin-bottom:3px;">PENERIMA</div>
                                    <div style="font-size:12px;font-weight:600;color:var(--white);"><?= escape($detail['receiver'] ?? '-') ?></div>
                                    <div style="font-size:11px;color:var(--muted);margin-top:2px;"><?= escape($detail['destination'] ?? '-') ?></div>
                                </div>
                            </div>
                            <?php if (!empty($history)): ?>
                            <div style="font-size:10px;letter-spacing:1px;text-transform:uppercase;color:var(--muted);font-weight:600;margin-bottom:10px;">Riwayat Pengiriman</div>
                            <?php foreach ($history as $i => $h): ?>
                            <div class="track-item">
                                <div class="track-dot <?= $i === 0 ? 'first' : 'rest' ?>">
                                    <?php if ($i === 0): ?><i class="bi bi-check-lg"></i><?php else: ?><i class="bi bi-circle-fill" style="font-size:6px;"></i><?php endif; ?>
                                </div>
                                <div class="track-text">
                                    <div class="track-desc"><?= escape($h['desc']) ?></div>
                                    <div class="track-meta">
                                        <?= escape($h['date']) ?>
                                        <?php if ($h['location']): ?> · <i class="bi bi-geo-alt"></i> <?= escape($h['location']) ?><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>

                        <?php elseif ($tracking_error): ?>
                            <div style="text-align:center;padding:20px;color:var(--muted);">
                                <i class="bi bi-exclamation-circle" style="font-size:24px;display:block;margin-bottom:8px;color:var(--yellow);"></i>
                                <div style="font-size:13px;"><?= escape($tracking_error) ?></div>
                                <div style="font-size:11px;margin-top:6px;">Resi: <span style="color:var(--green);font-family:monospace;"><?= escape($row['no_resi']) ?></span></div>
                            </div>
                        <?php else: ?>
                            <div style="text-align:center;padding:20px;color:var(--muted);">
                                <i class="bi bi-hourglass-split" style="font-size:24px;display:block;margin-bottom:8px;"></i>
                                <div style="font-size:13px;">Tracking belum tersedia</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

            </div><!-- /kolom kiri -->

            <!-- KOLOM KANAN -->
            <div>

                <!-- DATA PEMBELI -->
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(249,168,212,.15);color:var(--pink);"><i class="bi bi-person"></i></div>
                        <h3>Data Pembeli</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-row">
                            <span class="info-label">Nama</span>
                            <span class="info-val"><?= escape($row['nama_pembeli']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email</span>
                            <span class="info-val" style="font-size:12px;"><?= escape($row['email_pembeli']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">No. HP</span>
                            <span class="info-val"><?= escape($row['hp_pembeli'] ?? '-') ?></span>
                        </div>
                        <?php if ($is_cod): ?>
            
                        <?php endif; ?>
                    </div>
                </div>

                <!-- BUKTI TRANSFER: hanya non-COD -->
                <?php if (!$is_cod && ($st_transfer !== 'menunggu' || !$row['bukti_transfer'])): ?>
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(167,139,250,.15);color:var(--accent);"><i class="bi bi-receipt"></i></div>
                        <h3>Bukti Transfer</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($row['bukti_transfer']): ?>
                        <div class="bukti-wrap">
                            <img src="../uploads/bukti_transfer/<?= escape($row['bukti_transfer']) ?>"
                                 alt="Bukti Transfer" onclick="openLightbox(this.src)" title="Klik untuk zoom">
                        </div>
                        <div style="margin-top:10px;font-size:12px;color:var(--muted);">
                            Via <?= strtoupper(escape($row['metode_transfer'] ?? '')) ?>
                            <?php if ($row['jumlah_transfer']): ?> · <?= formatRupiah($row['jumlah_transfer']) ?><?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="no-bukti"><i class="bi bi-image" style="font-size:24px;display:block;margin-bottom:8px;"></i>Belum ada bukti transfer</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- UPDATE STATUS MANUAL -->
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(251,146,60,.15);color:var(--orange);"><i class="bi bi-pencil-square"></i></div>
                        <h3>Update Status</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="aksi" value="update_status">
                            <div class="form-group">
                                <label>Status Pesanan</label>
                                <select name="status" class="form-select">
                                    <?php foreach ($all_status as $s): ?>
                                    <option value="<?= $s ?>" <?= $status === $s ? 'selected' : '' ?>>
                                        <?= match($s) {
                                            'menunggu'     => 'Menunggu',
                                            'dikonfirmasi' => 'Dikonfirmasi',
                                            'diproses'     => 'Diproses',
                                            'dikirim'      => $is_cod ? 'Dalam Pengantaran' : 'Dikirim',
                                            'selesai'      => 'Selesai',
                                            'dibatalkan'   => 'Dibatalkan',
                                            default        => ucfirst($s)
                                        } ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-accent" style="width:100%;justify-content:center;"><i class="bi bi-check2"></i> Simpan Status</button>
                        </form>
                    </div>
                </div>

                <!-- ALUR PESANAN (TIMELINE) -->
                <div class="card">
                    <div class="card-head">
                        <div class="icon" style="background:rgba(167,139,250,.15);color:var(--accent);"><i class="bi bi-clock-history"></i></div>
                        <h3>Alur Pesanan</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $flow = [];
                        $flow['menunggu'] = ['label'=>'Pesanan Masuk', 'sub'=>'Pembeli sudah checkout'];

                        if ($is_cod) {
                            $flow['diproses'] = ['label'=>'Sedang Diproses',      'sub'=>'Barang disiapkan'];
                            $flow['dikirim']  = ['label'=>'Dalam Pengantaran',    'sub'=>'Admin mengantar ke pembeli'];
                            $flow['selesai']  = ['label'=>'Selesai',              'sub'=>'COD lunas, pesanan selesai'];
                        } else {
                            $flow['dikonfirmasi'] = ['label'=>'Transfer Dikonfirmasi','sub'=>'Pembayaran valid'];
                            $flow['diproses']     = ['label'=>'Sedang Diproses',      'sub'=>'Barang disiapkan'];
                            $flow['dikirim']      = ['label'=>'Dikirim',              'sub'=>$row['no_resi'] ? 'Resi: '.$row['no_resi'] : 'Menunggu resi'];
                            $flow['selesai']      = ['label'=>'Selesai',              'sub'=>'Pesanan diterima'];
                        }

                        if ($status === 'dibatalkan') {
                            $flow['dibatalkan'] = ['label'=>'Dibatalkan','sub'=>'Pesanan dibatalkan'];
                        }

                        $status_order = $is_cod
                            ? ['menunggu','diproses','dikirim','selesai']
                            : ['menunggu','dikonfirmasi','diproses','dikirim','selesai'];

                        $current_idx = array_search($status, $status_order);
                        if ($current_idx === false) $current_idx = -1;

                        foreach ($flow as $s => $f):
                            $idx = array_search($s, $status_order);
                            if ($s === 'dibatalkan') {
                                $dot_class = 'active';
                            } elseif ($s === $status) {
                                $dot_class = 'active';
                            } elseif ($idx !== false && $idx < $current_idx) {
                                $dot_class = 'done';
                            } else {
                                $dot_class = 'pending';
                            }

                            $icon = match($s) {
                                'menunggu'     => 'bi-bag',
                                'dikonfirmasi' => 'bi-check-circle',
                                'diproses'     => 'bi-arrow-repeat',
                                'dikirim'      => 'bi-truck',
                                'selesai'      => 'bi-check2-all',
                                'dibatalkan'   => 'bi-x-circle',
                                default        => 'bi-circle'
                            };

                            $title_color = match($dot_class) {
                                'active' => 'color:var(--accent);',
                                'done'   => 'color:var(--green);',
                                default  => 'color:var(--muted);'
                            };
                        ?>
                        <div class="tl-item">
                            <div class="tl-dot <?= $dot_class ?>"><i class="bi <?= $icon ?>"></i></div>
                            <div class="tl-text">
                                <div class="title" style="<?= $title_color ?>"><?= $f['label'] ?></div>
                                <div class="sub"><?= $f['sub'] ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- /kolom kanan -->
        </div>
    </div>
</div>

<!-- MODAL TOLAK TRANSFER -->
<?php if (!$is_cod): ?>
<div class="overlay" id="modalTolak">
    <div class="modal-box">
        <h4><i class="bi bi-x-circle" style="color:var(--red);margin-right:6px;"></i> Tolak Transfer</h4>
        <p>Berikan alasan penolakan agar pembeli tahu apa yang perlu diperbaiki.</p>
        <form method="POST">
            <input type="hidden" name="aksi" value="tolak_transfer">
            <div class="form-group">
                <label>Alasan Penolakan</label>
                <textarea name="catatan_tolak" class="form-textarea" placeholder="Contoh: Jumlah transfer kurang, bukti transfer tidak jelas, dll."></textarea>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('modalTolak').classList.remove('show')">Batal</button>
                <button type="submit" class="btn btn-red"><i class="bi bi-x-circle-fill"></i> Tolak Transfer</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox" onclick="this.classList.remove('show')">
    <img id="lightboxImg" src="" alt="Bukti Transfer">
</div>

<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    document.getElementById('lightbox').classList.add('show');
}
<?php if (!$is_cod): ?>
document.getElementById('modalTolak').addEventListener('click', function(e){
    if (e.target === this) this.classList.remove('show');
});
<?php endif; ?>
</script>
</body>
</html>