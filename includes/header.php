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
body { font-family: 'DM Sans', sans-serif; background: var(--cream); color: var(--dark); }
a { text-decoration: none; color: inherit; }

/* ── NAVBAR ── */
.navbar {
    position: sticky; top: 0; z-index: 999;
    background: rgba(255,255,255,0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border-bottom: 1px solid var(--border);
    padding: 0 48px;
    height: 64px;
    display: flex; align-items: center; justify-content: space-between;
}

/* LOGO */
.navbar-logo {
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 900;
    color: var(--dark); letter-spacing: -.3px;
    display: flex; align-items: center; gap: 2px;
}
.navbar-logo span { color: var(--accent2); }

/* NAV LINKS (tengah, opsional) */
.navbar-links {
    display: flex; gap: 28px; align-items: center;
}
.navbar-links a {
    font-size: 13px; font-weight: 500; color: var(--muted);
    transition: color .2s; position: relative;
}
.navbar-links a:hover { color: var(--dark); }
.navbar-links a.active { color: var(--accent2); font-weight: 600; }
.navbar-links a.active::after {
    content: ''; position: absolute; bottom: -4px; left: 0; right: 0;
    height: 2px; border-radius: 2px;
    background: linear-gradient(90deg, var(--accent2), var(--pink));
}

/* AKSI KANAN */
.navbar-actions {
    display: flex; align-items: center; gap: 6px;
}

/* ICON BUTTON BASE */
.nav-icon-btn {
    position: relative;
    width: 40px; height: 40px; border-radius: 12px;
    background: transparent; border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: var(--muted); font-size: 18px;
    transition: background .2s, color .2s, transform .15s;
}
.nav-icon-btn:hover {
    background: var(--surface);
    color: var(--dark);
    transform: translateY(-1px);
}

/* BADGE (notif) */
.nav-badge {
    position: absolute; top: 6px; right: 6px;
    width: 8px; height: 8px; border-radius: 50%;
    background: var(--red);
    border: 2px solid #fff;
}

/* LOVE BUTTON */
.nav-icon-btn.love { color: var(--muted); }
.nav-icon-btn.love:hover { color: var(--red); background: rgba(244,63,94,.08); }
.nav-icon-btn.love.active { color: var(--red); }
.nav-icon-btn.love.active i::before { content: "\f415"; }

/* MESSAGE BUTTON */
.nav-icon-btn.msg:hover { color: var(--accent2); background: rgba(124,58,237,.08); }

/* DIVIDER */
.nav-divider {
    width: 1px; height: 24px;
    background: var(--border);
    margin: 0 4px;
}

/* ── PROFILE DROPDOWN ── */
.profile-wrap { position: relative; }

.profile-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 5px 10px 5px 5px;
    border-radius: 40px;
    background: transparent; border: 1.5px solid var(--border);
    cursor: pointer; transition: border-color .2s, background .2s;
    font-family: 'DM Sans', sans-serif;
}
.profile-btn:hover { border-color: var(--accent); background: var(--surface); }

.profile-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent2), var(--pink));
    color: #fff; font-size: 13px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

.profile-name {
    font-size: 13px; font-weight: 600; color: var(--dark);
    max-width: 120px; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis;
}
.profile-caret {
    font-size: 12px; color: var(--muted);
    transition: transform .25s;
}
.profile-wrap.open .profile-caret { transform: rotate(180deg); }

/* DROPDOWN MENU */
.profile-dropdown {
    position: absolute; top: calc(100% + 10px); right: 0;
    width: 220px;
    background: #fff;
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(100,60,180,.12), 0 2px 8px rgba(0,0,0,.06);
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

/* DROPDOWN HEADER */
.dd-header {
    padding: 14px 16px 12px;
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.dd-avatar {
    width: 38px; height: 38px; border-radius: 50%;
    background: linear-gradient(135deg, var(--accent2), var(--pink));
    color: #fff; font-size: 14px; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.dd-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.dd-info .dd-name { font-size: 13px; font-weight: 700; color: var(--dark); }
.dd-info .dd-role { font-size: 11px; color: var(--muted); margin-top: 1px; }

/* DROPDOWN ITEMS */
.dd-body { padding: 6px; }
.dd-item {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 10px;
    font-size: 13px; font-weight: 500; color: var(--dark);
    transition: background .15s, color .15s;
    cursor: pointer;
}
.dd-item:hover { background: var(--surface); color: var(--accent2); }
.dd-item i { font-size: 15px; width: 18px; color: var(--muted); flex-shrink: 0; transition: color .15s; }
.dd-item:hover i { color: var(--accent2); }
.dd-item.danger { color: var(--red); }
.dd-item.danger i { color: var(--red); }
.dd-item.danger:hover { background: rgba(244,63,94,.07); color: var(--red); }

.dd-sep { height: 1px; background: var(--border); margin: 4px 6px; }
</style>
</head>
<body>

<?php
// Ambil data user jika belum ada
if (!isset($user) && isset($_SESSION['user_id'])) {
    $uid = (int)$_SESSION['user_id'];
    $q_u = mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$uid LIMIT 1");
    $user = $q_u ? mysqli_fetch_assoc($q_u) : [];
}
$nama_user   = $user['nama'] ?? ($_SESSION['nama'] ?? 'User');
$foto_profil = $user['foto_profil'] ?? '';
$inisial     = strtoupper(substr($nama_user, 0, 1));

// Cek jumlah pesan belum dibaca dari admin
$unread_msg = 0;
if (isset($conn)) {
    try {
        $uid   = (int)($_SESSION['user_id'] ?? 0);
        $q_msg = mysqli_query($conn, "SELECT COUNT(*) as c FROM chat WHERE pembeli_id=$uid AND pengirim='admin' AND sudah_dibaca=0");
        if ($q_msg) $unread_msg = mysqli_fetch_assoc($q_msg)['c'] ?? 0;
    } catch (Exception $e) {
        $unread_msg = 0;
    }
}
?>

<nav class="navbar">

    <!-- LOGO -->
    <a href="../pages/home.php" class="navbar-logo">
        Cloudy <span>Girls</span>
    </a>


    <!-- AKSI KANAN -->
    <div class="navbar-actions">

        <!-- PESAN / CHAT -->
        <a href="../pages/chat.php" class="nav-icon-btn msg" title="Pesan">
            <i class="bi bi-chat-dots"></i>
            <?php if ($unread_msg > 0): ?>
            <span class="nav-badge"></span>
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
                <!-- HEADER -->
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
                        <div class="dd-role">Pembeli</div>
                    </div>
                </div>

                <!-- MENU ITEMS -->
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

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('profileWrap');
    if (wrap && !wrap.contains(e.target)) {
        wrap.classList.remove('open');
        document.getElementById('profileBtn').setAttribute('aria-expanded', false);
    }
});

// Tutup dengan Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const wrap = document.getElementById('profileWrap');
        if (wrap) wrap.classList.remove('open');
    }
});
</script>