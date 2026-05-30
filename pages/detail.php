<?php
session_name('session_pembeli');
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
        $color = $i <= $rating ? '#E8607A' : '#F9C8D4';
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

// Hanya 1 foto utama
$foto_utama = $produk['foto_utama'];

// Ulasan produk ini
$q_ulasan = mysqli_query($conn, "
    SELECT ul.*, p.nama AS nama_pembeli, p.foto_profil
    FROM ulasan ul JOIN pembeli p ON p.id=ul.pembeli_id
    WHERE ul.produk_id=$id ORDER BY ul.created_at DESC
");
$ulasan_list = $q_ulasan ? mysqli_fetch_all($q_ulasan, MYSQLI_ASSOC) : [];
$avg_rating  = count($ulasan_list) ? array_sum(array_column($ulasan_list,'rating')) / count($ulasan_list) : 0;

// Cek apakah sudah ada pesanan aktif
// Cek apakah sudah ada pesanan aktif
$q_pesan = mysqli_query($conn, "
    SELECT id FROM pesanan 
    WHERE produk_id=$id 
    AND pembeli_id=$user_id 
    AND status NOT IN ('dibatalkan','selesai')
    AND NOT (status='menunggu' AND status_transfer='ditolak')
    LIMIT 1
");
$sudah_pesan = $q_pesan && mysqli_num_rows($q_pesan) > 0;

// Cek nego aktif milik pembeli untuk produk ini
$q_nego = mysqli_query($conn, "SELECT * FROM nego_harga WHERE produk_id=$id AND pembeli_id=$user_id ORDER BY updated_at DESC LIMIT 1");
$nego   = $q_nego && mysqli_num_rows($q_nego) > 0 ? mysqli_fetch_assoc($q_nego) : null;

$base_url   = '../';
$page_title = escape($produk['nama_barang']);
include '../includes/header.php';
?>
<style>
/* ── PINK SOFT GRADIENT PALETTE ──
   Top:    #FF8FAB  (pink medium)
   Mid:    #FFB3C6  (pink soft)
   Bottom: #FFD6E0  (pink pastel lembut)
   Accent: #E8607A  (pink deep untuk teks/aksi)
   Dark:   #3D1A24  (gelap hangat)
*/

.container-detail{max-width:1100px;margin:40px auto;padding:0 40px;display:grid;grid-template-columns:1fr 1fr;gap:48px;}

/* Foto */
.foto-utama{aspect-ratio:3/4;border-radius:20px;overflow:hidden;background: #f9cfcf;border:1.5px}
.foto-utama img{width:100%;height:100%;object-fit:cover;}

/* Info produk */
.produk-info{}
.produk-kategori{font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#E8607A;margin-bottom:8px;}
.produk-nama{font-family:'Playfair Display',serif;font-size:28px;font-weight:700;line-height:1.2;margin-bottom:12px !important;color:#3D1A24;}
.produk-harga{font-size:28px;font-weight:700;color:#E8607A;margin-bottom:4px;}
.harga-asli{font-size:16px;color:#C48899;text-decoration:line-through;margin-bottom:16px;}

.produk-meta{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.meta-tag{font-size:12px;padding:5px 14px;border-radius:20px;background:#FFF0F4;border:1.5px solid #FFB3C6;color:#C48899;}

.produk-desc{font-size:14px;line-height:1.7;color:#C48899;margin-bottom:0;}
.divider{height:1px;background:#FFB3C6;margin:20px 0;}

/* Tombol Beli */
.btn-beli{
    width:100%;padding:14px;
    background:#59B292;
    color:#fff;border:none;border-radius:12px;
    font-size:15px;font-weight:700;cursor:pointer;
    transition:background .2s, transform .1s;
    margin-bottom:10px;
    display:flex;align-items:center;justify-content:center;gap:8px;
    letter-spacing:.3px;
}
.btn-beli:hover{background:#FF4F90;transform:translateY(-1px);}
.btn-beli:disabled{background:#F9C8D4;cursor:not-allowed;transform:none;}

/* Tombol Nego */
.btn-nego{
    width:100%;padding:13px;
    background:#FFF0F4;
    color:#E8607A;
    border:1.5px solid #FF8FAB;
    border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;
    transition:all .2s;
    display:flex;align-items:center;justify-content:center;gap:8px;
    margin-bottom:10px;
}
.btn-nego:hover{background:#FFD6E0;border-color:#E8607A;}

/* Tombol Chat */
.btn-chat{
    width:100%;padding:13px;
    background:#fff;
    color:#3D1A24;
    border:1.5px solid #FFB3C6;
    border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;
    transition:border-color .2s, background .2s;
    display:flex;align-items:center;justify-content:center;gap:8px;
    text-decoration:none;
}
.btn-chat:hover{border-color:#FF8FAB;background:#59B292;}

/* ── STATUS NEGO ── */
.nego-status{border-radius:12px;padding:14px 16px;margin-bottom:12px;font-size:13px;}
.nego-menunggu{background:rgba(255,179,198,.15);border:1.5px solid #FFB3C6;color:#A0465A;}
.nego-disetujui{background:rgba(232,96,122,.08);border:1.5px solid #FF8FAB;color:#7A2036;}
.nego-ditolak{background:rgba(239,68,68,.07);border:1.5px solid rgba(239,68,68,.3);color:#991b1b;}
.nego-counter{background:rgba(255,143,171,.1);border:1.5px solid #FF8FAB;color:#9B2C42;}
.nego-status-title{font-weight:700;margin-bottom:4px;}
.nego-status-harga{font-size:18px;font-weight:700;margin:6px 0;color:#E8607A;}

/* ── MODAL NEGO ── */
.modal-overlay{position:fixed;inset:0;background:rgba(61,26,36,0.45);z-index:999;display:flex;align-items:center;justify-content:center;opacity:0;visibility:hidden;transition:all .25s;}
.modal-overlay.open{opacity:1;visibility:visible;}
.modal-box{
    background:#fff;
    border-radius:20px;padding:32px;
    width:100%;max-width:420px;margin:20px;
    transform:translateY(16px);transition:transform .25s;
    border:1.5px solid #FFB3C6;
}
.modal-overlay.open .modal-box{transform:translateY(0);}
.modal-title{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;margin-bottom:6px;color:#3D1A24;}
.modal-sub{font-size:13px;color:#C48899;margin-bottom:24px;}
.modal-field{margin-bottom:16px;}
.modal-field label{display:block;font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:#3D1A24;margin-bottom:6px;}
.modal-field input,.modal-field textarea{
    width:100%;padding:10px 14px;
    border:1.5px solid #FFB3C6;
    border-radius:10px;
    font-family:'DM Sans',sans-serif;font-size:14px;outline:none;
    transition:border-color .2s;color:#3D1A24;
    background:#FFF8FA;
}
.modal-field input:focus,.modal-field textarea:focus{border-color:#FF8FAB;background:#fff;}
.modal-field textarea{resize:vertical;min-height:80px;}
.modal-actions{display:flex;gap:10px;margin-top:20px;}
.modal-btn-batal{flex:1;padding:12px;background:#FFF0F4;border:1.5px solid #FFB3C6;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;color:#C48899;}
.modal-btn-batal:hover{background:#FFD6E0;}
.modal-btn-kirim{flex:2;padding:12px;background:#FF6FA3;color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;transition:background .2s;}
.modal-btn-kirim:hover{background:#59B292;}
.harga-ref{font-size:12px;color:#C48899;margin-bottom:4px;}

/* ── ULASAN ── */
.ulasan-section{max-width:1100px;margin:40px auto 60px;padding:0 40px;}
.ulasan-section h3{font-family:'Playfair Display',serif;font-size:20px;font-weight:700;margin-bottom:20px !important;color:#3D1A24;}
.ulasan-card{
    background:#fff;
    border:1.5px solid #FFB3C6;
    border-radius:14px;padding:18px;margin-bottom:14px;
}

.ulasan-header{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
.ulasan-avatar{
    width:36px;height:36px;border-radius:50%;
    background:linear-gradient(135deg,#FF8FAB,#FFD6E0);
    color:#fff;display:flex;align-items:center;justify-content:center;
    font-size:13px;font-weight:700;overflow:hidden;
}
.ulasan-avatar img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.ulasan-nama{font-size:13px;font-weight:600;color:#3D1A24;}
.ulasan-tgl{font-size:11px;color:#C48899;margin-top:2px;}
.ulasan-text{font-size:13px;color:#C48899;line-height:1.6;margin-top:8px;}

@media(max-width:768px){
    .container-detail{grid-template-columns:1fr;gap:24px;padding:0 16px;}
    .ulasan-section{padding:0 16px;}
}
</style>

<div class="container-detail">
    <!-- FOTO -->
    <div>
        <div class="foto-utama">
            <img src="../uploads/produk/<?= escape($foto_utama) ?>"
                 alt="<?= escape($produk['nama_barang']) ?>"
                 onerror="this.src='https://placehold.co/400x500/FFD6E0/E8607A?text=Cloudy+Girls'">
        </div>
    </div>

    <!-- INFO -->
    <div class="produk-info">
        <div class="produk-kategori"><?= escape($produk['kategori']) ?></div>
        <h1 class="produk-nama"><?= escape($produk['nama_barang']) ?></h1>

        <?php
        $harga_tampil  = $produk['harga'];
        $nego_checkout = null;
        if ($nego) {
            if ($nego['status'] === 'disetujui') {
                $harga_tampil  = $nego['harga_deal'];
                $nego_checkout = $nego['id'];
            }
        }
        ?>

        <!-- Harga -->
        <?php if ($nego_checkout): ?>
        <div class="produk-harga"><?= formatRupiah($harga_tampil) ?></div>
        <div class="harga-asli"><?= formatRupiah($produk['harga']) ?></div>
        <?php else: ?>
        <div class="produk-harga"><?= formatRupiah($harga_tampil) ?></div>
        <?php endif; ?>

        <!-- Meta -->
        <div class="produk-meta">
            <span class="meta-tag"><i class="bi bi-patch-check"></i> <?= escape($produk['kondisi']) ?></span>
            <?php if ($produk['ukuran']): ?>
            <span class="meta-tag"><i class="bi bi-tag"></i> Ukuran <?= escape($produk['ukuran']) ?></span>
            <?php endif; ?>
            <?php if (count($ulasan_list) > 0): ?>
            <span class="meta-tag"><i class="bi bi-star-fill" style="color:#E8607A"></i> <?= number_format($avg_rating,1) ?> (<?= count($ulasan_list) ?> ulasan)</span>
            <?php endif; ?>
        </div>

        <!-- STATUS NEGO -->
        <?php if ($nego): ?>
            <?php if ($nego['status'] === 'menunggu'): ?>
            <div class="nego-status nego-menunggu">
                <div class="nego-status-title"><i class="bi bi-hourglass-split"></i> Nego sedang diproses</div>
                <div>Penawaran kamu sebesar <strong><?= formatRupiah($nego['harga_tawar']) ?></strong> sedang ditinjau penjual.</div>
            </div>
            <?php elseif ($nego['status'] === 'disetujui'): ?>
            <div class="nego-status nego-disetujui">
                <div class="nego-status-title"><i class="bi bi-check-circle-fill"></i> Nego disetujui!</div>
                <div>Harga deal kamu:</div>
                <div class="nego-status-harga"><?= formatRupiah($nego['harga_deal']) ?></div>
                <?php if ($nego['pesan_admin']): ?>
                <div style="font-size:12px;margin-top:4px;"><i class="bi bi-chat-quote"></i> "<?= escape($nego['pesan_admin']) ?>"</div>
                <?php endif; ?>
            </div>
            <?php elseif ($nego['status'] === 'ditolak'): ?>
            <div class="nego-status nego-ditolak">
                <div class="nego-status-title"><i class="bi bi-x-circle-fill"></i> Nego ditolak</div>
                <?php if ($nego['pesan_admin']): ?>
                <div><?= escape($nego['pesan_admin']) ?></div>
                <?php else: ?>
                <div>Penjual tidak menyetujui harga tawarmu.</div>
                <?php endif; ?>
            </div>
            <?php elseif ($nego['status'] === 'counter_ditolak'): ?>
            <div class="nego-status nego-ditolak">
                <div class="nego-status-title"><i class="bi bi-x-circle-fill"></i> Kamu menolak harga counter</div>
                <div>Kamu bisa mengajukan penawaran baru.</div>
            </div>
            <?php elseif ($nego['status'] === 'counter'): ?>
            <div class="nego-status nego-counter">
                <div class="nego-status-title"><i class="bi bi-arrow-left-right"></i> Penjual mengajukan harga balik</div>
                <div>Harga counter dari penjual:</div>
                <div class="nego-status-harga"><?= formatRupiah($nego['harga_counter']) ?></div>
                <?php if ($nego['pesan_admin']): ?>
                <div style="font-size:12px;margin-top:4px;"><i class="bi bi-chat-quote"></i> "<?= escape($nego['pesan_admin']) ?>"</div>
                <?php endif; ?>
                <div style="display:flex;gap:8px;margin-top:12px;">
                    <form method="POST" action="../transaksi/proses_nego.php" style="flex:1;">
                        <input type="hidden" name="nego_id" value="<?= $nego['id'] ?>">
                        <input type="hidden" name="aksi" value="terima_counter">
                        <button type="submit" style="width:100%;padding:9px;background:#FF6FA3;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                            Terima Counter
                        </button>
                    </form>
                    <form method="POST" action="../transaksi/proses_nego.php" style="flex:1;">
                        <input type="hidden" name="nego_id" value="<?= $nego['id'] ?>">
                        <input type="hidden" name="aksi" value="tolak_counter">
                        <button type="submit" style="width:100%;padding:9px;background:#FFF0F4;color:#C48899;border:1.5px solid #FFB3C6;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                            Tolak Counter
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- TOMBOL AKSI -->
        <?php if ($produk['status'] === 'aktif' && !$sudah_pesan): ?>
            <?php if ($nego_checkout): ?>
            <button class="btn-beli" onclick="window.location='../transaksi/checkout.php?produk_id=<?= $id ?>&nego_id=<?= $nego_checkout ?>'">
                <i class="bi bi-bag-check"></i> Beli dengan Harga Nego
            </button>
            <?php elseif ($nego && in_array($nego['status'], ['menunggu','counter'])): ?>
            <button class="btn-beli" onclick="window.location='../transaksi/checkout.php?produk_id=<?= $id ?>'">
                <i class="bi bi-bag-check"></i> Beli Sekarang (Harga Normal)
            </button>
            <?php else: ?>
            <button class="btn-beli" onclick="window.location='../transaksi/checkout.php?produk_id=<?= $id ?>'">
                <i class="bi bi-bag-check"></i> Beli Sekarang
            </button>
            <button class="btn-nego" onclick="bukaModalNego()">
                <i class="bi bi-tags"></i> Nego Harga
            </button>
            <?php endif; ?>
        <?php elseif ($sudah_pesan): ?>
        <button class="btn-beli" disabled><i class="bi bi-check-circle"></i> Sudah Dipesan</button>
        <?php else: ?>
        <button class="btn-beli" disabled><i class="bi bi-x-circle"></i> Produk Tidak Tersedia</button>
        <?php endif; ?>

        <!-- Tanya Penjual -->
        <a href="../pages/chat.php?produk_id=<?= $id ?>" class="btn-chat">
            <i class="bi bi-chat-dots"></i> Tanya Penjual
        </a>

        <!-- DIVIDER + DESKRIPSI -->
        <?php if ($produk['deskripsi']): ?>
        <div class="divider"></div>
        <div>
            <div style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:#3D1A24;margin-bottom:8px;">Deskripsi</div>
            <p class="produk-desc"><?= nl2br(escape($produk['deskripsi'])) ?></p>
        </div>
        <?php endif; ?>

    </div>
</div>

<!-- MODAL NEGO -->
<div class="modal-overlay" id="modalNego">
    <div class="modal-box">
        <div class="modal-title">Nego Harga</div>
        <div class="modal-sub">Ajukan harga tawarmu, penjual akan merespons secepatnya.</div>
        <form method="POST" action="../transaksi/proses_nego.php">
            <input type="hidden" name="produk_id" value="<?= $id ?>">
            <input type="hidden" name="aksi" value="ajukan">
            <div class="modal-field">
                <div class="harga-ref">Harga asli: <strong style="color:#E8607A;"><?= formatRupiah($produk['harga']) ?></strong></div>
                <label>Harga Tawarmu (Rp)</label>
                <input type="number"
                       name="harga_tawar"
                       min="1"
                       max="<?= $produk['harga'] - 1 ?>"
                       placeholder="Contoh: <?= $produk['harga'] - 10000 ?>"
                       required>
            </div>
            <div class="modal-field">
                <label>Pesan (opsional)</label>
                <textarea name="pesan" placeholder="Contoh: kak boleh kurang sedikit? kondisi masih bagus kan?"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="modal-btn-batal" onclick="tutupModalNego()">Batal</button>
                <button type="submit" class="modal-btn-kirim"><i class="bi bi-send"></i> Kirim Penawaran</button>
            </div>
        </form>
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
function bukaModalNego() {
    document.getElementById('modalNego').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function tutupModalNego() {
    document.getElementById('modalNego').classList.remove('open');
    document.body.style.overflow = '';
}
document.getElementById('modalNego').addEventListener('click', function(e) {
    if (e.target === this) tutupModalNego();
});
function gantiFoto(el, src) {
    document.getElementById('mainImgEl').src = src;
    document.querySelectorAll('.foto-thumb').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
}
</script>