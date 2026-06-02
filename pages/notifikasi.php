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

// Ikon per tipe
$ikon = [
    'pesanan'    => ['icon' => 'bi-bag-check',      'color' => '#C43860'],
    'transfer'   => ['icon' => 'bi-credit-card',    'color' => '#7C3AED'],
    'pengiriman' => ['icon' => 'bi-truck',           'color' => '#0891B2'],
    'chat'       => ['icon' => 'bi-chat-dots',       'color' => '#059669'],
    'sistem'     => ['icon' => 'bi-info-circle',     'color' => '#6B7280'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifikasi – CloudyGirls</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #fdf4f7; color: #2D1520; min-height: 100vh; }

        .page-wrap { max-width: 680px; margin: 0 auto; padding: 24px 16px 60px; }

        .page-header { display: flex; align-items: center; gap: 10px; margin-bottom: 24px; }
        .page-header h1 { font-size: 20px; font-weight: 700; color: #2D1520; }
        .page-header .count-badge {
            background: #C43860; color: #fff;
            font-size: 12px; font-weight: 700;
            padding: 2px 8px; border-radius: 20px;
        }

        .empty-state { text-align: center; padding: 60px 20px; color: #9CA3AF; }
        .empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
        .empty-state p { font-size: 15px; }

        .notif-card {
            background: #fff;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 10px;
            display: flex;
            gap: 14px;
            align-items: flex-start;
            box-shadow: 0 1px 4px rgba(196,56,96,.07);
            border-left: 4px solid transparent;
            transition: box-shadow .2s;
            text-decoration: none;
            color: inherit;
        }
        .notif-card:hover { box-shadow: 0 4px 14px rgba(196,56,96,.13); }
        .notif-card.unread { border-left-color: #C43860; background: #fff8fb; }

        .notif-icon {
            width: 42px; height: 42px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }

        .notif-body { flex: 1; min-width: 0; }
        .notif-judul { font-size: 14px; font-weight: 600; margin-bottom: 3px; }
        .notif-pesan { font-size: 13px; color: #555; line-height: 1.5; }
        .notif-time  { font-size: 11px; color: #9CA3AF; margin-top: 5px; }

        .unread-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #C43860; flex-shrink: 0; margin-top: 6px;
        }
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

    <?php if (empty($list)): ?>
    <div class="empty-state">
        <i class="bi bi-bell-slash"></i>
        <p>Belum ada notifikasi untuk kamu.</p>
    </div>
    <?php else: ?>

    <?php foreach ($list as $n):
        $tipe  = $n['tipe'] ?? 'sistem';
        $meta  = $ikon[$tipe] ?? $ikon['sistem'];
        $waktu = date('d M Y, H:i', strtotime($n['created_at']));
        $unread = !$n['is_read'];

        // Link ke halaman terkait berdasarkan tipe
        $link = match($tipe) {
            'pesanan', 'transfer', 'pengiriman' => '../pages/pesanan.php',
            'chat'    => '../pages/chat.php',
            default   => '#'
        };
    ?>
    <a href="<?= $link ?>" class="notif-card <?= $unread ? 'unread' : '' ?>">
        <div class="notif-icon" style="background: <?= $meta['color'] ?>1a; color: <?= $meta['color'] ?>;">
            <i class="bi <?= $meta['icon'] ?>"></i>
        </div>
        <div class="notif-body">
            <div class="notif-judul"><?= htmlspecialchars($n['judul']) ?></div>
            <div class="notif-pesan"><?= htmlspecialchars($n['pesan']) ?></div>
            <div class="notif-time"><i class="bi bi-clock"></i> <?= $waktu ?></div>
        </div>
        <?php if ($unread): ?>
        <div class="unread-dot"></div>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>

    <?php endif; ?>
</div>

</body>
</html>
