<?php
session_start();
require_once 'config/koneksi.php';

// Helper functions
function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $rating ? '#F59E0B' : '#D1D5DB';
        $html .= '<i class="bi bi-star-fill" style="color:' . $color . ';font-size:13px;"></i>';
    }
    return $html;
}

// Ambil pengaturan toko
$q_toko = mysqli_query($conn, "SELECT * FROM pengaturan_toko LIMIT 1");
$toko   = $q_toko ? mysqli_fetch_assoc($q_toko) : [];

// Ambil produk terbaru (status aktif, maks 8)
$filter_kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$where = "status='aktif'";
if ($filter_kategori && $filter_kategori !== 'all') $where .= " AND kategori='$filter_kategori'";
$q_produk = mysqli_query($conn, "SELECT * FROM produk WHERE $where ORDER BY created_at DESC LIMIT 8");

// Ambil ulasan terbaru rating >= 4
$q_ulasan = mysqli_query($conn, "
    SELECT ul.rating, ul.komentar, p.nama AS nama_pembeli
    FROM ulasan ul
    JOIN pembeli p ON p.id = ul.pembeli_id
    WHERE ul.rating >= 4 AND ul.komentar != ''
    ORDER BY ul.created_at DESC LIMIT 3
");
$ulasan_list = $q_ulasan ? mysqli_fetch_all($q_ulasan, MYSQLI_ASSOC) : [];

$kategori_list = ['Atasan','Bawahan','Dress/Gamis','Outer','Hijab & Aksesoris'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,700&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root{--cream:#FAF7F2;--dark:#1C1917;--accent:#A78BFA;--accent2:#7C3AED;--muted:#78716C;--border:#E7E5E4;--white:#FFFFFF;--pink:#F9A8D4;--pink2:#EC4899;}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;color:var(--dark);background:var(--cream);overflow-x:hidden;}
a{text-decoration:none !important;}
/* HEADER */
header{background:var(--white);border-bottom:1px solid var(--border);position:sticky;top:0;z-index:100;}
.header-inner{max-width:1280px;margin:0 auto;padding:0 40px;height:68px;display:flex;align-items:center;justify-content:space-between;}
.logo{font-family:'Playfair Display',serif;font-size:22px;font-weight:900;color:var(--dark);}
.logo span{color:var(--accent2);}
.auth-btns{display:flex;gap:8px;align-items:center;}
.btn-masuk{font-size:13px;font-weight:500;color:var(--muted);padding:8px 18px;border-radius:20px;border:1px solid var(--border);transition:all .2s;}
.btn-masuk:hover{border-color:var(--dark);color:var(--dark);}
.btn-daftar{font-size:13px;font-weight:600;color:#fff;padding:8px 18px;border-radius:20px;background:linear-gradient(135deg,var(--accent2),var(--pink2));transition:all .2s;}
.btn-daftar:hover{opacity:.85;}
/* HERO */
.hero{min-height:560px;display:flex;align-items:center;justify-content:center;text-align:center;position:relative;overflow:hidden;background:linear-gradient(135deg,#1C1917 0%,#2D1B69 50%,#831843 100%);}
.hero-grain{position:absolute;inset:0;opacity:.04;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");background-size:128px;pointer-events:none;}
.hero-content{position:relative;z-index:2;color:#fff;padding:80px 40px;max-width:680px;animation:heroIn .8s ease both;}
@keyframes heroIn{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.hero-eyebrow{display:inline-block;font-size:11px;letter-spacing:3px;text-transform:uppercase;color:var(--pink);background:rgba(249,168,212,.15);border:1px solid rgba(249,168,212,.3);padding:6px 16px;border-radius:20px;margin-bottom:20px;}
.hero-title{font-family:'Playfair Display',serif;font-size:clamp(36px,5vw,64px);font-weight:900;line-height:1.08;margin-bottom:16px;}
.hero-title em{font-style:italic;color:var(--pink);}
.hero-sub{font-size:15px;color:rgba(255,255,255,.7);line-height:1.7;margin-bottom:32px;}
.hero-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap;}
.hero-cta{display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--accent2),var(--pink2));color:#fff;padding:14px 30px;border-radius:40px;font-weight:600;font-size:14px;transition:all .2s;box-shadow:0 8px 24px rgba(124,58,237,.4);}
.hero-cta:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(124,58,237,.5);color:#fff;}
.hero-cta-outline{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);backdrop-filter:blur(8px);color:#fff;padding:14px 30px;border-radius:40px;border:1px solid rgba(255,255,255,.25);font-weight:500;font-size:14px;transition:background .2s;}
.hero-cta-outline:hover{background:rgba(255,255,255,.2);color:#fff;}
/* CATEGORY BAR */
.cat-bar{background:var(--white);border-bottom:1px solid var(--border);position:sticky;top:68px;z-index:99;}
.cat-inner{max-width:1280px;margin:0 auto;padding:0 40px;height:52px;display:flex;align-items:center;gap:6px;overflow-x:auto;}
.cat-inner::-webkit-scrollbar{display:none;}
.cat-link{font-size:13px;font-weight:500;color:var(--muted);padding:6px 16px;border-radius:20px;white-space:nowrap;transition:all .2s;}
.cat-link:hover,.cat-link.active{color:var(--dark);background:var(--cream);}
.cat-link.active{font-weight:600;}
/* PRODUCTS */
.section{max-width:1280px;margin:0 auto;padding:40px 40px 60px;}
.section-title{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;margin-bottom:24px !important;}
.product-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;}
.product-card{background:var(--white);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:transform .25s,box-shadow .25s;}
.product-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(0,0,0,.1);}
.card-img{position:relative;aspect-ratio:3/4;overflow:hidden;background:var(--cream);}
.card-img img{width:100%;height:100%;object-fit:cover;transition:transform .4s;}
.product-card:hover .card-img img{transform:scale(1.06);}
.card-kondisi{position:absolute;top:10px;left:10px;background:rgba(28,25,23,.75);backdrop-filter:blur(6px);color:#fff;font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:4px 10px;border-radius:20px;}
.card-body{padding:14px 16px 16px;}
.card-nama{font-size:14px;font-weight:500;line-height:1.4;margin-bottom:8px;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;}
.card-harga{font-size:15px;font-weight:700;color:var(--accent2);}
.card-ukuran{font-size:11px;color:var(--muted);margin-top:4px;}
/* TESTIMONIAL */
.testi-section{background:var(--white);padding:60px 40px;border-top:1px solid var(--border);}
.testi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;max-width:1280px;margin:0 auto;}
.testi-card{background:var(--cream);border:1px solid var(--border);border-radius:16px;padding:24px;position:relative;}
.testi-quote{font-size:40px;line-height:1;color:var(--accent2);opacity:.2;font-family:'Playfair Display',serif;position:absolute;top:16px;right:20px;}
.testi-stars{display:flex;gap:3px;margin-bottom:12px;}
.testi-text{font-size:13px;line-height:1.7;margin-bottom:16px;color:var(--dark);}
.testi-name{font-size:13px;font-weight:600;}
/* FOOTER */
footer{background:var(--white);border-top:1px solid var(--border);margin-top:60px;}
.footer-inner{max-width:1280px;margin:0 auto;padding:40px 40px 20px;display:grid;grid-template-columns:2fr 1fr 1fr;gap:40px;}
.footer-logo{font-family:'Playfair Display',serif;font-size:20px;font-weight:900;margin-bottom:10px;display:block;}
.footer-logo span{color:var(--accent2);}
.footer-col h4{font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px !important;}
.footer-links{display:flex;flex-direction:column;gap:10px;font-size:13px;}
.footer-links a{color:var(--muted);}
.footer-links a:hover{color:var(--dark);}
.footer-bottom{max-width:1280px;margin:0 auto;padding:16px 40px;border-top:1px solid var(--border);}
/* RESPONSIVE */
@media(max-width:1024px){.product-grid{grid-template-columns:repeat(3,1fr);}}
@media(max-width:768px){
    .header-inner,.section,.footer-inner{padding-left:16px;padding-right:16px;}
    .product-grid{grid-template-columns:repeat(2,1fr);gap:14px;}
    .testi-grid{grid-template-columns:1fr;}
    .testi-section{padding:40px 16px;}
    .footer-inner{grid-template-columns:1fr 1fr;}
    .cat-inner{padding:0 16px;}
}
@media(max-width:480px){.product-grid{grid-template-columns:repeat(2,1fr);gap:10px;}.hero-title{font-size:32px;}}
</style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="header-inner">
        <a href="index.php" class="logo">Cloudy <span>Girls</span></a>
        <div class="auth-btns">
            <a href="auth/login.php" class="btn-masuk">Masuk</a>
<a href="auth/register.php" class="btn-daftar">Daftar </a>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-grain"></div>
    <div class="hero-content">
        <h1 class="hero-title">Tampil Cantik<br>dengan <em>Harga Hemat</em></h1>
        <p class="hero-sub">Koleksi pakaian wanita preloved berkualitas pilihan — atasan, bawahan, dress, outer, hingga hijab.</p>
        <div class="hero-actions">
            <a href="auth/login.php" class="hero-cta">Mulai Belanja <i class="bi bi-arrow-right"></i></a>
            <a href="#produk" class="hero-cta-outline">Lihat Koleksi</a>
        </div>
    </div>
</section>

<!-- CATEGORY BAR -->
<div class="cat-bar">
    <div class="cat-inner">
        <a href="index.php" class="cat-link <?= $filter_kategori === '' ? 'active' : '' ?>">Semua</a>
        <?php foreach ($kategori_list as $kat): ?>
        <a href="index.php?kategori=<?= urlencode($kat) ?>" class="cat-link <?= $filter_kategori === $kat ? 'active' : '' ?>"><?= htmlspecialchars($kat) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- PRODUK -->
<div class="section" id="produk">
    <div class="section-title">
        <?= $filter_kategori ? 'Kategori: ' . htmlspecialchars($filter_kategori) : 'Koleksi Terbaru' ?>
    </div>
    <div class="product-grid">
        <?php if ($q_produk && mysqli_num_rows($q_produk) > 0):
            while ($row = mysqli_fetch_assoc($q_produk)): ?>
        <a href="auth/login.php" class="product-card">
            <div class="card-img">
                <span class="card-kondisi"><?= htmlspecialchars($row['kondisi'] ?? '') ?></span>
                <img src="uploads/produk/<?= htmlspecialchars($row['foto_utama'] ?? '') ?>"
                     alt="<?= htmlspecialchars($row['nama_barang'] ?? '') ?>"
                     onerror="this.src='https://placehold.co/400x500/FAF7F2/A78BFA?text=Cloudy+Girls'"
                     loading="lazy">
            </div>
            <div class="card-body">
                <div class="card-nama"><?= htmlspecialchars($row['nama_barang'] ?? '') ?></div>
                <div class="card-harga"><?= formatRupiah($row['harga']) ?></div>
                <?php if (!empty($row['ukuran'])): ?>
                <div class="card-ukuran"><i class="bi bi-tag"></i> <?= htmlspecialchars($row['ukuran']) ?></div>
                <?php endif; ?>
            </div>
        </a>
        <?php endwhile; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:80px 20px;">
            <i class="bi bi-handbag" style="font-size:3rem;color:var(--border);"></i>
            <p style="margin-top:12px;color:var(--muted);font-size:14px;">Belum ada produk tersedia.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- TESTIMONIAL -->
<?php if (!empty($ulasan_list)): ?>
<section class="testi-section">
    <div style="text-align:center;margin-bottom:40px;">
        <h2 style="font-family:'Playfair Display',serif;font-size:26px;font-weight:700;">Kata Pembeli Kami</h2>
    </div>
    <div class="testi-grid">
        <?php $colors = ['#7C3AED','#EC4899','#A78BFA'];
        foreach ($ulasan_list as $idx => $ul): ?>
        <div class="testi-card">
            <div class="testi-quote">"</div>
            <div class="testi-stars">
                <?= renderStars($ul['rating']) ?>
            </div>
            <p class="testi-text"><?= htmlspecialchars($ul['komentar'] ?? '') ?></p>
            <div style="display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:<?= $colors[$idx % 3] ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;">
                    <?= strtoupper(substr($ul['nama_pembeli'], 0, 1)) ?>
                </div>
                <div class="testi-name"><?= htmlspecialchars($ul['nama_pembeli'] ?? '') ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div>
            <span class="footer-logo">Cloudy <span>Girls</span></span>
            <p style="font-size:13px;color:var(--muted);line-height:1.7;max-width:220px;">Toko preloved pakaian wanita berkualitas dari Banyuwangi.</p>
        </div>
        <div>
            <h4>Kategori</h4>
            <div class="footer-links">
                <?php foreach ($kategori_list as $kat): ?>
                <a href="index.php?kategori=<?= urlencode($kat) ?>"><?= htmlspecialchars($kat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4>Info</h4>
            <div class="footer-links">
                <a href="auth/login.php">Masuk</a>
                <a href="auth/register.php">Daftar</a>
                <a href="auth/login_admin.php">Admin</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p style="font-size:12px;color:var(--muted);">© <?= date('Y') ?> Cloudy Girls — All rights reserved.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>