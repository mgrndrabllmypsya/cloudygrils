<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    mysqli_query($conn, "DELETE FROM ulasan WHERE id=$id");
    header("Location: ulasan.php?msg=deleted"); exit;
}

$filter_rating = $_GET['rating'] ?? '';
$search        = $_GET['search'] ?? '';
$where = "WHERE 1=1";
if ($filter_rating) $where .= " AND ul.rating=" . (int)$filter_rating;
if ($search)        $where .= " AND (pb.nama LIKE '%" . mysqli_real_escape_string($conn, $search) . "%' OR pr.nama_barang LIKE '%" . mysqli_real_escape_string($conn, $search) . "%')";

$q_ulasan = mysqli_query($conn, "
    SELECT ul.*, pb.nama AS nama_pembeli, pr.nama_barang
    FROM ulasan ul
    JOIN pembeli pb ON pb.id = ul.pembeli_id
    JOIN produk pr ON pr.id = ul.produk_id
    $where
    ORDER BY ul.created_at DESC
");

$total_ulasan   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ulasan"))[0] ?? 0;
$avg_rating     = mysqli_fetch_row(mysqli_query($conn, "SELECT ROUND(AVG(rating),1) FROM ulasan"))[0] ?? 0;
$total_bintang5 = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ulasan WHERE rating=5"))[0] ?? 0;
$total_unread   = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
$nego_menunggu  = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;

$penjual_nama = $_SESSION['penjual_nama'] ?? 'Penjual';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ulasan — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Lato:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --bg:#FFF0F5; --surface:#FFFFFF; --surface2:#FFE8F2; --border:#F4A7C3;
    --accent:#E8719A; --accent2:#D4547F; --pink:#F4A7C3; --pink2:#E8719A;
    --green:#00BFA5; --yellow:#FFB300; --red:#FF1744;
    --text:#1A1A1A; --text2:#444444; --muted:#BBA0B0; --white:#FFFFFF;

    --font-heading: 'Poppins', sans-serif;
    --font-body:    'Lato', sans-serif;
    --font-ui:      'Poppins', sans-serif;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: var(--font-body); background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
body::before { content: ''; position: fixed; inset: 0; background-image: radial-gradient(circle, #F4A7C3 1px, transparent 1px); background-size: 28px 28px; opacity: .15; pointer-events: none; z-index: 0; }
a { text-decoration: none; color: inherit; }

/* SIDEBAR */
.sidebar { width: 300px; background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 50; border-radius: 0 28px 28px 0; box-shadow: 6px 0 32px rgba(212,84,127,.28); overflow: hidden; }
.sidebar-logo { padding: 28px 28px 22px; border-bottom: 1.5px solid rgba(255,255,255,.2); background: rgba(255,255,255,.12); }
.sidebar-logo .logo-img { width: 38px; height: 38px; object-fit: contain; background: #ffffff; border-radius: 50%; flex-shrink: 0; padding: 4px; box-sizing: border-box; border: 1.5px solid rgba(255,255,255,.4); }
.sidebar-logo .logo { font-family: var(--font-heading); font-size: 24px; font-weight: 900; color: #1db899b1 !important; letter-spacing: -.3px; margin: 0; line-height: 1; }
.sidebar-logo .logo span { color: #ff009db1; }
.sidebar-logo small { display: block; font-family: var(--font-body); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.65); margin-top: 8px; }
.sidebar-nav { flex: 1; padding: 20px 18px; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; }
.nav-section { font-family: var(--font-ui); font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.55); padding: 18px 16px 8px; font-weight: 600; }
.nav-item { font-family: var(--font-ui); display: flex; align-items: center; gap: 14px; padding: 13px 18px; border-radius: 12px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,.85); transition: all .2s; letter-spacing: 0.01em; }
.nav-item:hover { background: rgba(255,255,255,.2); color: #fff; transform: translateX(3px); }
.nav-item.active { background: rgba(255,255,255,.28); color: #fff; font-weight: 600; border-left: 3px solid #fff; padding-left: 15px; }
.nav-item i { font-size: 17px; width: 22px; flex-shrink: 0; }
.badge-notif { font-family: var(--font-ui); background: #fff; color: var(--accent); font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: auto; }
.nav-item-toko { font-family: var(--font-ui); margin-top: 0; background: transparent; border: none; color: rgba(255,255,255,.85) !important; font-weight: 500 !important; justify-content: flex-start; border-radius: 12px; box-shadow: none; letter-spacing: 0.01em; }
.nav-item-toko:hover { background: rgba(255,255,255,.2) !important; border-color: transparent !important; box-shadow: none; transform: translateX(3px) !important; color: #fff !important; }
.nav-ext-icon { font-size: 11px !important; width: auto !important; margin-left: auto; opacity: .6; }
.sidebar-footer { padding: 16px 18px 20px; border-top: 1.5px solid rgba(255,255,255,.2); background: rgba(0,0,0,.1); }
.btn-logout { font-family: var(--font-ui); display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,.85); transition: background .2s; width: 100%; letter-spacing: 0.01em; }
.btn-logout i { font-size: 16px; }
.btn-logout:hover { background: rgba(255,255,255,.2); color: #fff; }

/* MAIN */
.main { margin-left: 300px; flex: 1; display: flex; flex-direction: column; position: relative; z-index: 1; }
.topbar { background: rgba(255,255,255,.95); backdrop-filter: blur(12px); border-bottom: 1.5px solid var(--border); padding: 0 32px; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 40; box-shadow: 0 2px 12px rgba(212,84,127,.07); }
.topbar-left { display: flex; align-items: center; gap: 12px; }
.topbar-title { font-family: var(--font-heading); font-size: 18px; font-weight: 700; color: var(--text); }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-date { font-family: var(--font-body); font-size: 12px; color: var(--muted); }
.content { padding: 26px 28px; flex: 1; }

/* STATS */
.stats-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 16px; margin-bottom: 28px; }
.stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 20px; position: relative; overflow: hidden; transition: transform .2s, border-color .2s; }
.stat-card:hover { transform: translateY(-2px); border-color: var(--accent); }
.stat-card::before { content: ''; position: absolute; top: 0; right: 0; width: 80px; height: 80px; border-radius: 50%; opacity: .07; transform: translate(20px,-20px); }
.stat-card.purple::before { background: var(--accent2); }
.stat-card.yellow::before { background: var(--yellow); }
.stat-card.green::before  { background: var(--green); }
.stat-icon { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 17px; margin-bottom: 14px; }
.stat-card.purple .stat-icon { background: rgba(124,58,237,.2); color: var(--accent); }
.stat-card.yellow .stat-icon { background: rgba(251,191,36,.2); color: var(--yellow); }
.stat-card.green  .stat-icon { background: rgba(52,211,153,.2);  color: var(--green); }
.stat-value { font-family: var(--font-heading); font-size: 26px; font-weight: 700; color: var(--text); line-height: 1; margin-bottom: 4px; }
.stat-label { font-family: var(--font-body); font-size: 12px; color: var(--muted); }

/* CARD */
.card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; }
.card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
.card-header h3 { font-family: var(--font-heading); font-size: 14px; font-weight: 600; color: var(--text); }
.filter-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
.filter-select, .search-input { font-family: var(--font-body); background: var(--surface2); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-size: 12px; padding: 6px 12px; outline: none; }
.filter-select:focus, .search-input:focus { border-color: var(--accent); }
.btn-filter { font-family: var(--font-ui); padding: 6px 14px; border-radius: 8px; background: var(--accent2); color: #fff; font-size: 12px; font-weight: 600; border: none; cursor: pointer; }

/* TABLE */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { font-family: var(--font-ui); text-align: left; font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); padding: 10px 20px; border-bottom: 1px solid var(--border); font-weight: 600; }
td { font-family: var(--font-body); padding: 12px 20px; font-size: 13px; border-bottom: 1px solid rgba(244,167,195,.3); vertical-align: top; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: var(--surface2); }
.stars { color: var(--yellow); font-size: 13px; letter-spacing: 1px; }
.star-empty { color: var(--border); }
.review-text { font-family: var(--font-body); font-size: 12px; color: var(--muted); max-width: 280px; line-height: 1.5; }
.empty { font-family: var(--font-body); text-align: center; padding: 40px 20px; color: var(--muted); font-size: 13px; }
.empty i { font-size: 2rem; display: block; margin-bottom: 8px; }
.alert { font-family: var(--font-body); padding: 12px 20px; border-radius: 10px; font-size: 13px; margin-bottom: 16px; background: rgba(248,113,113,.15); color: var(--red); border: 1px solid rgba(248,113,113,.3); display: flex; align-items: center; gap: 8px; }
.btn-del { font-family: var(--font-ui); background: none; border: none; color: var(--red); cursor: pointer; font-size: 12px; padding: 4px 8px; border-radius: 6px; transition: background .2s; }
.btn-del:hover { background: rgba(248,113,113,.1); }

/* MODAL */
.confirm-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.7); z-index: 200; align-items: center; justify-content: center; }
.confirm-overlay.show { display: flex; }
.confirm-box { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px; max-width: 360px; width: 90%; }
.confirm-box h4 { font-family: var(--font-heading); font-size: 15px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
.confirm-box p { font-family: var(--font-body); font-size: 13px; color: var(--muted); margin-bottom: 20px; }
.confirm-btns { display: flex; gap: 8px; justify-content: flex-end; }
.btn-cancel-c { font-family: var(--font-ui); padding: 8px 16px; border-radius: 8px; background: var(--surface2); border: 1px solid var(--border); color: var(--muted); font-size: 13px; cursor: pointer; }
.btn-del-c { font-family: var(--font-ui); padding: 8px 16px; border-radius: 8px; background: var(--red); color: #fff; font-size: 13px; font-weight: 600; border: none; cursor: pointer; }

/* MOBILE */
.btn-toggle-sidebar { display: none; background: var(--surface2); border: 1.5px solid var(--border); border-radius: 10px; width: 38px; height: 38px; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; color: var(--text); }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 98; }
.sidebar-overlay.active { display: block; }
@media (max-width: 1024px) {
    .main { margin-left: 0 !important; }
    .sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 280px; border-radius: 0; transform: translateX(-100%); transition: transform 0.3s ease; z-index: 99; }
    .sidebar.active { transform: translateX(0); }
    .btn-toggle-sidebar { display: flex !important; }
    .stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 10px; }
    .topbar { padding: 0 16px; }
}
@media (max-width: 768px) {
    .topbar { padding: 0 14px; height: auto; min-height: 56px; }
    .content { padding: 14px 12px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 8px; }
    .stat-card { padding: 14px; }
    .card-header { flex-wrap: wrap; gap: 8px; }
    .filter-row { width: 100%; flex-wrap: wrap; }
    .filter-row .search-input, .filter-row .filter-select { flex: 1; min-width: 120px; }
    table th:nth-child(6), table td:nth-child(6) { display: none; }
}
@media (max-width: 480px) {
    .stats-grid { grid-template-columns: repeat(2, 1fr) !important; gap: 8px; }
    .content { padding: 12px 10px; }
    .topbar-date { display: none; }
    .topbar-title { font-size: 15px; }
    td, th { padding: 6px 8px; font-size: 11px; }
    table th:nth-child(3), table td:nth-child(3), table th:nth-child(6), table td:nth-child(6) { display: none; }
    .review-text { max-width: 140px; }
}
EOF
echo "done ulasan"

cat > /mnt/user-data/outputs/pengaturan-style.css << 'EOF'
:root {
    --bg:#FFF0F5; --surface:#FFFFFF; --surface2:#FFE8F2; --border:#F4A7C3;
    --accent:#E8719A; --accent2:#D4547F; --pink:#F4A7C3; --pink2:#E8719A;
    --green:#00BFA5; --yellow:#FFB300; --red:#FF1744;
    --text:#1A1A1A; --text2:#444444; --muted:#BBA0B0; --white:#FFFFFF;

    --font-heading: 'Poppins', sans-serif;
    --font-body:    'Lato', sans-serif;
    --font-ui:      'Poppins', sans-serif;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: var(--font-body); background: var(--bg); color: var(--text); display: flex; min-height: 100vh; }
body::before { content: ''; position: fixed; inset: 0; background-image: radial-gradient(circle, #F4A7C3 1px, transparent 1px); background-size: 28px 28px; opacity: .15; pointer-events: none; z-index: 0; }
a { text-decoration: none; color: inherit; }

/* SIDEBAR */
.sidebar { width: 300px; background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%); display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; z-index: 50; border-radius: 0 28px 28px 0; box-shadow: 6px 0 32px rgba(212,84,127,.28); overflow: hidden; }
.sidebar-logo { padding: 28px 28px 22px; border-bottom: 1.5px solid rgba(255,255,255,.2); background: rgba(255,255,255,.12); }
.sidebar-logo .logo-img { width: 38px; height: 38px; object-fit: contain; background: #ffffff; border-radius: 50%; flex-shrink: 0; padding: 4px; box-sizing: border-box; border: 1.5px solid rgba(255,255,255,.4); }
.sidebar-logo .logo { font-family: var(--font-heading); font-size: 24px; font-weight: 900; color: #1db899b1 !important; letter-spacing: -.3px; margin: 0; line-height: 1; }
.sidebar-logo .logo span { color: #ff009db1; }
.sidebar-logo small { display: block; font-family: var(--font-body); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: rgba(255,255,255,.65); margin-top: 8px; }
.sidebar-nav { flex: 1; padding: 20px 18px; display: flex; flex-direction: column; gap: 4px; overflow-y: auto; }
.nav-section { font-family: var(--font-ui); font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: rgba(255,255,255,.55); padding: 18px 16px 8px; font-weight: 600; }
.nav-item { font-family: var(--font-ui); display: flex; align-items: center; gap: 14px; padding: 13px 18px; border-radius: 12px; font-size: 14px; font-weight: 500; color: rgba(255,255,255,.85); transition: all .2s; letter-spacing: 0.01em; }
.nav-item:hover { background: rgba(255,255,255,.2); color: #fff; transform: translateX(3px); }
.nav-item.active { background: rgba(255,255,255,.28); color: #fff; font-weight: 600; border-left: 3px solid #fff; padding-left: 15px; }
.nav-item i { font-size: 17px; width: 22px; flex-shrink: 0; }
.badge-notif { font-family: var(--font-ui); background: #fff; color: var(--accent); font-size: 10px; font-weight: 700; padding: 2px 7px; border-radius: 10px; margin-left: auto; }
.nav-item-toko { font-family: var(--font-ui); margin-top: 0; background: transparent; border: none; color: rgba(255,255,255,.85) !important; font-weight: 500 !important; justify-content: flex-start; border-radius: 12px; box-shadow: none; letter-spacing: 0.01em; }
.nav-item-toko:hover { background: rgba(255,255,255,.2) !important; border-color: transparent !important; box-shadow: none; transform: translateX(3px) !important; color: #fff !important; }
.nav-ext-icon { font-size: 11px !important; width: auto !important; margin-left: auto; opacity: .6; }
.sidebar-footer { padding: 16px 18px 20px; border-top: 1.5px solid rgba(255,255,255,.2); background: rgba(0,0,0,.1); }
.btn-logout { font-family: var(--font-ui); display: flex; align-items: center; gap: 10px; padding: 11px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; color: rgba(255,255,255,.85); transition: background .2s; width: 100%; letter-spacing: 0.01em; }
.btn-logout i { font-size: 16px; }
.btn-logout:hover { background: rgba(255,255,255,.2); color: #fff; }

/* MAIN */
.main { margin-left: 300px; flex: 1; display: flex; flex-direction: column; position: relative; z-index: 1; }
.topbar { background: rgba(255,255,255,.95); backdrop-filter: blur(12px); border-bottom: 1.5px solid var(--border); padding: 0 32px; height: 64px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 40; box-shadow: 0 2px 12px rgba(212,84,127,.07); }
.topbar-left { display: flex; align-items: center; gap: 12px; }
.topbar-title { font-family: var(--font-heading); font-size: 18px; font-weight: 700; color: var(--text); }
.topbar-right { display: flex; align-items: center; gap: 10px; }
.topbar-date { font-family: var(--font-body); font-size: 12px; color: var(--muted); }
.content { padding: 28px 32px; flex: 1; }
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

/* CARD */
.card { background: var(--surface); border: 1.5px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: 0 2px 16px rgba(212,84,127,.06); }
.card-header { padding: 16px 20px; border-bottom: 1.5px solid var(--border); background: var(--surface2); }
.card-header h3 { font-family: var(--font-heading); font-size: 14px; font-weight: 600; color: var(--text); }
.card-header p { font-family: var(--font-body); font-size: 12px; color: var(--muted); margin-top: 2px; }
.card-body { padding: 20px; }

/* FORM */
.form-group { margin-bottom: 16px; }
.form-label { font-family: var(--font-ui); display: block; font-size: 12px; font-weight: 600; color: var(--text2); margin-bottom: 6px; letter-spacing: .3px; }
.form-input, .form-textarea { font-family: var(--font-body); width: 100%; background: var(--surface2); border: 1.5px solid var(--border); border-radius: 8px; color: var(--text); font-size: 13px; padding: 10px 12px; outline: none; transition: border-color .2s, box-shadow .2s; }
.form-input:focus, .form-textarea:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(212,84,127,.1); }
.form-input::placeholder, .form-textarea::placeholder { color: var(--muted); }
.form-textarea { resize: vertical; min-height: 80px; }

/* INPUT WITH ICON */
.input-icon-wrap { position: relative; }
.input-icon-wrap .form-input { padding-left: 36px; }
.input-icon-wrap .input-icon { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: 14px; color: var(--muted); pointer-events: none; }

/* REKENING */
.rek-divider { margin: 20px 0 16px; padding-top: 18px; border-top: 1.5px dashed var(--border); }
.rek-divider-title { font-family: var(--font-ui); font-size: 12px; font-weight: 700; color: var(--accent); display: flex; align-items: center; gap: 6px; margin-bottom: 14px; text-transform: uppercase; letter-spacing: .5px; }
.rek-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.rek-preview { background: var(--surface2); border: 1.5px solid var(--border); border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; display: flex; align-items: center; gap: 10px; }
.rek-preview .rek-icon { font-size: 22px; flex-shrink: 0; }
.rek-preview .rek-detail .rek-bank  { font-family: var(--font-ui); font-size: 12px; font-weight: 700; color: var(--text); }
.rek-preview .rek-detail .rek-nomor { font-family: var(--font-heading); font-size: 13px; font-weight: 600; color: var(--accent); letter-spacing: .5px; margin-top: 1px; }
.rek-preview .rek-detail .rek-atas  { font-family: var(--font-body); font-size: 11px; color: var(--muted); margin-top: 1px; }

/* LOGO UPLOAD */
.logo-upload-wrap { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
.logo-preview { width: 80px; height: 80px; border-radius: 12px; flex-shrink: 0; background: var(--surface2); border: 2px dashed var(--border); display: flex; align-items: center; justify-content: center; overflow: hidden; cursor: pointer; transition: border-color .2s; }
.logo-preview:hover { border-color: var(--accent); }
.logo-preview img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
.logo-placeholder { font-size: 28px; color: var(--muted); }
.logo-upload-info p { font-family: var(--font-body); font-size: 12px; color: var(--muted); line-height: 1.5; }
.btn-upload-logo { font-family: var(--font-ui); display: inline-flex; align-items: center; gap: 6px; margin-top: 8px; padding: 7px 14px; border-radius: 7px; background: var(--surface2); border: 1.5px solid var(--border); font-size: 12px; color: var(--accent); cursor: pointer; transition: border-color .2s, background .2s; }
.btn-upload-logo:hover { border-color: var(--accent); background: rgba(212,84,127,.06); }
#inputLogo { display: none; }

/* MAPS INFO */
.maps-info { background: rgba(212,84,127,.05); border: 1px solid rgba(212,84,127,.2); border-radius: 8px; padding: 10px 12px; margin-top: 6px; }
.maps-info-title { font-family: var(--font-ui); font-size: 11px; font-weight: 600; color: var(--accent); margin-bottom: 4px; }
.maps-info ol { font-family: var(--font-body); font-size: 11px; color: var(--muted); padding-left: 14px; line-height: 1.9; margin: 0; }

/* BUTTONS */
.btn-save { font-family: var(--font-ui); display: inline-flex; align-items: center; gap: 8px; padding: 10px 22px; border-radius: 8px; background: linear-gradient(135deg, #F4A7C3, #E8719A); color: #fff; font-size: 13px; font-weight: 600; border: none; cursor: pointer; box-shadow: 0 4px 14px rgba(212,84,127,.35); transition: opacity .2s, transform .15s; }
.btn-save:hover { opacity: .88; transform: translateY(-1px); }

/* ALERTS */
.alert { font-family: var(--font-body); padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.alert.success { background: rgba(0,191,165,.12); color: var(--green); border: 1px solid rgba(0,191,165,.3); }
.alert.error   { background: rgba(255,23,68,.1);  color: var(--red);   border: 1px solid rgba(255,23,68,.25); }

/* PASSWORD STRENGTH */
.pw-strength { height: 4px; border-radius: 2px; margin-top: 6px; background: var(--border); overflow: hidden; }
.pw-strength-bar { height: 100%; border-radius: 2px; transition: width .3s, background .3s; width: 0; }

/* MOBILE */
.btn-toggle-sidebar { display: none; background: var(--surface2); border: 1.5px solid var(--border); border-radius: 10px; width: 38px; height: 38px; align-items: center; justify-content: center; cursor: pointer; font-size: 18px; color: var(--text); }
.sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 98; }
.sidebar-overlay.active { display: block; }
@media (max-width: 1024px) {
    .main { margin-left: 0 !important; }
    .sidebar { position: fixed; left: 0; top: 0; height: 100vh; width: 280px; border-radius: 0; transform: translateX(-100%); transition: transform 0.3s ease; z-index: 99; }
    .sidebar.active { transform: translateX(0); }
    .btn-toggle-sidebar { display: flex !important; }
    .topbar { padding: 0 16px; }
    .content { padding: 16px 14px; }
    .grid-2 { grid-template-columns: 1fr; }
    .rek-grid { grid-template-columns: 1fr; }
}
@media (max-width: 768px) {
    .topbar { padding: 0 14px; height: auto; min-height: 56px; }
    .content { padding: 14px 12px; }
}
@media (max-width: 480px) {
    .topbar-date { display: none; }
    .topbar-title { font-size: 15px; }
    .content { padding: 12px 10px; }
    .card-body { padding: 14px; }
}
</style>
</head>
<body>
<?php include '../includes/sidebar.php'; ?>

<!-- OVERLAY SIDEBAR (untuk mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="main">
    <div class="topbar">
        <div class="topbar-left">
            <!-- TOMBOL HAMBURGER — hanya muncul di mobile/tablet -->
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title">Ulasan</div>
        </div>
        <div class="topbar-right">
            <span class="topbar-date"><i class="bi bi-calendar3"></i> <?= date('d M Y') ?></span>
        </div>
    </div>

    <div class="content">

        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
        <div class="alert"><i class="bi bi-trash"></i> Ulasan berhasil dihapus.</div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card purple">
                <div class="stat-icon"><i class="bi bi-chat-square-text"></i></div>
                <div class="stat-value"><?= $total_ulasan ?></div>
                <div class="stat-label">Total Ulasan</div>
            </div>
            <div class="stat-card yellow">
                <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                <div class="stat-value"><?= $avg_rating ?></div>
                <div class="stat-label">Rata-rata Rating</div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="bi bi-stars"></i></div>
                <div class="stat-value"><?= $total_bintang5 ?></div>
                <div class="stat-label">Bintang 5</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3><i class="bi bi-star" style="color:var(--yellow);margin-right:6px;"></i> Daftar Ulasan</h3>
                <form method="GET" class="filter-row">
                    <input type="text" name="search" class="search-input" placeholder="Cari pembeli / produk..." value="<?= escape($search) ?>">
                    <select name="rating" class="filter-select">
                        <option value="">Semua Rating</option>
                        <?php for ($i=5; $i>=1; $i--): ?>
                        <option value="<?= $i ?>" <?= $filter_rating==$i ? 'selected' : '' ?>><?= $i ?> Bintang</option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn-filter"><i class="bi bi-funnel"></i> Filter</button>
                </form>
            </div>
            <div class="table-wrap">
                <?php if ($q_ulasan && mysqli_num_rows($q_ulasan) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Pembeli</th>
                            <th>Produk</th>
                            <th>Rating</th>
                            <th>Komentar</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php $no=1; while ($row = mysqli_fetch_assoc($q_ulasan)): ?>
                        <tr>
                            <td style="color:var(--muted);"><?= $no++ ?></td>
                            <td style="font-weight:500;color:var(--text);"><?= escape($row['nama_pembeli']) ?></td>
                            <td style="color:var(--muted);font-size:12px;"><?= escape($row['nama_barang']) ?></td>
                            <td>
                                <div class="stars">
                                    <?php for ($i=1; $i<=5; $i++): ?>
                                        <i class="bi bi-star-fill <?= $i <= $row['rating'] ? '' : 'star-empty' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </td>
                            <td><div class="review-text"><?= escape($row['komentar'] ?? '-') ?></div></td>
                            <td style="color:var(--muted);font-size:12px;"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <button class="btn-del" onclick="confirmDelete(<?= $row['id'] ?>)">
                                    <i class="bi bi-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty"><i class="bi bi-star"></i> Tidak ada ulasan ditemukan</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- CONFIRM DELETE MODAL -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <h4><i class="bi bi-exclamation-triangle" style="color:var(--red);margin-right:6px;"></i> Hapus Ulasan?</h4>
        <p>Ulasan ini akan dihapus secara permanen.</p>
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
/* Sidebar toggle */
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
function closeSidebar() {
    document.querySelector('.sidebar').classList.remove('active');
    document.getElementById('sidebarOverlay').classList.remove('active');
}

/* Tutup sidebar saat klik nav-item di mobile */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sidebar .nav-item, .sidebar .btn-logout').forEach(function (link) {
        link.addEventListener('click', function () {
            if (window.innerWidth <= 1024) closeSidebar();
        });
    });
});

/* Confirm delete */
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    document.getElementById('confirmOverlay').classList.add('show');
}
function closeConfirm() {
    document.getElementById('confirmOverlay').classList.remove('show');
}
document.getElementById('confirmOverlay').addEventListener('click', function (e) {
    if (e.target === this) closeConfirm();
});
</script>
</body>
</html>