<?php
session_name('session_pembeli');
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

$user_id = (int)$_SESSION['user_id'];

// Ambil data wishlist
$q_wl = mysqli_query($conn,
    "SELECT p.* FROM wishlist w
     JOIN produk p ON w.produk_id = p.id
     WHERE w.pembeli_id = $user_id AND p.status='aktif'
     ORDER BY w.created_at DESC"
);

$page_title = 'Wishlist — Cloudy Girls';
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
input, select, textarea {
    font-family: var(--font-ui);
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

/* ── HAPUS BUTTON ── */
.btn-hapus {
    position: absolute; top: 8px; right: 8px;
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(255,255,255,.95); backdrop-filter: blur(6px);
    border: 1.5px solid var(--border);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: var(--red);
    transition: all .2s; z-index: 2;
    box-shadow: 0 2px 10px rgba(255,143,171,.18);
    text-decoration: none !important;
    -webkit-tap-highlight-color: transparent;
}
@media (max-width: 639px) {
    .btn-hapus { width: 32px; height: 32px; font-size: 14px; }
}
.btn-hapus:hover { transform: scale(1.18); border-color: var(--pink); color: var(--accent2); }

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

/* ── RESPONSIVE: MOBILE ── */
@media (max-width: 480px) {
    .section-head { flex-direction: column; align-items: flex-start; gap: 4px; }
}
</style>

<div class="section">
    <div class="section-head">
        <div class="section-title">Wishlist <span>Saya</span></div>
        <span class="section-count">
            <?= mysqli_num_rows($q_wl) ?> produk
        </span>
    </div>

    <div class="product-grid">
        <?php if ($q_wl && mysqli_num_rows($q_wl) > 0):
            while ($row = mysqli_fetch_assoc($q_wl)):
                $pid = (int)$row['id'];
        ?>
        <div class="product-card">
            <!-- Tombol hapus dari wishlist -->
            <a href="wishlist_toggle.php?produk_id=<?= $pid ?>&kembali=wishlist.php"
               class="btn-hapus" title="Hapus dari wishlist">
                <i class="bi bi-heart-fill"></i>
            </a>

            <a href="detail.php?id=<?= $pid ?>">
                <div class="card-img">
                    <img src="../uploads/produk/<?= escape($row['foto_utama'] ?? '') ?>"
                         alt="<?= escape($row['nama_barang'] ?? '') ?>"
                         onerror="this.src='https://placehold.co/400x500/FFF0F4/D94F6E?text=Cloudy+Girls'"
                         loading="lazy">
                </div>
                <div class="card-body">
                    <div class="card-nama"><?= escape($row['nama_barang'] ?? '') ?></div>
                    <div class="card-harga"><?= formatRupiah($row['harga']) ?></div>
                </div>
            </a>
        </div>
        <?php endwhile; else: ?>
        <div class="empty-state">
            <i class="bi bi-heart"></i>
            <p>Belum ada produk di wishlist.</p>
            <a href="home.php">Lihat produk →</a>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>