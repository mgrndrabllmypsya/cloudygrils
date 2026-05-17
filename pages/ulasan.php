<?php
session_start();
require_once '../config/koneksi.php';

if (!isset($_SESSION['login']) || !$_SESSION['login']) {
    header("Location: ../auth/login.php"); exit;
}

function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }

$user_id    = $_SESSION['user_id'];
$pesanan_id = (int)($_GET['pesanan_id'] ?? 0);

if (!$pesanan_id) {
    header("Location: pesanan.php"); exit;
}

// Ambil data pesanan + produk
$stmt = $conn->prepare("
    SELECT p.*, pr.nama_barang, pr.foto_utama, pr.id AS produk_id
    FROM pesanan p
    JOIN produk pr ON pr.id = p.produk_id
    WHERE p.id = ? AND p.pembeli_id = ? AND p.status = 'selesai'
    LIMIT 1
");
$stmt->bind_param("ii", $pesanan_id, $user_id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    header("Location: pesanan.php"); exit;
}

// Cek sudah pernah beri ulasan belum
$cek = $conn->prepare("SELECT id FROM ulasan WHERE pesanan_id = ? AND pembeli_id = ?");
$cek->bind_param("ii", $pesanan_id, $user_id);
$cek->execute();
$sudah_ulasan = $cek->get_result()->fetch_assoc();
$cek->close();

$sukses = false;
$error  = '';

// Proses submit ulasan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$sudah_ulasan) {
    $rating  = (int)($_POST['rating'] ?? 0);
    $komentar = trim($_POST['komentar'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Pilih rating bintang terlebih dahulu.';
    } elseif (strlen($komentar) < 5) {
        $error = 'Ulasan minimal 5 karakter.';
    } else {
        $produk_id = $pesanan['produk_id'];
        $ins = $conn->prepare("
            INSERT INTO ulasan (pesanan_id, pembeli_id, produk_id, rating, komentar, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $ins->bind_param("iiiis", $pesanan_id, $user_id, $produk_id, $rating, $komentar);
        if ($ins->execute()) {
            $sukses = true;
            $sudah_ulasan = true;
        } else {
            $error = 'Gagal menyimpan ulasan. Coba lagi.';
        }
        $ins->close();
    }
}

$foto = $pesanan['foto_utama'] ?? '';
$fotoSrc = $foto ? '../uploads/produk/' . $foto : 'https://placehold.co/80x80/FDE8F2/D63384?text=CG';

$page_title = 'Beri Ulasan — Cloudy Girls';
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

.page-wrap { max-width:560px; margin:0 auto; padding:40px 20px 80px; }

.back-btn {
    display:inline-flex; align-items:center; gap:6px;
    font-size:13px; color:var(--muted); margin-bottom:24px;
    text-decoration:none; transition:color .2s;
}
.back-btn:hover { color:var(--pink-deep); }

.page-title {
    font-family:'Playfair Display',serif;
    font-size:24px; font-weight:700;
    color:var(--dark); margin-bottom:6px;
}
.page-sub { font-size:13px; color:var(--muted); margin-bottom:28px; }

/* PRODUK MINI */
.produk-mini {
    display:flex; gap:14px; align-items:center;
    background:var(--white); border:1.5px solid var(--border);
    border-radius:14px; padding:14px 16px; margin-bottom:24px;
}
.produk-mini img {
    width:60px; height:72px; border-radius:8px;
    object-fit:cover; border:1px solid var(--border); flex-shrink:0;
}
.produk-mini .nama { font-weight:600; font-size:14px; color:var(--dark); }
.produk-mini .resi { font-size:12px; color:var(--muted); margin-top:3px; }

/* FORM CARD */
.form-card {
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px; padding:24px;
}

/* STAR RATING */
.star-label { font-size:13px; font-weight:600; color:var(--dark); margin-bottom:10px; display:block; }
.stars {
    display:flex; flex-direction:row-reverse;
    justify-content:flex-end; gap:6px; margin-bottom:20px;
}
.stars input { display:none; }
.stars label {
    font-size:36px; color:#e5e7eb; cursor:pointer;
    transition:color .15s; line-height:1;
}
.stars input:checked ~ label,
.stars label:hover,
.stars label:hover ~ label {
    color:#FBBF24;
}

/* TEXTAREA */
.form-group { margin-bottom:18px; }
.form-group label {
    display:block; font-size:12px; font-weight:600;
    color:var(--muted); text-transform:uppercase;
    letter-spacing:.7px; margin-bottom:8px;
}
.form-group textarea {
    width:100%; min-height:110px;
    background:var(--pink-blush); border:1.5px solid var(--border);
    border-radius:10px; padding:12px 14px;
    font-family:'DM Sans',sans-serif; font-size:14px;
    color:var(--dark); outline:none; resize:vertical;
    transition:border-color .2s;
}
.form-group textarea:focus { border-color:var(--pink-deep); }
.form-group textarea::placeholder { color:var(--muted); }

/* CHAR COUNTER */
.char-count { font-size:11px; color:var(--muted); text-align:right; margin-top:4px; }

/* BUTTON */
.btn-submit {
    width:100%; padding:13px;
    background:linear-gradient(135deg, var(--pink-deep), var(--pink-mid));
    color:#fff; border:none; border-radius:12px;
    font-size:14px; font-weight:700; cursor:pointer;
    font-family:'DM Sans',sans-serif; transition:opacity .2s;
}
.btn-submit:hover { opacity:.88; }
.btn-submit:disabled { opacity:.5; cursor:not-allowed; }

/* ALERT */
.alert {
    padding:12px 16px; border-radius:10px;
    font-size:13px; margin-bottom:18px;
    display:flex; align-items:center; gap:8px;
}
.alert-error { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
.alert-success { background:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }

/* SUDAH ULASAN */
.done-box {
    text-align:center; padding:40px 20px;
    background:var(--white); border:1.5px solid var(--border);
    border-radius:16px;
}
.done-box .icon { font-size:3rem; margin-bottom:12px; display:block; }
.done-box h3 {
    font-family:'Playfair Display',serif;
    font-size:20px; margin-bottom:8px;
}
.done-box p { font-size:13px; color:var(--muted); margin-bottom:20px; }
.btn-back-home {
    display:inline-flex; align-items:center; gap:6px;
    padding:10px 24px;
    background:linear-gradient(135deg, var(--pink-deep), var(--pink-mid));
    color:#fff; border-radius:20px; font-size:13px; font-weight:600;
    text-decoration:none; transition:opacity .2s;
}
.btn-back-home:hover { opacity:.88; color:#fff; }
</style>

<div class="page-wrap">
    <a href="pesanan.php" class="back-btn">← Kembali ke Pesanan</a>

    <?php if ($sukses || $sudah_ulasan): ?>
    <!-- SUDAH BERI ULASAN -->
    <div class="done-box">
        <span class="icon">⭐</span>
        <h3>Terima kasih atas ulasanmu!</h3>
        <p>Ulasanmu sangat membantu pembeli lain untuk memilih produk yang tepat.</p>
        <a href="pesanan.php" class="btn-back-home">Kembali ke Pesanan</a>
    </div>

    <?php else: ?>

    <div class="page-title">⭐ Beri Ulasan</div>
    <p class="page-sub">Bagikan pengalamanmu dengan produk ini</p>

    <!-- INFO PRODUK -->
    <div class="produk-mini">
        <img src="<?= escape($fotoSrc) ?>" alt="produk"
             onerror="this.src='https://placehold.co/60x72/FDE8F2/D63384?text=CG'">
        <div>
            <div class="nama"><?= escape($pesanan['nama_barang']) ?></div>
            <div class="resi">Pesanan #<?= escape($pesanan['kode_pesanan']) ?></div>
            <?php if ($pesanan['no_resi']): ?>
            <div class="resi">Resi: <?= escape($pesanan['no_resi']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- FORM ULASAN -->
    <div class="form-card">
        <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= escape($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="formUlasan">
            <!-- RATING BINTANG -->
            <span class="star-label">Rating Produk</span>
            <div class="stars">
                <input type="radio" name="rating" id="s5" value="5">
                <label for="s5" title="5 bintang">★</label>
                <input type="radio" name="rating" id="s4" value="4">
                <label for="s4" title="4 bintang">★</label>
                <input type="radio" name="rating" id="s3" value="3">
                <label for="s3" title="3 bintang">★</label>
                <input type="radio" name="rating" id="s2" value="2">
                <label for="s2" title="2 bintang">★</label>
                <input type="radio" name="rating" id="s1" value="1">
                <label for="s1" title="1 bintang">★</label>
            </div>

            <!-- KOMENTAR -->
            <div class="form-group">
                <label>Ulasan Kamu</label>
                <textarea name="komentar" id="komentar" maxlength="500"
                    placeholder="Ceritakan pengalamanmu dengan produk ini... kualitas, kondisi, pengiriman, dll."
                    ><?= escape($_POST['komentar'] ?? '') ?></textarea>
                <div class="char-count"><span id="charCount">0</span>/500</div>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                ⭐ Kirim Ulasan
            </button>
        </form>
    </div>

    <?php endif; ?>
</div>

<script>
// Char counter
const komentar = document.getElementById('komentar');
const charCount = document.getElementById('charCount');
if (komentar) {
    komentar.addEventListener('input', () => {
        charCount.textContent = komentar.value.length;
    });
    charCount.textContent = komentar.value.length;
}

// Prevent double submit
const form = document.getElementById('formUlasan');
const btn  = document.getElementById('btnSubmit');
if (form) {
    form.addEventListener('submit', () => {
        if (btn) { btn.disabled = true; btn.textContent = 'Menyimpan...'; }
    });
}
</script>

<?php include '../includes/footer.php'; ?>