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
:root {
    --bg:      #FFF5F8;
    --surface: #FFFFFF;
    --surface2:#FFF0F5;
    --border:  #FFB6D0;
    --accent:  #FF4081;
    --accent2: #F50057;
    --pink:    #FF80AB;
    --muted:   #AAAAAA;
    --text:    #1A1A1A;
    --text2:   #444444;
    --yellow:  #FFB300;
    --red:     #FF1744;
    --green:   #00BFA5;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; color: var(--text); background: var(--bg); }
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, #FFB6D0 1px, transparent 1px);
    background-size: 28px 28px;
    opacity: .1;
    pointer-events: none;
    z-index: 0;
}
a { text-decoration: none !important; color: inherit; }

/* ── CATEGORY BAR ── */
.cat-bar {
    background: rgba(255,255,255,.95);
    backdrop-filter: blur(10px);
    border-bottom: 1.5px solid var(--border);
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
    border: 1px solid transparent;
}
.cat-link:hover { color: var(--accent); background: rgba(255,64,129,.06); border-color: var(--border); }
.cat-link.active {
    color: #fff;
    background: linear-gradient(135deg, var(--pink), var(--accent2));
    font-weight: 600; border-color: transparent;
    box-shadow: 0 3px 10px rgba(255,64,129,.3);
}

/* ── SEARCH ── */
.search-bar {
    max-width: 1280px; margin: 0 auto;
    padding: 24px 40px 0;
    position: relative; z-index: 1;
}
.search-form { display: flex; gap: 10px; }
.search-input {
    flex: 1; padding: 11px 20px;
    border: 1.5px solid var(--border); border-radius: 40px;
    font-family: 'DM Sans', sans-serif; font-size: 13px;
    outline: none; transition: border-color .2s, box-shadow .2s;
    background: var(--surface);
    color: var(--text);
}
.search-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(255,64,129,.1);
}
.search-input::placeholder { color: var(--muted); }
.search-btn {
    padding: 11px 24px;
    background: linear-gradient(135deg, var(--pink), var(--accent2));
    color: #fff; border: none; border-radius: 40px;
    font-size: 13px; font-weight: 600; cursor: pointer;
    transition: opacity .2s, transform .15s;
    box-shadow: 0 4px 14px rgba(255,64,129,.35);
    white-space: nowrap;
}
.search-btn:hover { opacity: .88; transform: translateY(-1px); }

/* ── SECTION ── */
.section {
    max-width: 1280px; margin: 0 auto;
    padding: 28px 40px 60px;
    position: relative; z-index: 1;
}
.section-head {
    display: flex; align-items: center;
    justify-content: space-between; margin-bottom: 22px;
}
.section-title {
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 700; color: var(--text);
}
.section-title span { color: var(--accent); }
.section-count { font-size: 13px; color: var(--muted); }

/* ── PRODUCT GRID ── */
.product-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 20px; }

/* ── PRODUCT CARD ── */
.product-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 16px; overflow: hidden;
    transition: transform .25s, box-shadow .25s, border-color .25s;
    position: relative; display: block; color: var(--text);
    box-shadow: 0 2px 10px rgba(255,64,129,.05);
}
.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 48px rgba(255,64,129,.15);
    border-color: var(--accent);
}
.card-img { position: relative; aspect-ratio: 3/4; overflow: hidden; background: var(--surface2); }
.card-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .4s; }
.product-card:hover .card-img img { transform: scale(1.06); }
.card-kondisi {
    position: absolute; top: 10px; left: 10px;
    background: rgba(255,64,129,.85); backdrop-filter: blur(6px);
    color: #fff; font-size: 10px; font-weight: 600;
    letter-spacing: 1px; text-transform: uppercase;
    padding: 4px 10px; border-radius: 20px;
}

/* ── LOVE BUTTON ── */
.btn-love {
    position: absolute; top: 10px; right: 10px;
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(255,255,255,.95); backdrop-filter: blur(6px);
    border: 1.5px solid var(--border);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; color: var(--muted);
    transition: all .2s; z-index: 2;
    box-shadow: 0 2px 10px rgba(255,64,129,.15);
    text-decoration: none !important;
}
.btn-love:hover { transform: scale(1.18); border-color: var(--accent); color: var(--red); }
.btn-love.liked { color: var(--red); border-color: rgba(255,23,68,.3); }

.card-body { padding: 14px 16px 16px; }
.card-nama {
    font-size: 14px; font-weight: 500; line-height: 1.4; margin-bottom: 8px;
    overflow: hidden; display: -webkit-box;
    -webkit-line-clamp: 2; -webkit-box-orient: vertical;
    color: var(--text);
}
.card-harga { font-size: 15px; font-weight: 700; color: var(--accent); }
.card-meta-row {
    display: flex; align-items: center;
    justify-content: space-between; margin-top: 6px;
}
.card-ukuran { font-size: 11px; color: var(--muted); }
.card-likes { font-size: 11px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
.card-likes i { color: var(--red); font-size: 11px; }

/* ── EMPTY STATE ── */
.empty-state { grid-column: 1/-1; text-align: center; padding: 80px 20px; }
.empty-state i { font-size: 3rem; color: var(--border); display: block; margin-bottom: 12px; }
.empty-state p { color: var(--muted); font-size: 14px; margin-bottom: 12px; }
.empty-state a { color: var(--accent); font-weight: 600; font-size: 13px; }

/* ── FOOTER ── */
footer {
    background: var(--surface);
    border-top: 1.5px solid var(--border);
    margin-top: 0; position: relative; z-index: 1;
}
.footer-inner {
    max-width: 1280px; margin: 0 auto;
    padding: 40px 40px 20px;
    display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 40px;
}
.footer-logo { font-family: 'Playfair Display', serif; font-size: 20px; font-weight: 900; margin-bottom: 10px; display: block; }
.footer-logo span { color: var(--accent); }
.footer-links { display: flex; flex-direction: column; gap: 10px; font-size: 13px; }
.footer-links a { color: var(--muted); transition: color .2s; }
.footer-links a:hover { color: var(--accent); }
.footer-bottom {
    max-width: 1280px; margin: 0 auto;
    padding: 16px 40px;
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

/* ── RESPONSIVE ── */
@media (max-width: 1280px) { .product-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 1024px) { .product-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px) {
    .section, .search-bar, .footer-inner { padding-left: 16px; padding-right: 16px; }
    .product-grid { grid-template-columns: repeat(2, 1fr); gap: 14px; }
    .footer-inner { grid-template-columns: 1fr 1fr; }
    .cat-inner { padding: 0 16px; }
    .footer-bottom { padding: 14px 16px; flex-direction: column; gap: 10px; }
}
@media (max-width: 480px) { .product-grid { gap: 10px; } }
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
            <i class="bi bi-search"></i> Cari
        </button>
    </form>
</div>

<!-- PRODUCTS -->
<div class="section" id="produk">
    <div class="section-head">
        <div class="section-title">
            <?php
            if ($cari)             echo 'Hasil: <span>"' . escape($cari) . '"</span>';
            elseif ($filter_kategori) echo 'Kategori: <span>' . escape($filter_kategori) . '</span>';
            else                   echo 'Koleksi <span>Terbaru</span>';
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
                         onerror="this.src='https://placehold.co/400x500/FFF0F5/FF4081?text=Cloudy+Girls'"
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
<footer>
    <div class="footer-inner">
        <div>
            <span class="footer-logo">Cloudy <span>Girls</span></span>
            <p style="font-size:13px;color:var(--muted);line-height:1.7;max-width:220px;">
                <?= escape($toko['deskripsi'] ?? 'Toko preloved pakaian wanita berkualitas dari Banyuwangi.') ?>
            </p>
            <?php if (!empty($toko['no_hp'])): ?>
            <a href="https://wa.me/<?= escape($toko['no_hp']) ?>"
               style="display:inline-flex;align-items:center;gap:6px;margin-top:12px;font-size:13px;color:var(--green);font-weight:600;">
                <i class="bi bi-whatsapp"></i> Hubungi Kami
            </a>
            <?php endif; ?>
        </div>
        <div>
            <h4 style="font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px;color:var(--text);">Kategori</h4>
            <div class="footer-links">
                <?php foreach ($kategori_list as $kat): ?>
                <a href="home.php?kategori=<?= urlencode($kat) ?>"><?= escape($kat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div>
            <h4 style="font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px;color:var(--text);">Akun</h4>
            <div class="footer-links">
                <a href="pesanan.php">Pesanan Saya</a>
                <a href="profil.php">Profil</a>
                <a href="../auth/logout.php">Keluar</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p style="font-size:12px;color:var(--muted);">© <?= date('Y') ?> Cloudy Girls — Banyuwangi ♡</p>
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

<?php include '../includes/footer.php'; ?>