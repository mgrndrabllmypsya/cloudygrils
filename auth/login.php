<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — Cloudy Girls</title>
    <link rel="icon" type="image/png" href="../uploads/toko/logo.png">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800;900&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --rose: #E8547A;
            --rose-light: #F9D0DA;
            --rose-dark: #C03060;
            --mint: #5EC4B6;
            --mint-light: #C8F0EC;
            --cream: #FDF6F8;
            --sand: #F5E8ED;
            --text: #2D1520;
            --text2: #8C5A6A;
            --muted: #B89AA6;
            --white: #FFFFFF;
            --line: rgba(232, 84, 122, 0.15);
        }

        body {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            font-family: 'Outfit', sans-serif;
            background: var(--cream);
            overflow: hidden;
        }

        /* LEFT PANEL */
        .panel-left {
            position: relative;
            background: linear-gradient(160deg, #F7C5D3 0%, #FADADD 35%, #C8F0EC 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 60px 48px;
            overflow: hidden;
        }

        .panel-left::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.25);
            top: -160px;
            left: -160px;
        }

        .panel-left::after {
            content: '';
            position: absolute;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(94, 196, 182, 0.2);
            bottom: -80px;
            right: -80px;
        }

        .deco-circle {
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .deco-1 {
            width: 180px;
            height: 180px;
            background: rgba(232, 84, 122, 0.12);
            top: 60px;
            right: 40px;
        }

        .deco-2 {
            width: 80px;
            height: 80px;
            background: rgba(94, 196, 182, 0.25);
            bottom: 160px;
            left: 55px;
        }

        .deco-3 {
            width: 40px;
            height: 40px;
            background: rgba(232, 84, 122, 0.2);
            bottom: 80px;
            right: 120px;
        }

        .left-content {
            position: relative;
            z-index: 1;
            text-align: center;
        }

        /* LOGO CLOUDY GIRLS */
        .login-logo {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            text-decoration: none !important;
            cursor: pointer;
            margin-bottom: 14px;
            transition: transform .25s ease;
        }

        .login-logo:hover {
            transform: translateY(-2px);
        }

        .login-logo:active {
            transform: scale(.97);
        }

        .login-logo-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #FFB3C6;
            transition:
                transform .4s cubic-bezier(.34, 1.56, .64, 1),
                border-color .25s ease,
                box-shadow .25s ease;
        }

        .login-logo:hover .login-logo-img {
            transform: rotate(10deg) scale(1.08);
            border-color: #D94F6E;
            box-shadow: 0 0 0 3px rgba(217, 79, 110, .15);
        }

        .login-logo-text {
            font-family: 'Poppins', sans-serif;
            font-size: 22px;
            font-weight: 900;
            color: #1db899b1 !important;
            letter-spacing: -0.5px;
            display: inline-block;
        }

        .login-logo-text span {
            color: #ff009db1 !important;
        }

        .login-logo:hover .login-logo-text {
            background: linear-gradient(90deg, #1db899, #ff009d, #1db899);
            background-size: 200% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: logoShimmer 1.2s linear infinite;
        }

        .login-logo:hover .login-logo-text span {
            -webkit-text-fill-color: transparent;
        }

        @keyframes logoShimmer {
            0% { background-position: 200% center; }
            100% { background-position: -200% center; }
        }

        .brand-tagline {
            font-size: 14px;
            font-weight: 400;
            color: var(--text2);
            letter-spacing: 0.5px;
            line-height: 1.6;
            max-width: 260px;
            margin: 0 auto 40px;
        }

        .features {
            display: flex;
            flex-direction: column;
            gap: 10px;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
        }

        .feat-pill {
            background: rgba(255, 255, 255, 0.65);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 50px;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 500;
            color: var(--text2);
            transition: transform .2s, background .2s;
            animation: slideIn .6s ease both;
        }

        .feat-pill:hover {
            transform: translateX(4px);
            background: rgba(255, 255, 255, 0.85);
        }

        .feat-pill:nth-child(1) { animation-delay: .1s; }
        .feat-pill:nth-child(2) { animation-delay: .2s; }
        .feat-pill:nth-child(3) { animation-delay: .3s; }

        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-16px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .feat-pill .dot {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }

        .dot-rose { background: var(--rose-light); color: var(--rose); }
        .dot-mint  { background: var(--mint-light); color: var(--mint); }

        /* ✅ FIX: dot-sand pakai warna lebih gelap agar ikon terlihat */
        .dot-sand  { background: #F5C6D5; color: var(--rose-dark); }

        /* RIGHT PANEL */
        .panel-right {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 56px;
            overflow-y: auto;
            background: var(--white);
            position: relative;
        }

        .panel-right::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--rose-light), var(--rose), var(--mint));
        }

        .form-box {
            width: 100%;
            max-width: 360px;
            animation: fadeUp .55s cubic-bezier(.16, 1, .3, 1) both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            letter-spacing: .5px;
            margin-bottom: 40px;
            transition: color .2s, gap .2s;
        }

        .back-link:hover {
            color: var(--rose);
            gap: 10px;
        }

        .form-heading {
            margin-bottom: 32px;
            overflow: visible !important;
        }

        .form-heading h1 {
            font-family: 'Playfair Display', serif;
            font-size: clamp(44px, 11vw, 58px);
            font-weight: 900;
            line-height: 1.15;
            letter-spacing: -1px;
            color: #2D1520;
            margin-bottom: 12px;
            padding-bottom: 10px;
            overflow: visible !important;
        }

        .form-heading h1 em {
            display: inline-block;
            font-style: italic;
            color: #ff5fa8;
            padding-right: 8px;
            padding-bottom: 8px;
            animation: selamatGerak 2.8s ease-in-out infinite;
            text-shadow: 0 8px 22px rgba(255, 95, 168, .18);
        }

        @keyframes selamatGerak {
            0%, 100% { transform: translateY(0); filter: brightness(1); }
            50%       { transform: translateY(-4px); filter: brightness(1.18); }
        }

        .form-heading p {
            font-size: 13.5px;
            color: var(--muted);
        }

        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid var(--sand);
            margin-bottom: 30px;
        }

        .tab {
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .5px;
            padding: 10px 20px;
            text-decoration: none;
            color: var(--muted);
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all .25s;
        }

        .tab.active { color: var(--rose); border-bottom-color: var(--rose); }
        .tab:not(.active):hover { color: var(--text2); }

        .alert-err {
            background: #FFF0F3;
            border: 1px solid rgba(232, 84, 122, .3);
            color: var(--rose-dark);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 12.5px;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake .4s ease;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25%       { transform: translateX(-6px); }
            75%       { transform: translateX(6px); }
        }

        .field { margin-bottom: 20px; }

        .field label {
            display: block;
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .9px;
            text-transform: uppercase;
            color: var(--text2);
            margin-bottom: 8px;
        }

        .field-wrap { position: relative; }

        .field input {
            width: 100%;
            padding: 13px 16px 13px 44px;
            border: 1.5px solid var(--line);
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            color: var(--text);
            background: var(--cream);
            outline: none;
            transition: border-color .25s, background .25s, box-shadow .25s;
        }

        .field input:hover {
            border-color: rgba(232, 84, 122, .35);
            background: #fff;
        }

        .field input:focus {
            border-color: var(--mint);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(94, 196, 182, .15);
        }

        .field input::placeholder {
            color: var(--muted);
            font-size: 13.5px;
        }

        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 15px;
            color: var(--muted);
            pointer-events: none;
            transition: color .25s;
        }

        .field input:focus ~ .field-icon { color: var(--mint); }

        .toggle-pw {
            position: absolute;
            right: 13px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--muted);
            cursor: pointer;
            font-size: 15px;
            padding: 4px;
            transition: color .2s;
        }

        .toggle-pw:hover { color: var(--rose); }

        .row-forgot {
            display: flex;
            justify-content: flex-end;
            margin: -6px 0 26px;
        }

        .forgot {
            font-size: 12px;
            color: var(--muted);
            text-decoration: none;
            transition: color .2s;
        }

        .forgot:hover { color: var(--rose); }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background: var(--rose);
            color: #fff;
            border: none;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: background .3s, transform .2s, box-shadow .3s;
            box-shadow: 0 6px 20px rgba(232, 84, 122, .3);
        }

        .btn-submit:hover {
            background: var(--rose-dark);
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(192, 48, 96, .35);
        }

        .btn-submit:active { transform: translateY(0); }

        .card-bottom {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            margin-top: 20px;
        }

        .card-bottom a {
            color: var(--rose);
            font-weight: 600;
            text-decoration: none;
        }

        .card-bottom a:hover {
            color: var(--rose-dark);
            text-decoration: underline;
        }

        .float-shape {
            position: absolute;
            pointer-events: none;
            opacity: .06;
        }

        .float-1 {
            width: 200px; height: 200px;
            border-radius: 50%;
            background: var(--rose);
            bottom: -60px; right: -60px;
        }

        .float-2 {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: var(--mint);
            top: 80px; right: 30px;
        }

        /* AUTH TITLE ANIMATION */
        .auth-title {
            display: flex;
            flex-direction: column;
            gap: 4px;
            line-height: .95;
        }

        .auth-title .halo {
            font-family: 'Playfair Display', serif;
            font-size: clamp(52px, 9vw, 72px);
            font-weight: 900;
            color: #2D1520;
            display: inline-block;
            animation: haloFloat 3s ease-in-out infinite;
            transform-origin: center;
        }

        .auth-title .welcome {
            font-family: 'Playfair Display', serif;
            font-size: clamp(56px, 10vw, 78px);
            font-style: italic;
            font-weight: 900;
            line-height: .9;
            background: linear-gradient(90deg, #ff7ab6, #ff4fa0, #ff9bd1, #ff4fa0);
            background-size: 300% auto;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation:
                welcomeGradient 4s linear infinite,
                welcomeGlow 2s ease-in-out infinite;
        }

        @keyframes haloFloat {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-8px); }
        }

        @keyframes welcomeGradient {
            0%   { background-position: 0% center; }
            100% { background-position: 300% center; }
        }

        @keyframes welcomeGlow {
            0%, 100% { filter: drop-shadow(0 0 0px rgba(255, 79, 160, .3)); }
            50%       { filter: drop-shadow(0 0 12px rgba(255, 79, 160, .55)); }
        }

        /* ===============================
           MOBILE RESPONSIVE
        ================================ */
        @media (max-width: 800px) {
            html, body {
                width: 100%;
                min-height: 100vh;
                overflow-x: hidden;
                overflow-y: auto;
            }

            body {
                display: flex;
                flex-direction: column;
                background: #fff;
            }

            .panel-left {
                display: flex !important;
                height: 72px;
                min-height: 72px;
                padding: 0 16px;
                background: #fff !important;
                align-items: center;
                justify-content: center;
                border-bottom: 1.5px solid #FFB3C6;
                overflow: visible !important;
            }

            .panel-left::before,
            .panel-left::after,
            .deco-circle,
            .brand-tagline,
            .features {
                display: none !important;
            }

            .left-content {
                width: 100%;
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
            }

            .login-logo {
                margin: 0;
                display: inline-flex;
                justify-content: center;
                align-items: center;
                gap: clamp(8px, 2.5vw, 12px);
                max-width: 100%;
                text-decoration: none !important;
                cursor: pointer;
            }

            .login-logo-img {
                width: clamp(34px, 9vw, 42px);
                height: clamp(34px, 9vw, 42px);
                flex-shrink: 0;
            }

            .login-logo-text {
                font-family: 'Poppins', sans-serif;
                font-size: clamp(20px, 6vw, 24px);
                font-weight: 900;
                line-height: 1;
                white-space: nowrap;
                color: #1db899b1 !important;
                transition: transform .25s ease, text-shadow .25s ease;
            }

            .login-logo-text span { color: #ff009db1 !important; }

            .login-logo:hover .login-logo-img {
                transform: rotate(10deg) scale(1.08);
            }

            .login-logo:hover .login-logo-text {
                transform: translateY(-2px) scale(1.04);
                background: linear-gradient(90deg, #1db899, #ff009d, #1db899);
                background-size: 200% auto;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                animation: logoShimmer 1.2s linear infinite;
                text-shadow: 0 8px 24px rgba(255, 0, 157, .22);
            }

            .login-logo:hover .login-logo-text span {
                -webkit-text-fill-color: transparent;
            }

            .login-logo:active { transform: scale(.96); }

            .panel-right {
                flex: 1;
                min-height: calc(100vh - 72px);
                padding: 38px 28px 90px;
                align-items: flex-start;
                overflow: visible;
            }

            .form-box {
                width: 100%;
                max-width: 430px;
                margin: 0 auto;
                overflow: visible;
            }
        }

        @media (max-width: 480px) {
            .auth-title .halo    { font-size: 50px !important; }
            .auth-title .welcome { font-size: 52px !important; line-height: 1 !important; }
        }

        @media (max-width: 390px) {
            .auth-title .halo    { font-size: 44px !important; }
            .auth-title .welcome { font-size: 46px !important; }
        }
    </style>
</head>

<body>

    <div class="panel-left">
        <div class="deco-circle deco-1"></div>
        <div class="deco-circle deco-2"></div>
        <div class="deco-circle deco-3"></div>

        <div class="left-content">
            <a href="../index.php" class="login-logo">
                <img src="../uploads/toko/logo.png" class="login-logo-img" alt="Cloudy Girls">
                <span class="login-logo-text">Cloudy <span>Girls</span></span>
            </a>

            <p class="brand-tagline">Fashion yang menemani setiap momenmu dengan gaya dan kepercayaan diri.</p>

            <div class="features">
                <div class="feat-pill">
                    <div class="dot dot-rose"><i class="bi bi-bag-heart-fill"></i></div>
                    Ribuan pilihan outfit terkini
                </div>
                <div class="feat-pill">
                    <div class="dot dot-mint"><i class="bi bi-truck"></i></div>
                    Pengiriman cepat ke seluruh Indonesia
                </div>
                <div class="feat-pill">
                    <div class="dot dot-sand"><i class="bi bi-shield-check-fill"></i></div>
                    Belanja aman & terpercaya
                </div>
            </div>
        </div>
    </div>

    <div class="panel-right">
        <div class="float-shape float-1"></div>
        <div class="float-shape float-2"></div>

        <div class="form-box">
            <a href="../index.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Kembali ke beranda
            </a>

            <div class="form-heading">
                <h1 class="auth-title">
                    <span class="halo">Halo,</span>
                    <span class="welcome">Selamat Datang!</span>
                </h1>
                <p>Masuk ke akunmu untuk mulai belanja</p>
            </div>

            <div class="tabs">
                <a href="login.php" class="tab active">Masuk</a>
                <a href="register.php" class="tab">Daftar</a>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert-err">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    Email atau password salah. Silakan coba lagi.
                </div>
            <?php endif; ?>

            <form method="POST" action="proses_login.php">
                <div class="field">
                    <label>Email</label>
                    <div class="field-wrap">
                        <input type="email" name="email" placeholder="contoh@email.com" required autocomplete="email">
                        <i class="bi bi-envelope field-icon"></i>
                    </div>
                </div>

                <div class="field">
                    <label>Password</label>
                    <div class="field-wrap">
                        <input type="password" name="password" id="pw" placeholder="••••••••" required autocomplete="current-password">
                        <i class="bi bi-lock field-icon"></i>
                        <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Lihat password">
                            <i class="bi bi-eye-slash" id="pw-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="row-forgot">
                    <a href="lupa_password.php" class="forgot">Lupa password?</a>
                </div>

                <button type="submit" class="btn-submit">Masuk Sekarang</button>
            </form>

            <div class="card-bottom">
                Belum punya akun? <a href="register.php">Daftar sekarang</a>
            </div>
        </div>
    </div>

    <script>
        function togglePw() {
            const pw = document.getElementById('pw');
            const ic = document.getElementById('pw-icon');
            pw.type = pw.type === 'password' ? 'text' : 'password';
            ic.classList.toggle('bi-eye-slash');
            ic.classList.toggle('bi-eye');
        }

        document.querySelectorAll('.field input').forEach(input => {
            input.addEventListener('focus', () => {
                const icon = input.closest('.field-wrap').querySelector('.field-icon');
                if (icon) icon.style.color = 'var(--mint)';
            });
            input.addEventListener('blur', () => {
                const icon = input.closest('.field-wrap').querySelector('.field-icon');
                if (icon) icon.style.color = '';
            });
        });
    </script>

</body>

</html>