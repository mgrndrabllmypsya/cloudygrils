<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$user_id   = $_SESSION['user_id'];
$produk_id = (int)($_GET['produk_id'] ?? 0);

// Ambil data pembeli
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$user_id LIMIT 1"));

// Ambil semua produk yang pernah dichat pembeli ini
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

// Jika produk_id dari URL, tambahkan ke list kalau belum ada
if ($produk_id) {
    $pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM produk WHERE id=$produk_id LIMIT 1"));
    if (!$pr) { header("Location: home.php"); exit; }
} else {
    // Default ke produk pertama di list
    $first = mysqli_fetch_assoc($q_list);
    if ($first) {
        $produk_id = $first['produk_id'];
        mysqli_data_seek($q_list, 0);
    }
    if ($produk_id) {
        $pr = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM produk WHERE id=$produk_id LIMIT 1"));
    }
}

// Handle kirim pesan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $produk_id) {
    $pesan = trim($_POST['pesan'] ?? '');
    if ($pesan !== '') {
        $pesan_esc = $conn->real_escape_string($pesan);
        $conn->query("INSERT INTO chat (produk_id, pembeli_id, pengirim, pesan, tipe, sudah_dibaca, created_at)
                      VALUES ($produk_id, $user_id, 'pembeli', '$pesan_esc', 'teks', 0, NOW())");
    }
    header("Location: chat.php?produk_id=$produk_id"); exit;
}

// Ambil pesan untuk produk aktif
$pesan_list = [];
if ($produk_id) {
    // Tandai pesan admin sebagai sudah dibaca
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

.chat-wrap {
    max-width:960px; margin:0 auto; padding:24px 20px 40px;
    display:grid; grid-template-columns:280px 1fr;
    gap:16px; height:calc(100vh - 120px);
}

/* LIST SIDEBAR */
.chat-list {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden; display:flex; flex-direction:column;
}
.chat-list-head {
    padding:16px; border-bottom:1px solid var(--border);
    background:var(--pink-blush);
    font-weight:700; font-size:14px;
}
.chat-list-body { flex:1; overflow-y:auto; }
.chat-item {
    display:flex; gap:10px; padding:12px 14px;
    border-bottom:1px solid var(--border); cursor:pointer;
    transition:background .15s; align-items:center;
}
.chat-item:hover { background:var(--pink-blush); }
.chat-item.active { background:var(--pink-pale); border-left:3px solid var(--pink-deep); }
.chat-item-img {
    width:44px; height:44px; border-radius:8px;
    object-fit:cover; border:1px solid var(--border); flex-shrink:0;
}
.chat-item-info { flex:1; min-width:0; }
.chat-item-nama { font-size:13px; font-weight:600; color:var(--dark); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.chat-item-preview { font-size:11px; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.badge-unread { background:var(--pink-deep); color:#fff; font-size:10px; font-weight:700; padding:2px 6px; border-radius:10px; flex-shrink:0; }
.chat-empty-list { padding:32px 16px; text-align:center; color:var(--muted); font-size:13px; }

/* AREA CHAT */
.chat-area {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; overflow:hidden;
    display:flex; flex-direction:column;
}
.chat-header {
    padding:14px 16px; border-bottom:1px solid var(--border);
    background:var(--pink-blush);
    display:flex; align-items:center; gap:12px;
}
.chat-header img { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border); }
.chat-header-info .nama { font-weight:700; font-size:14px; }
.chat-header-info .sub { font-size:11px; color:var(--muted); }
.chat-messages { flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:10px; background:var(--cream); }

/* BUBBLE */
.bubble-wrap { display:flex; gap:8px; align-items:flex-end; }
.bubble-wrap.saya { flex-direction:row-reverse; }
.bubble-avatar { width:28px; height:28px; border-radius:50%; object-fit:cover; flex-shrink:0; }
.bubble {
    max-width:65%; padding:9px 13px; border-radius:14px;
    font-size:13px; line-height:1.5;
}
.bubble-wrap.admin .bubble { background:var(--white); border:1px solid var(--border); border-bottom-left-radius:4px; color:var(--dark); }
.bubble-wrap.saya .bubble { background:linear-gradient(135deg,var(--pink-deep),var(--pink-mid)); color:#fff; border-bottom-right-radius:4px; }
.bubble-time { font-size:10px; color:var(--muted); margin-top:4px; text-align:right; }
.bubble-wrap.admin .bubble-time { text-align:left; }

/* INPUT */
.chat-input-area {
    padding:12px 16px; border-top:1px solid var(--border);
    background:var(--white); display:flex; gap:8px; align-items:flex-end;
}
.chat-input {
    flex:1; padding:10px 14px; border:1.5px solid var(--border);
    border-radius:20px; font-family:'DM Sans',sans-serif; font-size:13px;
    outline:none; resize:none; max-height:100px; line-height:1.5;
    color:var(--dark); background:var(--cream); transition:border-color .2s;
}
.chat-input:focus { border-color:var(--pink-deep); background:#fff; }
.btn-kirim {
    width:38px; height:38px; border-radius:50%;
    background:linear-gradient(135deg,var(--pink-deep),var(--pink-mid));
    border:none; cursor:pointer; display:flex; align-items:center; justify-content:center;
    color:#fff; font-size:16px; flex-shrink:0; transition:opacity .2s;
}
.btn-kirim:hover { opacity:.88; }

/* PLACEHOLDER CHAT */
.chat-placeholder { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; color:var(--muted); text-align:center; padding:40px; }
.chat-placeholder i { font-size:3rem; margin-bottom:12px; opacity:.3; }

@media(max-width:680px) {
    .chat-wrap { grid-template-columns:1fr; height:auto; }
    .chat-list { max-height:200px; }
    .chat-area { height:60vh; }
}
</style>

<div class="chat-wrap">

    <!-- LIST SIDEBAR -->
    <div class="chat-list">
        <div class="chat-list-head">💬 Pesan</div>
        <div class="chat-list-body">
            <?php
            $has_list = false;
            // Tambahkan produk aktif jika baru mulai chat
            if ($produk_id && isset($pr)) {
                $cek_existing = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM chat WHERE produk_id=$produk_id AND pembeli_id=$user_id LIMIT 1"));
                if (!$cek_existing) {
                    // Produk baru, tampilkan di list walau belum ada chat
                    echo '<a href="chat.php?produk_id=' . $produk_id . '" class="chat-item active">';
                    $fotoSrc = !empty($pr['foto_utama']) ? '../uploads/produk/' . escape($pr['foto_utama']) : 'https://placehold.co/44x44/FDE8F2/D63384?text=CG';
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

        <!-- HEADER -->
        <div class="chat-header">
            <?php $fotoSrc = !empty($pr['foto_utama']) ? '../uploads/produk/' . escape($pr['foto_utama']) : 'https://placehold.co/40x40/FDE8F2/D63384?text=CG'; ?>
            <img src="<?= $fotoSrc ?>" alt="produk">
            <div class="chat-header-info">
                <div class="nama"><?= escape($pr['nama_barang']) ?></div>
                <div class="sub">Chat dengan Cloudy Girls · Rp <?= number_format($pr['harga'],0,',','.') ?></div>
            </div>
            <a href="detail.php?id=<?= $produk_id ?>" style="margin-left:auto;font-size:12px;color:var(--pink-deep);font-weight:600;">
                <i class="bi bi-box-arrow-up-right"></i> Lihat Produk
            </a>
        </div>

        <!-- PESAN -->
        <div class="chat-messages" id="chatMessages">
            <?php if (empty($pesan_list)): ?>
            <div style="text-align:center;color:var(--muted);font-size:13px;padding:20px;">
                Mulai percakapan dengan penjual 👋
            </div>
            <?php endif; ?>

            <?php foreach ($pesan_list as $p):
                $isSaya = $p['pengirim'] === 'pembeli';
                $waktu  = date('H:i', strtotime($p['created_at']));
                $tgl    = date('d M', strtotime($p['created_at']));
            ?>
            <div class="bubble-wrap <?= $isSaya ? 'saya' : 'admin' ?>">
                <?php if (!$isSaya): ?>
                <div style="width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#D63384,#F06292);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0;">CG</div>
                <?php endif; ?>
                <div>
                    <div class="bubble"><?= nl2br(escape($p['pesan'])) ?></div>
                    <div class="bubble-time"><?= $tgl ?> <?= $waktu ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- INPUT -->
        <form method="POST" class="chat-input-area">
            <textarea name="pesan" class="chat-input" placeholder="Tulis pesan..." rows="1"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();this.form.submit();}"></textarea>
            <button type="submit" class="btn-kirim"><i class="bi bi-send-fill"></i></button>
        </form>

        <?php else: ?>
        <div class="chat-placeholder">
            <i class="bi bi-chat-dots"></i>
            <p>Pilih percakapan atau buka produk<br>dan klik "Tanya Penjual"</p>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Auto scroll ke bawah
const msgs = document.getElementById('chatMessages');
if (msgs) msgs.scrollTop = msgs.scrollHeight;

// Auto resize textarea
const ta = document.querySelector('.chat-input');
if (ta) {
    ta.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
    });
}
</script>

<?php include '../includes/footer.php'; ?>