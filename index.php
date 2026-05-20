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
$q_produk = mysqli_query($conn, "SELECT * FROM produk WHERE $where ORDER BY created_at DESC LIMIT 8");

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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    /* === PINK SOFT GRADIENT PALETTE === */
    /* Diambil dari gambar: pink cerah (#FF8FAB) → soft (#FFB3C6) → pastel (#FFD6E0) */
    --bg:      #FFF0F4;          /* latar halaman, pink pastel sangat lembut */
    --surface: #FFFFFF;          /* kartu / panel putih bersih */
    --surface2:#FFF0F4;          /* surface sekunder, sedikit pink */
    --border:  #FFB3C6;          /* border: pink soft tengah gradient */
    --accent:  #D94F6E;          /* aksen utama: pink tua elegan */
    --accent2: #C0395A;          /* aksen hover/deep */
    --pink:    #FF8FAB;          /* pink cerah (warna atas gradient) */
    --pink2:   #FFB3C6;          /* pink soft (warna tengah gradient) */
    --muted:   #C48899;          /* teks abu-abu bernuansa pink */
    --text:    #2D1520;          /* teks utama, gelap kecoklatan */
    --text2:   #5C3244;          /* teks sekunder */
    --yellow:  #E8956D;          /* bintang rating: peach-oranye lembut */
    --green:   #5BAF9E;          /* whatsapp / CTA hijau soft */
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; color: var(--text); background: var(--bg); overflow-x: hidden; }
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

/* HEADER */
header {
    background: rgba(255,255,255,.97);
    backdrop-filter: blur(12px);
    border-bottom: 1.5px solid var(--border);
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 2px 16px rgba(255,143,171,.10);
}
.header-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 40px;
    height: 68px; display: flex; align-items: center; justify-content: space-between;
}
.logo { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 900; color: var(--text); }
.logo span { color: var(--accent); }
.auth-btns { display: flex; gap: 8px; align-items: center; }
.btn-masuk {
    font-size: 13px; font-weight: 500; color: var(--text2);
    padding: 8px 18px; border-radius: 20px;
    border: 1.5px solid var(--border); transition: all .2s;
}
.btn-masuk:hover { border-color: var(--accent); color: var(--accent); }
.btn-daftar {
    font-size: 13px; font-weight: 600; color: #fff;
    padding: 8px 18px; border-radius: 20px;
    background: linear-gradient(135deg, var(--pink), var(--accent2));
    box-shadow: 0 4px 14px rgba(217,79,110,.30);
    transition: opacity .2s;
}
.btn-daftar:hover { opacity: .88; color: #fff; }

/* HERO — menggunakan gradien persis seperti gambar */
.hero {
    min-height: 560px;
    display: flex; align-items: center; justify-content: center;
    text-align: center; position: relative; overflow: hidden;
    background: linear-gradient(180deg, #FF8FAB 0%, #FFB3C6 55%, #FFD6E0 100%);
    z-index: 1;
}
.hero-dots {
    position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,.30) 1px, transparent 1px);
    background-size: 24px 24px; pointer-events: none;
}
.hero-blob { position: absolute; border-radius: 50%; filter: blur(60px); pointer-events: none; }
.hero-blob-1 { width: 400px; height: 400px; background: rgba(255,255,255,.20); top: -100px; right: -80px; }
.hero-blob-2 { width: 300px; height: 300px; background: rgba(255,143,171,.25); bottom: -80px; left: -60px; }
.hero-content {
    position: relative; z-index: 2; color: #fff;
    padding: 80px 40px; max-width: 680px;
    animation: heroIn .8s ease both;
}
@keyframes heroIn { from { opacity:0; transform:translateY(24px) } to { opacity:1; transform:translateY(0) } }
.hero-eyebrow {
    display: inline-block; font-size: 11px; letter-spacing: 3px;
    text-transform: uppercase; color: var(--text);
    background: rgba(255,255,255,.45); border: 1px solid rgba(255,255,255,.60);
    padding: 6px 16px; border-radius: 20px; margin-bottom: 20px;
}
.hero-title {
    font-family: 'Playfair Display', serif;
    font-size: clamp(36px, 5vw, 62px);
    font-weight: 900; line-height: 1.08; margin-bottom: 16px;
    color: var(--text);
    text-shadow: 0 2px 12px rgba(255,255,255,.5);
}
.hero-title em { font-style: italic; color: var(--accent2); }
.hero-sub { font-size: 15px; color: var(--text2); line-height: 1.7; margin-bottom: 32px; }
.hero-actions { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; }
.hero-cta {
    display: inline-flex; align-items: center; gap: 8px;
    background: var(--accent); color: #fff;
    padding: 14px 30px; border-radius: 40px;
    font-weight: 700; font-size: 14px;
    box-shadow: 0 8px 24px rgba(217,79,110,.35); transition: all .2s;
}
.hero-cta:hover { transform: translateY(-2px); box-shadow: 0 14px 32px rgba(217,79,110,.45); color: #fff; background: var(--accent2); }
.hero-cta-outline {
    display: inline-flex; align-items: center; gap: 8px;
    background: rgba(255,255,255,.50); backdrop-filter: blur(8px);
    color: var(--text); padding: 14px 30px; border-radius: 40px;
    border: 1.5px solid rgba(255,255,255,.70);
    font-weight: 500; font-size: 14px; transition: background .2s;
}
.hero-cta-outline:hover { background: rgba(255,255,255,.70); color: var(--text); }

/* STATS STRIP */
.stats-strip { background: var(--surface); border-bottom: 1.5px solid var(--border); position: relative; z-index: 2; }
.stats-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 40px;
    display: flex; align-items: center; justify-content: center;
}
.stat-item {
    display: flex; align-items: center; gap: 12px;
    padding: 18px 40px; flex: 1; max-width: 260px;
    border-right: 1px solid var(--border);
}
.stat-item:last-child { border-right: none; }
.stat-icon-wrap {
    width: 40px; height: 40px; border-radius: 10px;
    background: var(--surface2); border: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: var(--accent); flex-shrink: 0;
}
.stat-text .val { font-size: 18px; font-weight: 700; color: var(--text); }
.stat-text .lbl { font-size: 11px; color: var(--muted); }

/* CATEGORY BAR */
.cat-bar {
    background: #FFF5F8;
    border-bottom: 1.5px solid var(--border);
    position: sticky; top: 68px; z-index: 99;
}
.cat-inner {
    max-width: 1280px; margin: 0 auto; padding: 0 40px;
    height: 52px; display: flex; align-items: center; gap: 6px; overflow-x: auto;
}
.cat-inner::-webkit-scrollbar { display: none; }
.cat-link {
    font-size: 13px; font-weight: 500; color: var(--muted);
    padding: 6px 16px; border-radius: 20px; white-space: nowrap;
    transition: all .2s; border: 1px solid transparent;
}
.cat-link:hover { color: var(--accent); background: rgba(217,79,110,.06); border-color: var(--border); }
.cat-link.active {
    color: #fff;
    background: linear-gradient(135deg, var(--pink), var(--accent));
    font-weight: 600; border-color: transparent;
    box-shadow: 0 3px 10px rgba(217,79,110,.25);
}

/* SECTION */
.section { max-width: 1280px; margin: 0 auto; padding: 40px 40px 60px; position: relative; z-index: 1; }
.section-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; }
.section-title { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; }
.section-title span { color: var(--accent); }

/* PRODUCT GRID */
.product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.product-card {
    background: var(--surface); border: 1.5px solid var(--border);
    border-radius: 14px; overflow: hidden;
    transition: transform .25s, box-shadow .25s;
    box-shadow: 0 2px 10px rgba(255,179,198,.15);
}
.product-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(217,79,110,.18);
    border-color: var(--accent);
}
.card-img { position: relative; aspect-ratio: 3/4; overflow: hidden; background: var(--surface2); }
.card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.product-card:hover .card-img img { transform: scale(1.06); }
.card-kondisi {
    position: absolute; top: 10px; left: 10px;
    background: rgba(217,79,110,.82); backdrop-filter: blur(6px);
    color: #fff; font-size: 10px; font-weight: 600;
    letter-spacing: 1px; text-transform: uppercase;
    padding: 4px 10px; border-radius: 20px;
}
.card-body { padding: 14px 16px 16px; }
.card-nama {
    font-size: 14px; font-weight: 500; line-height: 1.4; margin-bottom: 8px;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
}
.card-harga { font-size: 15px; font-weight: 700; color: var(--accent); }
.card-ukuran { font-size: 11px; color: var(--muted); margin-top: 4px; }

/* TESTIMONIAL */
.testi-section {
    /* gradient persis mengikuti gambar: atas gelap → bawah lembut */
    background: linear-gradient(180deg, #FF8FAB 0%, #FFB3C6 55%, #FFD6E0 100%);
    padding: 64px 40px; position: relative; overflow: hidden; z-index: 1;
}
.testi-section::before {
    content: ''; position: absolute; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,.22) 1px, transparent 1px);
    background-size: 24px 24px; pointer-events: none;
}
.testi-section-header { text-align: center; margin-bottom: 40px; position: relative; z-index: 2; }
.testi-section-header h2 { font-family: 'Playfair Display', serif; font-size: 28px; font-weight: 700; color: var(--text); }
.testi-section-header p { font-size: 14px; color: var(--text2); margin-top: 6px; }
.testi-grid {
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 24px; max-width: 1280px; margin: 0 auto; position: relative; z-index: 2;
}
.testi-card {
    background: rgba(255,255,255,.90); border: 1.5px solid rgba(255,255,255,.60);
    border-radius: 16px; padding: 24px; position: relative;
    box-shadow: 0 8px 32px rgba(217,79,110,.10);
}
.testi-quote {
    font-size: 48px; line-height: 1; color: var(--accent); opacity: .15;
    font-family: 'Playfair Display', serif; position: absolute; top: 12px; right: 18px;
}
.testi-stars { display: flex; gap: 3px; margin-bottom: 12px; }
.testi-text { font-size: 13px; line-height: 1.7; margin-bottom: 16px; color: var(--text2); }
.testi-name { font-size: 13px; font-weight: 600; color: var(--text); }
.testi-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: linear-gradient(135deg, var(--pink), var(--accent));
    color: #fff; display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; flex-shrink: 0;
}

/* FOOTER */
footer { background: var(--surface); border-top: 1.5px solid var(--border); position: relative; z-index: 1; }
.footer-inner {
    max-width: 1280px; margin: 0 auto; padding: 48px 40px 24px;
    display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 40px;
}
.footer-logo { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 900; display: block; margin-bottom: 10px; }
.footer-logo span { color: var(--accent); }
.footer-col h4 { font-size: 12px; font-weight: 700; letter-spacing: .5px; margin-bottom: 14px; color: var(--text); }
.footer-links { display: flex; flex-direction: column; gap: 10px; font-size: 13px; }
.footer-links a { color: var(--muted); transition: color .2s; }
.footer-links a:hover { color: var(--accent); }
.footer-bottom {
    max-width: 1280px; margin: 0 auto; padding: 16px 40px;
    border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
}
.footer-socials { display: flex; gap: 10px; }
.footer-socials a {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--surface2); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--muted); transition: all .2s;
}
.footer-socials a:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

/* EMPTY */
.empty-state { grid-column: 1/-1; text-align: center; padding: 80px 20px; }
.empty-state i { font-size: 3rem; color: var(--border); display: block; margin-bottom: 12px; }
.empty-state p { color: var(--muted); font-size: 14px; }

/* RESPONSIVE */
@media (max-width: 1280px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
    .header-inner, .section, .footer-inner { padding-left: 16px; padding-right: 16px; }
    .product-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .testi-grid { grid-template-columns: 1fr; }
    .testi-section { padding: 40px 16px; }
    .footer-inner { grid-template-columns: 1fr 1fr; padding: 32px 16px 16px; }
    .cat-inner { padding: 0 16px; }
    .stats-inner { flex-wrap: wrap; }
    .stat-item { border-right: none; border-bottom: 1px solid var(--border); max-width: 100%; padding: 14px 20px; }
    .footer-bottom { padding: 14px 16px; flex-direction: column; gap: 10px; }
}
@media (max-width: 480px) {
    .product-grid { gap: 10px; }
    .hero-title { font-size: 32px; }
}
</style>
</head>
<body>

<!-- HEADER -->
<header>
    <div class="header-inner">
        <a href="index.php" class="logo">Cloudy <span>Girls</span></a>
        <div class="auth-btns">
            <a href="auth/login.php"    class="btn-masuk">Masuk</a>
            <a href="auth/register.php" class="btn-daftar">Daftar</a>
        </div>
    </div>
</header>

<!-- HERO -->
<section class="hero">
    <div class="hero-dots"></div>
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>
    <div class="hero-content">
        <h1 class="hero-title">Tampil Cantik<br>dengan <em>Harga Hemat</em></h1>
        <p class="hero-sub">Koleksi pakaian wanita berkualitas pilihan — atasan, bawahan, dress, outer, hingga hijab.</p>
        <div class="hero-actions">
            <a href="auth/login.php" class="hero-cta"><i class="bi bi-bag-heart"></i> Mulai Belanja</a>
            <a href="#produk" class="hero-cta-outline">Lihat Koleksi <i class="bi bi-arrow-down"></i></a>
        </div>
    </div>
</section>

<!-- CATEGORY BAR -->
<div class="cat-bar">
    <div class="cat-inner">
        <a href="index.php" class="cat-link <?= $filter_kategori === '' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i> Semua
        </a>
        <?php foreach ($kategori_list as $kat): ?>
        <a href="index.php?kategori=<?= urlencode($kat) ?>"
           class="cat-link <?= $filter_kategori === $kat ? 'active' : '' ?>">
            <?= escape($kat) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- PRODUK -->
<div class="section" id="produk">
    <div class="section-header">
        <div class="section-title">
            <?= $filter_kategori
                ? 'Kategori: <span>' . escape($filter_kategori) . '</span>'
                : 'Koleksi <span>Terbaru</span>' ?>
        </div>
        <a href="auth/login.php" style="font-size:13px;color:var(--accent);font-weight:600;display:flex;align-items:center;gap:4px;">
            Lihat Semua <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="product-grid">
        <?php if ($q_produk && mysqli_num_rows($q_produk) > 0):
            while ($row = mysqli_fetch_assoc($q_produk)): ?>
        <a href="auth/login.php" class="product-card">
            <div class="card-img">
                <span class="card-kondisi"><?= escape($row['kondisi'] ?? '') ?></span>
                <img src="uploads/produk/<?= escape($row['foto_utama'] ?? '') ?>"
                     alt="<?= escape($row['nama_barang'] ?? '') ?>"
                     onerror="this.src='https://placehold.co/400x500/FFD6E0/D94F6E?text=Cloudy+Girls'"
                     loading="lazy">
            </div>
            <div class="card-body">
                <div class="card-nama"><?= escape($row['nama_barang'] ?? '') ?></div>
                <div class="card-harga"><?= formatRupiah($row['harga']) ?></div>
                <?php if (!empty($row['ukuran'])): ?>
                <div class="card-ukuran"><i class="bi bi-tag"></i> <?= escape($row['ukuran']) ?></div>
                <?php endif; ?>
            </div>
        </a>
        <?php endwhile; else: ?>
        <div class="empty-state">
            <i class="bi bi-handbag"></i>
            <p>Belum ada produk tersedia.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- TESTIMONIAL -->
<?php if (!empty($ulasan_list)): ?>
<section class="testi-section">
    <div class="testi-section-header">
        <h2>Kata Pembeli Kami</h2>
        <p>Ribuan pembeli sudah mempercayai Cloudy Girls</p>
    </div>
    <div class="testi-grid">
        <?php foreach ($ulasan_list as $ul): ?>
        <div class="testi-card">
            <div class="testi-quote">"</div>
            <div class="testi-stars"><?= renderStars($ul['rating']) ?></div>
            <p class="testi-text"><?= escape($ul['komentar'] ?? '') ?></p>
            <div style="display:flex;align-items:center;gap:10px;">
                <div class="testi-avatar"><?= strtoupper(substr($ul['nama_pembeli'], 0, 1)) ?></div>
                <div class="testi-name"><?= escape($ul['nama_pembeli'] ?? '') ?></div>
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
            <p style="font-size:13px;color:var(--muted);line-height:1.7;max-width:220px;">
                Toko preloved pakaian wanita berkualitas dari Banyuwangi.
            </p>
            <?php if (!empty($toko['no_hp'])): ?>
            <a href="https://wa.me/<?= escape($toko['no_hp']) ?>"
               style="display:inline-flex;align-items:center;gap:6px;margin-top:14px;font-size:13px;color:var(--green);font-weight:600;">
                <i class="bi bi-whatsapp"></i> Hubungi Kami
            </a>
            <?php endif; ?>
        </div>
        <div>
            <h4>Kategori</h4>
            <div class="footer-links">
                <?php foreach ($kategori_list as $kat): ?>
                <a href="index.php?kategori=<?= urlencode($kat) ?>"><?= escape($kat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4>Info</h4>
            <div class="footer-links">
                <a href="auth/login.php">Masuk</a>
                <a href="auth/register.php">Daftar</a>
                <a href="auth/login_admin.php">Admin</a>
                <?php if (!empty($toko['instagram'])): ?>
                <a href="https://instagram.com/<?= ltrim(escape($toko['instagram']), '@') ?>" target="_blank">
                    <i class="bi bi-instagram"></i> <?= escape($toko['instagram']) ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p style="font-size:12px;color:var(--muted);">© <?= date('Y') ?> Cloudy Girls — All rights reserved.</p>
        <div class="footer-socials">
            <?php if (!empty($toko['instagram'])): ?>
            <a href="https://instagram.com/<?= ltrim(escape($toko['instagram']), '@') ?>" target="_blank">
                <i class="bi bi-instagram"></i>
            </a>
            <?php endif; ?>
            <?php if (!empty($toko['no_hp'])): ?>
            <a href="https://wa.me/<?= escape($toko['no_hp']) ?>" target="_blank">
                <i class="bi bi-whatsapp"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</footer>

</body>
</html>