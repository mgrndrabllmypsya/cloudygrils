<?php
session_name('session_penjual');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || $_SESSION['user_role'] !== 'penjual') {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
$pembeli_id = (int)($_GET['pembeli_id'] ?? 0);
$produk_id  = (int)($_GET['produk_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pembeli_id && $produk_id) {
    $pesan = trim($_POST['pesan'] ?? '');
    if ($pesan !== '') {
        $pesan_esc = $conn->real_escape_string($pesan);
        $conn->query("INSERT INTO chat (produk_id, pembeli_id, pengirim, pesan, tipe, sudah_dibaca, created_at)
                      VALUES ($produk_id, $pembeli_id, 'admin', '$pesan_esc', 'teks', 0, NOW())");
    }
    header("Location: chat.php?pembeli_id=$pembeli_id&produk_id=$produk_id"); exit;
}

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

$pesan_list    = [];
$pembeli_aktif = null;
$produk_aktif  = null;

if ($pembeli_id && $produk_id) {
    $conn->query("UPDATE chat SET sudah_dibaca=1 WHERE pembeli_id=$pembeli_id AND produk_id=$produk_id AND pengirim='pembeli'");
    $pembeli_aktif = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$pembeli_id LIMIT 1"));
    $produk_aktif  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM produk WHERE id=$produk_id LIMIT 1"));
    $q_pesan = mysqli_query($conn, "SELECT * FROM chat WHERE pembeli_id=$pembeli_id AND produk_id=$produk_id ORDER BY created_at ASC");
    while ($p = mysqli_fetch_assoc($q_pesan)) $pesan_list[] = $p;
}

$total_unread = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
$nego_menunggu = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;
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
    --bg:#FFF0F5; --surface:#FFFFFF; --surface2:#FFE8F2; --border:#F4A7C3;
    --accent:#E8719A; --accent2:#D4547F; --pink:#F4A7C3; --pink2:#E8719A;
    --green:#00BFA5; --yellow:#FFB300; --red:#FF1744;
    --text:#1A1A1A; --text2:#444444; --muted:#BBA0B0; --white:#FFFFFF;
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text); display:flex; height:100vh; overflow:hidden; }
a { text-decoration:none; color:inherit; }

/* ── SIDEBAR ── */
.sidebar {
    width: 300px;
    background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    border-radius: 0 28px 28px 0;
    box-shadow: 6px 0 32px rgba(212,84,127,.28);
    overflow-y: auto;
    z-index: 50;
}
.sidebar-logo {
    padding: 28px 28px 22px;
    border-bottom: 1.5px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.12);
}
.sidebar-logo .logo-img {
    width: 38px;
    height: 38px;
    object-fit: contain;     /* Mengatur isi gambar di dalam lingkaran */
    background: #ffffff;      /* Memberikan latar belakang bulat putih bersih di belakang logo */
    border-radius: 50%;       /* MEMBUAT BULAT SEMPURNA */
    flex-shrink: 0;           /* Mencegah gambar menyusut/gepeng */
    padding: 4px;             /* Memberi jarak manis antara logo dengan tepi lingkaran putih */
    box-sizing: border-box;
    border: 1.5px solid rgba(255, 255, 255, 0.4);
}
.sidebar-logo .logo {
    font-family: 'Playfair Display', serif;
    font-size: 24px; 
    font-weight: 900;
    color: #1db899b1 !important; /* Warna Hijau Toska yang fresh */
    letter-spacing: -.3px;
    margin: 0;
    line-height: 1;
}
.sidebar-logo .logo span { 
    color: #ff009db1; !important; /* Warna Pink Terang menyala */
}
.sidebar-logo small {
    display: block; 
    font-size: 10px;
    letter-spacing: 2px; 
    text-transform: uppercase;
    color: rgba(255,255,255,.65); 
    margin-top: 8px;
}
.sidebar-nav { flex:1; padding:20px 18px; display:flex; flex-direction:column; gap:4px; overflow-y:auto; }
.nav-section { font-size:10px; letter-spacing:1.5px; text-transform:uppercase; color:rgba(255,255,255,.55); padding:18px 16px 8px; font-weight:600; }
.nav-item { display:flex; align-items:center; gap:14px; padding:13px 18px; border-radius:12px; font-size:14px; font-weight:500; color:rgba(255,255,255,.85); transition:all .2s; letter-spacing:0.01em; }
.nav-item:hover { background:rgba(255,255,255,.2); color:#fff; transform:translateX(3px); }
.nav-item.active { background:rgba(255,255,255,.28); color:#fff; font-weight:600; border-left:3px solid #fff; padding-left:15px; }
.nav-item i { font-size:17px; width:22px; flex-shrink:0; }
.badge-notif { background:#fff; color:var(--accent); font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; margin-left:auto; }
.sidebar-footer { padding:16px 18px 20px; border-top:1.5px solid rgba(255,255,255,.2); background:rgba(0,0,0,.1); }
.btn-logout { display:flex; align-items:center; gap:10px; padding:11px 16px; border-radius:10px; font-size:13px; font-weight:500; color:rgba(255,255,255,.85); transition:background .2s; width:100%; letter-spacing:0.01em; }
.btn-logout i { font-size:16px; }
.btn-logout:hover { background:rgba(255,255,255,.2); color:#fff; }
.nav-item-toko {
    margin-top: 0;
    background: transparent;
    border: none;
    color: rgba(255,255,255,.85) !important;
    font-weight: 500 !important;
    justify-content: flex-start;
    border-radius: 12px;
    box-shadow: none;
    letter-spacing: 0.01em;
}
.nav-item-toko:hover {
    background: rgba(255,255,255,.2) !important;
    border-color: transparent !important;
    box-shadow: none;
    transform: translateX(3px) !important;
    color: #fff !important;
}
.nav-ext-icon {
    font-size: 11px !important;
    width: auto !important;
    margin-left: auto;
    opacity: .6;
}

/* ── MAIN ── */
.main { flex:1; display:flex; flex-direction:column; min-width:0; overflow:hidden; }
.topbar {
    background:rgba(255,255,255,.95);
    backdrop-filter:blur(12px);
    border-bottom:1.5px solid var(--border);
    padding:0 32px; height:64px;
    display:flex; align-items:center; justify-content:space-between;
    flex-shrink:0;
    box-shadow:0 2px 12px rgba(212,84,127,.07);
}
.topbar-title { font-family:'Playfair Display',serif; font-size:18px; font-weight:700; color:var(--text); }

/* ── CHAT LAYOUT ── */
.chat-layout { flex:1; display:grid; grid-template-columns:300px 1fr; overflow:hidden; }

/* LIST */
.chat-list { background:var(--surface); border-right:1.5px solid var(--border); display:flex; flex-direction:column; overflow:hidden; }
.chat-list-head { padding:14px 16px; border-bottom:1px solid var(--border); font-size:13px; font-weight:600; color:var(--text); flex-shrink:0; background:linear-gradient(to right,#FFF0F5,#fff); }
.chat-list-body { flex:1; overflow-y:auto; }
.chat-list-body::-webkit-scrollbar { width:3px; }
.chat-list-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

.chat-item { display:flex; gap:10px; padding:12px 14px; border-bottom:1px solid rgba(255,214,224,.5); cursor:pointer; transition:background .15s; align-items:center; text-decoration:none; color:inherit; }
.chat-item:hover { background:var(--surface2); }
.chat-item.active { background:rgba(212,84,127,.08); border-left:3px solid var(--accent); }
.chat-item-img { width:42px; height:42px; border-radius:10px; object-fit:cover; border:1.5px solid var(--border); flex-shrink:0; }
.chat-item-info { flex:1; min-width:0; }
.chat-item-nama { font-size:13px; font-weight:600; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-item-sub { font-size:11px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.badge-unread { background:var(--accent2); color:#fff; font-size:10px; font-weight:700; min-width:18px; height:18px; border-radius:10px; padding:0 5px; flex-shrink:0; display:flex; align-items:center; justify-content:center; }

/* AREA CHAT */
.chat-area { display:flex; flex-direction:column; overflow:hidden; }

/* CHAT HEADER */
.chat-header { padding:14px 20px; border-bottom:1.5px solid var(--border); background:linear-gradient(to right,#FFF0F5,#fff); display:flex; align-items:center; gap:12px; flex-shrink:0; }
.chat-header-img { width:40px; height:40px; border-radius:10px; object-fit:cover; border:1.5px solid var(--border); flex-shrink:0; }
.chat-header-info { flex:1; min-width:0; }
.chat-header-info .nama { font-size:13px; font-weight:700; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-header-info .sub { font-size:11px; color:var(--muted); margin-top:2px; }

/* MESSAGES */
.chat-messages { flex:1; overflow-y:auto; padding:16px 20px; display:flex; flex-direction:column; gap:10px; background:var(--bg); }
.chat-messages::-webkit-scrollbar { width:4px; }
.chat-messages::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

/* DATE SEPARATOR */
.date-sep { text-align:center; font-size:11px; color:var(--muted); display:flex; align-items:center; gap:8px; margin:4px 0; }
.date-sep::before,.date-sep::after { content:''; flex:1; height:1px; background:var(--border); }

/* BUBBLE */
.bubble-wrap { display:flex; gap:8px; align-items:flex-end; max-width:75%; }
.bubble-wrap.admin-msg { flex-direction:row-reverse; margin-left:auto; }
.bubble-wrap.pembeli { margin-right:auto; }
.bubble-avatar { width:30px; height:30px; border-radius:50%; background:var(--surface2); border:1.5px solid var(--border); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:var(--accent); flex-shrink:0; }
.bubble-content { display:flex; flex-direction:column; max-width:100%; }
.bubble { padding:10px 14px; border-radius:16px; font-size:13px; line-height:1.6; word-break:break-word; white-space:pre-wrap; }
.bubble-wrap.pembeli .bubble { background:var(--surface); border:1.5px solid var(--border); border-bottom-left-radius:4px; color:var(--text); }
.bubble-wrap.admin-msg .bubble { background:linear-gradient(135deg,var(--accent2),var(--pink2)); color:#fff; border-bottom-right-radius:4px; }
.bubble-time { font-size:10px; color:var(--muted); margin-top:4px; padding:0 2px; }
.bubble-wrap.admin-msg .bubble-time { text-align:right; }
.bubble-wrap.pembeli .bubble-time { text-align:left; }

/* INPUT */
.chat-input-area { padding:12px 16px; border-top:1.5px solid var(--border); background:var(--surface); display:flex; gap:8px; align-items:flex-end; flex-shrink:0; }
.chat-input { flex:1; padding:10px 16px; border:1.5px solid var(--border); border-radius:24px; font-family:'DM Sans',sans-serif; font-size:13px; outline:none; resize:none; max-height:100px; line-height:1.5; color:var(--text); background:var(--surface2); transition:border-color .2s; overflow-y:auto; }
.chat-input:focus { border-color:var(--accent); background:#fff; }
.btn-kirim { width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,var(--accent2),var(--pink2)); border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#fff; font-size:15px; flex-shrink:0; transition:opacity .2s,transform .15s; }
.btn-kirim:hover { opacity:.85; transform:scale(1.06); }

.chat-placeholder { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--muted); text-align:center; padding:40px; }
.chat-placeholder i { font-size:3rem; margin-bottom:12px; opacity:.25; display:block; }
.chat-placeholder p { font-size:13px; line-height:1.7; }
.empty-list { padding:32px 16px; text-align:center; color:var(--muted); font-size:12px; }
</style>
</head>
<body>

<?php include '../includes/sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <div class="topbar-title">Chat Pembeli</div>
        <?php if ($total_unread > 0): ?>
        <span style="font-size:12px;color:var(--red);display:flex;align-items:center;gap:5px;">
            <i class="bi bi-circle-fill" style="font-size:8px;"></i> <?= $total_unread ?> pesan belum dibaca
        </span>
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
                        $fotoSrc  = !empty($item['foto_utama']) ? '../uploads/produk/' . escape($item['foto_utama']) : 'https://placehold.co/42x42/FFE8F2/E8719A?text=CG';
                ?>
                <a href="chat.php?pembeli_id=<?= $item['pembeli_id'] ?>&produk_id=<?= $item['produk_id'] ?>" class="chat-item <?= $isActive ? 'active' : '' ?>">
                    <img src="<?= $fotoSrc ?>" class="chat-item-img" alt="produk">
                    <div class="chat-item-info">
                        <div class="chat-item-nama"><?= escape($item['nama_pembeli']) ?></div>
                        <div class="chat-item-sub"><?= escape(substr($item['nama_barang'],0,22)) ?> · <?= escape(substr($item['pesan_terakhir']??'...',0,18)) ?></div>
                    </div>
                    <?php if ($item['belum_dibaca'] > 0): ?>
                    <span class="badge-unread"><?= $item['belum_dibaca'] ?></span>
                    <?php endif; ?>
                </a>
                <?php endwhile; else: ?>
                <div class="empty-list">
                    <i class="bi bi-chat-dots" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>
                    Belum ada percakapan
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- AREA CHAT -->
        <div class="chat-area">
            <?php if ($pembeli_aktif && $produk_aktif): ?>

            <div class="chat-header">
                <?php $fotoSrc = !empty($produk_aktif['foto_utama']) ? '../uploads/produk/' . escape($produk_aktif['foto_utama']) : 'https://placehold.co/40x40/FFE8F2/E8719A?text=CG'; ?>
                <img src="<?= $fotoSrc ?>" class="chat-header-img" alt="produk">
                <div class="chat-header-info">
                    <div class="nama"><?= escape($pembeli_aktif['nama']) ?></div>
                    <div class="sub"><?= escape($produk_aktif['nama_barang']) ?></div>
                </div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <?php if (empty($pesan_list)): ?>
                <div style="text-align:center;color:var(--muted);font-size:13px;padding:32px;">Belum ada pesan.</div>
                <?php endif; ?>

                <?php
                $prev_date = '';
                foreach ($pesan_list as $p):
                    $isAdmin = $p['pengirim'] === 'admin';
                    $waktu   = date('H:i', strtotime($p['created_at']));
                    $tgl     = date('d M Y', strtotime($p['created_at']));
                    if ($tgl !== $prev_date): $prev_date = $tgl;
                ?>
                <div class="date-sep"><?= $tgl === date('d M Y') ? 'Hari ini' : $tgl ?></div>
                <?php endif; ?>

                <div class="bubble-wrap <?= $isAdmin ? 'admin-msg' : 'pembeli' ?>">
                    <div class="bubble-content">
                        <div class="bubble"><?= nl2br(escape($p['pesan'])) ?></div>
                        <div class="bubble-time"><?= $waktu ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
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