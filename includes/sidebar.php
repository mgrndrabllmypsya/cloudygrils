<?php
// Tentukan halaman aktif otomatis
$current = basename($_SERVER['PHP_SELF']);

// Load logo & nama admin (jika belum di-load di halaman pemanggil)
if (!isset($admin_nama)) $admin_nama = $_SESSION['admin_nama'] ?? 'Admin';
if (!isset($logo_path))  $logo_path  = null;
?>
<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo">Cloudy <span>Girls</span></div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Menu</div>
        <a href="dashboard.php"   class="nav-item <?= $current === 'dashboard.php'   ? 'active' : '' ?>"><i class="bi bi-grid-1x2"></i> Dashboard</a>
        <a href="produk.php"      class="nav-item <?= $current === 'produk.php'      ? 'active' : '' ?>"><i class="bi bi-handbag"></i> Produk</a>
        <a href="pesanan.php"     class="nav-item <?= $current === 'pesanan.php'     ? 'active' : '' ?>"><i class="bi bi-bag-check"></i> Pesanan</a>
        <a href="chat.php"        class="nav-item <?= $current === 'chat.php'        ? 'active' : '' ?>"><i class="bi bi-chat-dots"></i> Chat</a>
        <a href="nego.php"        class="nav-item <?= $current === 'nego.php'        ? 'active' : '' ?>"><i class="bi bi-tags"></i> Nego Harga</a>
        <div class="nav-section">Lainnya</div>
        <a href="ulasan.php"      class="nav-item <?= $current === 'ulasan.php'      ? 'active' : '' ?>"><i class="bi bi-star"></i> Ulasan</a>
        <a href="pengaturan.php"  class="nav-item <?= $current === 'pengaturan.php'  ? 'active' : '' ?>"><i class="bi bi-gear"></i> Pengaturan</a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-card-wrap">
            <div class="admin-dropdown" id="adminDropdown">
                <div class="dropdown-header">
                    <div class="dh-avatar">
                        <?php if ($logo_path && file_exists($logo_path)): ?>
                            <img src="<?= escape($logo_path) ?>" alt="logo">
                        <?php else: ?>
                            <?= strtoupper(substr($admin_nama, 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="dh-name"><?= escape($admin_nama) ?></div>
                        <div class="dh-role">Administrator</div>
                    </div>
                </div>
                <a href="pengaturan.php" class="dropdown-item"><i class="bi bi-gear"></i> Pengaturan Akun</a>
                <a href="../pages/home.php" class="dropdown-item" target="_blank"><i class="bi bi-shop"></i> Lihat Toko</a>
                <div class="dropdown-divider"></div>
                <a href="../auth/logout_admin.php" class="dropdown-item danger"><i class="bi bi-box-arrow-left"></i> Keluar</a>
            </div>
            <div class="admin-card" id="adminCardBtn" onclick="toggleDropdown()">
                <div class="admin-avatar">
                    <?php if ($logo_path && file_exists($logo_path)): ?>
                        <img src="<?= escape($logo_path) ?>" alt="logo">
                    <?php else: ?>
                        <?= strtoupper(substr($admin_nama, 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <div class="admin-info">
                    <div class="name"><?= escape($admin_nama) ?></div>
                    <div class="role">Administrator</div>
                </div>
                <i class="bi bi-chevron-up chevron"></i>
            </div>
        </div>
        <a href="../auth/logout_admin.php" class="btn-logout"><i class="bi bi-box-arrow-left"></i> Keluar</a>
    </div>
</aside>