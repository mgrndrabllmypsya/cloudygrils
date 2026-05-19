<?php
session_start();
require_once '../config/konesi.php';

$error      = '';
$success    = '';
$valid_token = false;
$token      = trim($_GET['token'] ?? '');

// Validasi token dari kolom di tabel pembeli
if (empty($token)) {
    $error = 'Token tidak valid atau sudah kedaluwarsa.';
} else {
    $stmt = $pdo->prepare("
        SELECT id, email, nama
        FROM pembeli
        WHERE reset_token = ?
          AND reset_expired > NOW()
    ");
    $stmt->execute([$token]);
    $pembeli = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($pembeli) {
        $valid_token  = true;
        $email_target = $pembeli['email'];
    } else {
        $error = 'Link reset password tidak valid atau sudah kedaluwarsa. Silakan minta link baru.';
    }
}

// Proses submit password baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid_token) {
    $password     = $_POST['password'] ?? '';
    $confirm_pass = $_POST['confirm_password'] ?? '';

    if (empty($password)) {
        $error = 'Password tidak boleh kosong.';
    } elseif (strlen($password) < 8) {
        $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirm_pass) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);

        // Update password & hapus token sekaligus
        $stmt = $pdo->prepare("
            UPDATE pembeli
            SET password = ?, reset_token = NULL, reset_expired = NULL
            WHERE id = ?
        ");
        $stmt->execute([$hashed, $pembeli['id']]);

        $success     = 'Password berhasil direset! Kamu sekarang bisa login dengan password baru.';
        $valid_token = false;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — CloudyGrils</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --violet: #7c3aed;
            --violet-light: #ede9fe;
            --violet-dark: #5b21b6;
            --pink: #ec4899;
            --bg: #faf9ff;
            --card-bg: #ffffff;
            --text: #1e1b2e;
            --muted: #6b7280;
            --border: #e5e7eb;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --success: #16a34a;
            --success-bg: #f0fdf4;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: fixed;
            top: -120px; right: -120px;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(124,58,237,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -100px; left: -100px;
            width: 350px; height: 350px;
            background: radial-gradient(circle, rgba(236,72,153,0.10) 0%, transparent 70%);
            pointer-events: none;
        }

        .card {
            background: var(--card-bg);
            border-radius: 24px;
            padding: 2.5rem 2.25rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 4px 24px rgba(124,58,237,0.10), 0 1px 4px rgba(0,0,0,0.06);
            position: relative;
            z-index: 1;
        }

        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 2rem; }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--violet), var(--pink));
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
        }
        .logo-text {
            font-family: 'Syne', sans-serif;
            font-weight: 800; font-size: 1.2rem;
            color: var(--text); letter-spacing: -0.5px;
        }
        .logo-text span { color: var(--violet); }

        .icon-wrap {
            width: 60px; height: 60px;
            border-radius: 18px;
            display: flex; align-items: center; justify-content: center;
            margin-bottom: 1.25rem; font-size: 28px;
        }
        .icon-wrap.default { background: var(--violet-light); }
        .icon-wrap.success { background: #dcfce7; }
        .icon-wrap.error   { background: var(--danger-bg); }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem; font-weight: 700;
            color: var(--text); margin-bottom: 0.5rem;
        }

        .subtitle { font-size: 0.9rem; color: var(--muted); line-height: 1.6; margin-bottom: 1.75rem; }

        .alert {
            padding: 12px 14px; border-radius: 12px;
            font-size: 0.875rem; margin-bottom: 1.25rem;
            display: flex; align-items: flex-start; gap: 8px; line-height: 1.5;
        }
        .alert-error   { background: var(--danger-bg);  color: var(--danger);  border: 1px solid #fecaca; }
        .alert-success { background: var(--success-bg); color: var(--success); border: 1px solid #bbf7d0; }

        .form-group { margin-bottom: 1.25rem; }

        label { display: block; font-size: 0.875rem; font-weight: 500; color: var(--text); margin-bottom: 6px; }

        .input-wrap { position: relative; }

        .input-wrap .icon-left {
            position: absolute; left: 14px; top: 50%;
            transform: translateY(-50%); color: var(--muted); pointer-events: none;
        }

        .toggle-pass {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            color: var(--muted); padding: 0;
            display: flex; align-items: center;
        }
        .toggle-pass:hover { color: var(--violet); }

        input[type="password"],
        input[type="text"] {
            width: 100%; padding: 12px 42px;
            border: 1.5px solid var(--border); border-radius: 12px;
            font-size: 0.9rem; font-family: 'Outfit', sans-serif;
            color: var(--text); background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }
        input:focus {
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.12); background: #fff;
        }
        input::placeholder { color: #c4b5fd; }

        .strength-bar { margin-top: 8px; height: 4px; border-radius: 4px; background: var(--border); overflow: hidden; }
        .strength-bar-fill { height: 100%; border-radius: 4px; transition: width 0.3s, background 0.3s; width: 0%; }
        .strength-label { font-size: 0.75rem; margin-top: 4px; color: var(--muted); }

        .match-hint { font-size: 0.75rem; margin-top: 5px; min-height: 16px; transition: color 0.2s; }

        .btn {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, var(--violet), var(--violet-dark));
            color: white; border: none; border-radius: 12px;
            font-size: 0.95rem; font-weight: 600; font-family: 'Outfit', sans-serif;
            cursor: pointer; transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(124,58,237,0.35);
            text-decoration: none; display: block; text-align: center;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(124,58,237,0.4); }
        .btn:active { transform: translateY(0); }

        .back-link {
            display: flex; align-items: center; justify-content: center;
            gap: 6px; margin-top: 1.25rem; font-size: 0.875rem;
            color: var(--muted); text-decoration: none; transition: color 0.2s;
        }
        .back-link:hover { color: var(--violet); }

        .requirements {
            background: #f5f3ff; border-radius: 12px;
            padding: 12px 14px; font-size: 0.8rem;
            color: #6d28d9; line-height: 1.7; margin-bottom: 1.25rem;
        }
        .requirements ul { padding-left: 1.2rem; margin-top: 4px; }

        .user-info {
            display: flex; align-items: center; gap: 10px;
            background: #f5f3ff; border-radius: 12px;
            padding: 10px 14px; margin-bottom: 1.25rem;
            font-size: 0.85rem; color: #5b21b6;
        }
        .user-info strong { font-weight: 600; }
    </style>
</head>
<body>
<div class="card">

    <div class="logo">
        <div class="logo-icon">☁️</div>
        <div class="logo-text">Cloudy<span>Grils</span></div>
    </div>

    <?php if ($success): ?>

        <div class="icon-wrap success">✅</div>
        <h1>Password Berhasil Direset!</h1>
        <p class="subtitle">Password kamu sudah diperbarui. Silakan login dengan password baru.</p>
        <a href="login.php" class="btn">Pergi ke Halaman Login</a>

    <?php elseif (!$valid_token): ?>

        <div class="icon-wrap error">❌</div>
        <h1>Link Tidak Valid</h1>
        <p class="subtitle"><?= htmlspecialchars($error) ?></p>
        <a href="lupa_password.php" class="btn">Minta Link Baru</a>

    <?php else: ?>

        <div class="icon-wrap default">🔑</div>
        <h1>Buat Password Baru</h1>
        <p class="subtitle">Masukkan password baru yang kuat untuk akunmu.</p>

        <div class="user-info">
            <span>👤</span>
            <span>Akun: <strong><?= htmlspecialchars($pembeli['nama']) ?></strong>
            (<?= htmlspecialchars($pembeli['email']) ?>)</span>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <div class="requirements">
            <strong>Syarat password:</strong>
            <ul>
                <li>Minimal 8 karakter</li>
                <li>Kombinasi huruf &amp; angka lebih aman</li>
                <li>Tambahkan simbol untuk lebih kuat</li>
            </ul>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label for="password">Password Baru</label>
                <div class="input-wrap">
                    <span class="icon-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="password" name="password"
                           placeholder="Masukkan password baru"
                           required autocomplete="new-password">
                    <button type="button" class="toggle-pass" onclick="togglePass('password', this)" aria-label="Lihat password">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="strength-bar"><div class="strength-bar-fill" id="strength-fill"></div></div>
                <div class="strength-label" id="strength-label">Masukkan password</div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Konfirmasi Password</label>
                <div class="input-wrap">
                    <span class="icon-left">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M9 12l2 2 4-4"/>
                            <rect x="3" y="11" width="18" height="11" rx="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Ulangi password baru"
                           required autocomplete="new-password"
                           oninput="checkMatch()">
                    <button type="button" class="toggle-pass" onclick="togglePass('confirm_password', this)" aria-label="Lihat konfirmasi">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>
                <div class="match-hint" id="match-hint"></div>
            </div>

            <button type="submit" class="btn" id="submit-btn">Simpan Password Baru</button>
        </form>

    <?php endif; ?>

    <a href="login.php" class="back-link">← Kembali ke login</a>
</div>

<script>
    function togglePass(id, btn) {
        const input = document.getElementById(id);
        const isPass = input.type === 'password';
        input.type = isPass ? 'text' : 'password';
        btn.querySelector('svg').innerHTML = isPass
            ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/>'
            : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
    }

    const passInput = document.getElementById('password');
    if (passInput) {
        passInput.addEventListener('input', function () {
            const v = this.value;
            const fill  = document.getElementById('strength-fill');
            const label = document.getElementById('strength-label');
            let s = 0;
            if (v.length >= 8)          s++;
            if (/[A-Z]/.test(v))        s++;
            if (/[0-9]/.test(v))        s++;
            if (/[^A-Za-z0-9]/.test(v)) s++;
            const levels = [
                { pct: '0%',   color: '#e5e7eb', text: 'Masukkan password' },
                { pct: '25%',  color: '#ef4444', text: '😟 Lemah' },
                { pct: '50%',  color: '#f97316', text: '😐 Cukup' },
                { pct: '75%',  color: '#eab308', text: '😊 Kuat' },
                { pct: '100%', color: '#22c55e', text: '💪 Sangat kuat!' },
            ];
            const lvl = v.length === 0 ? 0 : Math.min(s + 1, 4);
            fill.style.width      = levels[lvl].pct;
            fill.style.background = levels[lvl].color;
            label.textContent     = levels[lvl].text;
            label.style.color     = levels[lvl].color;
            checkMatch();
        });
    }

    function checkMatch() {
        const pass    = document.getElementById('password')?.value || '';
        const confirm = document.getElementById('confirm_password')?.value || '';
        const hint    = document.getElementById('match-hint');
        const btn     = document.getElementById('submit-btn');
        if (!confirm) { hint.textContent = ''; return; }
        if (pass === confirm) {
            hint.textContent  = '✅ Password cocok';
            hint.style.color  = '#16a34a';
            btn.disabled      = false;
            btn.style.opacity = '1';
        } else {
            hint.textContent  = '❌ Password tidak cocok';
            hint.style.color  = '#ef4444';
            btn.disabled      = true;
            btn.style.opacity = '0.6';
        }
    }
</script>
</body>
</html>