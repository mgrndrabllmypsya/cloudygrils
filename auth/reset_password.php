<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config/koneksi.php';

// Harus sudah verifikasi OTP dulu
if (!isset($_SESSION['otp_verified']) || !$_SESSION['otp_verified'] || !isset($_SESSION['otp_email'])) {
    header("Location: lupa_password.php"); exit;
}

$error   = '';
$success = '';
$email   = $_SESSION['otp_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password   = $_POST['password']  ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';

    if (empty($password)) {
        $error = 'Password tidak boleh kosong.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } elseif ($password !== $konfirmasi) {
        $error = 'Konfirmasi password tidak cocok.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE pembeli SET password = ?, reset_token = NULL, reset_expired = NULL WHERE email = ?");
        $stmt->bind_param("ss", $hash, $email);
        $stmt->execute();

        unset($_SESSION['otp_verified'], $_SESSION['otp_email']);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Cloudy Girls</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:      #FFF0F4;
            --surface: #FFFFFF;
            --surface2:#FFF5F8;
            --border:  #FFB3C6;
            --accent:  #D94F6E;
            --accent2: #C43860;
            --pink:    #FF8FAB;
            --pink2:   #FFB3C6;
            --pink3:   #FFD6E0;
            --muted:   #C48899;
            --text:    #2D1520;
            --text2:   #6B3A4A;
            --red:     #D94F6E;
        }

        body {
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            font-family: 'DM Sans', sans-serif;
            background: #f9cfcf;
            padding: 24px;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: radial-gradient(circle, rgba(255,255,255,.18) 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
        }

        .blob {
            position: fixed; border-radius: 50%; filter: blur(80px); pointer-events: none; z-index: 0;
        }
        .blob-1 { width: 320px; height: 320px; background: rgba(255,255,255,.30); top: -60px; right: -60px; }
        .blob-2 { width: 260px; height: 260px; background: rgba(255,179,198,.45); bottom: -50px; left: -50px; }

        .card {
            width: 100%; max-width: 380px;
            background: rgba(249, 242, 242, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1.5px solid rgba(255,179,198,.6);
            border-radius: 24px;
            padding: 40px 36px;
            animation: fadeUp .45s ease both;
            position: relative; z-index: 1;
            box-shadow: 0 24px 64px rgba(255,143,171,.25), 0 4px 16px rgba(255,179,198,.2);
        }
        @keyframes fadeUp {
            from { opacity:0; transform:translateY(18px) }
            to   { opacity:1; transform:translateY(0) }
        }

        /* Logo */
        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            margin-bottom: 5px;
            width: 100%;
        }
        .logo-img {
            width: 110px;
            height: auto;
            object-fit: contain;
            margin-bottom: 4px;
        }
        .logo-text {
            font-family: 'Playfair Display', serif;
            font-size: 24px;
            font-weight: 700;
            color: #1db899b1;
            text-align: center;
            line-height: 1.2;
        }
        .logo-text span { color: #ff009db1; }

        /* Card top */
        .card-top {
            text-align: center;
            margin-bottom: 28px;
            margin-top: 16px;
        }
        .card-top h1 {
            font-family: 'Playfair Display', serif;
            font-size: 22px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 4px;
        }
        .card-top p { font-size: 12px; color: var(--muted); }

        /* Alert */
        .alert-err {
            background: rgba(217,79,110,.08);
            border: 1px solid rgba(217,79,110,.25);
            color: var(--red);
            padding: 10px 14px; border-radius: 10px;
            font-size: 12px; margin-bottom: 18px;
            display: flex; align-items: center; gap: 8px;
        }

        /* Fields */
        .field { margin-bottom: 16px; }
        .field label {
            display: block; font-size: 11px; font-weight: 600;
            letter-spacing: .8px; text-transform: uppercase;
            color: var(--text2); margin-bottom: 6px;
        }
        .field-wrap { position: relative; }
        .field-wrap .icon {
            position: absolute; left: 13px; top: 50%;
            transform: translateY(-50%); color: var(--pink2);
            font-size: 14px; pointer-events: none;
        }
        .field-wrap input {
            width: 100%; padding: 11px 42px 11px 40px;
            border: 2px solid #F48FB1; border-radius: 10px;
            font-family: 'DM Sans', sans-serif; font-size: 13px;
            color: var(--text); background: #fffef4;
            outline: none; transition: border-color .2s, box-shadow .2s, background .2s;
        }
        .field-wrap input:focus {
            border-color: #47cbd0;
            box-shadow: 0 0 0 4px rgba(255,111,163,.15);
            background: #1db899b1;
        }
        .field-wrap input::placeholder { color: #D4809A; }

        .toggle-pw {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; color: var(--muted);
            cursor: pointer; font-size: 14px; padding: 2px;
            transition: color .2s;
        }
        .toggle-pw:hover { color: var(--accent); }

        /* Submit */
        .btn-submit {
            width: 100%; padding: 13px;
            background: #59B292;
            color: #fff; border: none; border-radius: 12px;
            font-family: 'DM Sans', sans-serif;
            font-size: 13px; font-weight: 700;
            letter-spacing: 1px; text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 6px 22px rgba(255,111,163,.45);
            transition: background .2s, transform .15s, box-shadow .2s;
            margin-top: 8px;
        }
        .btn-submit:hover {
            background: #FF4F90;
            transform: translateY(-1px);
            box-shadow: 0 10px 30px rgba(255,79,144,.45);
        }
        .btn-submit:active { transform: scale(.985); }

        /* Success */
        .success-wrap { text-align: center; padding: 16px 0; }
        .success-wrap .check { font-size: 3rem; display: block; margin-bottom: 12px; }
        .success-wrap h1 {
            font-family: 'Playfair Display', serif;
            font-size: 20px; font-weight: 700;
            color: var(--text); margin-bottom: 8px;
        }
        .success-wrap p { color: var(--muted); font-size: 13px; margin-bottom: 24px; line-height: 1.6; }
        .btn-login {
            display: inline-block; padding: 13px 32px;
            background: #59B292; color: #fff;
            border-radius: 12px; font-weight: 700; font-size: 13px;
            letter-spacing: 1px; text-transform: uppercase;
            text-decoration: none;
            box-shadow: 0 6px 22px rgba(255,111,163,.45);
            transition: background .2s, transform .15s;
        }
        .btn-login:hover { background: #FF4F90; transform: translateY(-1px); }

        .back-home {
            display: flex; align-items: center; justify-content: center; gap: 5px;
            margin-top: 18px; font-size: 11px; color: #59B292;
            text-decoration: none; transition: color .2s;
        }
        .back-home:hover { color: var(--accent); }
    </style>
</head>
<body>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<div class="card">
    <a href="../index.php" class="logo-container">
        <img src="../uploads/toko/logo.png" class="logo-img" alt="Logo">
        <span class="logo-text">Cloudy <span>Girls</span></span>
    </a>

    <?php if ($success): ?>
    <div class="success-wrap">
        <span class="check">✅</span>
        <h1>Password Berhasil Direset!</h1>
        <p>Password kamu sudah diperbarui.<br>Silakan login dengan password baru.</p>
        <a href="login.php" class="btn-login">Masuk Sekarang</a>
    </div>
    <?php else: ?>

    <div class="card-top">
        <h1>Buat Password Baru</h1>
        <p>Masukkan password baru untuk akun kamu.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="field">
            <label>Password Baru</label>
            <div class="field-wrap">
                <i class="bi bi-lock icon"></i>
                <input type="password" name="password" id="pw1" placeholder="Minimal 6 karakter" required>
                <button type="button" class="toggle-pw" onclick="togglePw('pw1', 'ic1')">
                    <i class="bi bi-eye-slash" id="ic1"></i>
                </button>
            </div>
        </div>
        <div class="field">
            <label>Konfirmasi Password</label>
            <div class="field-wrap">
                <i class="bi bi-lock icon"></i>
                <input type="password" name="konfirmasi" id="pw2" placeholder="Ulangi password baru" required>
                <button type="button" class="toggle-pw" onclick="togglePw('pw2', 'ic2')">
                    <i class="bi bi-eye-slash" id="ic2"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-submit">Simpan Password Baru</button>
    </form>

    <a href="login.php" class="back-home"><i class="bi bi-arrow-left"></i> Kembali ke halaman login</a>

    <?php endif; ?>
</div>

<script>
    function togglePw(inputId, iconId) {
        const pw = document.getElementById(inputId);
        const ic = document.getElementById(iconId);
        pw.type = pw.type === 'password' ? 'text' : 'password';
        ic.classList.toggle('bi-eye-slash');
        ic.classList.toggle('bi-eye');
    }
</script>
</body>
</html>