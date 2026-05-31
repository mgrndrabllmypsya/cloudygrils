<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../config/koneksi.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Email tidak boleh kosong.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
<<<<<<< HEAD
        $stmt = $conn->prepare("SELECT id, nama FROM pembeli WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result  = $stmt->get_result();
        $pembeli = $result->fetch_assoc();
=======
        // Cek apakah email ada di tabel pembeli
        $stmt = $pdo->prepare("SELECT id, nama FROM pembeli WHERE email = ?");
        $stmt->execute([$email]);
        $pembeli = $stmt->fetch(PDO::FETCH_ASSOC);
>>>>>>> 13fd55fa054d9dcf379e49d7810a40816e571a55

        if ($pembeli) {
            $otp        = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expired_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

<<<<<<< HEAD
            $stmt2 = $conn->prepare("UPDATE pembeli SET reset_token = ?, reset_expired = ? WHERE email = ?");
            $stmt2->bind_param("sss", $otp, $expired_at, $email);
            $stmt2->execute();
=======
            // Simpan token langsung ke kolom tabel pembeli
            $stmt = $pdo->prepare("UPDATE pembeli SET reset_token = ?, reset_expired = ? WHERE email = ?");
            $stmt->execute([$token, $expired_at, $email]);
>>>>>>> 13fd55fa054d9dcf379e49d7810a40816e571a55

            // Kirim pakai mail() PHP biasa
            $from    = 'noreply@claudygirls.mif.myhost.id';
            $subject = 'Kode OTP Reset Password - Cloudy Girls';
            $body    = "
                <div style='font-family:Arial,sans-serif;background:#FFF0F4;padding:32px;'>
                    <div style='max-width:480px;margin:0 auto;background:#fff;border-radius:16px;padding:32px;border:1px solid #FFB3C6;'>
                        <h2 style='color:#D94F6E;'>🔐 Kode OTP Kamu</h2>
                        <p>Halo <strong>{$pembeli['nama']}</strong>,</p>
                        <p>Gunakan kode OTP berikut untuk mereset password:</p>
                        <div style='text-align:center;margin:24px 0;'>
                            <span style='font-size:40px;font-weight:900;letter-spacing:12px;color:#D94F6E;background:#FFF0F4;padding:16px 24px;border-radius:12px;border:2px dashed #FFB3C6;'>
                                {$otp}
                            </span>
                        </div>
                        <p style='color:#C48899;font-size:13px;text-align:center;'>Kode berlaku <strong>10 menit</strong>. Jangan berikan ke siapapun.</p>
                        <p style='color:#aaa;font-size:12px;margin-top:16px;'>Jika tidak merasa meminta reset password, abaikan email ini.</p>
                    </div>
                </div>
            ";
            $headers  = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: Cloudy Girls <$from>\r\n";
            $headers .= "Reply-To: $from\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            $kirim = mail($email, $subject, $body, $headers);

            if ($kirim) {
                $_SESSION['otp_email'] = $email;
                header("Location: Verifikasi_otp.php");
                exit;
            } else {
                $error = 'Gagal mengirim email. Silakan hubungi admin.';
            }
        } else {
            // Email tidak terdaftar — tetap redirect (keamanan)
            $_SESSION['otp_email'] = $email;
            header("Location: Verifikasi_otp.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password — Cloudy Girls</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&family=Syne:wght@700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root { --bg:#FFF0F4; --border:#FFB3C6; --accent:#D94F6E; --muted:#C48899; --text:#2D1520; --text2:#6B3A4A; }
        body { min-height:100vh; display:flex; align-items:center; justify-content:center; font-family:'DM Sans',sans-serif; background:#f9cfcf; padding:24px; position:relative; }
        body::before { content:''; position:fixed; inset:0; background-image:radial-gradient(circle,rgba(255,255,255,.18) 1px,transparent 1px); background-size:24px 24px; pointer-events:none; }
        .card { width:100%; max-width:380px; background:rgba(249,242,242,0.95); backdrop-filter:blur(16px); border:1.5px solid rgba(255,179,198,.6); border-radius:24px; padding:40px 36px; animation:fadeUp .45s ease both; position:relative; z-index:1; box-shadow:0 24px 64px rgba(255,143,171,.25); }
        @keyframes fadeUp { from{opacity:0;transform:translateY(18px)} to{opacity:1;transform:translateY(0)} }
        .logo-top-container { display:flex; justify-content:center; margin-bottom:8px; }
        .logo-img { width:90px; height:auto; object-fit:contain; }
        .brand-title-row { display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:16px; }
        .logo-text { font-family:'Playfair Display',serif; font-size:24px; font-weight:900; color:#1db899b1; line-height:1; }
        .logo-text .pink-text { color:#ff009db1; }
        .page-title { font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:700; color:var(--text); text-align:center; margin-bottom:6px; }
        .subtitle { font-size:0.88rem; color:var(--muted); line-height:1.6; margin-bottom:1.5rem; text-align:center; }
        .alert { padding:12px 14px; border-radius:12px; font-size:0.875rem; margin-bottom:1.25rem; display:flex; align-items:flex-start; gap:8px; line-height:1.5; }
        .alert-error { background:#fff0f0; color:#c0392b; border:1px solid #fecaca; }
        .form-group { margin-bottom:1.25rem; }
        label { display:block; font-size:0.875rem; font-weight:500; color:var(--text); margin-bottom:6px; }
        .input-wrap { position:relative; }
        .input-wrap svg { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:var(--muted); pointer-events:none; }
        input[type="email"] { width:100%; padding:12px 14px 12px 42px; border:1.5px solid var(--border); border-radius:12px; font-size:0.9rem; font-family:'DM Sans',sans-serif; color:var(--text); background:#fafafa; transition:border-color .2s, box-shadow .2s; outline:none; }
        input[type="email"]:focus { border-color:#FF6FA3; box-shadow:0 0 0 4px rgba(255,111,163,.15); background:#fff; }
        input[type="email"]::placeholder { color:#D4809A; }
        .btn { width:100%; padding:13px; background:#FF6FA3; color:white; border:none; border-radius:12px; font-size:0.95rem; font-weight:600; font-family:'DM Sans',sans-serif; cursor:pointer; transition:transform .15s, box-shadow .2s, background .2s; box-shadow:0 4px 14px rgba(255,111,163,.40); }
        .btn:hover { transform:translateY(-1px); background:#FF4F90; }
        .back-link { display:flex; align-items:center; justify-content:center; gap:6px; margin-top:1.25rem; font-size:0.875rem; color:var(--text2); text-decoration:none; transition:color .2s; font-weight:500; }
        .back-link:hover { color:var(--accent); }
        .divider { height:1px; background:var(--border); margin:1.5rem 0; }
        .info-box { background:#FFF5F8; border-radius:12px; padding:12px 14px; font-size:0.8rem; color:var(--text2); line-height:1.6; border:1px solid var(--border); }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo-top-container">
            <img src="../uploads/toko/logo.png" class="logo-img" alt="Logo">
        </div>
        <div class="brand-title-row">
            <span style="font-size:24px">🔐</span>
            <h1 class="logo-text">Cloudy <span class="pink-text">Girls</span></h1>
        </div>
        <h1 class="page-title">Lupa Password?</h1>
        <p class="subtitle">Masukkan email yang terdaftar, kami akan kirimkan kode OTP untuk mereset password.</p>

        <?php if ($error): ?>
        <div class="alert alert-error"><span>⚠️</span><span><?= htmlspecialchars($error) ?></span></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Alamat Email</label>
                <div class="input-wrap">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 7 10 7 10-7"/>
                    </svg>
                    <input type="email" id="email" name="email" placeholder="contoh@email.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
                </div>
            </div>
            <button type="submit" class="btn">Kirim Kode OTP</button>
        </form>

        <div class="divider"></div>
        <div class="info-box">
            <strong>📬 Tidak dapat email?</strong><br>
            Cek folder <em>Spam</em> atau <em>Promotions</em>. Kode OTP berlaku <strong>10 menit</strong>.
        </div>
        <a href="login.php" class="back-link">← Kembali ke halaman login</a>
    </div>
</body>
</html>