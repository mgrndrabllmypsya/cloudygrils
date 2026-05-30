<?php
session_name('session_pembeli');
session_start();
require_once '../config/koneksi.php'; // pakai koneksi.php, bukan db.php

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

// Ambil data wishlist - pakai mysqli, bukan pdo
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
/* sama seperti style di home.php, copy paste saja */
:root {
    --pink-deep:#D63384; --pink-mid:#F06292; --pink-soft:#F8BBD9;
    --pink-pale:#FDE8F2; --pink-blush:#FFF0F7; --cream:#FFF8FC;
    --white:#FFFFFF; --dark:#2D1B25; --muted:#A07090;
    --border:#F2D0E5; --red:#F43F5E;
}
.page-wrap, .page-wrap *, .profil-layout, .profil-layout *,
.sidebar-profil, .card, .alert {
    box-sizing: border-box;
}
body { font-family:'DM Sans',sans-serif; color:var(--dark); background:var(--cream); }
a { text-decoration:none !important; }
.section { max-width:1280px; margin:0 auto; padding:28px 40px 60px; }
.section-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:22px; }
.section-title { font-family:'Playfair Display',serif; font-size:22px; font-weight:700; }
.product-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
.product-card {
    background:var(--white); border:1px solid var(--border);
    border-radius:16px; overflow:hidden; position:relative;
    transition:transform .25s, box-shadow .25s;
}
.product-card:hover { transform:translateY(-5px); box-shadow:0 20px 48px rgba(214,51,132,.13); }
.card-img { aspect-ratio:3/4; overflow:hidden; background:var(--pink-blush); position:relative; }
.card-img img { width:100%; height:100%; object-fit:cover; transition:transform .4s; }
.product-card:hover .card-img img { transform:scale(1.06); }
.card-body { padding:14px 16px 16px; }
.card-nama { font-size:14px; font-weight:500; margin-bottom:8px; color:var(--dark); }
.card-harga { font-size:15px; font-weight:700; color:var(--pink-deep); }
.btn-hapus {
    position:absolute; top:10px; right:10px;
    width:36px; height:36px; border-radius:50%;
    background:rgba(255,255,255,.92);
    border:none; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    font-size:17px; color:var(--red); z-index:2;
    box-shadow:0 2px 10px rgba(214,51,132,.18);
}
@media(max-width:768px) { .product-grid { grid-template-columns:repeat(2,1fr); } .section { padding:16px; } }
/* Fix footer */
footer * {
    margin: revert;
    padding: revert;
    box-sizing: revert;
}
</style>

<div class="section">
    <div class="section-head">
        <div class="section-title">Wishlist Saya</div>
        <small style="color:var(--muted);font-size:13px;">
            <?= mysqli_num_rows($q_wl) ?> produk
        </small>
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
                         onerror="this.src='https://placehold.co/400x500/FDE8F2/D63384?text=Cloudy+Girls'"
                         loading="lazy">
                </div>
                <div class="card-body">
                    <div class="card-nama"><?= escape($row['nama_barang'] ?? '') ?></div>
                    <div class="card-harga"><?= formatRupiah($row['harga']) ?></div>
                </div>
            </a>
        </div>
        <?php endwhile; else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:80px 20px;">
            <i class="bi bi-heart" style="font-size:3rem;color:var(--border);"></i>
            <p style="margin-top:12px;color:var(--muted);font-size:14px;">Belum ada produk di wishlist.</p>
            <a href="home.php" style="color:var(--pink-deep);font-weight:600;margin-top:10px;display:inline-block;">
                Lihat produk →
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>