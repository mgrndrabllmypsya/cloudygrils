<?php
/**
 * sidebar.php — Komponen Sidebar Penjual (Cloudy Girls)
 * -------------------------------------------------------
 * Cara pakai di setiap halaman:
 *
 *   1. Pastikan session sudah di-start dan koneksi DB sudah di-require
 *      SEBELUM include file ini.
 *
 *   2. Tentukan halaman aktif dengan mendefinisikan variabel $active_page
 *      SEBELUM include file ini. Nilai yang valid:
 *
 *        'dashboard'   → dashboard.php
 *        'produk'      → produk.php
 *        'pesanan'     → pesanan.php  / detail_pesanan.php
 *        'chat'        → chat.php
 *        'nego'        → nego.php
 *        'ulasan'      → ulasan.php
 *        'pengaturan'  → pengaturan.php
 *
 *   3. Contoh penggunaan di dashboard.php:
 *
 *        <?php
 *        session_name('session_penjual');
 *        session_start();
 *        require_once '../config/koneksi.php';
 *        // ... logika halaman ...
 *        $active_page = 'dashboard';
 *        ?>
 *        <!DOCTYPE html>
 *        <html>
 *        <head>...</head>
 *        <body>
 *        <?php include 'sidebar.php'; ?>
 *        <div class="main">
 *            ...
 *        </div>
 *        </body>
 *        </html>
 *
 * -------------------------------------------------------
 * CSS yang dibutuhkan sudah ada di dalam file ini (di-inject
 * via <style> tag sekali saja). Sisipkan include ini SETELAH
 * tag <body> pembuka.
 * -------------------------------------------------------
 */

// ── Hitung notifikasi chat belum dibaca ──────────────────────────────────────
if (!isset($total_unread)) {
    $total_unread = mysqli_fetch_row(
        mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0")
    )[0] ?? 0;
}

// ── Hitung nego menunggu (untuk badge di nav) ────────────────────────────────
if (!isset($total_nego_menunggu)) {
    $total_nego_menunggu = mysqli_fetch_row(
        mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'")
    )[0] ?? 0;
}

// ── Data admin / penjual yang login ─────────────────────────────────────────
$sb_nama = $_SESSION['admin_nama'] ?? ($_SESSION['penjual_nama'] ?? 'Admin');

// ── Logo toko (opsional, jika tabel pengaturan_toko ada) ────────────────────
$sb_logo_path = null;
if (!isset($settings)) {
    $q_set = mysqli_query($conn, "SELECT logo FROM pengaturan_toko WHERE id=1");
    $settings = $q_set ? (mysqli_fetch_assoc($q_set) ?? []) : [];
}
if (!empty($settings['logo'])) {
    $sb_logo_path = '../uploads/toko/' . $settings['logo'];
}

// ── Helper: apakah menu ini aktif? ──────────────────────────────────────────
if (!function_exists('sb_active')) {
    function sb_active($page) {
        global $active_page;
        return (isset($active_page) && $active_page === $page) ? ' active' : '';
    }
}

// ── Helper escape (jika belum didefinisikan di halaman pemanggil) ────────────
if (!function_exists('sb_esc')) {
    function sb_esc($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}
?>

<style>
/* ════════════════════════════════════════════════════════
   SIDEBAR — Cloudy Girls Admin
   Ditulis sekali, dipakai di semua halaman penjual.
   ════════════════════════════════════════════════════════ */
.sidebar {
    width: 240px;
    background: linear-gradient(180deg, #F4A7C3 0%, #E8719A 45%, #D4547F 100%);
    display: flex;
    flex-direction: column;
    position: fixed;
    top: 0; left: 0; bottom: 0;
    z-index: 50;
    box-shadow: 4px 0 28px rgba(212, 84, 127, .3);
    overflow: hidden;
}

/* ── Logo ── */
.sidebar-logo {
    padding: 24px 24px 20px;
    border-bottom: 1.5px solid rgba(255,255,255,.2);
    background: rgba(255,255,255,.12);
    flex-shrink: 0;
}
.sidebar-logo .logo {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 900;
    color: #fff;
}
.sidebar-logo .logo span { color: #FFE0EF; }
.sidebar-logo small {
    display: block;
    font-size: 10px;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: rgba(255,255,255,.65);
    margin-top: 2px;
}

/* ── Nav ── */
.sidebar-nav {
    flex: 1;
    padding: 16px 12px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,.25) transparent;
}
.sidebar-nav::-webkit-scrollbar { width: 3px; }
.sidebar-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,.25); border-radius: 4px; }

.nav-section {
    font-size: 10px;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    color: rgba(255,255,255,.55);
    padding: 14px 14px 6px;
    font-weight: 600;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,.82);
    text-decoration: none;
    transition: all .2s;
    border-left: 3px solid transparent;
}
.nav-item:hover {
    background: rgba(255,255,255,.2);
    color: #fff;
}
.nav-item.active {
    background: rgba(255,255,255,.28);
    color: #fff;
    font-weight: 600;
    border-left-color: #fff;
}
.nav-item i {
    font-size: 16px;
    width: 20px;
    flex-shrink: 0;
}

/* ── Badge notifikasi ── */
.badge-notif {
    background: #fff;
    color: #D4547F;
    font-size: 10px;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 10px;
    margin-left: auto;
    flex-shrink: 0;
}

/* ── Footer / Info admin ── */
.sidebar-footer {
    padding: 14px 12px;
    border-top: 1.5px solid rgba(255,255,255,.2);
    background: rgba(0,0,0,.1);
    flex-shrink: 0;
}

/* Card admin dengan dropdown */
.sb-admin-wrap { position: relative; margin-bottom: 8px; }

.sb-admin-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    background: rgba(255,255,255,.18);
    border-radius: 10px;
    border: 1.5px solid rgba(255,255,255,.3);
    cursor: pointer;
    user-select: none;
    transition: all .2s;
}
.sb-admin-card:hover {
    background: rgba(255,255,255,.28);
    border-color: rgba(255,255,255,.6);
}
.sb-admin-avatar {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: rgba(255,255,255,.3);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 13px; color: #fff;
    flex-shrink: 0;
    overflow: hidden;
    border: 2px solid rgba(255,255,255,.5);
}
.sb-admin-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.sb-admin-info { flex: 1; min-width: 0; }
.sb-admin-info .name  { font-size: 13px; font-weight: 600; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.sb-admin-info .role  { font-size: 10px; color: rgba(255,255,255,.65); }
.sb-chevron { font-size: 11px; color: rgba(255,255,255,.7); transition: transform .2s; }
.sb-admin-card.open .sb-chevron { transform: rotate(180deg); }

/* Dropdown */
.sb-dropdown {
    display: none;
    position: absolute;
    bottom: calc(100% + 8px);
    left: 0; right: 0;
    background: #fff;
    border: 1.5px solid #F4A7C3;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 8px 28px rgba(212,84,127,.2);
    z-index: 100;
}
.sb-dropdown.show { display: block; }

.sb-dropdown-header {
    padding: 12px 14px;
    border-bottom: 1px solid #F4A7C3;
    display: flex; align-items: center; gap: 10px;
    background: linear-gradient(135deg, #FFE0EF, #FFF0F5);
}
.sb-dh-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: linear-gradient(135deg, #F4A7C3, #E8719A);
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 14px; color: #fff;
    flex-shrink: 0; overflow: hidden;
}
.sb-dh-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.sb-dh-name { font-size: 13px; font-weight: 600; color: #1A1A1A; }
.sb-dh-role { font-size: 10px; color: #BBA0B0; }

.sb-dropdown a {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    font-size: 13px; color: #444;
    text-decoration: none;
    transition: background .15s;
}
.sb-dropdown a:hover { background: #FFE8F2; color: #E8719A; }
.sb-dropdown a.danger { color: #FF1744; }
.sb-dropdown a.danger:hover { background: #FFEBEE; }
.sb-dropdown a i { font-size: 14px; width: 17px; }
.sb-dropdown hr { border: none; border-top: 1px solid #F4A7C3; margin: 0; }

/* Tombol logout (di bawah card) */
.sb-btn-logout {
    display: flex; align-items: center; gap: 8px;
    padding: 8px 14px; border-radius: 8px;
    font-size: 12px; color: rgba(255,255,255,.85);
    text-decoration: none;
    transition: background .2s;
    width: 100%;
}
.sb-btn-logout:hover { background: rgba(255,255,255,.2); color: #fff; }
</style>

<aside class="sidebar">

    <!-- Logo -->
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
        <small>Admin Panel</small>
    </div>

    <!-- Navigasi -->
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>

        <a href="dashboard.php"   class="nav-item<?= sb_active('dashboard') ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <a href="produk.php"      class="nav-item<?= sb_active('produk') ?>">
            <i class="bi bi-handbag"></i> Produk
        </a>

        <a href="pesanan.php"     class="nav-item<?= sb_active('pesanan') ?>">
            <i class="bi bi-bag-check"></i> Pesanan
        </a>

        <a href="chat.php"        class="nav-item<?= sb_active('chat') ?>">
            <i class="bi bi-chat-dots"></i> Chat
            <?php if ($total_unread > 0): ?>
                <span class="badge-notif"><?= $total_unread ?></span>
            <?php endif; ?>
        </a>

        <a href="nego.php"        class="nav-item<?= sb_active('nego') ?>">
            <i class="bi bi-tags"></i> Nego Harga
            <?php if ($total_nego_menunggu > 0): ?>
                <span class="badge-notif"><?= $total_nego_menunggu ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section">Lainnya</div>

        <a href="ulasan.php"      class="nav-item<?= sb_active('ulasan') ?>">
            <i class="bi bi-star"></i> Ulasan
        </a>

        <a href="pengaturan.php"  class="nav-item<?= sb_active('pengaturan') ?>">
            <i class="bi bi-gear"></i> Pengaturan
        </a>
    </nav>

    <!-- Footer: info admin + dropdown -->
    <div class="sidebar-footer">
        <div class="sb-admin-wrap">

            <!-- Dropdown menu -->
            <div class="sb-dropdown" id="sbDropdown">
                <div class="sb-dropdown-header">
                    <div class="sb-dh-avatar">
                        <?php if ($sb_logo_path && file_exists($sb_logo_path)): ?>
                            <img src="<?= sb_esc($sb_logo_path) ?>" alt="logo">
                        <?php else: ?>
                            <?= strtoupper(substr($sb_nama, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="sb-dh-name"><?= sb_esc($sb_nama) ?></div>
                        <div class="sb-dh-role">Administrator</div>
                    </div>
                </div>
                <a href="pengaturan.php"><i class="bi bi-gear"></i> Pengaturan Akun</a>
                <a href="../pages/home.php" target="_blank"><i class="bi bi-shop"></i> Lihat Toko</a>
                <hr>
                <a href="../auth/logout_penjual.php" class="danger"><i class="bi bi-box-arrow-left"></i> Keluar</a>
            </div>

            <!-- Kartu admin (klik untuk buka dropdown) -->
            <div class="sb-admin-card" id="sbAdminCard" onclick="sbToggleDropdown()">
                <div class="sb-admin-avatar">
                    <?php if ($sb_logo_path && file_exists($sb_logo_path)): ?>
                        <img src="<?= sb_esc($sb_logo_path) ?>" alt="logo">
                    <?php else: ?>
                        <?= strtoupper(substr($sb_nama, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="sb-admin-info">
                    <div class="name"><?= sb_esc($sb_nama) ?></div>
                    <div class="role">Administrator</div>
                </div>
                <i class="bi bi-chevron-up sb-chevron"></i>
            </div>
        </div>

        <a href="../auth/logout_penjual.php" class="sb-btn-logout">
            <i class="bi bi-box-arrow-left"></i> Keluar
        </a>
    </div>

</aside>

<script>
(function () {
    function sbToggleDropdown() {
        document.getElementById('sbDropdown').classList.toggle('show');
        document.getElementById('sbAdminCard').classList.toggle('open');
    }

    // Tutup dropdown jika klik di luar
    document.addEventListener('click', function (e) {
        var wrap = document.querySelector('.sb-admin-wrap');
        if (wrap && !wrap.contains(e.target)) {
            document.getElementById('sbDropdown').classList.remove('show');
            document.getElementById('sbAdminCard').classList.remove('open');
        }
    });

    // Ekspos fungsi ke global agar bisa dipanggil dari onclick=""
    window.sbToggleDropdown = sbToggleDropdown;
})();
</script>