<?php
/**
 * sidebar.php — Sidebar terpusat untuk semua halaman penjual
 */

$total_unread     = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM chat WHERE pengirim='pembeli' AND sudah_dibaca=0"))[0] ?? 0;
$nego_menunggu    = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM nego_harga WHERE status='menunggu'"))[0] ?? 0;
$pesanan_menunggu = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM pesanan WHERE status='menunggu'"))[0] ?? 0;
$ulasan_baru      = mysqli_fetch_row(mysqli_query($conn, "SELECT COUNT(*) FROM ulasan WHERE DATE(created_at) = CURDATE()"))[0] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);

function sidebar_active($page, $current) {
    return $page === $current ? 'active' : '';
}
?>
<style>
    
@media (max-width:900px) {
    .sidebar {
        position:fixed !important;
        left:0 !important;
        top:0 !important;
        height:100vh !important;
        width:220px !important;
        max-width:65vw !important;
        border-radius:0 24px 24px 0 !important;
        overflow:hidden !important;
        display:flex !important;
        flex-direction:column !important; /* ← sama seperti desktop */
        transform:translateX(-100%) !important;
        transition:transform 0.3s ease !important;
        z-index:99 !important;
    }
    .sidebar.active {
        transform:translateX(0) !important;
    }
    .sidebar-nav {
        flex: 1 !important;
        overflow-y: auto !important;
        scrollbar-width: none !important;
    }
    .sidebar-nav::-webkit-scrollbar {
        display: none !important;
    }
    .sidebar-footer {
        flex-shrink: 0 !important;
    }
}
</style>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div style="display:flex; align-items:center; gap:12px;">
            <img src="../uploads/toko/logo.png" class="logo-img"
                 onerror="this.src='https://placehold.co/32x32/FFE4EE/FF4081?text=CG'">
            <div class="logo" style="line-height:1; margin:0;">Cloudy <span>Girls</span></div>
        </div>
        <small>Seller Dashboard</small>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>

        <a href="dashboard.php" class="nav-item <?= sidebar_active('dashboard.php', $current_page) ?>">
            <i class="bi bi-grid-1x2"></i> Dashboard
        </a>

        <a href="produk.php" class="nav-item <?= sidebar_active('produk.php', $current_page) ?>">
            <i class="bi bi-handbag"></i> Produk
        </a>

        <a href="pesanan.php" class="nav-item <?= in_array($current_page, ['pesanan.php','detail_pesanan.php']) ? 'active' : '' ?>">
            <i class="bi bi-bag-check"></i> Pesanan
            <?php if ($pesanan_menunggu > 0): ?>
                <span class="badge-notif"><?= $pesanan_menunggu ?></span>
            <?php endif; ?>
        </a>

        <a href="chat.php" class="nav-item <?= sidebar_active('chat.php', $current_page) ?>">
            <i class="bi bi-chat-dots"></i> Chat
            <?php if ($total_unread > 0): ?>
                <span class="badge-notif"><?= $total_unread ?></span>
            <?php endif; ?>
        </a>

        <a href="nego.php" class="nav-item <?= sidebar_active('nego.php', $current_page) ?>">
            <i class="bi bi-tags"></i> Nego Harga
            <?php if ($nego_menunggu > 0): ?>
                <span class="badge-notif"><?= $nego_menunggu ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section">Lainnya</div>

        <a href="ulasan.php" class="nav-item <?= sidebar_active('ulasan.php', $current_page) ?>">
            <i class="bi bi-star"></i> Ulasan
        </a>

        <a href="pengaturan.php" class="nav-item <?= sidebar_active('pengaturan.php', $current_page) ?>">
            <i class="bi bi-gear"></i> Pengaturan
        </a>

        <a href="../index.php" target="_blank" class="nav-item nav-item-toko">
            <i class="bi bi-shop"></i> Lihat Toko
            <i class="bi bi-box-arrow-up-right nav-ext-icon"></i>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="../auth/logout_penjual.php" class="btn-logout">
            <i class="bi bi-box-arrow-left"></i> Keluar
        </a>
    </div>
</aside>

<script>
document.querySelectorAll('.sidebar .nav-item, .sidebar .btn-logout').forEach(function(link) {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 900) {
            document.querySelector('.sidebar').classList.remove('active');
            var overlay = document.getElementById('sidebarOverlay');
            if (overlay) overlay.classList.remove('active');
        }
    });
});
</script>