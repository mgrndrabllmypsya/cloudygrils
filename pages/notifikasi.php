<?php
session_name('session_pembeli');
session_start();
require_once '../config/koneksi.php';
require_once '../includes/notifikasi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

$user_id = (int)$_SESSION['user_id'];

// Tandai semua notifikasi sebagai dibaca saat halaman dibuka
tandaiDibacaPembeli($conn, $user_id);

// Ambil semua notifikasi
$list = getNotifikasiPembeli($conn, $user_id);

// Semua notifikasi ditampilkan dalam satu tab

// Ikon & warna per tipe
$ikon = [
    'pesanan'    => ['icon' => 'bi-bag-check',       'color' => '#C43860'],
    'transfer'   => ['icon' => 'bi-credit-card',     'color' => '#7C3AED'],
    'pengiriman' => ['icon' => 'bi-truck',            'color' => '#0891B2'],
    'chat'       => ['icon' => 'bi-chat-dots',        'color' => '#059669'],
    'sistem'     => ['icon' => 'bi-info-circle',      'color' => '#6B7280'],
    // Tipe nego — dibedakan berdasarkan kata kunci di judul
    'nego'       => ['icon' => 'bi-tag',              'color' => '#C43860'],
];

// Helper: tentukan ikon & warna khusus notifikasi nego berdasarkan judul
function getMetaNego($judul) {
    $j = mb_strtolower($judul);
    if (str_contains($j, 'disetujui'))       return ['icon' => 'bi-check-circle-fill', 'color' => '#059669'];
    if (str_contains($j, 'ditolak'))         return ['icon' => 'bi-x-circle-fill',     'color' => '#DC2626'];
    if (str_contains($j, 'penawaran balik')) return ['icon' => 'bi-arrow-left-right',  'color' => '#D97706'];
    return ['icon' => 'bi-tag', 'color' => '#C43860'];
}

// Helper: badge label & warna berdasarkan judul notifikasi nego
function getBadgeNego($judul) {
    $j = mb_strtolower($judul);
    if (str_contains($j, 'disetujui'))       return ['label' => 'Nego disetujui',  'bg' => '#D1FAE5', 'color' => '#065F46'];
    if (str_contains($j, 'ditolak'))         return ['label' => 'Nego ditolak',    'bg' => '#FEE2E2', 'color' => '#991B1B'];
    if (str_contains($j, 'penawaran balik')) return ['label' => 'Ditawar balik',   'bg' => '#FEF3C7', 'color' => '#92400E'];
    return ['label' => 'Negosiasi',          'bg' => '#FCE7F3', 'color' => '#9D174D'];
}

// Helper: badge label untuk pesanan berdasarkan tipe & isi pesan
function getBadgePesanan($tipe, $judul) {
    $j = mb_strtolower($judul);

    // Dikirim / dalam pengiriman (termasuk COD antar & dalam pengiriman transfer)
    if (str_contains($j, 'dalam pengiriman') || str_contains($j, 'dikirim')
        || str_contains($j, 'sedang mengantar') || str_contains($j, 'mengantar'))
        return ['label' => 'Dikirim',          'bg' => '#DBEAFE', 'color' => '#1E40AF'];

    // COD siap diambil
    if (str_contains($j, 'siap diambil'))
        return ['label' => 'Siap Diambil',     'bg' => '#DBEAFE', 'color' => '#1E40AF'];

    // Dikemas
    if (str_contains($j, 'dikemas'))
        return ['label' => 'Dikemas',          'bg' => '#EDE9FE', 'color' => '#5B21B6'];

    // Diproses
    if (str_contains($j, 'diproses'))
        return ['label' => 'Diproses',         'bg' => '#D1FAE5', 'color' => '#065F46'];

    // Selesai
    if (str_contains($j, 'selesai'))
        return ['label' => 'Selesai',          'bg' => '#D1FAE5', 'color' => '#065F46'];

    // Tiba
    if (str_contains($j, 'tiba'))
        return ['label' => 'Tiba di Tujuan',   'bg' => '#D1FAE5', 'color' => '#065F46'];

    // Dibatalkan
    if (str_contains($j, 'dibatalkan'))
        return ['label' => 'Dibatalkan',       'bg' => '#FEE2E2', 'color' => '#991B1B'];

    // Dikonfirmasi (transfer dikonfirmasi)
    if (str_contains($j, 'dikonfirmasi') || str_contains($j, 'pembayaran dikonfirmasi'))
        return ['label' => 'Dikonfirmasi',     'bg' => '#DBEAFE', 'color' => '#1E40AF'];

    // Transfer / bukti transfer
    if ($tipe === 'transfer' || str_contains($j, 'transfer') || str_contains($j, 'bukti'))
        return ['label' => 'Transfer',         'bg' => '#EDE9FE', 'color' => '#5B21B6'];

    // Menunggu (termasuk "berhasil dibuat", "baru dibuat", dll — pesanan baru = menunggu)
    if (str_contains($j, 'menunggu') || str_contains($j, 'berhasil dibuat') || str_contains($j, 'baru dibuat'))
        return ['label' => 'Menunggu',         'bg' => '#FEF3C7', 'color' => '#92400E'];

    return ['label' => 'Pesanan',              'bg' => '#FCE7F3', 'color' => '#9D174D'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi – CloudyGirls</title>
    <link rel="icon" type="image/png" href="../uploads/toko/logo.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #fdf4f7; color: #2D1520; min-height: 100vh; }

        .page-wrap { max-width: 680px; margin: 0 auto; padding: 0 0 60px; }

        /* ── Header ── */
        .page-header {
            display: flex; align-items: center; gap: 10px;
            padding: 24px 16px 0;
            margin-bottom: 0;
        }
        .page-header h1 { font-size: 20px; font-weight: 700; color: #2D1520; }
        .count-badge {
            background: #C43860; color: #fff;
            font-size: 12px; font-weight: 700;
            padding: 2px 8px; border-radius: 20px;
        }

        /* ── Tab ── */
        .tabs {
            display: flex;
            border-bottom: 1.5px solid #f0d6e0;
            margin: 16px 0 0;
            padding: 0 16px;
            background: #fdf4f7;
        }
        .tab {
            flex: 1; text-align: center;
            padding: 12px 0;
            font-size: 14px;
            color: #9CA3AF;
            cursor: pointer;
            border-bottom: 2.5px solid transparent;
            margin-bottom: -1.5px;
            transition: all .15s;
            user-select: none;
        }
        .tab.active { color: #C43860; font-weight: 600; border-bottom-color: #C43860; }
        .tab:hover:not(.active) { color: #2D1520; }

        /* ── Tab Content ── */
        .tab-content { display: none; padding: 12px 0; }
        .tab-content.active { display: block; }

        /* ── Section label ── */
        .section-label {
            font-size: 11px; font-weight: 600;
            color: #9CA3AF;
            letter-spacing: .06em;
            text-transform: uppercase;
            padding: 10px 16px 4px;
        }

        /* ── Notif Card ── */
        .notif-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 14px 14px 16px;
            margin: 0 12px 8px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
            box-shadow: 0 1px 4px rgba(196,56,96,.07);
            border-left: 4px solid transparent;
            transition: box-shadow .2s;
            text-decoration: none;
            color: inherit;
            position: relative;
        }
        .notif-card:hover { box-shadow: 0 4px 14px rgba(196,56,96,.13); }
        .notif-card.unread { border-left-color: #C43860; background: #fff8fb; }

        /* ── Icon ── */
        .notif-icon {
            width: 40px; height: 40px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 17px; flex-shrink: 0;
        }

        /* ── Body ── */
        .notif-body { flex: 1; min-width: 0; }
        .notif-judul { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
        .notif-pesan { font-size: 12px; color: #555; line-height: 1.5; margin-bottom: 6px; }
        .notif-time  { font-size: 11px; color: #9CA3AF; margin-top: 5px; }

        /* ── Badge ── */
        .badge {
            display: inline-block;
            font-size: 11px; font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
        }

        /* ── Unread dot ── */
        .unread-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #C43860; flex-shrink: 0; margin-top: 5px;
        }

        /* ── Empty state ── */
        .empty-state { text-align: center; padding: 50px 20px; color: #9CA3AF; }
        .empty-state i { font-size: 44px; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 14px; }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>

<div class="page-wrap">
    <div class="page-header">
        <i class="bi bi-bell-fill" style="font-size:22px; color:#C43860;"></i>
        <h1>Notifikasi</h1>
        <?php if (count($list) > 0): ?>
        <span class="count-badge"><?= count($list) ?></span>
        <?php endif; ?>
    </div>

    <!-- Tab Navigation -->
    <div class="tabs">
        <div class="tab active">
            Semua <?= count($list) > 0 ? '(' . count($list) . ')' : '' ?>
        </div>
    </div>

    <!-- ── TAB: SEMUA ── -->
    <div id="tab-semua" class="tab-content active">
        <?php if (empty($list)): ?>
        <div class="empty-state">
            <i class="bi bi-bell-slash"></i>
            <p>Belum ada notifikasi untuk kamu.</p>
        </div>
        <?php else: ?>

        <?php
        // Kelompokkan: Terbaru (≤ 1 hari) vs Sebelumnya
        $terbaru    = [];
        $sebelumnya = [];
        foreach ($list as $n) {
            $diff = time() - strtotime($n['created_at']);
            if ($diff <= 86400) $terbaru[] = $n;
            else $sebelumnya[] = $n;
        }
        ?>

        <?php if (!empty($terbaru)): ?>
        <div class="section-label">Terbaru</div>
        <?php foreach ($terbaru as $n): ?>
            <?php echo renderNotifCard($n, $ikon); ?>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($sebelumnya)): ?>
        <div class="section-label">Sebelumnya</div>
        <?php foreach ($sebelumnya as $n): ?>
            <?php echo renderNotifCard($n, $ikon); ?>
        <?php endforeach; ?>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<?php
// ── Render satu kartu notifikasi ──────────────────────────────────────────
function renderNotifCard($n, $ikon) {
    $tipe   = $n['tipe'] ?? 'sistem';
    $unread = !$n['is_read'];
    $waktu  = formatWaktu($n['created_at']);

    // Link tujuan
    $link = match($tipe) {
        'pesanan', 'transfer', 'pengiriman' => '../pages/pesanan.php',
        'nego'   => '../pages/nego.php',
        'chat'   => '../pages/chat.php',
        default  => '#'
    };

    if ($tipe === 'nego') {
        $meta  = getMetaNego($n['judul']);
        $badge = getBadgeNego($n['judul']);
    } else {
        $meta  = $ikon[$tipe] ?? $ikon['sistem'];
        $badge = getBadgePesanan($tipe, $n['judul']);
    }

    $unread_class = $unread ? 'unread' : '';
    $icon_bg      = $meta['color'] . '1a';

    ob_start();
    ?>
    <a href="<?= $link ?>" class="notif-card <?= $unread_class ?>">
        <div class="notif-icon" style="background:<?= $icon_bg ?>; color:<?= $meta['color'] ?>;">
            <i class="bi <?= $meta['icon'] ?>"></i>
        </div>
        <div class="notif-body">
            <div class="notif-judul"><?= htmlspecialchars($n['judul']) ?></div>
            <div class="notif-pesan"><?= htmlspecialchars($n['pesan']) ?></div>
            <span class="badge" style="background:<?= $badge['bg'] ?>; color:<?= $badge['color'] ?>;">
                <?= $badge['label'] ?>
            </span>
            <div class="notif-time"><i class="bi bi-clock"></i> <?= $waktu ?></div>
        </div>
        <?php if ($unread): ?>
        <div class="unread-dot"></div>
        <?php endif; ?>
    </a>
    <?php
    return ob_get_clean();
}

// ── Format waktu relatif ──────────────────────────────────────────────────
function formatWaktu($created_at) {
    $diff = time() - strtotime($created_at);
    if ($diff < 60)         return 'Baru saja';
    if ($diff < 3600)       return (int)($diff / 60) . ' menit lalu';
    if ($diff < 86400)      return (int)($diff / 3600) . ' jam lalu';
    if ($diff < 172800)     return 'Kemarin';
    if ($diff < 604800)     return (int)($diff / 86400) . ' hari lalu';
    return date('d M Y', strtotime($created_at));
}
?>



</body>
</html>