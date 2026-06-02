<?php
session_name('session_pembeli');
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$user_id   = $_SESSION['user_id'];
$produk_id = (int)($_GET['produk_id'] ?? 0);

$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$user_id LIMIT 1"));

$q_list = mysqli_query($conn, "
    SELECT DISTINCT c.produk_id, pr.nama_barang, pr.foto_utama,
           (SELECT pesan FROM chat WHERE produk_id=c.produk_id AND pembeli_id=$user_id ORDER BY created_at DESC LIMIT 1) AS pesan_terakhir,
           (SELECT created_at FROM chat WHERE produk_id=c.produk_id AND pembeli_id=$user_id ORDER BY created_at DESC LIMIT 1) AS waktu_terakhir,
           (SELECT COUNT(*) FROM chat WHERE produk_id=c.produk_id AND pembeli_id=$user_id AND pengirim='admin' AND sudah_dibaca=0) AS belum_dibaca
    FROM chat c
    JOIN produk pr ON pr.id = c.produk_id
    WHERE c.pembeli_id = $user_id
    ORDER BY waktu_terakhir DESC
");

if ($produk_id) {
    $pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM produk WHERE id=$produk_id LIMIT 1"));
    if (!$pr) { header("Location: home.php"); exit; }
}
// Tidak ada auto-select — biarkan area chat menampilkan placeholder
// sampai user klik salah satu percakapan di list

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $produk_id) {
    $pesan = trim($_POST['pesan'] ?? '');
    if ($pesan !== '') {
        $pesan_esc = $conn->real_escape_string($pesan);
        $conn->query("INSERT INTO chat (produk_id, pembeli_id, pengirim, pesan, tipe, sudah_dibaca, created_at)
                      VALUES ($produk_id, $user_id, 'pembeli', '$pesan_esc', 'teks', 0, NOW())");
    }
    header("Location: chat.php?produk_id=$produk_id"); exit;
}

$pesan_list = [];
if ($produk_id) {
    $conn->query("UPDATE chat SET sudah_dibaca=1 WHERE produk_id=$produk_id AND pembeli_id=$user_id AND pengirim='admin'");
    $q_pesan = mysqli_query($conn, "
        SELECT * FROM chat
        WHERE produk_id=$produk_id AND pembeli_id=$user_id
        ORDER BY created_at ASC
    ");
    while ($p = mysqli_fetch_assoc($q_pesan)) $pesan_list[] = $p;
}

$page_title = 'Chat — Cloudy Girls';
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
}
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'DM Sans',sans-serif; color:var(--dark); background:var(--cream); }
a { text-decoration:none; color:inherit; }

/* ── LAYOUT DESKTOP ── */
.chat-page { max-width:1000px; margin:0 auto; padding:24px 20px 40px; }
.chat-wrap {
    display:grid; grid-template-columns:280px 1fr;
    gap:16px; height:calc(100vh - 140px); min-height:500px;
}

/* ── LIST SIDEBAR ── */
.chat-list {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden;
    display:flex; flex-direction:column;
    box-shadow:0 2px 12px rgba(214,51,132,.08);
}
.chat-list-head {
    padding:14px 16px; border-bottom:1px solid var(--border);
    background:var(--pink-blush);
    font-weight:700; font-size:14px; flex-shrink:0;
    display:flex; align-items:center; justify-content:space-between;
}
.btn-keluar {
    display:flex; align-items:center; gap:5px;
    font-size:12px; font-weight:600; color:var(--pink-deep);
    background:var(--pink-pale); border:1.5px solid var(--border);
    border-radius:8px; padding:5px 10px; cursor:pointer;
    text-decoration:none; transition:background .15s, opacity .15s;
    white-space:nowrap;
}
.btn-keluar:hover { background:var(--pink-soft); opacity:.85; }
.chat-list-body { flex:1; overflow-y:auto; }
.chat-list-body::-webkit-scrollbar { width:3px; }
.chat-list-body::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

.chat-item {
    display:flex; gap:10px; padding:12px 14px;
    border-bottom:1px solid var(--border); cursor:pointer;
    transition:background .15s; align-items:center;
    text-decoration:none; color:inherit;
}
.chat-item:hover { background:var(--pink-blush); }
.chat-item.active { background:var(--pink-pale); border-left:3px solid var(--pink-deep); }
.chat-item-img {
    width:44px; height:44px; border-radius:10px;
    object-fit:cover; border:1.5px solid var(--border); flex-shrink:0;
}
.chat-item-info { flex:1; min-width:0; }
.chat-item-nama {
    font-size:13px; font-weight:600; color:var(--dark);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.chat-item-preview {
    font-size:11px; color:var(--muted);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;
}
.badge-unread {
    background:var(--pink-deep); color:#fff;
    font-size:10px; font-weight:700;
    min-width:18px; height:18px; border-radius:10px;
    padding:0 5px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
}
.chat-empty-list {
    padding:32px 16px; text-align:center;
    color:var(--muted); font-size:13px; line-height:1.7;
}

/* ── AREA CHAT ── */
.chat-area {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden;
    display:flex; flex-direction:column;
    box-shadow:0 2px 12px rgba(214,51,132,.08);
}

.chat-header {
    padding:14px 18px; border-bottom:1.5px solid var(--border);
    background:var(--pink-blush);
    display:flex; align-items:center; gap:12px;
    flex-shrink:0;
}
.chat-header-img {
    width:42px; height:42px; border-radius:10px;
    object-fit:cover; border:1.5px solid var(--border); flex-shrink:0;
}
.chat-header-info { flex:1; min-width:0; }
.chat-header-info .nama {
    font-weight:700; font-size:14px; color:var(--dark);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.chat-header-info .sub { font-size:11px; color:var(--muted); margin-top:2px; }
.chat-header-link {
    font-size:12px; color:var(--pink-deep); font-weight:600;
    display:flex; align-items:center; gap:4px; flex-shrink:0;
    white-space:nowrap;
}
.chat-header-link:hover { text-decoration:underline !important; }

.chat-messages {
    flex:1; overflow-y:auto; padding:16px;
    display:flex; flex-direction:column; gap:12px;
    background:var(--cream);
    overscroll-behavior:contain;
}
.chat-messages::-webkit-scrollbar { width:4px; }
.chat-messages::-webkit-scrollbar-thumb { background:var(--border); border-radius:4px; }

.date-sep {
    text-align:center; font-size:11px; color:var(--muted);
    display:flex; align-items:center; gap:8px; margin:4px 0;
}
.date-sep::before, .date-sep::after { content:''; flex:1; height:1px; background:var(--border); }

.bubble-wrap {
    display:flex; gap:8px; align-items:flex-end;
    max-width:80%;
}
.bubble-wrap.saya { flex-direction:row-reverse; margin-left:auto; }
.bubble-wrap.admin { margin-right:auto; }

.bubble-content { display:flex; flex-direction:column; max-width:100%; }

.bubble {
    padding:10px 14px; border-radius:16px;
    font-size:13px; line-height:1.6;
    word-break:break-word; white-space:pre-wrap;
}
.bubble-wrap.admin .bubble {
    background:var(--white); border:1.5px solid var(--border);
    border-bottom-left-radius:4px; color:var(--dark);
}
.bubble-wrap.saya .bubble {
    background:linear-gradient(135deg,var(--pink-deep),var(--pink-mid));
    color:#fff; border-bottom-right-radius:4px;
}

.bubble-time {
    font-size:10px; color:var(--muted);
    margin-top:4px; padding:0 2px;
}
.bubble-wrap.saya .bubble-time { text-align:right; }
.bubble-wrap.admin .bubble-time { text-align:left; }

.chat-input-area {
    padding:12px 16px; border-top:1.5px solid var(--border);
    background:var(--white);
    display:flex; gap:8px; align-items:flex-end; flex-shrink:0;
}
.chat-input {
    flex:1; padding:10px 16px;
    border:1.5px solid var(--border); border-radius:24px;
    font-family:'DM Sans',sans-serif; font-size:13px;
    outline:none; resize:none; max-height:100px; line-height:1.5;
    color:var(--dark); background:var(--cream); transition:border-color .2s;
    overflow-y:auto;
}
.chat-input:focus { border-color:var(--pink-deep); background:#fff; }
.btn-kirim {
    width:40px; height:40px; border-radius:50%; flex-shrink:0;
    background:linear-gradient(135deg,var(--pink-deep),var(--pink-mid));
    border:none; cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:16px; transition:opacity .2s, transform .15s;
}
.btn-kirim:hover { opacity:.88; transform:scale(1.06); }

.chat-placeholder {
    flex:1; display:flex; flex-direction:column;
    align-items:center; justify-content:center;
    color:var(--muted); text-align:center; padding:40px;
}
.chat-placeholder i { font-size:3rem; margin-bottom:12px; opacity:.25; display:block; }
.chat-placeholder p { font-size:13px; line-height:1.7; }

/* ── RESPONSIVE MOBILE ── */
@media(max-width:768px) {
    body > header, body > footer,
    .navbar, .site-header, .site-footer,
    nav.navbar { display:none !important; }

    body { overflow: hidden; }

    .chat-page {
        padding: 0;
        max-width: 100%;
        width: 100%;
        height: 100dvh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    .chat-wrap {
        display: flex;
        flex-direction: column;
        grid-template-columns: unset;
        grid-template-rows: unset;
        flex: 1;
        min-height: 0;
        gap: 0;
        overflow: hidden;
    }

    /* ── STATE: Belum pilih chat → tampilkan list penuh ── */
    .mobile-show-list .chat-list {
        border-radius: 0;
        border-left: none; border-right: none; border-top: none;
        height: 100%;
        min-height: 0;
        max-height: 100%;
        flex: 1;
        overflow: hidden;
    }
    .mobile-show-list .chat-list-body {
        overflow-y: auto;
        overflow-x: hidden;
        display: flex;
        flex-direction: column;
    }
    .mobile-show-list .chat-item {
        display: flex;
        flex-direction: row;
        align-items: center;
        width: 100%;
        padding: 12px 14px;
        gap: 10px;
        border-bottom: 1px solid var(--border);
        border-right: none;
        border-top: none;
        text-align: left;
        flex-shrink: 0;
    }
    .mobile-show-list .chat-item.active { border-top: none; border-left: 3px solid var(--pink-deep); }
    .mobile-show-list .chat-item-img { width: 44px; height: 44px; border-radius: 10px; }
    .mobile-show-list .chat-item-info { width: auto; flex: 1; min-width: 0; }
    .mobile-show-list .chat-item-nama { font-size: 13px; white-space: nowrap; }
    .mobile-show-list .chat-item-preview { display: block; }
    .mobile-show-list .chat-area { display: none !important; }

    /* ── STATE: Sudah pilih chat → sembunyikan list, tampilkan chat penuh ── */
    .mobile-show-chat .chat-list { display: none !important; }
    .mobile-show-chat .chat-area {
        border-radius: 0;
        border: none;
        flex: 1;
        min-height: 0;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* Header chat: tambah tombol Back di mobile */
    .btn-back-mobile {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 34px; height: 34px;
        border-radius: 8px;
        background: var(--pink-pale);
        border: 1.5px solid var(--border);
        color: var(--pink-deep);
        font-size: 16px;
        cursor: pointer;
        flex-shrink: 0;
        text-decoration: none;
    }

    .chat-messages {
        flex: 1;
        min-height: 0;
        padding: 12px 10px;
        overflow-y: auto;
    }
    .chat-header { padding: 10px 14px; flex-shrink: 0; }
    .chat-input-area {
        padding: 8px 12px;
        flex-shrink: 0;
        position: relative;
        z-index: 10;
    }
    .bubble-wrap { max-width: 88%; }
}

/* Tombol back hanya muncul di mobile */
.btn-back-mobile { display: none; }
@media(max-width:768px) {
    .btn-back-mobile { display: flex; }
}

@media(max-width:400px) {
    .chat-item-img { width: 36px; height: 36px; }
    .chat-header-img { width: 34px; height: 34px; }
    .bubble { font-size: 12px; padding: 8px 12px; }
    .btn-kirim { width: 36px; height: 36px; font-size: 14px; }
}
</style>

<div class="chat-page" id="chatPage">
<div class="chat-wrap">

    <!-- LIST SIDEBAR -->
    <div class="chat-list">
        <div class="chat-list-head">
            <span>💬 Pesan</span>
            <a href="home.php" class="btn-keluar"><i class="bi bi-box-arrow-left"></i> Keluar</a>
        </div>
        <div class="chat-list-body">
            <?php
            $has_list = false;

            if ($produk_id && isset($pr)) {
                $cek_existing = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM chat WHERE produk_id=$produk_id AND pembeli_id=$user_id LIMIT 1"));
                if (!$cek_existing) {
                    $fotoSrc = !empty($pr['foto_utama']) ? '../uploads/produk/' . escape($pr['foto_utama']) : 'https://placehold.co/44x44/FDE8F2/D63384?text=CG';
                    echo '<a href="chat.php?produk_id=' . $produk_id . '" class="chat-item active">';
                    echo '<img src="' . $fotoSrc . '" class="chat-item-img" alt="produk">';
                    echo '<div class="chat-item-info">';
                    echo '<div class="chat-item-nama">' . escape($pr['nama_barang']) . '</div>';
                    echo '<div class="chat-item-preview">Mulai percakapan...</div>';
                    echo '</div></a>';
                    $has_list = true;
                }
            }

            if ($q_list && mysqli_num_rows($q_list) > 0):
                while ($item = mysqli_fetch_assoc($q_list)):
                    $has_list = true;
                    $isActive = $item['produk_id'] == $produk_id;
                    $fotoSrc  = !empty($item['foto_utama']) ? '../uploads/produk/' . escape($item['foto_utama']) : 'https://placehold.co/44x44/FDE8F2/D63384?text=CG';
            ?>
            <a href="chat.php?produk_id=<?= $item['produk_id'] ?>" class="chat-item <?= $isActive ? 'active' : '' ?>">
                <img src="<?= $fotoSrc ?>" class="chat-item-img" alt="produk">
                <div class="chat-item-info">
                    <div class="chat-item-nama"><?= escape($item['nama_barang']) ?></div>
                    <div class="chat-item-preview"><?= escape(substr($item['pesan_terakhir'] ?? '...', 0, 40)) ?></div>
                </div>
                <?php if ($item['belum_dibaca'] > 0): ?>
                <span class="badge-unread"><?= $item['belum_dibaca'] ?></span>
                <?php endif; ?>
            </a>
            <?php endwhile; endif; ?>

            <?php if (!$has_list): ?>
            <div class="chat-empty-list">
                <i class="bi bi-chat-dots" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3;"></i>
                Belum ada percakapan.<br>Klik "Tanya Penjual" di halaman produk.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- AREA CHAT -->
    <div class="chat-area">
        <?php if ($produk_id && isset($pr)): ?>

        <div class="chat-header">
            <?php $fotoSrc = !empty($pr['foto_utama']) ? '../uploads/produk/' . escape($pr['foto_utama']) : 'https://placehold.co/42x42/FDE8F2/D63384?text=CG'; ?>
            <a href="chat.php" class="btn-back-mobile"><i class="bi bi-arrow-left"></i></a>
            <img src="<?= $fotoSrc ?>" class="chat-header-img" alt="produk">
            <div class="chat-header-info">
                <div class="nama"><?= escape($pr['nama_barang']) ?></div>
                <div class="sub">Chat dengan Cloudy Girls · Rp <?= number_format($pr['harga'],0,',','.') ?></div>
            </div>
            <a href="detail.php?id=<?= $produk_id ?>" class="chat-header-link">
                <i class="bi bi-box-arrow-up-right"></i> Lihat Produk
            </a>
        </div>

        <div class="chat-messages" id="chatMessages">
            <?php if (empty($pesan_list)): ?>
            <div style="text-align:center;color:var(--muted);font-size:13px;padding:32px 20px;line-height:1.7;">
                👋 Halo! Tanyakan apapun tentang produk ini kepada penjual.
            </div>
            <?php endif; ?>

            <?php
            $prev_date = '';
            foreach ($pesan_list as $p):
                $isSaya  = $p['pengirim'] === 'pembeli';
                $waktu   = date('H:i', strtotime($p['created_at']));
                $tgl     = date('d M Y', strtotime($p['created_at']));
                if ($tgl !== $prev_date):
                    $prev_date = $tgl;
            ?>
            <div class="date-sep"><?= $tgl === date('d M Y') ? 'Hari ini' : $tgl ?></div>
            <?php endif; ?>
            <div class="bubble-wrap <?= $isSaya ? 'saya' : 'admin' ?>">
                <div class="bubble-content">
                    <div class="bubble"><?= nl2br(escape($p['pesan'])) ?></div>
                    <div class="bubble-time"><?= $waktu ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <form method="POST" class="chat-input-area" id="formChat">
            <textarea name="pesan" id="pesanInput" class="chat-input" placeholder="Tulis pesan..." rows="1"
                autocomplete="off"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();kirimPesan();}"></textarea>
            <button type="button" class="btn-kirim" onclick="kirimPesan()"><i class="bi bi-send-fill"></i></button>
        </form>

        <?php else: ?>
        <div class="chat-placeholder">
            <i class="bi bi-chat-dots"></i>
            <p>Pilih percakapan di sebelah kiri<br>atau buka produk dan klik "Tanya Penjual"</p>
        </div>
        <?php endif; ?>
    </div>

</div>
</div>

<script>
// ── Set mobile state (list vs chat) ──
(function() {
    const wrap = document.querySelector('.chat-wrap');
    if (!wrap) return;
    const isMobile = window.innerWidth <= 768;
    if (!isMobile) return;
    // Cek apakah ada produk_id di URL
    const params = new URLSearchParams(window.location.search);
    const hasProduk = params.has('produk_id') && params.get('produk_id') !== '';
    if (hasProduk) {
        wrap.classList.add('mobile-show-chat');
    } else {
        wrap.classList.add('mobile-show-list');
    }
    // Resize handler jika orientasi berubah
    window.addEventListener('resize', function() {
        const m = window.innerWidth <= 768;
        if (!m) {
            wrap.classList.remove('mobile-show-list', 'mobile-show-chat');
        }
    });
})();

// ── Scroll ke pesan terbaru ──
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;

// ── Saat keyboard muncul (visualViewport resize), scroll ke bawah ──
if (window.visualViewport) {
    window.visualViewport.addEventListener('resize', function() {
        setTimeout(() => {
            const m = document.getElementById('chatMessages');
            if (m) m.scrollTop = m.scrollHeight;
        }, 150);
    });
}

// ── Auto resize textarea ──
const ta = document.getElementById('pesanInput');
if (ta) {
    ta.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
    // TIDAK ada ta.focus() — keyboard tidak muncul otomatis
}

// ── Kirim pesan via AJAX ──
async function kirimPesan() {
    const input = document.getElementById('pesanInput');
    if (!input) return;

    const pesan = input.value.trim();
    if (!pesan) return;

    // Clear & tutup keyboard SEBELUM kirim
    input.blur();
    input.value = '';
    input.style.height = 'auto';

    const fd = new FormData();
    fd.append('pesan', pesan);
    await fetch(window.location.href, { method: 'POST', body: fd });

    // Reload tanpa tambah history stack
    window.location.replace(window.location.href);
}

// ── Cegah browser restore textarea saat back ──
window.addEventListener('pageshow', function() {
    const input = document.getElementById('pesanInput');
    if (input) { input.value = ''; input.style.height = 'auto'; }
});
</script>