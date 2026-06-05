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

// ── HANDLE: Pembeli konfirmasi pesanan diterima ──────────────────────────────
if (isset($_POST['aksi']) && $_POST['aksi'] === 'pesanan_diterima') {
    $pesanan_id = (int)($_POST['pesanan_id'] ?? 0);
    if ($pesanan_id) {
        // Pastikan pesanan milik pembeli ini dan statusnya 'dikirim'
        $cek = mysqli_fetch_assoc(mysqli_query($conn,
            "SELECT id FROM pesanan WHERE id=$pesanan_id AND pembeli_id=$user_id AND status='dikirim' LIMIT 1"
        ));
        if ($cek) {
            mysqli_query($conn, "UPDATE pesanan SET
                status='selesai',
                selesai_at=NOW(),
                diselesaikan_oleh='pembeli'
                WHERE id=$pesanan_id");
        }
    }
   header("Location: pesanan.php?msg=diterima"); exit;
}

// Ambil semua pesanan milik pembeli ini
$q = mysqli_query($conn, "
    SELECT p.*, pr.nama_barang, pr.foto_utama,
           u.id AS ulasan_id
    FROM pesanan p
    JOIN produk pr ON pr.id = p.produk_id
    LEFT JOIN ulasan u ON u.pesanan_id = p.id AND u.pembeli_id = $user_id
    WHERE p.pembeli_id = $user_id
    ORDER BY p.created_at DESC
");

$page_title = 'Pesanan Saya — Cloudy Girls';
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
    --green:      #10b981;
    --orange:     #f59e0b;
    --blue:       #3b82f6;
    --red:        #ef4444;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Lato',sans-serif; color:var(--dark); background:var(--cream); }
a { text-decoration:none; }

.page-wrap { max-width:900px; margin:0 auto; padding:32px 20px 80px; }

.page-header { margin-bottom:28px; }
.page-header h1 {font-family:'Poppins',sans-serif; font-weight:800; letter-spacing:-0.5px;
    font-size:26px; font-weight:700; color:var(--dark);
}
.page-header p { font-size:13px; color:var(--muted); margin-top:4px; }

/* ── ALERT ── */
.alert-diterima {
    display:flex; align-items:center; gap:10px;
    background:#d1fae5; color:#065f46;
    border:1px solid #6ee7b7;
    border-radius:12px; padding:12px 18px;
    font-size:13px; font-weight:600;
    margin-bottom:20px;
}

/* ── STATUS BADGE ── */
.badge {
    display:inline-flex; align-items:center; gap:5px;
    font-size:11px; font-weight:600; padding:3px 10px;
    border-radius:20px; text-transform:uppercase; letter-spacing:.5px;
}
.badge-menunggu    { background:#fef3c7; color:#92400e; }
.badge-dikonfirmasi { background:#dbeafe; color:#1e40af; }
.badge-diproses    { background:#e0e7ff; color:#3730a3; }
.badge-dikirim     { background:#d1fae5; color:#065f46; }
.badge-selesai     { background:#d1fae5; color:#065f46; }
.badge-dibatalkan  { background:#fee2e2; color:#991b1b; }

/* ── TRANSFER BADGE ── */
.tbadge-menunggu    { background:#fef3c7; color:#92400e; }
.tbadge-dikonfirmasi { background:#d1fae5; color:#065f46; }
.tbadge-ditolak     { background:#fee2e2; color:#991b1b; }

/* ── CARD PESANAN ── */
.pesanan-card {
    background:var(--white);
    border:1.5px solid var(--border);
    border-radius:16px;
    margin-bottom:16px;
    overflow:hidden;
    transition:box-shadow .2s;
}
.pesanan-card:hover { box-shadow:0 8px 32px rgba(214,51,132,.1); }

.card-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 20px;
    background:var(--pink-blush);
    border-bottom:1px solid var(--border);
    flex-wrap:wrap; gap:8px;
}
.card-head .kode {
    font-weight:700; font-size:13px; color:var(--pink-deep);
    font-family:'Poppins',sans-serif;
}
.card-head .tgl { font-size:12px; color:var(--muted); }

.card-body { padding:16px 20px; display:flex; gap:16px; align-items:flex-start; }

.prod-img {
    width:72px; height:90px; border-radius:10px;
    object-fit:cover; flex-shrink:0;
    border:1px solid var(--border);
    background:var(--pink-pale);
}

.prod-info { flex:1; }
.prod-nama { font-weight:600; font-size:14px; color:var(--dark); margin-bottom:4px; }
.prod-meta { font-size:12px; color:var(--muted); margin-bottom:8px; }

.price-row { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.total-harga { font-size:15px; font-weight:700; color:var(--pink-deep); }

.card-foot {
    padding:12px 20px;
    border-top:1px solid var(--border);
    display:flex; align-items:center; justify-content:space-between;
    flex-wrap:wrap; gap:8px;
    background:#fafafa;
}

.info-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }

/* ── BUTTONS ── */
.btn-lacak {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 16px;
    background:linear-gradient(135deg,var(--pink-deep),var(--pink-mid));
    color:#fff; border:none; border-radius:20px;
    font-size:12px; font-weight:600; cursor:pointer;
    text-decoration:none; transition:opacity .2s;
}
.btn-lacak:hover { opacity:.88; color:#fff; }

.btn-outline {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 16px;
    background:transparent;
    color:var(--pink-deep);
    border:1.5px solid var(--pink-soft);
    border-radius:20px;
    font-size:12px; font-weight:600; cursor:pointer;
    text-decoration:none; transition:all .2s;
}
.btn-outline:hover { background:var(--pink-pale); border-color:var(--pink-deep); color:var(--pink-deep); }

/* ── TOMBOL PESANAN DITERIMA ── */
.btn-diterima {
    display:inline-flex; align-items:center; gap:6px;
    padding:8px 18px;
    background:linear-gradient(135deg,#10b981,#059669);
    color:#fff; border:none; border-radius:20px;
    font-size:12px; font-weight:700; cursor:pointer;
    transition:opacity .2s; font-family:'Poppins',sans-serif;
.btn-diterima:hover { opacity:.88; }

/* ── NOTIF DIKIRIM ── */
.notif-dikirim {
    background:#ecfdf5;
    border:1px solid #6ee7b7;
    border-radius:10px;
    padding:10px 14px;
    font-size:12px; color:#065f46;
    margin-top:10px;
    display:flex; align-items:center; gap:8px;
    flex-wrap:wrap; justify-content:space-between;
}

/* ── TRANSFER INFO ── */
.transfer-info {
    background:var(--pink-pale);
    border-radius:10px;
    padding:10px 14px;
    font-size:12px; color:var(--muted);
    margin-top:10px;
    display:flex; align-items:center; gap:8px;
}
.transfer-info.ditolak { background:#fee2e2; color:#991b1b; }

/* ── RESI INFO ── */
.resi-info {
    background:#d1fae5;
    border-radius:10px;
    padding:10px 14px;
    font-size:12px; color:#065f46;
    margin-top:10px;
    display:flex; align-items:center; justify-content:space-between;
    gap:8px; flex-wrap:wrap;
}

/* ── EMPTY ── */
.empty-state {
    text-align:center; padding:80px 20px;
    background:var(--white); border-radius:16px;
    border:1.5px solid var(--border);
}
.empty-state .icon { font-size:3rem; margin-bottom:16px; display:block; }
.empty-state h3 { font-family:'Poppins',sans-serif; font-weight:700;
.empty-state p { font-size:13px; color:var(--muted); margin-bottom:20px; }

@media(max-width:600px) {
    .card-body { flex-direction:column; }
    .prod-img { width:100%; height:180px; }
}
</style>

<div class="page-wrap">
    <div class="page-header">
        <h1>📦 Pesanan Saya</h1>
        <p>Riwayat dan status semua pesananmu</p>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'diterima'): ?>
    <div class="alert-diterima">
        <i class="bi bi-check-circle-fill" style="font-size:18px;"></i>
        Pesanan berhasil dikonfirmasi diterima! Terima kasih sudah berbelanja 🎉
    </div>
    <?php endif; ?>

    <?php if ($q && mysqli_num_rows($q) > 0): ?>
        <?php while ($p = mysqli_fetch_assoc($q)): ?>
        <?php
            $foto = $p['foto_utama'] ?? '';
            $fotoSrc = $foto ? '../uploads/produk/' . $foto : 'https://placehold.co/72x90/FDE8F2/D63384?text=CG';
            $tgl = date('d M Y, H:i', strtotime($p['created_at']));

            // URL tracking
            $resi = $p['no_resi'] ?? '';
            $ekspedisi = strtolower($p['ekspedisi'] ?? '');
            $urlLacak = '';
            if ($resi) {
                if (str_contains($ekspedisi, 'jnt')) {
                    $urlLacak = "https://www.jet.co.id/track/{$resi}";
                } elseif (str_contains($ekspedisi, 'jne')) {
                    $urlLacak = "https://www.jne.co.id/id/tracking/trace/{$resi}";
                }
            }
        ?>
        <div class="pesanan-card">
            <!-- HEAD -->
            <div class="card-head">
                <div>
                    <div class="kode"><?= escape($p['kode_pesanan']) ?></div>
                    <div class="tgl"><?= $tgl ?></div>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <span class="badge badge-<?= escape($p['status']) ?>">
                        <?php
                        $statusLabel = [
                            'menunggu'    => '⏳ Menunggu',
                            'dikonfirmasi'=> '✅ Dikonfirmasi',
                            'diproses'    => '📦 Diproses',
                            'dikirim'     => '🚚 Dikirim',
                            'selesai'     => '✅ Selesai',
                            'dibatalkan'  => '❌ Dibatalkan',
                        ];
                        echo $statusLabel[$p['status']] ?? $p['status'];
                        ?>
                    </span>
                    <span class="badge" style="background:<?= $p['metode']==='cod' ? '#fef3c7' : '#e0e7ff' ?>;color:<?= $p['metode']==='cod' ? '#92400e' : '#3730a3' ?>">
                        <?= strtoupper($p['metode']) ?>
                    </span>
                </div>
            </div>

            <!-- BODY -->
            <div class="card-body">
                <img src="<?= escape($fotoSrc) ?>" class="prod-img" alt="produk"
                     onerror="this.src='https://placehold.co/72x90/FDE8F2/D63384?text=CG'">
                <div class="prod-info">
                    <div class="prod-nama"><?= escape($p['nama_barang']) ?></div>
                    <div class="prod-meta">
                        <?php if ($p['nama_penerima']): ?>
                        👤 <?= escape($p['nama_penerima']) ?>
                        <?php endif; ?>
                    </div>

                    <div class="price-row">
                        <div class="total-harga"><?= formatRupiah($p['total_bayar']) ?></div>
                        <?php if ($p['diskon'] > 0): ?>
                        <span style="font-size:11px;color:var(--pink-deep);">– <?= formatRupiah($p['diskon']) ?> diskon ongkir</span>
                        <?php endif; ?>
                    </div>

                    <!-- Info transfer -->
                    <?php if ($p['metode'] === 'transfer'): ?>
                        <?php if ($p['status_transfer'] === 'menunggu'): ?>
                        <div class="transfer-info">
                            ⏳ Bukti transfer sedang dicek admin
                        </div>
                        <?php elseif ($p['status_transfer'] === 'dikonfirmasi'): ?>
                        <div class="transfer-info" style="background:#d1fae5;color:#065f46;">
                            ✅ Pembayaran dikonfirmasi
                        </div>
                        <?php elseif ($p['status_transfer'] === 'ditolak'): ?>
                        <div class="transfer-info ditolak">
                            ❌ Transfer ditolak: <?= escape($p['catatan_transfer'] ?? 'Silakan hubungi penjual') ?>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Info resi -->
                    <?php if ($resi): ?>
                    <div class="resi-info">
                        <span>🚚 No. Resi: <strong><?= escape($resi) ?></strong></span>
                        <?php if ($urlLacak): ?>
                        <a href="<?= $urlLacak ?>" target="_blank" class="btn-lacak">
                            <i class="bi bi-geo-alt"></i> Lacak Paket
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- ── TOMBOL PESANAN DITERIMA (muncul saat status = dikirim) ── -->
                    <?php if ($p['status'] === 'dikirim'): ?>
                    <div style="margin-top:12px;background:#ecfdf5;border:1px solid #6ee7b7;border-radius:10px;padding:12px 14px;">
                        <div style="font-size:12px;color:#065f46;margin-bottom:10px;font-weight:600;">
                            📬 Sudah menerima barangnya?
                        </div>
                        <div style="font-size:11px;color:#6b7280;margin-bottom:10px;line-height:1.5;">
                            Klik tombol di bawah jika barang sudah sampai di tanganmu. Pesanan akan otomatis ditandai selesai.
                        </div>
                        <form method="POST" onsubmit="return confirm('Konfirmasi bahwa pesanan sudah kamu terima?');">
                            <input type="hidden" name="aksi" value="pesanan_diterima">
                            <input type="hidden" name="pesanan_id" value="<?= $p['id'] ?>">
                            <button type="submit" class="btn-diterima">
                                <i class="bi bi-check-circle-fill"></i> Pesanan Diterima
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- FOOT -->
            <div class="card-foot">
                <div class="info-row">
                    <?php if ($p['metode'] === 'transfer' && $p['bukti_transfer']): ?>
                  <a href="../uploads/bukti_transfer/<?= escape($p['bukti_transfer']) ?>" target="_blank" class="btn-outline">
                        <i class="bi bi-image"></i> Lihat Bukti
                    </a>
                    <?php endif; ?>
                </div>
                <div class="info-row">
                    <?php if ($p['status'] === 'selesai'): ?>
                    <a href="../pages/ulasan.php?pesanan_id=<?= $p['id'] ?>" class="btn-outline">
                        <i class="bi bi-star"></i> Beri Ulasan
                    </a>
                    <?php endif; ?>
                    <?php if ($urlLacak): ?>
                    <a href="<?= $urlLacak ?>" target="_blank" class="btn-lacak">
                        <i class="bi bi-geo-alt"></i> Lacak Paket
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

    <?php else: ?>
    <div class="empty-state">
        <span class="icon">🛍</span>
        <h3>Belum Ada Pesanan</h3>
        <p>Kamu belum pernah melakukan pembelian.<br>Yuk belanja sekarang!</p>
        <a href="home.php" class="btn-lacak" style="display:inline-flex;">
            Mulai Belanja →
        </a>
    </div>
    <?php endif; ?>
</div>
