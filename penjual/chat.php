<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';

// Ambil parameter
$pembeli_id = (int)($_GET['pembeli_id'] ?? 0);
$produk_id  = (int)($_GET['produk_id'] ?? 0);

// Handle kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pembeli_id && $produk_id) {
    $pesan = trim($_POST['pesan'] ?? '');
    if ($pesan !== '') {
        $pesan_esc = $conn->real_escape_string($pesan);
        $conn->query("INSERT INTO chat (produk_id, pembeli_id, pengirim, pesan, tipe, sudah_dibaca, created_at)
                      VALUES ($produk_id, $pembeli_id, 'admin', '$pesan_esc', 'teks', 0, NOW())");
    }
    header("Location: chat.php?pembeli_id=$pembeli_id&produk_id=$produk_id"); exit;
}

// Daftar semua percakapan unik (per pembeli per produk)
$q_list = mysqli_query($conn, "
    SELECT c.pembeli_id, c.produk_id,
           pb.nama AS nama_pembeli, pb.email AS email_pembeli,
           pr.nama_barang, pr.foto_utama,
           (SELECT pesan FROM chat WHERE pembeli_id=c.pembeli_id AND produk_id=c.produk_id ORDER BY created_at DESC LIMIT 1) AS pesan_terakhir,
           (SELECT created_at FROM chat WHERE pembeli_id=c.pembeli_id AND produk_id=c.produk_id ORDER BY created_at DESC LIMIT 1) AS waktu_terakhir,
           (SELECT COUNT(*) FROM chat WHERE pembeli_id=c.pembeli_id AND produk_id=c.produk_id AND pengirim='pembeli' AND sudah_dibaca=0) AS belum_dibaca
    FROM chat c
    JOIN pembeli pb ON pb.id = c.pembeli_id
    JOIN produk pr ON pr.id = c.produk_id
    GROUP BY c.pembeli_id, c.produk_id
    ORDER BY waktu_terakhir DESC
");

// Pesan aktif
$pesan_list = [];
$pembeli_aktif = null;
$produk_aktif  = null;

if ($pembeli_id && $produk_id) {
    // Tandai sudah dibaca
    $conn->query("UPDATE chat SET sudah_dibaca=1 WHERE pembeli_id=$pembeli_id AND produk_id=$produk_id AND pengirim='pembeli'");

    $pembeli_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$pembeli_id LIMIT 1"));
    $produk_aktif  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM produk WHERE id=$produk_id LIMIT 1"));

    $q_pesan = mysqli_query($conn, "SELECT * FROM chat WHERE pembeli_id=$pembeli_id AND produk_id=$produk_id ORDER BY created_at ASC");
    while ($p = mysqli_fetch_assoc($q_pesan)) $pesan_list[] = $p;
}

$total_unread = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chat — Cloudy Girls Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --bg:#FFF5F8; --surface:#FFFFFF; --surface2:#FFF0F5; --border:#FFB6D0;
    --accent:#FF4081; --accent2:#F50057; --pink:#FF80AB; --pink2:#FF4081;
    --green:#00BFA5; --yellow:#FFB300; --red:#FF1744;
    --text:#1A1A1A; --muted:#AAAAAA; --white:#FFFFFF;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);display:flex;height:100vh;overflow:hidden;}
a{text-decoration:none;color:inherit;}

/* SIDEBAR */
.sidebar{width:240px;background:linear-gradient(180deg,#FF80AB 0%,#FF4081 45%,#F50057 100%);border-right:none;display:flex;flex-direction:column;flex-shrink:0;box-shadow:4px 0 28px rgba(255,64,129,.3);}
.sidebar-logo{padding:22px 22px 18px;border-bottom:1.5px solid rgba(255,255,255,.2);background:rgba(255,255,255,.12);}
.sidebar-logo .logo{font-family:'Playfair Display',serif;font-size:21px;font-weight:900;color:#fff;}
.sidebar-logo .logo span{color:#FFE4EE;}
.sidebar-logo small{display:block;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.65);margin-top:3px;}
.sidebar-nav{flex:1;padding:14px 10px;display:flex;flex-direction:column;gap:2px;overflow-y:auto;}
.nav-item{display:flex;align-items:center;gap:11px;padding:9px 13px;border-radius:10px;font-size:13px;font-weight:500;color:rgba(255,255,255,.8);transition:all .18s;}
.nav-item:hover{background:rgba(255,255,255,.2);color:#fff;}
.nav-item.active{background:rgba(255,255,255,.28);color:#fff;font-weight:600;border-left:3px solid #fff;}
.nav-item i{font-size:15px;width:18px;flex-shrink:0;}
.badge-notif{background:#fff;color:var(--accent);font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;margin-left:auto;}
.nav-section{font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(255,255,255,.55);padding:12px 12px 5px;font-weight:600;}
.sidebar-footer{padding:14px 10px;border-top:1.5px solid rgba(255,255,255,.2);background:rgba(0,0,0,.1);}
.admin-card{display:flex;align-items:center;gap:10px;padding:10px 12px;background:rgba(255,255,255,.18);border-radius:10px;margin-bottom:10px;border:1.5px solid rgba(255,255,255,.3);}
.admin-avatar{width:34px;height:34px;border-radius:50%;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;color:#fff;flex-shrink:0;border:2px solid rgba(255,255,255,.5);}
.admin-info .name{font-size:12px;font-weight:600;color:#fff;}
.admin-info .role{font-size:10px;color:rgba(255,255,255,.65);}
.btn-logout{display:flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;font-size:12px;color:rgba(255,255,255,.85);transition:background .2s;width:100%;}
.btn-logout:hover{background:rgba(255,255,255,.2);color:#fff;}

/* MAIN */
.main{flex:1;display:flex;flex-direction:column;min-width:0;}
.topbar{background:var(--surface);border-bottom:1px solid var(--border);padding:0 24px;height:64px;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.topbar-title{font-family:'Playfair Display',serif;font-size:17px;font-weight:700;}

/* CHAT LAYOUT */
.chat-layout{flex:1;display:grid;grid-template-columns:300px 1fr;overflow:hidden;}

/* LIST */
.chat-list{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden;}
.chat-list-head{padding:14px 16px;border-bottom:1px solid var(--border);font-size:13px;font-weight:600;color:var(--text);flex-shrink:0;}
.chat-list-body{flex:1;overflow-y:auto;}
.chat-item{display:flex;gap:10px;padding:12px 14px;border-bottom:1px solid rgba(255,214,224,.5);cursor:pointer;transition:background .15s;align-items:center;}
.chat-item:hover{background:var(--surface2);}
.chat-item.active{background:rgba(236,72,153,.08);border-left:3px solid var(--accent);}
.chat-item-img{width:40px;height:40px;border-radius:8px;object-fit:cover;border:1px solid var(--border);flex-shrink:0;}
.chat-item-info{flex:1;min-width:0;}
.chat-item-nama{font-size:12px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.chat-item-sub{font-size:11px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:1px;}
.badge-unread{background:var(--accent2);color:#fff;font-size:10px;font-weight:700;padding:2px 6px;border-radius:10px;flex-shrink:0;}

/* AREA CHAT */
.chat-area{display:flex;flex-direction:column;overflow:hidden;}
.chat-header{padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface);display:flex;align-items:center;gap:12px;flex-shrink:0;}
.chat-header img{width:38px;height:38px;border-radius:8px;object-fit:cover;border:1px solid var(--border);}
.chat-header-info .nama{font-size:13px;font-weight:600;color:var(--text);}
.chat-header-info .sub{font-size:11px;color:var(--muted);}
.chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;background:var(--bg);}

/* BUBBLE */
.bubble-wrap{display:flex;gap:8px;align-items:flex-end;}
.bubble-wrap.admin-msg{flex-direction:row-reverse;}
.bubble{max-width:65%;padding:9px 13px;border-radius:14px;font-size:13px;line-height:1.5;}
.bubble-wrap.pembeli .bubble{background:var(--surface);border:1px solid var(--border);border-bottom-left-radius:4px;color:var(--text);}
.bubble-wrap.admin-msg .bubble{background:linear-gradient(135deg,var(--accent2),var(--pink2));color:#fff;border-bottom-right-radius:4px;}
.bubble-time{font-size:10px;color:var(--muted);margin-top:3px;}
.bubble-wrap.admin-msg .bubble-time{text-align:right;}

/* INPUT */
.chat-input-area{padding:12px 16px;border-top:1px solid var(--border);background:var(--surface);display:flex;gap:8px;align-items:flex-end;flex-shrink:0;}
.chat-input{flex:1;padding:10px 14px;border:1px solid var(--border);border-radius:20px;font-family:'DM Sans',sans-serif;font-size:13px;outline:none;resize:none;max-height:100px;line-height:1.5;color:var(--text);background:var(--surface2);transition:border-color .2s;}
.chat-input:focus{border-color:var(--accent);}
.btn-kirim{width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,var(--accent2),var(--pink2));border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;flex-shrink:0;transition:opacity .2s;}
.btn-kirim:hover{opacity:.85;}

.chat-placeholder{flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:var(--muted);text-align:center;padding:40px;}
.chat-placeholder i{font-size:3rem;margin-bottom:12px;opacity:.3;}
.empty-list{padding:32px 16px;text-align:center;color:var(--muted);font-size:12px;}
</style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php" class="nav-item"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="produk.php" class="nav-item"><i class="bi bi-handbag"></i> Produk</a>
        <a href="pesanan.php" class="nav-item"><i class="bi bi-bag-check"></i> Pesanan</a>

        <a href="chat.php" class="nav-item active">
            <i class="bi bi-chat-dots"></i> Chat
            <?php if ($total_unread > 0): ?>
            <span class="badge-notif"><?= $total_unread ?></span>
            <?php endif; ?>
        </a>
        <a href="nego.php" class="nav-item"><i class="bi bi-tags"></i> Nego Harga</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php" class="nav-item"><i class="bi bi-star"></i> Ulasan</a>
        <a href="pengaturan.php" class="nav-item"><i class="bi bi-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card">
            <div class="admin-avatar"><?= strtoupper(substr($admin_nama, 0, 1)) ?></div>
            <div class="admin-info">
                <div class="name"><?= escape($admin_nama) ?></div>
                <div class="role">Administrator</div>
            </div>
        </div>
        <a href="../auth/logout_admin.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i> Keluar</a>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">Chat Pembeli</div>
        <?php if ($total_unread > 0): ?>
        <span style="font-size:12px;color:var(--red);"><i class="bi bi-circle-fill" style="font-size:8px;"></i> <?= $total_unread ?> pesan belum dibaca</span>
        <?php endif; ?>
    </div>

    <div class="chat-layout">

        <!-- LIST -->
        <div class="chat-list">
            <div class="chat-list-head">💬 Percakapan</div>
            <div class="chat-list-body">
                <?php if ($q_list && mysqli_num_rows($q_list) > 0):
                    while ($item = mysqli_fetch_assoc($q_list)):
                        $isActive = $item['pembeli_id'] == $pembeli_id && $item['produk_id'] == $produk_id;
                        $fotoSrc  = !empty($item['foto_utama']) ? '../uploads/produk/' . escape($item['foto_utama']) : 'https://placehold.co/40x40/232136/A78BFA?text=CG';
                ?>
                <a href="chat.php?pembeli_id=<?= $item['pembeli_id'] ?>&produk_id=<?= $item['produk_id'] ?>" class="chat-item <?= $isActive?'active':'' ?>">
                    <img src="<?= $fotoSrc ?>" class="chat-item-img" alt="produk">
                    <div class="chat-item-info">
                        <div class="chat-item-nama"><?= escape($item['nama_pembeli']) ?></div>
                        <div class="chat-item-sub"><?= escape(substr($item['nama_barang'],0,25)) ?> · <?= escape(substr($item['pesan_terakhir']??'...',0,20)) ?></div>
                    </div>
                    <?php if ($item['belum_dibaca'] > 0): ?>
                    <span class="badge-unread"><?= $item['belum_dibaca'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endwhile; else: ?>
                <div class="empty-list"><i class="bi bi-chat-dots" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>Belum ada percakapan</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AREA CHAT -->
        <div class="chat-area">
            <?php if ($pembeli_aktif && $produk_aktif): ?>

            <div class="chat-header">
                <?php $fotoSrc = !empty($produk_aktif['foto_utama']) ? '../uploads/produk/' . escape($produk_aktif['foto_utama']) : 'https://placehold.co/38x38/232136/A78BFA?text=CG'; ?>
                <img src="<?= $fotoSrc ?>" alt="produk">
                <div class="chat-header-info">
                    <div class="nama"><?= escape($pembeli_aktif['nama']) ?></div>
                    <div class="sub"><?= escape($produk_aktif['nama_barang']) ?> · <?= escape($pembeli_aktif['email']) ?></div>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php foreach ($pesan_list as $p):
                    $isAdmin = $p['pengirim'] === 'admin';
                    $waktu   = date('H:i', strtotime($p['created_at']));
                    $tgl     = date('d M', strtotime($p['created_at']));
                ?>
                <div class="bubble-wrap <?= $isAdmin ? 'admin-msg' : 'pembeli' ?>">
                    <?php if (!$isAdmin): ?>
                    <div style="width:28px;height:28px;border-radius:50%;background:var(--surface2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--accent);flex-shrink:0;">
                        <?= strtoupper(substr($pembeli_aktif['nama'],0,1)) ?>
                    </div>
                    <?php endif; ?>
                    <div>
                        <div class="bubble"><?= nl2br(escape($p['pesan'])) ?></div>
                        <div class="bubble-time"><?= $tgl ?> <?= $waktu ?></div>
                    </div>
                </div>
                <?php endforeach; ?>

                <?php if (empty($pesan_list)): ?>
                <div style="text-align:center;color:var(--muted);font-size:13px;padding:20px;">Belum ada pesan dalam percakapan ini.</div>
                <?php endif; ?>
            </div>

            <form method="POST" class="chat-input-area">
                <textarea name="pesan" class="chat-input" placeholder="Balas pesan..." rows="1"
                    onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
                <button type="submit" class="btn-kirim"><i class="bi bi-send-fill"></i></button>
            </form>

            <?php else: ?>
            <div class="chat-placeholder">
                <i class="bi bi-chat-dots"></i>
                <p>Pilih percakapan untuk mulai membalas</p>
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;

const ta = document.querySelector('.chat-input');
if (ta) {
    ta.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
}
</script>
</body>
</html>