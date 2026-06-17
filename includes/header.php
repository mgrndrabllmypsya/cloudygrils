<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $page_title ?? 'Cloudy Girls' ?></title>
<link rel="icon" type="image/png" href="../uploads/toko/logo.png">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
:root {
    --cream: #FAF7F2;
    --white: #FFFFFF;
    --dark: #1A1A2E;
    --border: #E8E0D5;
    --muted: #9B8FA8;
    --accent: #A78BFA;
    --accent2: #7C3AED;
    --pink: #EC4899;
    --red: #F43F5E;
    --surface: #F3EEF8;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: #FFF0F4; color: #2D1520; }
a { text-decoration: none; color: inherit; }

/* NAVBAR */
.navbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid #FFB3C6;
    padding: 0 48px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* LOGO CLOUDY GIRLS */
.navbar-logo {
    display: flex;
    align-items: center;
    gap: 12px;
    text-decoration: none !important;
    cursor: pointer;
    transition: transform .25s ease;
}

.navbar-logo:hover {
    transform: translateY(-2px);
}

.navbar-logo:active {
    transform: scale(.97);
}

.logo-img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 1px solid #FFB3C6;
    transition:
        transform .4s cubic-bezier(.34,1.56,.64,1),
        border-color .25s ease,
        box-shadow .25s ease;
}

.navbar-logo:hover .logo-img {
    transform: rotate(10deg) scale(1.08);
    border-color: #D94F6E;
    box-shadow: 0 0 0 3px rgba(217,79,110,.15);
}

.logo-text {
    font-family: 'Poppins', sans-serif;
    font-size: 22px;
    font-weight: 900;
    color: #1db899b1 !important;
    letter-spacing: -0.5px;
    display: inline-block;
}

.logo-text span {
    color: #ff009db1 !important;
}

.navbar-logo:hover .logo-text {
    background: linear-gradient(90deg, #1db899, #ff009d, #1db899);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: logoShimmer 1.2s linear infinite;
}

.navbar-logo:hover .logo-text span {
    -webkit-text-fill-color: transparent;
}

@keyframes logoShimmer {
    0% { background-position: 200% center; }
    100% { background-position: -200% center; }
}

/* NAV LINKS */
.navbar-links {
    display: flex;
    gap: 28px;
    align-items: center;
}

.navbar-links a {
    font-size: 13px;
    font-weight: 500;
    color: #C48899 !important;
    transition: color .2s;
    position: relative;
}

.navbar-links a:hover { color: #2D1520; }
.navbar-links a.active { color: #C43860 !important; font-weight: 600; }

.navbar-links a.active::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    right: 0;
    height: 2px;
    border-radius: 2px;
    background: linear-gradient(90deg, var(--accent2), var(--pink));
}

/* AKSI KANAN */
.navbar-actions {
    display: flex;
    align-items: center;
    gap: 6px;
}

.nav-icon-btn {
    position: relative;
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: transparent;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #C48899 !important;
    font-size: 18px;
    transition: background .2s, color .2s, transform .15s;
}

.nav-icon-btn:hover {
    background: #FFF0F4;
    color: #2D1520;
    transform: translateY(-1px);
}

.nav-badge {
    position: absolute;
    top: -4px;
    right: -4px;
    min-width: 17px;
    height: 17px;
    border-radius: 10px;
    padding: 0 4px;
    background: var(--red);
    color: #fff;
    font-size: 9px;
    font-weight: 700;
    border: 2px solid #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}

.nav-icon-btn.love { color: #C48899 !important; }
.nav-icon-btn.love:hover { color: #D94F6E; background: rgba(244,63,94,.08); }
.nav-icon-btn.love.active { color: #D94F6E; }
.nav-icon-btn.msg:hover { color: #C43860 !important; background: rgba(124,58,237,.08); }

.nav-divider {
    width: 1px;
    height: 24px;
    background: #FFB3C6;
    margin: 0 4px;
}

/* PROFILE */
.profile-wrap { position: relative; }

.profile-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 10px 5px 5px;
    border-radius: 40px;
    background: transparent;
    border: 1.5px solid #FFB3C6;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    font-family: 'DM Sans', sans-serif;
}

.profile-btn:hover {
    border-color: var(--accent);
    background: #FFF0F4;
}

.profile-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #D94F6E !important;
    color: #fff;
    font-size: 13px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.profile-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.profile-name {
    font-size: 13px;
    font-weight: 600;
    color: #2D1520;
    max-width: 120px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-caret {
    font-size: 12px;
    color: #C48899 !important;
    transition: transform .25s;
}

.profile-wrap.open .profile-caret {
    transform: rotate(180deg);
}

/* DROPDOWN */
.profile-dropdown {
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    width: 220px;
    background: #fff;
    border: 1px solid #FFB3C6;
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(100,60,180,.12), 0 2px 8px rgba(0,0,0,.06);
    overflow: hidden;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(8px) scale(.97);
    transform-origin: top right;
    transition: opacity .22s, transform .22s, visibility .22s;
    z-index: 1000;
}

.profile-wrap.open .profile-dropdown {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateY(0) scale(1);
}

.dd-header {
    padding: 14px 16px 12px;
    border-bottom: 1px solid #FFB3C6;
    display: flex;
    align-items: center;
    gap: 10px;
}

.dd-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #D94F6E;
    color: #fff;
    font-size: 14px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    overflow: hidden;
}

.dd-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.dd-info .dd-name {
    font-size: 13px;
    font-weight: 700;
    color: #2D1520;
}

.dd-body { padding: 6px; }

.dd-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: #2D1520;
    transition: background .15s, color .15s;
    cursor: pointer;
}

.dd-item:hover {
    background: #FFF0F4;
    color: #C43860 !important;
}

.dd-item i {
    font-size: 15px;
    width: 18px;
    color: #C48899 !important;
    flex-shrink: 0;
    transition: color .15s;
}

.dd-item:hover i { color: #C43860 !important; }
.dd-item.danger { color: #D94F6E; }
.dd-item.danger i { color: #D94F6E; }
.dd-item.danger:hover { background: #FFF0F4; color: #D94F6E; }
.dd-sep { height: 1px; background: #FFB3C6; margin: 4px 6px; }

/* RESPONSIVE */
@media (max-width: 768px) {
    .navbar { padding: 0 16px; height: 56px; }
    .logo-text { font-size: 18px; }
    .logo-img { width: 36px; height: 36px; }
    .nav-divider { display: none; }
    .profile-name { display: none; }
    .profile-btn {
        padding: 4px;
        border: 1.5px solid #FFB3C6;
        border-radius: 50%;
    }
    .profile-caret { display: none; }
    .nav-icon-btn { width: 36px; height: 36px; font-size: 16px; border-radius: 10px; }
    .navbar-actions { gap: 2px; }
    .profile-dropdown { right: 0; width: 200px; }
}

@media (max-width: 480px) {
    .navbar { padding: 0 12px; height: 52px; }
    .navbar-logo { gap: 6px; }
    .logo-text { font-size: 16px; }
    .logo-img { width: 30px; height: 30px; }
    .nav-icon-btn { width: 32px; height: 32px; font-size: 15px; }
    .profile-avatar { width: 28px; height: 28px; font-size: 11px; }
}
</style>
</head>
<body>

<?php
// ── AMBIL LOGO TOKO DARI DB ──
$_logo_h = '';
if (isset($conn)) {
    $q_logo = mysqli_query($conn, "SELECT logo FROM pengaturan_toko WHERE id=1 LIMIT 1");
    if ($q_logo) $_logo_h = mysqli_fetch_assoc($q_logo)['logo'] ?? '';
}
$logo_navbar_src = !empty($_logo_h)
    ? '../uploads/toko/' . htmlspecialchars($_logo_h) . '?v=1'
    : 'https://placehold.co/40x40/FFE4EE/FF4081?text=CG';

// ── DATA USER ──
if (isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    if (!isset($user) || empty($user['nama'])) {
        $q_u = mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$uid LIMIT 1");
        $user = $q_u ? mysqli_fetch_assoc($q_u) : [];
    }
    if (!empty($user['nama'])) {
        $_SESSION['nama'] = $user['nama'];
    }
}

$nama_user = $user['nama'] ?? ($_SESSION['nama'] ?? '');

if (empty($nama_user) && isset($_SESSION['user_id'])) {
    $uid_tmp = (int)$_SESSION['user_id'];
    $q_nama  = mysqli_query($conn, "SELECT nama FROM pembeli WHERE id=$uid_tmp LIMIT 1");
    $nama_user = $q_nama ? (mysqli_fetch_assoc($q_nama)['nama'] ?? 'User') : 'User';
}

if (empty($nama_user)) $nama_user = 'User';

$foto_profil = $user['foto_profil'] ?? '';
$inisial = strtoupper(substr($nama_user, 0, 1));

// ── UNREAD CHAT ──
$unread_msg = 0;
if (isset($conn)) {
    try {
        $uid = (int)($_SESSION['user_id'] ?? 0);
        $q_msg = mysqli_query($conn, "SELECT COUNT(*) as c FROM chat WHERE pembeli_id=$uid AND pengirim='admin' AND sudah_dibaca=0");
        if ($q_msg) $unread_msg = mysqli_fetch_assoc($q_msg)['c'] ?? 0;
    } catch (Exception $e) {
        $unread_msg = 0;
    }
}

// ── UNREAD NOTIFIKASI ──
$unread_notif = 0;
if (isset($conn)) {
    require_once __DIR__ . '/notifikasi.php';

    $uid_notif = (int)($_SESSION['user_id'] ?? 0);

    if (!$uid_notif && session_name() !== 'session_pembeli') {
        $active_session_name = session_name();
        $active_session_id = session_id();

        session_write_close();
        session_name('session_pembeli');
        session_start();

        $uid_notif = (int)($_SESSION['user_id'] ?? 0);

        session_write_close();
        session_name($active_session_name);
        session_id($active_session_id);
        session_start();
    }

    if ($uid_notif) {
        $unread_notif = countUnreadPembeli($conn, $uid_notif);
    }
}
?>

<nav class="navbar">
    <div class="navbar-brand">
        <a href="../pages/home.php" class="navbar-logo">
            <!-- ✅ Logo dari DB, bukan hardcoded -->
            <img src="<?= $logo_navbar_src ?>" class="logo-img" alt="Cloudy Girls"
                 onerror="this.src='https://placehold.co/40x40/FFE4EE/FF4081?text=CG'">
            <span class="logo-text">Cloudy <span>Girls</span></span>
        </a>
    </div>

    <div class="navbar-actions">
        <a href="../pages/chat.php" class="nav-icon-btn msg" title="Pesan">
            <i class="bi bi-chat-dots"></i>
            <?php if ($unread_msg > 0): ?>
                <span class="nav-badge"><?= $unread_msg ?></span>
            <?php endif; ?>
        </a>

        <a href="../pages/notifikasi.php" class="nav-icon-btn" title="Notifikasi">
            <i class="bi bi-bell"></i>
            <?php if ($unread_notif > 0): ?>
                <span class="nav-badge"><?= $unread_notif ?></span>
            <?php endif; ?>
        </a>

        <a href="../pages/wishlist.php" class="nav-icon-btn love" title="Wishlist">
            <i class="bi bi-heart"></i>
        </a>

        <div class="nav-divider"></div>

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
    const btn = document.getElementById('profileBtn');
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