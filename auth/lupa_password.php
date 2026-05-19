<?php
session_start();
require_once '../config/koneksi.php'; // Sesuaikan path ke database kamu

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Email tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Cek apakah email ada di tabel pembeli
        $stmt = $pdo->prepare("SELECT id, nama FROM pembeli WHERE email = ?");
        $stmt->execute([$email]);
        $pembeli = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($pembeli) {
            // Buat token unik
            $token = bin2hex(random_bytes(32));
            $expired_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Simpan token langsung ke kolom tabel pembeli
            $stmt = $pdo->prepare("UPDATE pembeli SET reset_token = ?, reset_expired = ? WHERE email = ?");
            $stmt->execute([$token, $expired_at, $email]);

            $reset_link = "http://localhost/cloudygrils/auth/reset_password.php?token=" . $token;
            $subject = "Reset Password - CloudyGrils";
            $message = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Reset Password Kamu</h2>
                    <p>Halo <strong>{$pembeli['nama']}</strong>,</p>
                    <p>Klik tombol di bawah untuk mereset password kamu. Link ini berlaku selama <strong>1 jam</strong>.</p>
                    <a href='{$reset_link}' style='
                        display: inline-block;
                        padding: 12px 28px;
                        background-color: #7c3aed;
                        color: white;
                        text-decoration: none;
                        border-radius: 8px;
                        font-weight: bold;
                        margin: 16px 0;
                    '>Reset Password</a>
                    <p>Atau copy link ini ke browser:</p>
                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    <p style='color: #888; font-size: 13px;'>Jika kamu tidak meminta reset password, abaikan email ini.</p>
                </body>
                </html>
            ";
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: no-reply@cloudygrils.com\r\n";

            mail($email, $subject, $message, $headers);
        }

        // Selalu tampilkan pesan sukses (keamanan: jangan bocorkan apakah email terdaftar)
        $success = 'Link reset password telah dikirim ke email kamu. Cek inbox atau folder spam.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — CloudyGrils</title>
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
            top: -120px;
            right: -120px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(124,58,237,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            bottom: -100px;
            left: -100px;
            width: 350px;
            height: 350px;
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

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 2rem;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--violet), var(--pink));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .logo-text {
            font-family: 'Syne', sans-serif;
            font-weight: 800;
            font-size: 1.2rem;
            color: var(--text);
            letter-spacing: -0.5px;
        }

        .logo-text span {
            color: var(--violet);
        }

        .icon-wrap {
            width: 60px;
            height: 60px;
            background: var(--violet-light);
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            font-size: 28px;
        }

        h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .subtitle {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.6;
            margin-bottom: 1.75rem;
        }

        .alert {
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            line-height: 1.5;
        }

        .alert-error {
            background: var(--danger-bg);
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .alert-success {
            background: var(--success-bg);
            color: var(--success);
            border: 1px solid #bbf7d0;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text);
            margin-bottom: 6px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            pointer-events: none;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border: 1.5px solid var(--border);
            border-radius: 12px;
            font-size: 0.9rem;
            font-family: 'Outfit', sans-serif;
            color: var(--text);
            background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }

        input[type="email"]:focus {
            border-color: var(--violet);
            box-shadow: 0 0 0 3px rgba(124,58,237,0.12);
            background: #fff;
        }

        input[type="email"]::placeholder {
            color: #c4b5fd;
        }

        .btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--violet), var(--violet-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            box-shadow: 0 4px 14px rgba(124,58,237,0.35);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(124,58,237,0.4);
        }

        .btn:active {
            transform: translateY(0);
        }

        .back-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: var(--muted);
            text-decoration: none;
            transition: color 0.2s;
        }

        .back-link:hover { color: var(--violet); }

        .divider {
            height: 1px;
            background: var(--border);
            margin: 1.5rem 0;
        }

        .info-box {
            background: #f5f3ff;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.8rem;
            color: #6d28d9;
            line-height: 1.6;
        }

        .info-box strong { font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <div class="logo-icon">☁️</div>
            <div class="logo-text">Cloudy<span>Grils</span></div>
        </div>

        <div class="icon-wrap">🔐</div>
        <h1>Lupa Password?</h1>
        <p class="subtitle">Tenang, masukkan email kamu dan kami akan kirimkan link untuk mereset password.</p>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <span>⚠️</span>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success">
            <span>✅</span>
            <span><?= htmlspecialchars($success) ?></span>
        </div>
        <?php else: ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-wrap">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/>
                    </svg>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        placeholder="contoh@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        required
                        autofocus
                    >
                </div>
            </div>

            <button type="submit" class="btn">Kirim Link Reset Password</button>
        </form>

        <div class="divider"></div>

        <div class="info-box">
            <strong>📬 Tidak dapat email?</strong><br>
            Cek folder <em>Spam</em> atau <em>Promotions</em>. Link berlaku selama <strong>1 jam</strong>.
        </div>
        <?php endif; ?>

        <a href="login.php" class="back-link">
            ← Kembali ke halaman login
        </a>
    </div>
</body>
</html>