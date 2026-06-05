<?php
/**
 * footer.php — Footer reusable untuk Cloudy Girls
 * Dipanggil di: home.php, index.php, dan halaman lain
 * 
 * UPDATE: Perbaikan link Instagram agar redirect ke akun penjual dengan benar
 */

if (!isset($toko)) {
    $q_toko = isset($conn) ? mysqli_query($conn, "SELECT * FROM pengaturan_toko LIMIT 1") : false;
    $toko   = $q_toko ? mysqli_fetch_assoc($q_toko) : [];
}
if (!isset($kategori_list)) {
    $kategori_list = ['Atasan', 'Bawahan', 'Dress/Gamis', 'Outer', 'Hijab & Aksesoris'];
}
if (!function_exists('escape')) {
    function escape($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
}

// Deteksi otomatis: link kategori sesuai halaman yang memanggil footer
$base = strpos($_SERVER['PHP_SELF'], '/pages/') !== false ? 'home.php' : 'index.php';
?>

<!-- ══════════════════════════════════════
     FOOTER — Cloudy Girls
     ══════════════════════════════════════ -->
<footer>
    <div class="footer-inner">

        <!-- Kolom 1 · Brand & Sosial -->
        <div>
            <span class="footer-logo">Cloudy <span>Girls</span></span>
            <p style="font-size:13px;color:var(--muted);line-height:1.7;max-width:320px;">
                <?= escape($toko['deskripsi'] ?? 'Toko preloved pakaian wanita berkualitas dari Banyuwangi.') ?>
            </p>
            <div style="display:flex;gap:12px;margin-top:14px;flex-wrap:wrap;">
                <?php if (!empty($toko['no_hp'])): ?>
                <a href="https://wa.me/<?= escape($toko['no_hp']) ?>"
                   style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--green);font-weight:600;">
                    <i class="bi bi-whatsapp"></i> Hubungi Kami
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Kolom 2 · Kategori -->
        <div>
            <h4 style="font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px;color:var(--text);">Kategori</h4>
            <div class="footer-links">
                <?php foreach ($kategori_list as $kat): ?>
                <a href="<?= $base ?>?kategori=<?= urlencode($kat) ?>"><?= escape($kat) ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Kolom 3 · Rules -->
        <div>
            <h4 style="font-size:12px;font-weight:700;letter-spacing:.5px;margin-bottom:14px;color:var(--text);">Rules</h4>
            <div class="footer-links">
                <a href="#" onclick="bukaModal('modal-cara-belanja');return false;">Cara Belanja</a>
                <a href="#" onclick="bukaModal('modal-syarat');return false;">Syarat &amp; Ketentuan</a>
                <a href="#" onclick="bukaModal('modal-fpfg');return false;">First Pay First Get</a>
                <a href="#" onclick="bukaModal('modal-pengembalian');return false;">Kebijakan Pengembalian</a>
            </div>
        </div>

    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <p style="font-size:12px;color:var(--muted);">© <?= date('Y') ?> Cloudy Girls — Second Hand, First Love</p>
    </div>
</footer>

<!-- ══════════════════════════════════════
     MODAL RULES
     ══════════════════════════════════════ -->

<style>
.cg-overlay {
    display: none;
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(45,21,32,.45);
    backdrop-filter: blur(4px);
    align-items: center; justify-content: center;
    padding: 20px;
}
.cg-overlay.aktif { display: flex; }
.cg-modal {
    background: #fff;
    border-radius: 20px;
    width: 100%; max-width: 480px;
    max-height: 88vh;
    overflow-y: auto;
    box-shadow: 0 24px 64px rgba(217,79,110,.22);
    animation: cgSlideUp .25s ease;
    position: relative;
}
@keyframes cgSlideUp {
    from { opacity:0; transform: translateY(30px); }
    to   { opacity:1; transform: translateY(0); }
}
.cg-modal-head {
    padding: 22px 24px 14px;
    border-bottom: 1.5px solid #FFB3C6;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; background: #fff; z-index: 1;
    border-radius: 20px 20px 0 0;
}
.cg-modal-head h3 {
    font-family: 'Poppins', sans-serif;
    font-size: 17px; font-weight: 700; color: #2D1520;
}
.cg-modal-head h3 span { color: #D94F6E; }
.cg-close {
    width: 32px; height: 32px; border-radius: 50%;
    border: 1.5px solid #FFB3C6; background: #FFF0F4;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; font-size: 15px; color: #C48899;
    transition: all .2s; flex-shrink: 0;
}
.cg-close:hover { background: #D94F6E; color: #fff; border-color: #D94F6E; }
.cg-modal-body { padding: 20px 24px 28px; }
.cg-rule-item {
    display: flex; gap: 12px; align-items: flex-start;
    margin-bottom: 16px;
}
.cg-rule-num {
    min-width: 28px; height: 28px; border-radius: 50%;
    background: #FFD6E0; color: #D94F6E;
    font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
    font-family: 'Poppins', sans-serif;
}
.cg-rule-text {
    font-family: 'Lato', sans-serif;
    font-size: 13.5px; color: #3D2028; line-height: 1.7;
}
.cg-rule-text strong { color: #D94F6E; }
.cg-rule-divider { border: none; border-top: 1px dashed #FFB3C6; margin: 18px 0; }
.cg-note {
    font-family: 'Lato', sans-serif;
    background: #FFF0F4; border-left: 3px solid #FF6FA3;
    border-radius: 0 10px 10px 0;
    padding: 12px 14px; font-size: 13px;
    color: #6B3A4A; line-height: 1.7;
    margin-top: 4px;
}
.cg-note strong { color: #D94F6E; }
</style>

<!-- Modal 1: Cara Belanja -->
<div class="cg-overlay" id="modal-cara-belanja" onclick="tutupModalLuar(event,this)">
    <div class="cg-modal">
        <div class="cg-modal-head">
            <h3>Cara <span>Belanja</span></h3>
            <div class="cg-close" onclick="tutupModal('modal-cara-belanja')">✕</div>
        </div>
        <div class="cg-modal-body">
            <div class="cg-rule-item">
                <div class="cg-rule-num">1</div>
                <div class="cg-rule-text"><strong>Pilih produk</strong> yang kamu suka — wajib baca caption dengan teliti, termasuk harga, ukuran, dan kondisi barang.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">2</div>
                <div class="cg-rule-text">Klik tombol <strong>Beli Sekarang</strong> di halaman detail produk. Bisa juga klik <strong>Nego Harga</strong> atau <strong>Tanya Penjual</strong> jika perlu.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">3</div>
                <div class="cg-rule-text">Pilih <strong>metode transaksi</strong> — COD (khusus Banyuwangi Kota) atau Transfer (BCA/DANA, kirim via JNT/JNE) — lalu isi alamat pengiriman.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">4</div>
                <div class="cg-rule-text">Klik <strong>Buat Pesanan</strong> untuk mengkonfirmasi pesananmu ke admin.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">5</div>
                <div class="cg-rule-text">
                    Lakukan <strong>pembayaran</strong> sesuai metode yang dipilih:<br><br>
                    🛵 <strong>COD</strong> — pembayaran dilakukan <strong>langsung di tempat</strong> saat barang diterima / saat kamu datang ke rumah penjual.<br><br>
                    💳 <strong>Transfer</strong> — lakukan transfer ke rekening admin, lalu <strong>wajib kirim bukti transfer</strong> ke admin agar pesanan segera diproses. ✨
                </div>
            </div>
            <hr class="cg-rule-divider">
            <div class="cg-note">
                Dengan melakukan pembelian dan payment, kamu dianggap sudah <strong>menyetujui seluruh rules toko</strong> kami. Be a smart &amp; responsible buyer, Cloudy's! 🛍️.
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Syarat & Ketentuan -->
<div class="cg-overlay" id="modal-syarat" onclick="tutupModalLuar(event,this)">
    <div class="cg-modal">
        <div class="cg-modal-head">
            <h3>Syarat <span>&amp; Ketentuan</span></h3>
            <div class="cg-close" onclick="tutupModal('modal-syarat')">✕</div>
        </div>
        <div class="cg-modal-body">
            <div class="cg-rule-item">
                <div class="cg-rule-num">1</div>
                <div class="cg-rule-text"><strong>Wajib baca caption</strong> — harga dan ukuran sudah tertera lengkap di setiap produk.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">2</div>
                <div class="cg-rule-text"><strong>Barang yang sudah dibeli tidak dapat dikembalikan / ditukar</strong> dengan alasan apapun (kekecilan, kebesaran, berubah pikiran, tidak sesuai ekspektasi), <strong>kecuali</strong> ada kesalahan pengiriman dari admin.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">3</div>
                <div class="cg-rule-text">Membeli dan melakukan payment artinya kamu sudah <strong>setuju dengan rules toko</strong> kami sepenuhnya.</div>
            </div>
            <hr class="cg-rule-divider">
            <div class="cg-note">
                Be a smart &amp; responsible buyer, Cloudy's! 🛍️.
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: First Pay First Get -->
<div class="cg-overlay" id="modal-fpfg" onclick="tutupModalLuar(event,this)">
    <div class="cg-modal">
        <div class="cg-modal-head">
            <h3>First Pay <span>First Get</span></h3>
            <div class="cg-close" onclick="tutupModal('modal-fpfg')">✕</div>
        </div>
        <div class="cg-modal-body">
            <div class="cg-rule-item">
                <div class="cg-rule-num">💡</div>
                <div class="cg-rule-text">Sistem kami adalah <strong>siapa cepat dia dapat</strong> — First Pay, First Get.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">⚠️</div>
                <div class="cg-rule-text">Jika ada <strong>beberapa pembeli</strong> yang menginginkan produk yang sama, barang akan diberikan kepada yang <strong>lebih dulu melakukan payment</strong>.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">🕐</div>
                <div class="cg-rule-text">Jika kamu sudah siap buat payment, <strong>segera lakukan</strong> — kami akan mengamankan item tersebut untukmu sampai kamu menyelesaikan payment.</div>
            </div>
            <hr class="cg-rule-divider">
            <div class="cg-note">
                Mohon pengertiannya ya, supaya semua <strong>fair buat semua pembeli</strong>. Jadi pastikan kalau udah klik langsung bayar — ga keambil orang lain lho!! 🙏.
            </div>
        </div>
    </div>
</div>

<!-- Modal 4: Kebijakan Pengembalian -->
<div class="cg-overlay" id="modal-pengembalian" onclick="tutupModalLuar(event,this)">
    <div class="cg-modal">
        <div class="cg-modal-head">
            <h3>Kebijakan <span>Pengembalian</span></h3>
            <div class="cg-close" onclick="tutupModal('modal-pengembalian')">✕</div>
        </div>
        <div class="cg-modal-body">
            <div class="cg-rule-item">
                <div class="cg-rule-num">✅</div>
                <div class="cg-rule-text">Pengembalian barang <strong>hanya berlaku</strong> jika terjadi <strong>kesalahan pengiriman dari pihak admin</strong> (barang yang dikirim tidak sesuai pesanan).</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">❌</div>
                <div class="cg-rule-text">Pengembalian <strong>tidak berlaku</strong> untuk alasan: kekecilan, kebesaran, berubah pikiran, atau tidak sesuai ekspektasi pribadi.</div>
            </div>
            <div class="cg-rule-item">
                <div class="cg-rule-num">📸</div>
                <div class="cg-rule-text">Jika ada kesalahan dari admin, segera <strong>hubungi kami via Pesan</strong> dengan menyertakan foto barang yang diterima sebagai bukti.</div>
            </div>
            <hr class="cg-rule-divider">
            <div class="cg-note">
                Kami berkomitmen untuk selalu <strong>teliti dalam setiap pengiriman</strong>. Terima kasih atas kepercayaan dan pengertianmu, Cloudy's!🙏.
            </div>
        </div>
    </div>
</div>

<script>
function bukaModal(id) {
    document.getElementById(id).classList.add('aktif');
    document.body.style.overflow = 'hidden';
}
function tutupModal(id) {
    document.getElementById(id).classList.remove('aktif');
    document.body.style.overflow = '';
}
function tutupModalLuar(e, overlay) {
    if (e.target === overlay) {
        overlay.classList.remove('aktif');
        document.body.style.overflow = '';
    }
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.cg-overlay.aktif').forEach(function(m) {
            m.classList.remove('aktif');
        });
        document.body.style.overflow = '';
    }
});
</script>