<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

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

// Wishlist milik user ini
$wishlist_ids = [];
try {
    $q_wl = mysqli_query($conn, "SELECT produk_id FROM wishlist WHERE pembeli_id=$user_id");
    if ($q_wl) while ($w = mysqli_fetch_assoc($q_wl)) $wishlist_ids[] = (int)$w['produk_id'];
} catch (Exception $e) {}

// Jumlah like per produk
$like_count = [];
try {
    $q_lc = mysqli_query($conn, "SELECT produk_id, COUNT(*) as c FROM wishlist GROUP BY produk_id");
    if ($q_lc) while ($lc = mysqli_fetch_assoc($q_lc)) $like_count[(int)$lc['produk_id']] = (int)$lc['c'];
} catch (Exception $e) {}

$page_title = 'Beranda — Cloudy Girls';
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
    --red:        #F43F5E;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; color: var(--dark); background: var(--cream); }
a { text-decoration: none !important; }



/* ── CATEGORY BAR ── */
.cat-bar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    position: sticky; top: 64px; z-index: 99;
}
.cat-inner {
    max-width: 1280px; margin: 0 auto;
    padding: 0 40px; height: 52px;
    display: flex; align-items: center; gap: 6px; overflow-x: auto;
}
.cat-inner::-webkit-scrollbar { display: none; }
.cat-link {
    font-size: 13px; font-weight: 500; color: var(--muted);
    padding: 6px 16px; border-radius: 20px;
    white-space: nowrap; transition: all .2s;
}
.cat-link:hover { color: var(--pink-deep); background: var(--pink-pale); }
.cat-link.active { color: var(--pink-deep); background: var(--pink-pale); font-weight: 600; }

/* ── SEARCH ── */
.search-bar { max-width: 1280px; margin: 0 auto; padding: 24px 40px 0; }
.search-form { display: flex; gap: 10px; }
.search-input {
    flex: 1; padding: 11px 20px;
    border: 1.5px solid var(--border); border-radius: 40px;
    font-family: 'DM Sans', sans-serif; font-size: 13px;
    outline: none; transition: border-color .2s; background: var(--white);
}
.search-input:focus { border-color: var(--pink-deep); }
.search-btn {
    padding: 11px 24px;
    background: linear-gradient(135deg, var(--pink-deep), var(--pink-mid));
    color: #fff; border: none; border-radius: 40px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: opacity .2s; box-shadow: 0 4px 14px rgba(214,51,132,0.3);
}
.search-btn:hover { opacity: .88; }

/* ── PRODUCTS ── */
.section { max-width: 1280px; margin: 0 auto; padding: 28px 40px 60px; }
.section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
.section-title { font-family: 'Playfair Display', serif; font-size: 22px; font-weight: 700; color: var(--dark); }
.product-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }

/* ── CARD ── */
.product-card {
    background: var(--white); border: 1px solid var(--border);
    border-radius: 16px; overflow: hidden;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    position: relative; display: block; color: var(--dark);
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 48px rgba(214,51,132,0.13);
    border-color: var(--pink-soft);
}
.card-img { position: relative; aspect-ratio: 3/4; overflow: hidden; background: var(--pink-blush); }
.card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.product-card:hover .card-img img { transform: scale(1.06); }
.card-kondisi {
    position: absolute; top: 10px; left: 10px;
    background: rgba(45,27,37,.8); backdrop-filter: blur(6px);
    color: #fff; font-size: 10px; font-weight: 600;
    letter-spacing: 1px; text-transform: uppercase;
    padding: 4px 10px; border-radius: 20px;
}

/* ── LOVE BUTTON ── */
.btn-love {
    position: absolute; top: 10px; right: 10px;
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,.92); backdrop-filter: blur(6px);
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; color: var(--muted);
    transition: all .2s; z-index: 2;
    box-shadow: 0 2px 10px rgba(214,51,132,0.18);
    text-decoration: none !important;
}
.btn-love:hover { transform: scale(1.18); background: #fff; color: var(--red); }
.btn-love.liked { color: var(--red); }

.card-body { padding: 14px 16px 16px; }
.card-nama {
    font-size: 14px; font-weight: 500; line-height: 1.4; margin-bottom: 8px;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical; color: var(--dark);
}
.card-harga { font-size: 15px; font-weight: 700; color: var(--pink-deep); }
.card-ukuran { font-size: 11px; color: var(--muted); margin-top: 4px; }
.card-meta-row { display: flex; align-items: center; justify-content: space-between; margin-top: 6px; }
.card-likes { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
.card-likes i { color: var(--red); font-size: 11px; }

/* ── FOOTER ── */
footer { background: var(--white); border-top: 1px solid var(--border); margin-top: 60px; }
.footer-inner {
    max-width: 1280px; margin: 0 auto;
    padding: 40px 40px 20px;
    display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 40px;
}
.footer-logo { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 900; margin-bottom: 10px; display: block; }
.footer-logo span { color: var(--pink-deep); }
.footer-links { display: flex; flex-direction: column; gap: 10px; font-size: 13px; }
.footer-links a { color: var(--muted); }
.footer-links a:hover { color: var(--pink-deep); }
.footer-bottom { max-width: 1280px; margin: 0 auto; padding: 16px 40px; border-top: 1px solid var(--border); }

/* ── RESPONSIVE ── */
@media(max-width:1024px) { .product-grid { grid-template-columns: repeat(3,1fr); } }
@media(max-width:768px) {
    .section, .search-bar, .footer-inner { padding-left: 16px; padding-right: 16px; }
    .product-grid { grid-template-columns: repeat(2,1fr); gap: 14px; }
    .footer-inner { grid-template-columns: 1fr 1fr; }
    .cat-inner { padding: 0 16px; }
    .hero-banner { padding: 40px 20px; }
    .hero-banner::after { display: none; }
}
@media(max-width:480px) { .product-grid { grid-template-columns: repeat(2,1fr); gap: 10px; } }
</style>

<!-- CATEGORY BAR -->
<div class="cat-bar">
    <div class="cat-inner">
        <a href="home.php" class="cat-link <?= $filter_kategori === '' ? 'active' : '' ?>">Semua</a>
        <?php foreach ($kategori_list as $kat): ?>
        <a href="home.php?kategori=<?= urlencode($kat) ?>" class="cat-link <?= $filter_kategori === $kat ? 'active' : '' ?>"><?= escape($kat) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<!-- SEARCH -->
<div class="search-bar">
    <form class="search-form" method="GET" action="home.php">
        <?php if ($filter_kategori): ?>
        <input type="hidden" name="kategori" value="<?= escape($filter_kategori) ?>">
        <?php endif; ?>
        <input type="text" name="cari" class="search-input" placeholder="Cari pakaian..." value="<?= escape($cari) ?>">
        <button type="submit" class="search-btn"><i class="bi bi-search"></i> Cari</button>
    </form>
</div>

<!-- PRODUCTS -->
<div class="section" id="produk">
    <div class="section-head">
        <div class="section-title">
            <?php
            if ($cari) echo 'Hasil: "' . escape($cari) . '"';
            elseif ($filter_kategori) echo 'Kategori: ' . escape($filter_kategori);
            else echo 'Koleksi Terbaru';
            ?>
        </div>
        <small style="color:var(--muted);font-size:13px;"><?= $total_produk ?> produk tersedia</small>
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
                         onerror="this.src='https://placehold.co/400x500/FDE8F2/D63384?text=Cloudy+Girls'"
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
        <div style="grid-column:1/-1;text-align:center;padding:80px 20px;">
            <i class="bi bi-handbag" style="font-size:3rem;color:var(--border);"></i>
            <p style="margin-top:12px;color:var(--muted);font-size:14px;">
                <?= $cari ? 'Produk tidak ditemukan.' : 'Belum ada produk tersedia.' ?>
            </p>
            <?php if ($cari || $filter_kategori): ?>
            <a href="home.php" style="color:var(--pink-deep);font-weight:600;margin-top:10px;display:inline-block;">Lihat semua produk →</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div>
            <span class="footer-logo">Cloudy <span>Girls</span></span>
            <p style="font-size:13px;color:var(--muted);line-height:1.7;max-width:220px;">Toko preloved pakaian wanita berkualitas dari Banyuwangi.</p>
        </div>
        <div>
            <h4 style="font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px;">Kategori</h4>
            <div class="footer-links">
                <?php foreach ($kategori_list as $kat): ?>
                <a href="home.php?kategori=<?= urlencode($kat) ?>"><?= escape($kat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4 style="font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px;">Akun</h4>
            <div class="footer-links">
                <a href="pesanan.php">Pesanan Saya</a>
                <a href="profil.php">Profil</a>
                <a href="../auth/logout.php">Keluar</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p style="font-size:12px;color:var(--muted);">© <?= date('Y') ?> Cloudy Girls — Banyuwangi ♡</p>
    </div>
</footer>

<?php include '../includes/footer.php'; ?>