<?php
session_start();
require_once '../config/koneksi.php';

// ── HELPER FUNCTIONS ──
function escape($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}
function formatTanggal($datetime) {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($datetime);
    return date('d', $ts) . ' ' . $bulan[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}
function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $rating ? '#f5a623' : '#ddd';
        $html .= "<i class='bi bi-star-fill' style='color:$color;font-size:12px;'></i>";
    }
    return $html;
}

// ── CEK LOGIN ──
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../auth/login.php"); exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) { header("Location: home.php"); exit; }

$q = mysqli_query($conn, "SELECT * FROM produk WHERE id=$id LIMIT 1");
if (!$q || mysqli_num_rows($q) === 0) { header("Location: home.php"); exit; }
$produk = mysqli_fetch_assoc($q);

$user_id = $_SESSION['user_id'];
$q_user  = mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$user_id LIMIT 1");
$user    = mysqli_fetch_assoc($q_user);

// Foto tambahan
$q_foto = mysqli_query($conn, "SELECT url_foto FROM foto_produk_tambahan WHERE produk_id=$id ORDER BY urutan ASC");
$foto_list = [$produk['foto_utama']];
if ($q_foto) while ($f = mysqli_fetch_assoc($q_foto)) $foto_list[] = $f['url_foto'];

// Ulasan produk ini
$q_ulasan = mysqli_query($conn, "
    SELECT ul.*, p.nama AS nama_pembeli, p.foto_profil
    FROM ulasan ul JOIN pembeli p ON p.id=ul.pembeli_id
    WHERE ul.produk_id=$id ORDER BY ul.created_at DESC
");
$ulasan_list = $q_ulasan ? mysqli_fetch_all($q_ulasan, MYSQLI_ASSOC) : [];
$avg_rating  = count($ulasan_list) ? array_sum(array_column($ulasan_list,'rating')) / count($ulasan_list) : 0;

// Toko
$q_toko = mysqli_query($conn, "SELECT * FROM pengaturan_toko LIMIT 1");
$toko   = $q_toko ? mysqli_fetch_assoc($q_toko) : [];

// Cek apakah sudah ada pesanan aktif
$q_pesan = mysqli_query($conn, "SELECT id FROM pesanan WHERE produk_id=$id AND pembeli_id=$user_id AND status NOT IN ('dibatalkan','selesai') LIMIT 1");
$sudah_pesan = $q_pesan && mysqli_num_rows($q_pesan) > 0;

$base_url = '../';
$page_title = escape($produk['nama_barang']);
include '../includes/header.php';
?>
<style>
.container-detail{max-width:1100px;margin:40px auto;padding:0 40px;display:grid;grid-template-columns:1fr 1fr;gap:48px;}
.foto-utama{aspect-ratio:3/4;border-radius:16px;overflow:hidden;background:var(--cream);border:1px solid var(--border);}
.foto-utama img{width:100%;height:100%;object-fit:cover;}
.foto-thumb-wrap{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;}
.foto-thumb{width:64px;height:64px;border-radius:10px;overflow:hidden;border:2px solid transparent;cursor:pointer;transition:border-color .2s;}
.foto-thumb.active,.foto-thumb:hover{border-color:var(--accent2);}
.foto-thumb img{width:100%;height:100%;object-fit:cover;}
.produk-info{}
.produk-kategori{font-size:11px;font-weight:600;letter-spacing:2px;text-transform:uppercase;color:var(--accent2);margin-bottom:8px;}
.produk-nama{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;line-height:1.2;margin-bottom:12px !important;}
.produk-harga{font-size:28px;font-weight:700;color:var(--accent2);margin-bottom:16px;}
.produk-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.meta-tag{font-size:12px;padding:5px 12px;border-radius:20px;background:var(--cream);border:1px solid var(--border);color:var(--muted);}
.produk-desc{font-size:14px;line-height:1.7;color:var(--muted);margin-bottom:24px;}
.divider{height:1px;background:var(--border);margin:20px 0;}
.btn-beli{width:100%;padding:14px;background:linear-gradient(135deg,var(--accent2),#EC4899);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;transition:opacity .2s;margin-bottom:10px;}
.btn-beli:hover{opacity:.88;}
.btn-beli:disabled{opacity:.5;cursor:not-allowed;}
.btn-chat{width:100%;padding:13px;background:var(--white);color:var(--dark);border:1.5px solid var(--border);border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;transition:border-color .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-chat:hover{border-color:var(--dark);}
.toko-info{margin-top:20px;padding:16px;background:var(--cream);border-radius:12px;border:1px solid var(--border);display:flex;align-items:center;gap:12px;}
.toko-avatar{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),#EC4899);color:#fff;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:700;font-family:'Playfair Display',serif;}
.ulasan-section{max-width:1100px;margin:40px auto 60px;padding:0 40px;}
.ulasan-section h3{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;margin-bottom:20px !important;}
.ulasan-card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:14px;}
.ulasan-header{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.ulasan-avatar{width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),#EC4899);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;overflow:hidden;}
.ulasan-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.ulasan-nama{font-size:13px;font-weight:600;}
.ulasan-tgl{font-size:11px;color:var(--muted);margin-top:2px;}
.ulasan-text{font-size:13px;color:var(--muted);line-height:1.6;margin-top:8px;}
@media(max-width:768px){.container-detail{grid-template-columns:1fr;gap:24px;padding:0 16px;}.ulasan-section{padding:0 16px;}}
</style>

<div class="container-detail">
    <!-- FOTO -->
    <div>
        <div class="foto-utama" id="mainImg">
            <img src="../uploads/produk/<?= escape($foto_list[0]) ?>"
                 alt="<?= escape($produk['nama_barang']) ?>"
                 id="mainImgEl"
                 onerror="this.src='https://placehold.co/400x500/FAF7F2/A78BFA?text=Cloudy+Girls'">
        </div>
        <?php if (count($foto_list) > 1): ?>
        <div class="foto-thumb-wrap">
            <?php foreach ($foto_list as $i => $foto): ?>
            <div class="foto-thumb <?= $i === 0 ? 'active' : '' ?>" onclick="gantiFoto(this, '../uploads/produk/<?= escape($foto) ?>')">
                <img src="../uploads/produk/<?= escape($foto) ?>" alt="foto <?= $i+1 ?>"
                     onerror="this.src='https://placehold.co/64x64/FAF7F2/A78BFA?text=+'">
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- INFO -->
    <div class="produk-info">
        <div class="produk-kategori"><?= escape($produk['kategori']) ?></div>
        <h1 class="produk-nama"><?= escape($produk['nama_barang']) ?></h1>
        <div class="produk-harga"><?= formatRupiah($produk['harga']) ?></div>

        <div class="produk-meta">
            <span class="meta-tag"><i class="bi bi-patch-check"></i> <?= escape($produk['kondisi']) ?></span>
            <?php if ($produk['ukuran']): ?>
            <span class="meta-tag"><i class="bi bi-tag"></i> Ukuran <?= escape($produk['ukuran']) ?></span>
            <?php endif; ?>
            <?php if (count($ulasan_list) > 0): ?>
            <span class="meta-tag"><i class="bi bi-star-fill" style="color:#f5a623"></i> <?= number_format($avg_rating,1) ?> (<?= count($ulasan_list) ?> ulasan)</span>
            <?php endif; ?>
        </div>

        <?php if ($produk['deskripsi']): ?>
        <p class="produk-desc"><?= nl2br(escape($produk['deskripsi'])) ?></p>
        <?php endif; ?>

        <?php if ($produk['harga'] > 50000): ?>
        <div style="background:rgba(124,58,237,.08);border:1px solid rgba(124,58,237,.2);border-radius:10px;padding:10px 14px;font-size:13px;color:var(--accent2);margin-bottom:16px;">
            <i class="bi bi-tag-fill"></i> <strong>Diskon Rp10.000</strong> untuk pengiriman (harga di atas Rp50.000)
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <?php if ($produk['status'] === 'aktif' && !$sudah_pesan): ?>
        <button class="btn-beli" onclick="window.location='../transaksi/checkout.php?produk_id=<?= $id ?>'">
            <i class="bi bi-bag-check"></i> Beli Sekarang
        </button>
        <?php elseif ($sudah_pesan): ?>
        <button class="btn-beli" disabled><i class="bi bi-check-circle"></i> Sudah Dipesan</button>
        <?php else: ?>
        <button class="btn-beli" disabled><i class="bi bi-x-circle"></i> Produk Tidak Tersedia</button>
        <?php endif; ?>

        <a href="../pages/chat.php?produk_id=<?= $id ?>" class="btn-chat">
            <i class="bi bi-chat-dots"></i> Tanya Penjual
        </a>

        <!-- INFO TOKO -->
        <div class="toko-info">
            <div class="toko-avatar">CG</div>
            <div>
                <div style="font-size:14px;font-weight:600;"><?= escape($toko['nama_toko'] ?? 'Cloudy Girls') ?></div>
                <div style="font-size:12px;color:var(--muted);">📍 <?= escape($toko['kota'] ?? 'Banyuwangi') ?></div>
                <?php if (!empty($toko['link_maps'])): ?>
                <a href="<?= escape($toko['link_maps']) ?>" target="_blank" style="font-size:12px;color:var(--accent2);">
                    <i class="bi bi-map"></i> Lihat di Maps
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ULASAN -->
<?php if (!empty($ulasan_list)): ?>
<div class="ulasan-section">
    <h3>Ulasan Pembeli (<?= count($ulasan_list) ?>)</h3>
    <?php foreach ($ulasan_list as $ul): ?>
    <div class="ulasan-card">
        <div class="ulasan-header">
            <div class="ulasan-avatar">
                <?php if (!empty($ul['foto_profil'])): ?>
                <img src="../uploads/foto_profil/<?= escape($ul['foto_profil']) ?>" alt="foto">
                <?php else: ?>
                <?= strtoupper(substr($ul['nama_pembeli'],0,1)) ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="ulasan-nama"><?= escape($ul['nama_pembeli']) ?></div>
                <div style="display:flex;gap:3px;"><?= renderStars($ul['rating']) ?></div>
                <div class="ulasan-tgl"><?= formatTanggal($ul['created_at']) ?></div>
            </div>
        </div>
        <?php if ($ul['komentar']): ?>
        <p class="ulasan-text"><?= escape($ul['komentar']) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function gantiFoto(el, src) {
    document.getElementById('mainImgEl').src = src;
    document.querySelectorAll('.foto-thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
</script>
<?php include '../includes/footer.php'; ?>