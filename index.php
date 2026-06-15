<?php
session_start();
require_once 'config/koneksi.php';

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }
function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $rating ? '#E05C7A' : '#FFD6E0';
        $html .= '<i class="bi bi-star-fill" style="color:' . $color . ';font-size:13px;"></i>';
    }
    return $html;
}

$q_toko = mysqli_query($conn, "SELECT * FROM pengaturan_toko LIMIT 1");
$toko   = $q_toko ? mysqli_fetch_assoc($q_toko) : [];
$filter_kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$where = "status='aktif'";
if ($filter_kategori && $filter_kategori !== 'all') $where .= " AND kategori='$filter_kategori'";
$q_produk = mysqli_query($conn, "SELECT * FROM produk WHERE $where ORDER BY created_at DESC LIMIT 10");
$q_hero   = mysqli_query($conn, "SELECT foto_utama, nama_barang FROM produk WHERE status='aktif' ORDER BY created_at DESC LIMIT 3");
$hero_imgs = $q_hero ? mysqli_fetch_all($q_hero, MYSQLI_ASSOC) : [];

function heroImg($arr, $idx, $fallback) {
    if (isset($arr[$idx]['foto_utama']) && $arr[$idx]['foto_utama']) {
        return 'uploads/produk/' . htmlspecialchars($arr[$idx]['foto_utama'], ENT_QUOTES, 'UTF-8');
    }
    return $fallback;
}

$about_imgs = [];
$r = mysqli_query($conn, "SELECT foto_utama, nama_barang, kategori FROM produk WHERE status='aktif' AND foto_utama != '' ORDER BY created_at DESC LIMIT 1");
$about_imgs[0] = $r ? mysqli_fetch_assoc($r) : [];
$r = mysqli_query($conn, "SELECT foto_utama, nama_barang, kategori FROM produk WHERE status='aktif' AND foto_utama != '' AND kategori='Atasan' ORDER BY created_at DESC LIMIT 1");
$about_imgs[1] = $r ? mysqli_fetch_assoc($r) : [];
$r = mysqli_query($conn, "SELECT foto_utama, nama_barang, kategori FROM produk WHERE status='aktif' AND foto_utama != '' AND kategori='Dress/Gamis' ORDER BY created_at DESC LIMIT 1");
$about_imgs[2] = $r ? mysqli_fetch_assoc($r) : [];

$q_ulasan = mysqli_query($conn, "
    SELECT ul.rating, ul.komentar, p.nama AS nama_pembeli
    FROM ulasan ul
    JOIN pembeli p ON p.id = ul.pembeli_id
    WHERE ul.rating >= 4 AND ul.komentar != ''
    ORDER BY ul.created_at DESC LIMIT 3
");
$ulasan_list = $q_ulasan ? mysqli_fetch_all($q_ulasan, MYSQLI_ASSOC) : [];
$kategori_list = ['Atasan','Bawahan','Dress/Gamis','Outer','Hijab & Aksesoris'];

// ── LOGO DARI DB ──
$logo_index_src = !empty($toko['logo'])
    ? 'uploads/toko/' . htmlspecialchars($toko['logo']) . '?v=' . time()
    : 'https://placehold.co/40x40/FFE4EE/FF4081?text=CG';

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    ob_start();
    if ($q_produk && mysqli_num_rows($q_produk) > 0):
        $i = 0;
        while ($row = mysqli_fetch_assoc($q_produk)):
            $i++;
            $col  = ($i - 1) % 5;
            $foto = $row['foto_utama'] ?? $row['foto'] ?? $row['gambar'] ?? $row['image'] ?? '';
    ?>
    <a href="auth/login.php" class="product-card" style="animation-delay:<?= $col * 80 ?>ms">
        <div class="card-img">
            <span class="card-kondisi"><?= escape($row['kondisi'] ?? '') ?></span>
            <img src="uploads/produk/<?= escape($foto) ?>"
                 alt="<?= escape($row['nama_barang'] ?? $row['nama'] ?? '') ?>"
                 onerror="this.src='https://placehold.co/400x500/FFD6E0/D94F6E?text=Cloudy+Girls'"
                 loading="lazy">
        </div>
        <div class="card-body">
            <div class="card-nama"><?= escape($row['nama_barang'] ?? $row['nama'] ?? '') ?></div>
            <div class="card-harga"><?= formatRupiah($row['harga'] ?? 0) ?></div>
            <?php if (!empty($row['ukuran'])): ?>
            <div class="card-ukuran"><i class="bi bi-tag"></i> <?= escape($row['ukuran']) ?></div>
            <?php endif; ?>
        </div>
    </a>
    <?php
        endwhile;
    else:
    ?>
    <div class="empty-state">
        <i class="bi bi-handbag"></i>
        <p>Belum ada produk tersedia.</p>
    </div>
    <?php endif;
    $html = ob_get_clean();
    header('Content-Type: application/json');
    echo json_encode(['html' => $html, 'kategori' => $filter_kategori]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cloudy Girls — Thrift Fashion Wanita</title>
 <link rel="icon" type="image/png" href="../uploads/toko/logo.png">

<link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Lato:ital,wght@0,300;0,400;0,700;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css">

<style>
:root {
    --bg:      #FFF0F4;
    --surface: #FFFFFF;
    --surface2:#FFF0F4;
    --border:  #FFB3C6;
    --accent:  #D94F6E;
    --accent2: #C0395A;
    --pink:    #FF8FAB;
    --pink2:   #FFB3C6;
    --muted:   #C48899;
    --text:    #2D1520;
    --text2:   #5C3244;
    --yellow:  #E8956D;
    --green:   #5BAF9E;
    --font-heading: 'Poppins', sans-serif;  
    --font-body:    'Lato', sans-serif;      
    --font-ui:      'Poppins', sans-serif;   
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: var(--font-body);
    color: var(--text);
    background: var(--bg);
    overflow-x: hidden;
    font-size: 15px;
    line-height: 1.7;
}
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, #FFB3C6 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: .10;
    pointer-events: none;
    z-index: 0;
}
a { text-decoration: none !important; color: inherit; }

h1, h2, h3, h4, h5, h6,
.h1, .h2, .h3, .h4, .h5, .h6 {
    font-family: var(--font-heading);
    font-weight: 700;
}

button, .btn,
.cat-link, .hero-badge, .hero-cta,
.hero-cta-outline, .btn-masuk, .btn-daftar,
.cta-btn-primary, .cta-btn-outline,
label, input, select, textarea {
    font-family: var(--font-ui);
}

@keyframes headerSlideDown {
    from { transform: translateY(-100%); opacity: 0; }
    to   { transform: translateY(0);     opacity: 1; }
}
@keyframes logoSlideIn {
    from { transform: translateX(-32px); opacity: 0; }
    to   { transform: translateX(0);     opacity: 1; }
}
@keyframes logoImgSpin {
    from { transform: rotate(-15deg) scale(0.6); opacity: 0; }
    to   { transform: rotate(0deg)   scale(1);   opacity: 1; }
}
@keyframes logoTextReveal {
    from { clip-path: inset(0 100% 0 0); opacity: 0; }
    to   { clip-path: inset(0 0% 0 0);   opacity: 1; }
}
@keyframes btnSlideIn {
    from { transform: translateX(28px); opacity: 0; }
    to   { transform: translateX(0);    opacity: 1; }
}
@keyframes btnPing {
    0%   { box-shadow: 0 4px 14px rgba(217,79,110,.30); }
    50%  { box-shadow: 0 4px 28px rgba(217,79,110,.65), 0 0 0 6px rgba(217,79,110,.10); }
    100% { box-shadow: 0 4px 14px rgba(217,79,110,.30); }
}
@keyframes borderSweep {
    from { background-position: -100% 0; }
    to   { background-position: 0% 0; }
}

header {
    background: rgba(255,255,255,.97);
    backdrop-filter: blur(12px);
    border-bottom: 1.5px solid var(--border);
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 16px rgba(217,79,110,.08);
    animation: headerSlideDown .55s cubic-bezier(.22,.68,0,1.2) both;
}
header::after {
    content: '';
    position: absolute; bottom: 0; left: 0; right: 0; height: 2px;
    background: linear-gradient(90deg, transparent, var(--pink), var(--accent), var(--pink2), transparent);
    background-size: 200% 100%;
    animation: borderSweep .9s ease .5s both;
}
.header-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 40px;
    height: 68px; display: flex; align-items: center; justify-content: space-between;
}

.logo-wrapper {
    display: flex; align-items: center; gap: 12px;
    text-decoration: none !important;
    animation: logoSlideIn .6s cubic-bezier(.22,.68,0,1.2) .2s both;
}
.logo-wrapper:hover { transform: translateY(-1px); transition: transform .2s ease; }

.logo-img {
    width: 40px; height: 40px; border-radius: 50%;
    object-fit: cover; border: 1px solid var(--border);
    animation: logoImgSpin .7s cubic-bezier(.34,1.56,.64,1) .3s both;
    transition: transform .4s cubic-bezier(.34,1.56,.64,1), border-color .2s, box-shadow .2s;
}
.logo-wrapper:hover .logo-img {
    transform: rotate(10deg) scale(1.08);
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(217,79,110,.15);
}

.logo-text {
    font-family: var(--font-heading);
    font-size: 22px; font-weight: 900;
    color: #1db899b1; letter-spacing: -0.5px;
    animation: logoTextReveal .55s ease .45s both;
    display: inline-block;
}
.logo-text span { color: #ff009db1; }

.logo-wrapper:hover .logo-text {
    background: linear-gradient(90deg, #1db899, #ff009d, #1db899);
    background-size: 200%;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: logoTextReveal .55s ease .45s both, shimmerText 1.2s linear infinite;
}
@keyframes shimmerText {
    0%   { background-position: 200% center; }
    100% { background-position: -200% center; }
}

.auth-btns { display: flex; gap: 8px; align-items: center; }

.btn-masuk {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 500; color: var(--text2);
    padding: 8px 18px; border-radius: 20px;
    border: 1.5px solid var(--border);
    transition: all .25s cubic-bezier(.34,1.56,.64,1);
    animation: btnSlideIn .55s cubic-bezier(.22,.68,0,1.2) .35s both;
    position: relative; overflow: hidden;
}
.btn-masuk::before {
    content: '';
    position: absolute; inset: 0; border-radius: 20px;
    background: linear-gradient(135deg, rgba(255,179,198,.18), rgba(217,79,110,.08));
    transform: scaleX(0); transform-origin: left;
    transition: transform .25s ease;
}
.btn-masuk:hover::before { transform: scaleX(1); }
.btn-masuk:hover {
    border-color: var(--accent); color: var(--accent);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(217,79,110,.15);
}
.btn-masuk:active { transform: translateY(0) scale(.97); }

.btn-daftar {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 600; color: #fff;
    padding: 8px 18px; border-radius: 20px;
    background: linear-gradient(135deg, var(--pink), var(--accent2));
    box-shadow: 0 4px 14px rgba(217,79,110,.30);
    transition: all .25s cubic-bezier(.34,1.56,.64,1);
    animation: btnSlideIn .55s cubic-bezier(.22,.68,0,1.2) .48s both,
               btnPing 2s ease 1.2s 2;
    position: relative; overflow: hidden;
}
.btn-daftar::before {
    content: '';
    position: absolute; top: 0; left: -75%;
    width: 50%; height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,.35), transparent);
    transform: skewX(-20deg);
    transition: left .4s ease;
}
.btn-daftar:hover::before { left: 125%; }
.btn-daftar:hover {
    transform: translateY(-2px) scale(1.04);
    box-shadow: 0 10px 28px rgba(217,79,110,.45);
    color: #fff;
}
.btn-daftar:active { transform: translateY(0) scale(.97); }

.hero {
    min-height: calc(100vh - 68px);
    display: flex; align-items: center;
    position: relative; overflow: hidden;
    background: linear-gradient(135deg, #FFDCE6 0%, #FFB3C6 40%, #FF8FAB 100%);
    z-index: 1;
}
.hero-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,.30) 1px, transparent 1px);
    background-size: 24px 24px; pointer-events: none;
}
.hero-blob { position: absolute; border-radius: 50%; filter: blur(70px); pointer-events: none; }
.hero-blob-1 { width: 500px; height: 500px; background: rgba(255,255,255,.22); top: -120px; right: -100px; }
.hero-blob-2 { width: 350px; height: 350px; background: rgba(217,79,110,.20); bottom: -100px; left: -80px; }
.hero-blob-3 { width: 200px; height: 200px; background: rgba(255,255,255,.15); top: 50%; left: 40%; transform: translate(-50%,-50%); }
.hero-inner {
    max-width: 1280px; margin: 0 auto; padding: 80px 40px;
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 60px; align-items: center;
    position: relative; z-index: 2; width: 100%;
}
.hero-left { animation: heroIn .9s ease both; }
@keyframes heroIn { from { opacity:0; transform:translateY(30px) } to { opacity:1; transform:translateY(0) } }

.hero-badge {
    font-family: var(--font-ui);
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.60); backdrop-filter: blur(8px);
    border: 1px solid rgba(255,255,255,.80);
    border-radius: 20px; padding: 6px 14px;
    font-size: 12px; font-weight: 600; color: var(--accent2);
    margin-bottom: 20px; letter-spacing: .5px;
}
.hero-badge i { font-size: 14px; }

.hero-title {
    font-family: var(--font-heading);
    font-size: clamp(36px, 4.5vw, 56px);
    font-weight: 800;
    line-height: 1.15;
    margin-bottom: 18px;
    color: var(--text);
    letter-spacing: -1px;
    text-shadow: 0 2px 12px rgba(255,255,255,.4);
}
.hero-title em {
    font-style: italic;
    font-weight: 800;
    color: var(--accent2);
}

.hero-sub {
    font-family: var(--font-body);
    font-size: 15px; color: var(--text2);
    line-height: 1.8; margin-bottom: 36px; max-width: 420px;
    font-weight: 400;
}
.hero-actions { display: flex; gap: 14px; flex-wrap: wrap; }
.hero-cta {
    font-family: var(--font-ui);
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--accent); color: #fff;
    padding: 14px 30px; border-radius: 40px;
    font-weight: 700; font-size: 14px;
    box-shadow: 0 8px 24px rgba(217,79,110,.40); transition: all .25s;
}
.hero-cta:hover { transform: translateY(-3px); box-shadow: 0 16px 36px rgba(217,79,110,.50); color: #fff; background: var(--accent2); }
.hero-cta-outline {
    font-family: var(--font-ui);
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.55); backdrop-filter: blur(8px);
    color: var(--text); padding: 14px 28px; border-radius: 40px;
    border: 1.5px solid rgba(255,255,255,.75);
    font-weight: 500; font-size: 14px; transition: all .2s; cursor: pointer;
}
.hero-cta-outline:hover { background: rgba(255,255,255,.75); color: var(--text); }
.hero-cta-outline .bi-arrow-down { transition: transform .3s; }
.hero-cta-outline:hover .bi-arrow-down { transform: translateY(4px); }
.hero-stats { display: flex; gap: 28px; margin-top: 44px; }
.hero-stat-item { text-align: center; }
.hero-stat-num {
    font-family: var(--font-heading);
    font-size: 26px; font-weight: 800; color: var(--accent2); line-height: 1;
}
.hero-stat-label {
    font-family: var(--font-body);
    font-size: 11px; color: var(--text2); margin-top: 3px; font-weight: 700;
    letter-spacing: 0.3px;
}
.hero-stat-divider { width: 1px; background: rgba(217,79,110,.25); align-self: stretch; }

.hero-right { position: relative; height: 460px; animation: heroInRight 1s ease both; }
@keyframes heroInRight { from { opacity:0; transform:translateX(30px) } to { opacity:1; transform:translateX(0) } }
.hero-img-main {
    position: absolute; top: 0; left: 50%; transform: translateX(-50%);
    width: 220px; height: 300px; border-radius: 20px; overflow: hidden;
    box-shadow: 0 24px 60px rgba(217,79,110,.30);
    border: 3px solid rgba(255,255,255,.80);
    animation: floatMain 4s ease-in-out infinite;
}
@keyframes floatMain { 0%,100%{transform:translateX(-50%) translateY(0)} 50%{transform:translateX(-50%) translateY(-12px)} }
.hero-img-main img { width: 100%; height: 100%; object-fit: cover; }
.hero-img-secondary {
    position: absolute; bottom: 30px; left: 0;
    width: 160px; height: 210px; border-radius: 16px; overflow: hidden;
    box-shadow: 0 16px 40px rgba(217,79,110,.22);
    border: 3px solid rgba(255,255,255,.80);
    animation: floatSec 4.5s ease-in-out infinite .5s;
}
@keyframes floatSec { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
.hero-img-secondary img { width: 100%; height: 100%; object-fit: cover; }
.hero-img-third {
    position: absolute; bottom: 20px; right: 0;
    width: 150px; height: 195px; border-radius: 16px; overflow: hidden;
    box-shadow: 0 16px 40px rgba(217,79,110,.22);
    border: 3px solid rgba(255,255,255,.80);
    animation: floatThird 5s ease-in-out infinite 1s;
}
@keyframes floatThird { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-10px)} }
.hero-img-third img { width: 100%; height: 100%; object-fit: cover; }
.hero-float-tag {
    position: absolute; background: rgba(255,255,255,.92);
    backdrop-filter: blur(8px); border-radius: 12px;
    padding: 8px 14px; box-shadow: 0 8px 24px rgba(217,79,110,.18);
    border: 1px solid rgba(255,255,255,.80);
    font-family: var(--font-ui);
    font-size: 12px; font-weight: 600; color: var(--text);
    display: flex; align-items: center; gap: 6px;
    animation: floatTag 3.5s ease-in-out infinite;
    white-space: nowrap; z-index: 3;
}
@keyframes floatTag { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
.hero-float-tag.tag-1 { top: 30px; right: 10px; animation-delay: .3s; }
.hero-float-tag.tag-2 { top: 50%; left: -10px; transform: translateY(-50%); animation-delay: 1s; }
.hero-float-tag .tag-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--accent); flex-shrink: 0; }
.hero-float-tag .tag-dot.green { background: var(--green); }
.waves-container { position: absolute; bottom: -1px; left: 0; width: 100%; overflow: hidden; line-height: 0; z-index: 3; }
.waves { position: relative; width: 100%; height: 12vh; min-height: 70px; max-height: 120px; }
.parallax > use { animation: move-forever 20s cubic-bezier(.55,.5,.45,.5) infinite; }
.parallax > use:nth-child(1) { animation-delay: -2s; animation-duration: 7s; }
.parallax > use:nth-child(2) { animation-delay: -3s; animation-duration: 10s; }
.parallax > use:nth-child(3) { animation-delay: -4s; animation-duration: 13s; }
.parallax > use:nth-child(4) { animation-delay: -5s; animation-duration: 16s; }
@keyframes move-forever { 0%{transform:translate3d(-90px,0,0)} 100%{transform:translate3d(85px,0,0)} }

.cat-bar {
    background: #fff; border-bottom: 1.5px solid var(--border);
    position: sticky; top: 67px; z-index: 99;
    box-shadow: 0 4px 20px rgba(255,179,198,.12);
}
.cat-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 40px;
    height: 64px; display: flex; align-items: center; gap: 10px; overflow-x: auto;
}
.cat-inner::-webkit-scrollbar { display: none; }
.cat-link {
    font-family: var(--font-ui);
    font-size: 14px; font-weight: 500; color: var(--muted);
    padding: 8px 20px; border-radius: 20px; white-space: nowrap;
    transition: all .2s; border: 1px solid transparent; cursor: pointer;
}
.cat-link:hover { color: var(--accent); background: rgba(217,79,110,.06); border-color: var(--border); }
.cat-link.active {
    color: #fff;
    background: linear-gradient(135deg, var(--pink), var(--accent));
    font-weight: 600; border-color: transparent;
    box-shadow: 0 3px 10px rgba(217,79,110,.25);
}

.section {
    max-width: 100%;
    background: #fff;
    margin: 0 auto;
    padding: 48px max(40px, calc((100% - 1280px) / 2 + 40px)) 60px;
    position: relative; z-index: 1;
}
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 28px; }
.section-title {
    font-family: var(--font-heading);
    font-size: 24px; font-weight: 700; color: #1db899b1;
}
.section-title span { color: #ff009db1; }

.product-grid { display: grid; grid-template-columns: repeat(5,minmax(0,1fr)); gap: 20px; }

@keyframes cardIn { from { opacity:0; transform:translateY(18px) } to { opacity:1; transform:translateY(0) } }
.product-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 16px; overflow: hidden;
    transition: transform .25s, box-shadow .25s;
    box-shadow: 0 2px 10px rgba(255,179,198,.15);
    animation: cardIn .45s ease both;
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 44px rgba(217,79,110,.20);
    border-color: var(--accent);
}
.card-img { position: relative; aspect-ratio: 3/4; overflow: hidden; background: var(--surface2); }
.card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.product-card:hover .card-img img { transform: scale(1.07); }
.card-kondisi {
    position: absolute; top: 10px; left: 10px;
    background: rgba(217,79,110,.85); backdrop-filter: blur(6px);
    color: #fff;
    font-family: var(--font-ui);
    font-size: 10px; font-weight: 600;
    letter-spacing: 1px; text-transform: uppercase;
    padding: 4px 10px; border-radius: 20px;
}
.card-body { padding: 14px 16px 16px; }
.card-nama {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 500; line-height: 1.4; margin-bottom: 8px;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.card-harga {
    font-family: var(--font-heading);
    font-size: 15px; font-weight: 700; color: var(--accent);
}
.card-ukuran {
    font-family: var(--font-body);
    font-size: 11px; color: var(--muted); margin-top: 4px;
}

.grid-loading {
    grid-column: 1/-1;
    display: grid; grid-template-columns: repeat(5,minmax(0,1fr)); gap: 20px;
}
.skeleton-card { border-radius: 16px; overflow: hidden; background: var(--surface); border: 1.5px solid var(--border); }
.skeleton-img { aspect-ratio: 3/4; background: linear-gradient(90deg, #FFE4EC 25%, #FFF0F4 50%, #FFE4EC 75%); background-size: 200% 100%; animation: shimmer 1.2s infinite; }
.skeleton-body { padding: 14px 16px 16px; }
.skeleton-line { height: 12px; border-radius: 6px; background: linear-gradient(90deg, #FFE4EC 25%, #FFF0F4 50%, #FFE4EC 75%); background-size: 200% 100%; animation: shimmer 1.2s infinite; margin-bottom: 8px; }
.skeleton-line.short { width: 60%; }
@keyframes shimmer { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

.testi-section { background: linear-gradient(135deg, #FF8FAB 0%, #FFB3C6 50%, #FFD6E0 100%); padding: 72px 40px; position: relative; overflow: hidden; z-index: 1; }
.testi-section::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,.22) 1px, transparent 1px); background-size: 24px 24px; pointer-events: none; }
.testi-section-header { text-align: center; margin-bottom: 48px; position: relative; z-index: 2; }
.testi-section-header h2 {
    font-family: var(--font-heading);
    font-size: clamp(24px, 3vw, 32px); font-weight: 700; color: var(--text);
}
.testi-section-header p {
    font-family: var(--font-body);
    font-size: 14px; color: var(--text2); margin-top: 8px;
}
.testi-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; max-width: 1280px; margin: 0 auto; position: relative; z-index: 2; }
.testi-card { background: rgba(255,255,255,.92); border: 1.5px solid rgba(255,255,255,.65); border-radius: 20px; padding: 28px; position: relative; box-shadow: 0 8px 32px rgba(217,79,110,.10); transition: transform .25s, box-shadow .25s; }
.testi-card:hover { transform: translateY(-4px); box-shadow: 0 20px 48px rgba(217,79,110,.18); }
.testi-quote {
    font-family: var(--font-heading);
    font-size: 56px; line-height: 1; color: var(--accent); opacity: .12;
    font-style: italic; font-weight: 800;
    position: absolute; top: 10px; right: 18px;
}
.testi-stars { display: flex; gap: 3px; margin-bottom: 14px; }
.testi-text {
    font-family: var(--font-body);
    font-size: 13px; line-height: 1.75; margin-bottom: 20px; color: var(--text2);
}
.testi-name {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 600; color: var(--text);
}
.testi-avatar { width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, var(--pink), var(--accent)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; flex-shrink: 0; font-family: var(--font-ui); }

.cta-section { background: linear-gradient(135deg, var(--accent2) 0%, var(--accent) 50%, var(--pink) 100%); padding: 64px 40px; text-align: center; position: relative; overflow: hidden; z-index: 1; }
.cta-section::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle, rgba(255,255,255,.15) 1px, transparent 1px); background-size: 20px 20px; pointer-events: none; }
.cta-inner { position: relative; z-index: 2; max-width: 760px; margin: 0 auto; width: 100%; }
.cta-inner h2 {
    font-family: var(--font-heading);
    font-size: clamp(26px, 3.5vw, 40px); font-weight: 800; color: #fff;
    margin-bottom: 14px; line-height: 1.2; letter-spacing: -0.5px;
}
.cta-inner p {
    font-family: var(--font-body);
    font-size: 15px; color: rgba(255,255,255,.85); margin-bottom: 32px; line-height: 1.7;
}
.cta-btns { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.cta-btn-primary {
    font-family: var(--font-ui);
    display: inline-flex; align-items: center; gap: 8px; background: #fff; color: var(--accent2);
    padding: 14px 32px; border-radius: 40px; font-weight: 700; font-size: 14px;
    box-shadow: 0 8px 28px rgba(0,0,0,.15); transition: all .25s;
}
.cta-btn-primary:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(0,0,0,.22); color: var(--accent2); }
.cta-btn-outline {
    font-family: var(--font-ui);
    display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,.18); color: #fff;
    padding: 14px 32px; border-radius: 40px; border: 1.5px solid rgba(255,255,255,.60);
    font-weight: 500; font-size: 14px; transition: all .2s;
}
.cta-btn-outline:hover { background: rgba(255,255,255,.30); color: #fff; }

footer { background: var(--bg); border-top: 1.5px solid var(--border); position: relative; z-index: 1; }
.footer-inner { max-width: 1280px; margin: 0 auto; padding: 48px 40px 24px; display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 40px; }
.footer-logo {
    font-family: var(--font-heading);
    font-size: 20px; font-weight: 900; display: block; margin-bottom: 10px;
    color: #1db899b1; letter-spacing: -0.5px;
}
.footer-logo span { color: #ff009db1; }
.footer-col h4 {
    font-family: var(--font-ui);
    font-size: 12px; font-weight: 700; letter-spacing: .5px; margin-bottom: 14px; color: var(--text);
}
.footer-links { display: flex; flex-direction: column; gap: 10px; font-size: 13px; font-family: var(--font-body); }
.footer-links a { color: var(--muted); transition: color .2s; }
.footer-links a:hover { color: var(--accent); }
.footer-bottom { max-width: 1280px; margin: 0 auto; padding: 16px 40px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; font-family: var(--font-body); font-size: 13px; }
.footer-socials { display: flex; gap: 10px; }
.footer-socials a { width: 32px; height: 32px; border-radius: 50%; background: var(--surface2); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 14px; color: var(--muted); transition: all .2s; }
.footer-socials a:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

.empty-state { grid-column: 1/-1; text-align: center; padding: 80px 20px; }
.empty-state i { font-size: 3rem; color: var(--border); display: block; margin-bottom: 12px; }
.empty-state p { color: var(--muted); font-family: var(--font-body); font-size: 14px; }

.scroll-flash { position: fixed; inset: 0; z-index: 9999; background: linear-gradient(180deg, #FF8FAB, #FFD6E0); opacity: 0; pointer-events: none; transition: opacity .18s ease; }
.scroll-flash.show { opacity: .18; }

@media (max-width: 1280px) { .product-grid { grid-template-columns: repeat(4,1fr); } .grid-loading { grid-template-columns: repeat(4,1fr); } }
@media (max-width: 1024px) {
    .hero-inner { grid-template-columns: 1fr; text-align: center; padding: 60px 40px 100px; }
    .hero-sub { max-width: 100%; }
    .hero-actions { justify-content: center; }
    .hero-stats { justify-content: center; }
    .hero-right { display: none; }
}
@media (max-width: 768px) {
    .header-inner { padding-left: 16px; padding-right: 16px; }
    .hero-inner { padding: 48px 20px 80px; }
    .hero-stats { gap: 16px; }
    .section { padding: 24px 16px 40px; }
    .product-grid { grid-template-columns: repeat(2,1fr); gap: 14px; }
    .grid-loading { grid-template-columns: repeat(2,1fr); }
    .testi-grid { grid-template-columns: 1fr; }
    .testi-section { padding: 48px 16px; }
    .cta-section { padding: 48px 20px; }
    .footer-inner { grid-template-columns: 1fr 1fr; padding: 32px 16px 16px; }
    .footer-bottom { padding: 14px 16px; flex-direction: column; gap: 8px; text-align: center; }
    .cat-inner { padding: 0 16px; }
    .cat-bar { top: 67px; }
}
@media (max-width: 480px) {
    .product-grid { gap: 10px; }
    .hero-title { font-size: 30px; }
    .hero-stats { flex-wrap: wrap; gap: 12px; }
    .footer-inner { grid-template-columns: 1fr; gap: 24px; }
    .footer-bottom { font-size: 12px; }
    .cta-btns { flex-direction: column; align-items: center; }
}
</style>
</head>
<body>

<div class="scroll-flash" id="scrollFlash"></div>

<header>
    <div class="header-inner">
        <a href="index.php" class="logo-wrapper">
            <!-- ✅ Logo dari DB, bukan hardcoded -->
            <img src="<?= $logo_index_src ?>" class="logo-img" alt="Cloudy Girls"
                 onerror="this.src='https://placehold.co/40x40/FFE4EE/FF4081?text=CG'">
            <span class="logo-text">Cloudy <span>Girls</span></span>
        </a>
        <div class="auth-btns">
            <a href="auth/login.php" class="btn-masuk">Masuk</a>
            <a href="auth/register.php" class="btn-daftar">Daftar</a>
        </div>
    </div>
</header>

<section class="hero">
    <div class="hero-dots"></div>
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>
    <div class="hero-blob hero-blob-3"></div>
    <div class="hero-inner">
        <div class="hero-left">
            <div class="hero-badge"><i class="bi bi-stars"></i> Thrift Fashion Pilihan</div>
            <h1 class="hero-title">Tampil Cantik<br>dengan <em>Harga Hemat</em></h1>
            <p class="hero-sub">Koleksi pakaian wanita berkualitas pilihan — atasan, bawahan, dress, outer, hingga hijab. Temukan outfit favoritmu dengan harga terjangkau.</p>
            <div class="hero-actions">
                <a href="auth/login.php" class="hero-cta"><i class="bi bi-bag-heart"></i> Mulai Belanja</a>
                <a href="#produk" class="hero-cta-outline" id="lihatKoleksi">Lihat Koleksi</a>
            </div>
        </div>
        <div class="hero-right">
            <div class="hero-float-tag tag-1"><span class="tag-dot green"></span> Stok Terbaru Minggu Ini</div>
            <div class="hero-float-tag tag-2"><span class="tag-dot"></span> Free Ongkir Min. Rp 50rb</div>
            <div class="hero-img-main">
                <img src="<?= heroImg($hero_imgs, 0, 'https://placehold.co/440x600/FFD6E0/D94F6E?text=Cloudy+Girls') ?>"
                     alt="<?= escape($hero_imgs[0]['nama_barang'] ?? 'Produk Terbaru') ?>"
                     onerror="this.src='https://placehold.co/440x600/FFD6E0/D94F6E?text=Cloudy+Girls'">
            </div>
            <div class="hero-img-secondary">
                <img src="<?= heroImg($hero_imgs, 1, 'https://placehold.co/320x420/FFB3C6/C0395A?text=Atasan') ?>"
                     alt="<?= escape($hero_imgs[1]['nama_barang'] ?? 'Koleksi Atasan') ?>"
                     onerror="this.src='https://placehold.co/320x420/FFB3C6/C0395A?text=Atasan'">
            </div>
            <div class="hero-img-third">
                <img src="<?= heroImg($hero_imgs, 2, 'https://placehold.co/300x390/FFF0F4/D94F6E?text=Dress') ?>"
                     alt="<?= escape($hero_imgs[2]['nama_barang'] ?? 'Koleksi Dress') ?>"
                     onerror="this.src='https://placehold.co/300x390/FFF0F4/D94F6E?text=Dress'">
            </div>
        </div>
    </div>
    <div class="waves-container">
        <svg class="waves" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
             viewBox="0 24 150 28" preserveAspectRatio="none" shape-rendering="auto">
            <defs><path id="gentle-wave" d="M-160 44c30 0 58-18 88-18s58 18 88 18 58-18 88-18 58 18 88 18v44h-352z"/></defs>
            <g class="parallax">
                <use xlink:href="#gentle-wave" x="48" y="0" fill="rgba(255,240,244,.7)"/>
                <use xlink:href="#gentle-wave" x="48" y="3" fill="rgba(255,240,244,.5)"/>
                <use xlink:href="#gentle-wave" x="48" y="5" fill="rgba(255,240,244,.3)"/>
                <use xlink:href="#gentle-wave" x="48" y="7" fill="#FFF0F4"/>
            </g>
        </svg>
    </div>
</section>

<div class="cat-bar" id="catBar">
    <div class="cat-inner">
        <a href="#" class="cat-link <?= $filter_kategori === '' ? 'active' : '' ?>" data-kategori="">
            <i class="bi bi-grid"></i> Semua
        </a>
        <?php foreach ($kategori_list as $kat): ?>
        <a href="#" class="cat-link <?= $filter_kategori === $kat ? 'active' : '' ?>"
           data-kategori="<?= escape($kat) ?>">
            <?= escape($kat) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="section" id="produk">
    <div class="section-header">
        <div class="section-title" id="produkTitle">
            <?= $filter_kategori
                ? 'Kategori: <span>' . escape($filter_kategori) . '</span>'
                : 'Koleksi <span>Terbaru</span>' ?>
        </div>
        <a href="auth/login.php" style="font-size:13px;color:var(--accent);font-weight:600;display:flex;align-items:center;gap:4px;font-family:var(--font-ui);">
            Lihat Semua <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <div class="product-grid" id="produkGrid">
        <?php
        if ($q_produk && mysqli_num_rows($q_produk) > 0):
            $i = 0;
            while ($row = mysqli_fetch_assoc($q_produk)):
                $i++;
                $col  = ($i - 1) % 5;
                $foto = $row['foto_utama'] ?? $row['foto'] ?? $row['gambar'] ?? $row['image'] ?? '';
        ?>
        <a href="auth/login.php" class="product-card" style="animation-delay:<?= $col * 80 ?>ms">
            <div class="card-img">
                <span class="card-kondisi"><?= escape($row['kondisi'] ?? '') ?></span>
                <img src="uploads/produk/<?= escape($foto) ?>"
                     alt="<?= escape($row['nama_barang'] ?? $row['nama'] ?? '') ?>"
                     onerror="this.src='https://placehold.co/400x500/FFD6E0/D94F6E?text=Cloudy+Girls'"
                     loading="lazy">
            </div>
            <div class="card-body">
                <div class="card-nama"><?= escape($row['nama_barang'] ?? $row['nama'] ?? '') ?></div>
                <div class="card-harga"><?= formatRupiah($row['harga'] ?? 0) ?></div>
                <?php if (!empty($row['ukuran'])): ?>
                <div class="card-ukuran"><i class="bi bi-tag"></i> <?= escape($row['ukuran']) ?></div>
                <?php endif; ?>
            </div>
        </a>
        <?php
            endwhile;
        else:
        ?>
        <div class="empty-state">
            <i class="bi bi-handbag"></i>
            <p>Belum ada produk tersedia.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($ulasan_list)): ?>
<section class="testi-section">
    <div class="testi-section-header" data-aos="fade-up">
        <h2>Kata Pembeli Kami</h2>
        <p>Ribuan pembeli sudah mempercayai Cloudy Girls</p>
    </div>
    <div class="testi-grid">
        <?php foreach ($ulasan_list as $idx => $ul): ?>
        <div class="testi-card" data-aos="fade-up" data-aos-delay="<?= $idx * 100 ?>">
            <div class="testi-quote">"</div>
            <div class="testi-stars"><?= renderStars($ul['rating']) ?></div>
            <p class="testi-text"><?= escape($ul['komentar'] ?? '') ?></p>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="testi-avatar"><?= strtoupper(substr($ul['nama_pembeli'] ?? 'A', 0, 1)) ?></div>
                <div class="testi-name"><?= escape($ul['nama_pembeli'] ?? '') ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="cta-section" data-aos="fade-up">
    <div class="cta-inner">
        <?php if (!empty($toko['maps_url'])): ?>
            <p style="font-size:13px;color:rgba(255,255,255,.80);margin-bottom:10px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;">
                <i class="bi bi-geo-alt-fill"></i> Lokasi Toko Kami
            </p>
            <?php if (!empty($toko['nama_toko'])): ?>
            <h2 style="margin-bottom:8px;"><?= escape($toko['nama_toko']) ?></h2>
            <?php endif; ?>
            <?php if (!empty($toko['alamat'])): ?>
            <p style="margin-bottom:20px;font-size:14px;color:rgba(255,255,255,.85);">
                <i class="bi bi-map"></i> <?= escape($toko['alamat']) ?>
            </p>
            <?php endif; ?>
            <div style="border-radius:20px;overflow:hidden;box-shadow:0 12px 40px rgba(0,0,0,.25);
                margin:0 auto 20px;border:3px solid rgba(255,255,255,.30);
                width:100%;max-width:700px;position:relative;">
                <iframe
                    src="<?= escape($toko['maps_url']) ?>"
                    width="100%" height="320"
                    style="border:0;display:block;width:100%;transform:none;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
            <?php
            $maps_direct = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($toko['alamat'] ?? 'Cloudy Girls');
            if (!empty($toko['maps_link'])) $maps_direct = $toko['maps_link'];
            ?>
            <a href="https://maps.app.goo.gl/zo5cvjjenoCqa7mk6" target="_blank" class="cta-btn-primary">
                <i class="bi bi-geo-alt-fill"></i> Buka di Google Maps
            </a>
        <?php else: ?>
            <h2>Siap Tampil Cantik<br>Hari Ini?</h2>
            <p>Daftar sekarang dan dapatkan akses ke ratusan koleksi thrift pilihan. Harga hemat, kualitas oke!</p>
            <div class="cta-btns">
                <a href="auth/register.php" class="cta-btn-primary"><i class="bi bi-person-plus"></i> Daftar Gratis</a>
                <a href="auth/login.php" class="cta-btn-outline"><i class="bi bi-bag-heart"></i> Masuk &amp; Belanja</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
AOS.init({ duration: 700, easing: 'ease-out-cubic', once: true, offset: 60 });

document.getElementById('lihatKoleksi').addEventListener('click', function(e) {
    e.preventDefault();
    scrollToProduk(true);
});

function scrollToProduk(withFlash) {
    var target = document.getElementById('produk');
    var header = document.querySelector('header');
    var catBar = document.getElementById('catBar');
    if (!target) return;
    if (withFlash) {
        var flash = document.getElementById('scrollFlash');
        flash.classList.add('show');
        setTimeout(function() { flash.classList.remove('show'); }, 320);
    }
    var headerH   = header ? header.offsetHeight : 68;
    var catH      = catBar ? catBar.offsetHeight : 64;
    var targetTop = target.getBoundingClientRect().top + window.scrollY;
    window.scrollTo({ top: targetTop - headerH - catH, behavior: 'smooth' });
}

function skeletonHTML() {
    var cols = window.innerWidth <= 768 ? 2 : (window.innerWidth <= 1280 ? 4 : 5);
    var html = '<div class="grid-loading">';
    for (var i = 0; i < cols; i++) {
        html += '<div class="skeleton-card"><div class="skeleton-img"></div><div class="skeleton-body"><div class="skeleton-line"></div><div class="skeleton-line short"></div></div></div>';
    }
    html += '</div>';
    return html;
}

document.querySelectorAll('.cat-link').forEach(function(link) {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        var kategori = this.getAttribute('data-kategori');
        document.querySelectorAll('.cat-link').forEach(function(l) { l.classList.remove('active'); });
        this.classList.add('active');
        var titleEl = document.getElementById('produkTitle');
        if (kategori) {
            titleEl.innerHTML = 'Kategori: <span>' + kategori + '</span>';
        } else {
            titleEl.innerHTML = 'Koleksi <span>Terbaru</span>';
        }
        var newUrl = kategori
            ? 'index.php?kategori=' + encodeURIComponent(kategori)
            : 'index.php';
        history.pushState({ kategori: kategori }, '', newUrl);
        var grid = document.getElementById('produkGrid');
        grid.innerHTML = skeletonHTML();
        var url = 'index.php?ajax=1' + (kategori ? '&kategori=' + encodeURIComponent(kategori) : '');
        fetch(url)
            .then(function(r) { return r.json(); })
            .then(function(data) { grid.innerHTML = data.html; })
            .catch(function() {
                grid.innerHTML = '<div class="empty-state"><i class="bi bi-wifi-off"></i><p>Gagal memuat produk.</p></div>';
            });
    });
});

window.addEventListener('popstate', function(e) {
    var kategori = (e.state && e.state.kategori) ? e.state.kategori : '';
    var activeLink = document.querySelector('.cat-link[data-kategori="' + kategori + '"]');
    if (activeLink) activeLink.click();
});
</script>

</body>
</html>