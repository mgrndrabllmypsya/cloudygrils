<?php
session_name('session_pembeli');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
function formatRupiah($angka) { return 'Rp ' . number_format($angka, 0, ',', '.'); }

$user_id = $_SESSION['user_id'];
$q_user  = mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$user_id LIMIT 1");
$user    = mysqli_fetch_assoc($q_user);

$filter_kategori = isset($_GET['kategori']) ? mysqli_real_escape_string($conn, $_GET['kategori']) : '';
$cari            = isset($_GET['cari'])     ? mysqli_real_escape_string($conn, $_GET['cari'])     : '';

$where = ["status='aktif'"];
if ($filter_kategori && $filter_kategori !== 'all') $where[] = "kategori='$filter_kategori'";
if ($cari) $where[] = "nama_barang LIKE '%$cari%'";

$sql_produk   = "SELECT * FROM produk WHERE " . implode(' AND ', $where) . " ORDER BY created_at DESC";
$q_produk     = mysqli_query($conn, $sql_produk);
$total_produk = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM produk WHERE status='aktif'"))[0] ?? 0;
$kategori_list = ['Atasan','Bawahan','Dress/Gamis','Outer','Hijab & Aksesoris'];

$wishlist_ids = [];
$q_wl = mysqli_query($conn, "SELECT produk_id FROM wishlist WHERE pembeli_id=$user_id");
if ($q_wl) while ($w = mysqli_fetch_assoc($q_wl)) $wishlist_ids[] = (int)$w['produk_id'];

$like_count = [];
$q_lc = mysqli_query($conn, "SELECT produk_id, COUNT(*) as c FROM wishlist GROUP BY produk_id");
if ($q_lc) while ($lc = mysqli_fetch_assoc($q_lc)) $like_count[(int)$lc['produk_id']] = (int)$lc['c'];

$q_toko = mysqli_query($conn, "SELECT * FROM pengaturan_toko LIMIT 1");
$toko   = $q_toko ? mysqli_fetch_assoc($q_toko) : [];

$page_title = 'Beranda — Cloudy Girls';
include '../includes/header.php';
?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&family=Lato:ital,wght@0,300;0,400;0,700;1,400&display=swap');

:root {
    --bg:      #FFF0F4;
    --surface: #FFFFFF;
    --surface2:#FFF5F8;
    --border:  #FFB3C6;
    --accent:  #D94F6E;
    --accent2: #C43860;
    --pink:    #FF8FAB;
    --pink2:   #FFB3C6;
    --pink3:   #FFD6E0;
    --muted:   #C48899;
    --text:    #2D1520;
    --text2:   #6B3A4A;
    --yellow:  #FFB300;
    --red:     #D94F6E;
    --green:   #00BFA5;

    --font-heading: 'Poppins', sans-serif;
    --font-body:    'Lato', sans-serif;
    --font-ui:      'Poppins', sans-serif;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: var(--font-body);
    color: var(--text);
    background: var(--bg);
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

/* ── Heading & UI elements ── */
h1, h2, h3, h4, h5, h6 {
    font-family: var(--font-heading);
    font-weight: 700;
}
button, .btn,
.cat-link, .search-btn,
input, select, textarea {
    font-family: var(--font-ui);
}

/* ── CATEGORY BAR ── */
.cat-bar {
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(10px);
    border-bottom: 1.5px solid var(--border);
    position: sticky; top: 64px; z-index: 99;
    width: 100%;
}
.cat-inner {
    max-width: 1280px; margin: 0 auto;
    padding: 0 clamp(12px, 4vw, 40px);
    height: 52px;
    display: flex; align-items: center; gap: 6px;
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    scroll-snap-type: x mandatory;
}
.cat-inner::-webkit-scrollbar { display: none; }
.cat-link {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 500; color: var(--muted);
    padding: 6px 14px; border-radius: 20px;
    white-space: nowrap; transition: all .2s;
    border: 1px solid transparent;
    scroll-snap-align: start;
    flex-shrink: 0;
}
.cat-link:hover {
    color: var(--accent);
    background: rgba(217,79,110,.06);
    border-color: var(--border);
}
.cat-link.active {
    color: #fff;
    background: #FF6FA3;
    font-weight: 600; border-color: transparent;
    box-shadow: 0 3px 10px rgba(255,111,163,.35);
}

/* ── SEARCH ── */
.search-bar {
    max-width: 1280px; margin: 0 auto;
    padding: 20px clamp(12px, 4vw, 40px) 0;
    position: relative; z-index: 1;
}
.search-form { display: flex; gap: 8px; }
.search-input {
    flex: 1; min-width: 0;
    padding: 11px 16px;
    border: 2px solid #F48FB1; border-radius: 40px;
    font-family: var(--font-body);
    font-size: 14px;
    outline: none; transition: border-color .2s, box-shadow .2s;
    background: #FFF0F4;
    color: var(--text);
}
.search-input:focus {
    border-color: #FF6FA3;
    box-shadow: 0 0 0 4px rgba(255,111,163,.15);
    background: #FFFFFF;
}
.search-input::placeholder { color: #D4809A; }
.search-btn {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 600;
    padding: 11px 20px;
    background: #FF6FA3;
    color: #fff; border: none; border-radius: 40px;
    cursor: pointer;
    transition: background .2s, transform .15s, box-shadow .2s;
    box-shadow: 0 4px 14px rgba(255,111,163,.40);
    white-space: nowrap; flex-shrink: 0;
    display: flex; align-items: center; gap: 6px;
}
.search-btn:hover { background: #FF4F90; transform: translateY(-1px); }
@media (max-width: 360px) {
    .search-btn .btn-label { display: none; }
    .search-btn { padding: 11px 14px; }
}

/* ── SECTION ── */
.section {
    max-width: 1280px; margin: 0 auto;
    padding: 24px clamp(12px, 4vw, 40px) 60px;
    position: relative; z-index: 1;
}
.section-head {
    display: flex; align-items: center;
    justify-content: space-between; margin-bottom: 18px;
    gap: 8px;
}
.section-title {
    font-family: var(--font-heading);
    font-size: clamp(17px, 3vw, 22px);
    font-weight: 700; color: #1db899b1;
    flex-shrink: 0;
}
.section-title span { color: #ff009db1; }
.section-count {
    font-family: var(--font-body);
    font-size: 12px; color: var(--muted); text-align: right;
}

/* ── PRODUCT GRID ── */
.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(min(180px, 100%), 1fr));
    gap: 16px;
}
@media (min-width: 1200px) { .product-grid { grid-template-columns: repeat(5, 1fr); } }
@media (min-width: 960px)  and (max-width: 1199px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }
@media (min-width: 640px)  and (max-width: 959px)  { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 639px) {
    .product-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
}

/* ── PRODUCT CARD ── */
.product-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 16px; overflow: hidden;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    position: relative; display: block; color: var(--text);
    box-shadow: 0 2px 10px rgba(255,143,171,.08);
}
@media (hover: hover) {
    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 48px rgba(255,111,163,.18);
        border-color: var(--pink);
    }
    .product-card:hover .card-img img { transform: scale(1.06); }
}
@media (hover: none) {
    .product-card:active { opacity: .85; }
}
.card-img { position: relative; aspect-ratio: 3/4; overflow: hidden; background: var(--surface2); }
.card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.card-kondisi {
    position: absolute; top: 8px; left: 8px;
    background: rgba(255,111,163,.88); backdrop-filter: blur(6px);
    color: #fff;
    font-family: var(--font-ui);
    font-size: 9px; font-weight: 600;
    letter-spacing: .8px; text-transform: uppercase;
    padding: 3px 8px; border-radius: 20px;
}

/* ── LOVE BUTTON ── */
.btn-love {
    position: absolute; top: 8px; right: 8px;
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(255,255,255,.95); backdrop-filter: blur(6px);
    border: 1.5px solid var(--border);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: var(--muted);
    transition: all .2s; z-index: 2;
    box-shadow: 0 2px 10px rgba(255,143,171,.18);
    text-decoration: none !important;
    -webkit-tap-highlight-color: transparent;
}
@media (max-width: 639px) {
    .btn-love { width: 32px; height: 32px; font-size: 14px; }
}
.btn-love:hover { transform: scale(1.18); border-color: var(--pink); color: var(--red); }
.btn-love.liked { color: var(--red); border-color: rgba(217,79,110,.3); }

/* ── CARD BODY ── */
.card-body { padding: 10px 12px 12px; }
@media (min-width: 640px) { .card-body { padding: 14px 16px 16px; } }

.card-nama {
    font-family: var(--font-ui);
    font-size: 13px; font-weight: 500; line-height: 1.4; margin-bottom: 6px;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    color: var(--text);
}
@media (min-width: 640px) { .card-nama { font-size: 14px; } }

.card-harga {
    font-family: var(--font-heading);
    font-size: 14px; font-weight: 700; color: var(--accent);
}
@media (min-width: 640px) { .card-harga { font-size: 15px; } }

.card-meta-row {
    display: flex; align-items: center;
    justify-content: space-between; margin-top: 5px;
}
.card-ukuran {
    font-family: var(--font-body);
    font-size: 11px; color: var(--muted);
}
.card-likes {
    font-family: var(--font-body);
    font-size: 11px; color: var(--muted);
    display: flex; align-items: center; gap: 3px;
}
.card-likes i { color: var(--red); font-size: 11px; }

/* ── EMPTY STATE ── */
.empty-state { grid-column: 1/-1; text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 3rem; color: var(--border); display: block; margin-bottom: 12px; }
.empty-state p {
    font-family: var(--font-body);
    color: var(--muted); font-size: 14px; margin-bottom: 12px;
}
.empty-state a {
    font-family: var(--font-ui);
    color: var(--accent); font-weight: 600; font-size: 13px;
}

/* ── FOOTER ── */
footer {
    background: var(--surface);
    border-top: 1.5px solid var(--border);
    margin-top: 0; position: relative; z-index: 1;
}
.footer-inner {
    max-width: 1280px; margin: 0 auto;
    padding: 36px clamp(12px, 4vw, 40px) 20px;
    display: grid;
    grid-template-columns: 2fr 1fr 1fr;
    gap: 32px;
}
.footer-logo {
    font-family: var(--font-heading);
    font-size: 20px; font-weight: 900;
    display: block; margin-bottom: 10px;
    color: #1db899b1;
}
.footer-logo span { color: #ff009db1; }
.footer-col h4 {
    font-family: var(--font-ui);
    font-size: 12px; font-weight: 700;
    letter-spacing: .5px; margin-bottom: 14px;
    color: var(--text);
}
.footer-links {
    display: flex; flex-direction: column; gap: 10px;
    font-family: var(--font-body);
    font-size: 13px;
}
.footer-links a { color: var(--muted); transition: color .2s; }
.footer-links a:hover { color: var(--accent); }
.footer-bottom {
    max-width: 1280px; margin: 0 auto;
    padding: 16px clamp(12px, 4vw, 40px);
    border-top: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 10px;
    font-family: var(--font-body);
    font-size: 13px;
}
.footer-socials { display: flex; gap: 10px; }
.footer-socials a {
    width: 32px; height: 32px; border-radius: 50%;
    background: var(--surface2); border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: var(--muted); transition: all .2s;
}
.footer-socials a:hover { background: #FF6FA3; border-color: #FF6FA3; color: #fff; }

/* ── RESPONSIVE: TABLET ── */
@media (max-width: 768px) {
    .footer-inner { grid-template-columns: 1fr 1fr; }
    .cat-bar { top: 56px; }
}

/* ── RESPONSIVE: MOBILE ── */
@media (max-width: 480px) {
    .cat-bar { top: 52px; }
    .footer-inner {
        grid-template-columns: 1fr;
        gap: 24px;
        padding-bottom: 8px;
    }
    .footer-bottom { justify-content: center; text-align: center; }
    .section-head { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>

<!-- CATEGORY BAR -->
<div class="cat-bar">
    <div class="cat-inner">
        <a href="home.php" class="cat-link <?= $filter_kategori === '' ? 'active' : '' ?>">
            <i class="bi bi-grid"></i> Semua
        </a>
        <?php foreach ($kategori_list as $kat): ?>
        <a href="home.php?kategori=<?= urlencode($kat) ?>"
           class="cat-link <?= $filter_kategori === $kat ? 'active' : '' ?>">
            <?= escape($kat) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- SEARCH -->
<div class="search-bar">
    <form class="search-form" method="GET" action="home.php">
        <?php if ($filter_kategori): ?>
        <input type="hidden" name="kategori" value="<?= escape($filter_kategori) ?>">
        <?php endif; ?>
        <input type="text" name="cari" class="search-input"
               placeholder="Cari pakaian..." value="<?= escape($cari) ?>">
        <button type="submit" class="search-btn">
            <i class="bi bi-search"></i> <span class="btn-label">Cari</span>
        </button>
    </form>
</div>

<!-- PRODUCTS -->
<div class="section" id="produk">
    <div class="section-head">
        <div class="section-title">
            <?php
            if ($cari)                echo 'Hasil: <span>"' . escape($cari) . '"</span>';
            elseif ($filter_kategori) echo 'Kategori: <span>' . escape($filter_kategori) . '</span>';
            else                      echo 'Koleksi <span>Terbaru</span>';
            ?>
        </div>
        <span class="section-count"><?= $total_produk ?> produk tersedia</span>
    </div>

    <div class="product-grid">
        <?php if ($q_produk && mysqli_num_rows($q_produk) > 0):
            while ($row = mysqli_fetch_assoc($q_produk)):
                $pid   = (int)$row['id'];
                $liked = in_array($pid, $wishlist_ids);
                $likes = $like_count[$pid] ?? 0;
        ?>
        <div class="product-card">
            <!-- LOVE BUTTON -->
            <a href="wishlist_toggle.php?produk_id=<?= $pid ?>&kembali=home.php<?= $filter_kategori ? urlencode('?kategori='.$filter_kategori) : '' ?>"
               class="btn-love <?= $liked ? 'liked' : '' ?>"
               title="<?= $liked ? 'Hapus dari wishlist' : 'Tambah ke wishlist' ?>">
                <i class="bi <?= $liked ? 'bi-heart-fill' : 'bi-heart' ?>"></i>
            </a>

            <a href="detail.php?id=<?= $pid ?>">
                <div class="card-img">
                    <span class="card-kondisi"><?= escape($row['kondisi'] ?? '') ?></span>
                    <img src="../uploads/produk/<?= escape($row['foto_utama'] ?? '') ?>"
                         alt="<?= escape($row['nama_barang'] ?? '') ?>"
                         onerror="this.src='https://placehold.co/400x500/FFF0F4/D94F6E?text=Cloudy+Girls'"
                         loading="lazy">
                </div>
                <div class="card-body">
                    <div class="card-nama"><?= escape($row['nama_barang'] ?? '') ?></div>
                    <div class="card-harga"><?= formatRupiah($row['harga']) ?></div>
                    <div class="card-meta-row">
                        <?php if (!empty($row['ukuran'])): ?>
                        <div class="card-ukuran"><i class="bi bi-tag"></i> <?= escape($row['ukuran']) ?></div>
                        <?php else: ?><div></div><?php endif; ?>
                        <?php if ($likes > 0): ?>
                        <div class="card-likes"><i class="bi bi-heart-fill"></i> <?= $likes ?></div>
                        <?php else: ?>
                        <div class="card-likes"></div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endwhile; else: ?>
        <div class="empty-state">
            <i class="bi bi-handbag"></i>
            <p><?= $cari ? 'Produk tidak ditemukan.' : 'Belum ada produk tersedia.' ?></p>
            <?php if ($cari || $filter_kategori): ?>
            <a href="home.php">← Lihat semua produk</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<?php include '../includes/footer.php'; ?>
</body>
</html>