<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'Cloudy Girls' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
:root {
    --red: #F43F5E;
    --accent: #A78BFA;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: #FFF0F4; color: #2D1520; }
a { text-decoration: none; color: inherit; }

/* ── NAVBAR ── */
.navbar {
    position: sticky; top: 0; z-index: 999;
    background: rgba(255,255,255,0.92);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid #FFB3C6;
    padding: 0 clamp(12px, 4vw, 48px);
    height: 64px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px;
}

/* ── LOGO ── */
.navbar-logo {
    font-family: 'Playfair Display', serif;
    font-size: clamp(16px, 3vw, 22px);
    font-weight: 900;
    color: #1db899b1;
    letter-spacing: -.3px;
    display: flex; align-items: center; gap: 4px;
    flex-shrink: 0;
}
.navbar-logo span { color: #ff009db1; }
.logo-img {
    width: clamp(32px, 5vw, 45px);
    height: clamp(32px, 5vw, 45px);
    object-fit: contain;
}
@media (max-width: 320px) {
    .navbar-logo .logo-text { display: none; }
}

/* ── AKSI KANAN ── */
.navbar-actions {
    display: flex; align-items: center; gap: 4px;
    flex-shrink: 0;
}

/* ── ICON BUTTON ── */
.nav-icon-btn {
    position: relative;
    width: 40px; height: 40px; border-radius: 12px;
    background: transparent; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: #C48899; font-size: 18px;
    transition: background .2s, color .2s, transform .15s;
    -webkit-tap-highlight-color: transparent;
    flex-shrink: 0;
}
@media (max-width: 639px) {
    .nav-icon-btn { width: 36px; height: 36px; font-size: 17px; }
}
@media (hover: hover) {
    .nav-icon-btn:hover { background: #FFF0F4; color: #2D1520; transform: translateY(-1px); }
    .nav-icon-btn.love:hover { color: #D94F6E; background: rgba(244,63,94,.08); }
    .nav-icon-btn.msg:hover  { color: #C43860; background: rgba(217,79,110,.08); }
}

/* ── BADGE ── */
.nav-badge {
    position: absolute; top: -4px; right: -4px;
    min-width: 16px; height: 16px;
    border-radius: 10px; padding: 0 3px;
    background: var(--red); color: #fff;
    font-size: 9px; font-weight: 700;
    border: 2px solid #fff;
    display: flex; align-items: center; justify-content: center;
    pointer-events: none;
}

/* ── DIVIDER ── */
.nav-divider {
    width: 1px; height: 24px;
    background: #FFB3C6;
    margin: 0 2px;
    flex-shrink: 0;
}
@media (max-width: 360px) { .nav-divider { display: none; } }

/* ── PROFILE DROPDOWN ── */
.profile-wrap { position: relative; }

.profile-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 10px 5px 5px;
    border-radius: 40px;
    background: transparent; border: 1.5px solid #FFB3C6;
    cursor: pointer; transition: border-color .2s, background .2s;
    font-family: 'DM Sans', sans-serif;
    -webkit-tap-highlight-color: transparent;
    max-width: 180px;
}
@media (hover: hover) {
    .profile-btn:hover { border-color: var(--accent); background: #FFF0F4; }
}

.profile-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    background: #D94F6E;
    color: #fff; font-size: 12px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

.profile-name {
    font-size: 13px; font-weight: 600; color: #2D1520;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    max-width: 90px;
}
@media (max-width: 420px) {
    .profile-name { display: none; }
    .profile-caret { display: none; }
    .profile-btn { padding: 4px; border-radius: 50%; }
}

.profile-caret {
    font-size: 11px; color: #C48899;
    transition: transform .25s; flex-shrink: 0;
}
.profile-wrap.open .profile-caret { transform: rotate(180deg); }

/* ── DROPDOWN MENU ── */
.profile-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0;
    width: 210px;
    background: #fff;
    border: 1px solid #FFB3C6;
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(217,79,110,.14), 0 2px 8px rgba(0,0,0,.06);
    overflow: hidden;
    opacity: 0; visibility: hidden; pointer-events: none;
    transform: translateY(8px) scale(.97);
    transform-origin: top right;
    transition: opacity .22s, transform .22s, visibility .22s;
    z-index: 1000;
}
.profile-wrap.open .profile-dropdown {
    opacity: 1; visibility: visible; pointer-events: auto;
    transform: translateY(0) scale(1);
}
@media (max-width: 420px) {
    .profile-dropdown { right: -8px; width: calc(100vw - 24px); max-width: 240px; }
}

.dd-header {
    padding: 12px 14px 10px;
    border-bottom: 1px solid #FFB3C6;
    display: flex; align-items: center; gap: 10px;
}
.dd-avatar {
    width: 36px; height: 36px; border-radius: 50%;
    background: #D94F6E;
    color: #fff; font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.dd-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.dd-info .dd-name { font-size: 13px; font-weight: 700; color: #2D1520; }
.dd-info .dd-role { font-size: 11px; color: #C48899; margin-top: 1px; }

.dd-body { padding: 6px; }
.dd-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 10px;
    font-size: 13px; font-weight: 500; color: #2D1520;
    transition: background .15s, color .15s;
    cursor: pointer;
}
.dd-item:hover { background: #FFF0F4; color: #C43860; }
.dd-item i { font-size: 15px; width: 18px; color: #C48899; flex-shrink: 0; transition: color .15s; }
.dd-item:hover i { color: #C43860; }
.dd-item.danger { color: #D94F6E; }
.dd-item.danger i { color: #D94F6E; }
.dd-item.danger:hover { background: #FFF0F4; }

.dd-sep { height: 1px; background: #FFB3C6; margin: 4px 6px; }
</style>
</head>
<body>

<?php
// Ambil data user — selalu query fresh dari DB agar nama tidak salah
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid > 0) {
    if (!isset($user) || empty($user['nama'])) {
        $q_u  = mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$uid LIMIT 1");
        $user = ($q_u && mysqli_num_rows($q_u) > 0) ? mysqli_fetch_assoc($q_u) : [];
    }
    // Sync nama ke session agar konsisten di semua halaman
    if (!empty($user['nama'])) {
        $_SESSION['nama'] = $user['nama'];
    }
}
$nama_user   = $user['nama'] ?? ($_SESSION['nama'] ?? 'User');
$foto_profil = $user['foto_profil'] ?? '';
$inisial     = strtoupper(substr($nama_user, 0, 1));

// Cek jumlah pesan belum dibaca dari admin
$unread_msg = 0;
if (isset($conn)) {
    try {
        $uid_msg = (int)($_SESSION['user_id'] ?? 0);
        $q_msg   = mysqli_query($conn, "SELECT COUNT(*) as c FROM chat WHERE pembeli_id=$uid_msg AND pengirim='admin' AND sudah_dibaca=0");
        if ($q_msg) $unread_msg = mysqli_fetch_assoc($q_msg)['c'] ?? 0;
    } catch (Exception $e) {
        $unread_msg = 0;
    }
}
?>

<nav class="navbar">

    <div class="navbar-brand">
        <a href="../pages/home.php" class="navbar-logo">
            <img src="../uploads/toko/logo.png" class="logo-img" alt="logo">
            <span class="logo-text">Cloudy <span>Girls</span></span>
        </a>
    </div>

    <!-- AKSI KANAN -->
    <div class="navbar-actions">

        <!-- PESAN / CHAT -->
        <a href="../pages/chat.php" class="nav-icon-btn msg" title="Pesan">
            <i class="bi bi-chat-dots"></i>
            <?php if ($unread_msg > 0): ?>
            <span class="nav-badge"><?= $unread_msg ?></span>
            <?php endif; ?>
        </a>

        <!-- WISHLIST / LOVE -->
        <a href="../pages/wishlist.php" class="nav-icon-btn love" title="Wishlist">
            <i class="bi bi-heart"></i>
        </a>

        <div class="nav-divider"></div>

        <!-- PROFILE DROPDOWN -->
        <div class="profile-wrap" id="profileWrap">
            <button class="profile-btn" onclick="toggleDropdown()" aria-expanded="false" id="profileBtn">
                <div class="profile-avatar">
                    <?php if ($foto_profil): ?>
                    <img src="../uploads/foto_profil/<?= htmlspecialchars($foto_profil, ENT_QUOTES) ?>" alt="foto">
                    <?php else: ?>
                    <?= $inisial ?>
                    <?php endif; ?>
                </div>
                <span class="profile-name"><?= htmlspecialchars($nama_user, ENT_QUOTES) ?></span>
                <i class="bi bi-chevron-down profile-caret"></i>
            </button>

            <div class="profile-dropdown" id="profileDropdown">
                <div class="dd-header">
                    <div class="dd-avatar">
                        <?php if ($foto_profil): ?>
                        <img src="../uploads/foto_profil/<?= htmlspecialchars($foto_profil, ENT_QUOTES) ?>" alt="foto">
                        <?php else: ?>
                        <?= $inisial ?>
                        <?php endif; ?>
                    </div>
                    <div class="dd-info">
                        <div class="dd-name"><?= htmlspecialchars($nama_user, ENT_QUOTES) ?></div>
                    </div>
                </div>

                <div class="dd-body">
                    <a href="../pages/profil.php" class="dd-item">
                        <i class="bi bi-person"></i> Profil Saya
                    </a>
                    <a href="../pages/pesanan.php" class="dd-item">
                        <i class="bi bi-bag-check"></i> Pesanan Saya
                    </a>
                    <div class="dd-sep"></div>
                    <a href="../auth/logout.php" class="dd-item danger">
                        <i class="bi bi-box-arrow-right"></i> Keluar
                    </a>
                </div>
            </div>
        </div>

    </div>
</nav>

<script>
function toggleDropdown() {
    const wrap = document.getElementById('profileWrap');
    const btn  = document.getElementById('profileBtn');
    const isOpen = wrap.classList.toggle('open');
    btn.setAttribute('aria-expanded', isOpen);
}
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('profileWrap');
    if (wrap && !wrap.contains(e.target)) {
        wrap.classList.remove('open');
        document.getElementById('profileBtn').setAttribute('aria-expanded', false);
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const wrap = document.getElementById('profileWrap');
        if (wrap) wrap.classList.remove('open');
    }
});
</script>