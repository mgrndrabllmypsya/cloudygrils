<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config/koneksi.php';

if (!isset($_SESSION['otp_email'])) {
    header("Location: lupa_password.php"); exit;
}

$error   = '';
$success = '';
$email   = $_SESSION['otp_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp'] ?? '');
    $now = date('Y-m-d H:i:s');

    if (empty($otp)) {
        $error = 'Kode OTP tidak boleh kosong.';
    } else {
        $stmt = $conn->prepare("SELECT id FROM pembeli WHERE email = ? AND reset_token = ? AND reset_expired > ?");
        $stmt->bind_param("sss", $email, $otp, $now);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $_SESSION['otp_verified'] = true;
            header("Location: reset_password.php"); exit;
        } else {
            $error = 'Kode OTP salah atau sudah kadaluarsa.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi OTP — Cloudy Girls</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Syne:wght@700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#FFF0F4; --border:#FFB3C6; --accent:#D94F6E; --muted:#C48899; --text:#2D1520; --text2:#6B3A4A; }
        body {
            min-height:100vh; display:flex; align-items:center; justify-content:center;
            font-family:'DM Sans',sans-serif; background:#f9cfcf; padding:24px; position:relative;
        }
        body::before {
            content:''; position:fixed; inset:0;
            background-image:radial-gradient(circle,rgba(255,255,255,.18) 1px,transparent 1px);
            background-size:24px 24px; pointer-events:none;
        }
        .card {
            width:100%; max-width:380px;
            background:rgba(249,242,242,0.95); backdrop-filter:blur(16px);
            border:1.5px solid rgba(255,179,198,.6); border-radius:24px;
            padding:40px 36px; animation:fadeUp .45s ease both;
            position:relative; z-index:1;
            box-shadow:0 24px 64px rgba(255,143,171,.25);
        }
        @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        .logo-top-container { display:flex; justify-content:center; margin-bottom:8px; }
        .logo-img { width:90px; height:auto; object-fit:contain; }
        .brand-title-row { display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:16px; }
        .gembok-icon { font-size:24px; line-height:1; }
        .logo-text { font-family:'Playfair Display',serif; font-size:24px; font-weight:900; color:#1db899b1; line-height:1; }
        .logo-text .pink-text { color:#ff009db1; }
        .page-title { font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:700; color:var(--text); text-align:center; margin-bottom:6px; }
        .subtitle { font-size:0.88rem; color:var(--muted); line-height:1.6; margin-bottom:1.5rem; text-align:center; }
        .email-badge {
            background:#FFF0F4; border:1px solid var(--border); border-radius:10px;
            padding:8px 14px; font-size:13px; font-weight:600; color:var(--accent);
            text-align:center; margin-bottom:1.5rem;
        }
        .alert { padding:12px 14px; border-radius:12px; font-size:0.875rem; margin-bottom:1.25rem; display:flex; align-items:flex-start; gap:8px; line-height:1.5; }
        .alert-error { background:#fff0f0; color:#c0392b; border:1px solid #fecaca; }
        .form-group { margin-bottom:1.25rem; }
        label { display:block; font-size:0.875rem; font-weight:500; color:var(--text); margin-bottom:6px; text-align:center; }

        /* OTP Input Style */
        .otp-input {
            width:100%; padding:16px; text-align:center;
            font-size:2rem; font-weight:900; letter-spacing:16px;
            border:2px solid var(--border); border-radius:16px;
            color:var(--accent); background:#FFF5F8;
            font-family:'DM Sans',sans-serif; outline:none;
            transition:border-color .2s, box-shadow .2s;
        }
        .otp-input:focus { border-color:#FF6FA3; box-shadow:0 0 0 4px rgba(255,111,163,.15); background:#fff; }
        .otp-input::placeholder { color:#FFB3C6; font-size:1.5rem; letter-spacing:8px; }

        .btn {
            width:100%; padding:13px; background:#FF6FA3; color:white; border:none;
            border-radius:12px; font-size:0.95rem; font-weight:600;
            font-family:'DM Sans',sans-serif; cursor:pointer;
            transition:transform .15s, box-shadow .2s, background .2s;
            box-shadow:0 4px 14px rgba(255,111,163,.40);
        }
        .btn:hover { transform:translateY(-1px); background:#FF4F90; }
        .btn:active { transform:translateY(0); }
        .timer { text-align:center; font-size:13px; color:var(--muted); margin-top:12px; }
        .timer span { font-weight:700; color:var(--accent); }
        .resend-link { color:var(--accent); font-weight:600; cursor:pointer; text-decoration:underline; display:none; }
        .back-link {
            display:flex; align-items:center; justify-content:center; gap:6px;
            margin-top:1.25rem; font-size:0.875rem; color:var(--text2);
            text-decoration:none; transition:color .2s; font-weight:500;
        }
        .back-link:hover { color:var(--accent); }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-top-container">
            <img src="../uploads/toko/logo.png" class="logo-img" alt="Logo">
        </div>
        <div class="brand-title-row">
            <span class="gembok-icon">📩</span>
            <h1 class="logo-text">Cloudy <span class="pink-text">Girls</span></h1>
        </div>
        <h1 class="page-title">Cek Email Kamu!</h1>
        <p class="subtitle">Kami sudah kirim kode OTP 6 digit ke:</p>
        <div class="email-badge"><?= htmlspecialchars($email) ?></div>

        <?php if ($error): ?>
        <div class="alert alert-error"><span>⚠️</span><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="otp">Masukkan Kode OTP</label>
                <input type="text" id="otp" name="otp" class="otp-input"
                    placeholder="------" maxlength="6"
                    inputmode="numeric" pattern="[0-9]{6}"
                    autocomplete="one-time-code" autofocus required>
            </div>
            <button type="submit" class="btn">Verifikasi Kode OTP</button>
        </form>

        <div class="timer">
            Kode berlaku: <span id="countdown">10:00</span>
            <br>
            <a class="resend-link" href="lupa_password.php" id="resendLink">Kirim ulang kode</a>
        </div>

        <a href="lupa_password.php" class="back-link">← Ganti email</a>
    </div>

    <script>
        // Countdown timer 10 menit
        let seconds = 600;
        const countdown = document.getElementById('countdown');
        const resendLink = document.getElementById('resendLink');
        const timer = setInterval(() => {
            seconds--;
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            countdown.textContent = m + ':' + s;
            if (seconds <= 0) {
                clearInterval(timer);
                countdown.textContent = 'Kadaluarsa';
                countdown.style.color = '#c0392b';
                resendLink.style.display = 'inline';
            }
        }, 1000);

        // Auto format OTP input - hanya angka
        document.getElementById('otp').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
    </script>
</body>
</html>