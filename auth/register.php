<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Playfair+Display:wght@600;700;800;900&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --rose:       #E8547A;
    --rose-light: #F9D0DA;
    --rose-dark:  #C03060;
    --mint:       #5EC4B6;
    --mint-light: #C8F0EC;
    --cream:      #FDF6F8;
    --sand:       #F5E8ED;
    --text:       #2D1520;
    --text2:      #8C5A6A;
    --muted:      #B89AA6;
    --white:      #FFFFFF;
    --line:       rgba(232, 84, 122, 0.15);
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
    background: linear-gradient(160deg, #C8F0EC 0%, #FADADD 55%, #F7C5D3 100%);
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
    width: 500px; height: 500px;
    border-radius: 50%;
    background: rgba(255,255,255,0.25);
    top: -160px; left: -160px;
}

.panel-left::after {
    content: '';
    position: absolute;
    width: 320px; height: 320px;
    border-radius: 50%;
    background: rgba(232, 84, 122, 0.12);
    bottom: -80px; right: -80px;
}

.deco-circle { position: absolute; border-radius: 50%; pointer-events: none; }
.deco-1 { width: 180px; height: 180px; background: rgba(94,196,182,0.18); top: 60px; right: 40px; }
.deco-2 { width: 80px; height: 80px; background: rgba(232,84,122,0.18); bottom: 160px; left: 55px; }
.deco-3 { width: 40px; height: 40px; background: rgba(94,196,182,0.25); bottom: 80px; right: 120px; }

.left-content { position: relative; z-index: 1; text-align: center; }

.brand-icon {
    width: 100px; height: 100px;
    border-radius: 28px;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(16px);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 28px;
    box-shadow: 0 20px 50px rgba(94,196,182,0.18), 0 4px 12px rgba(0,0,0,0.06);
    transition: transform .4s ease;
    text-decoration: none;
}
.brand-icon:hover { transform: translateY(-4px) rotate(-3deg); }
.brand-icon img { width: 68px; height: 68px; object-fit: contain; }

.brand-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 44px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.05;
    letter-spacing: -1px;
    margin-bottom: 12px;
}
.brand-name em { font-style: italic; color: var(--mint); }

.brand-tagline {
    font-size: 14px;
    font-weight: 400;
    color: var(--text2);
    letter-spacing: 0.5px;
    line-height: 1.6;
    max-width: 260px;
    margin: 0 auto 40px;
}

/* Steps visual */
.steps {
    display: flex;
    flex-direction: column;
    gap: 0;
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    animation: slideIn .6s ease both;
}
.step:nth-child(1) { animation-delay: .1s; }
.step:nth-child(2) { animation-delay: .2s; }
.step:nth-child(3) { animation-delay: .3s; }

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-16px); }
    to   { opacity: 1; transform: translateX(0); }
}

.step-line {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex-shrink: 0;
}
.step-num {
    width: 32px; height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.75);
    backdrop-filter: blur(8px);
    border: 1.5px solid rgba(255,255,255,0.9);
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700;
    color: var(--rose);
    box-shadow: 0 4px 12px rgba(232,84,122,.15);
    flex-shrink: 0;
}
.step-connector {
    width: 2px;
    height: 28px;
    background: rgba(255,255,255,0.45);
    margin: 3px 0;
}
.step:last-child .step-connector { display: none; }

.step-body {
    padding-top: 6px;
    padding-bottom: 28px;
}
.step-title {
    font-size: 13px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 2px;
}
.step-desc {
    font-size: 12px;
    color: var(--text2);
    line-height: 1.5;
}

/* RIGHT PANEL */
.panel-right {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 56px;
    overflow-y: auto;
    background: var(--white);
    position: relative;
}

.panel-right::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--mint-light), var(--mint), var(--rose));
}

.form-box {
    width: 100%;
    max-width: 360px;
    animation: fadeUp .55s cubic-bezier(.16,1,.3,1) both;
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
    margin-bottom: 32px;
    transition: color .2s, gap .2s;
}
.back-link:hover { color: var(--rose); gap: 10px; }
.back-link i { font-size: 13px; }

.form-heading { margin-bottom: 28px; }
.form-heading h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 34px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.1;
    margin-bottom: 6px;
}
.form-heading h1 em { font-style: italic; color: var(--mint); }
.form-heading p { font-size: 13.5px; color: var(--muted); font-weight: 400; }

/* TABS */
.tabs {
    display: flex;
    gap: 0;
    border-bottom: 2px solid var(--sand);
    margin-bottom: 26px;
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
.tab.active { color: var(--mint); border-bottom-color: var(--mint); }
.tab:not(.active):hover { color: var(--text2); }

/* ALERT */
.alert-err {
    background: #FFF0F3;
    border: 1px solid rgba(232,84,122,.3);
    color: var(--rose-dark);
    padding: 12px 14px;
    border-radius: 12px;
    font-size: 12.5px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 8px;
    animation: shake .4s ease;
}
@keyframes shake {
    0%,100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}

/* FIELDS */
.field { margin-bottom: 16px; }
.field label {
    display: block;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: .9px;
    text-transform: uppercase;
    color: var(--text2);
    margin-bottom: 7px;
}
.field-wrap { position: relative; }

.field input {
    width: 100%;
    padding: 12px 16px 12px 44px;
    border: 1.5px solid var(--line);
    border-radius: 14px;
    font-family: 'Outfit', sans-serif;
    font-size: 14px;
    color: var(--text);
    background: var(--cream);
    outline: none;
    transition: border-color .25s, background .25s, box-shadow .25s;
}
.field input:hover { border-color: rgba(94,196,182,.4); background: #fff; }
.field input:focus {
    border-color: var(--mint);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(94,196,182,.15);
}
.field input::placeholder { color: var(--muted); font-size: 13.5px; }
.field input:valid:not(:placeholder-shown) {
    border-color: rgba(94,196,182,.5);
}

.field-icon {
    position: absolute;
    left: 14px; top: 50%;
    transform: translateY(-50%);
    font-size: 15px;
    color: var(--muted);
    pointer-events: none;
    transition: color .25s;
}
.field input:focus ~ .field-icon { color: var(--mint); }

/* suffix icon (valid checkmark) */
.field-check {
    position: absolute;
    right: 14px; top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    color: var(--mint);
    pointer-events: none;
    opacity: 0;
    transition: opacity .2s;
}
.field input:valid:not(:placeholder-shown) ~ .field-check { opacity: 1; }

.toggle-pw {
    position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
    background: none; border: none;
    color: var(--muted); cursor: pointer;
    font-size: 15px; padding: 4px;
    transition: color .2s;
}
.toggle-pw:hover { color: var(--rose); }

/* Password strength */
.pw-strength {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
    margin-top: 8px;
}
.pw-bar {
    height: 3px;
    border-radius: 99px;
    background: var(--line);
    transition: background .3s;
}
.pw-bar.weak   { background: #E8547A; }
.pw-bar.medium { background: #F5A623; }
.pw-bar.strong { background: var(--mint); }

.pw-hint {
    font-size: 11px;
    color: var(--muted);
    margin-top: 4px;
    min-height: 16px;
    transition: color .2s;
}

/* SUBMIT */
.btn-submit {
    width: 100%;
    padding: 15px;
    background: var(--mint);
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
    margin-top: 8px;
    transition: background .3s, transform .2s, box-shadow .3s;
    box-shadow: 0 6px 20px rgba(94,196,182,.35);
}
.btn-submit::before {
    content: '';
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.18), transparent);
    pointer-events: none;
}
.btn-submit:hover {
    background: #4aaf9f;
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(74,175,159,.4);
}
.btn-submit:active { transform: translateY(0); }

.card-bottom {
    text-align: center;
    margin-top: 20px;
    font-size: 13px;
    color: var(--muted);
}
.card-bottom a {
    color: var(--rose);
    font-weight: 600;
    text-decoration: none;
    transition: color .2s;
}
.card-bottom a:hover { color: var(--rose-dark); text-decoration: underline; }

/* Terms */
.terms {
    font-size: 11.5px;
    color: var(--muted);
    text-align: center;
    margin-top: 16px;
    line-height: 1.6;
}
.terms a { color: var(--mint); text-decoration: none; font-weight: 500; }
.terms a:hover { text-decoration: underline; }

/* Float shapes */
.float-shape { position: absolute; pointer-events: none; opacity: .05; }
.float-1 { width: 200px; height: 200px; border-radius: 50%; background: var(--mint); bottom: -60px; right: -60px; }
.float-2 { width: 100px; height: 100px; border-radius: 50%; background: var(--rose); top: 80px; right: 30px; }

/* Row layout for two fields side by side */
.field-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

@media (max-width: 800px) {
    body { grid-template-columns: 1fr; overflow: auto; }
    .panel-left { display: none; }
    .panel-right { min-height: 100vh; padding: 36px 28px; }
    .form-box { max-width: 100%; }
    .field-row { grid-template-columns: 1fr; }
}

/* LOGO SAMA SEPERTI LOGIN */
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
    transition: transform .4s cubic-bezier(.34,1.56,.64,1),
                border-color .25s ease,
                box-shadow .25s ease;
}

.login-logo:hover .login-logo-img {
    transform: rotate(10deg) scale(1.08);
    border-color: #D94F6E;
    box-shadow: 0 0 0 3px rgba(217,79,110,.15);
}

.login-logo-text {
    font-family: 'Poppins', sans-serif;
    font-size: 22px;
    font-weight: 900;
    color: #1db899b1 !important;
    letter-spacing: -0.5px;
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

/* JUDUL REGISTER SAMA STYLE LOGIN */
.auth-title {
    display: flex;
    flex-direction: column;
    gap: 4px;
    line-height: .95;
    overflow: visible;
}

.auth-title .halo {
    font-family: 'Playfair Display', serif;
    font-size: clamp(52px, 9vw, 72px);
    font-weight: 900;
    color: #2D1520;
    display: inline-block;
    animation: haloFloat 3s ease-in-out infinite;
}

.auth-title .welcome {
    font-family: 'Playfair Display', serif;
    font-size: clamp(56px, 10vw, 78px);
    font-style: italic;
    font-weight: 900;
    line-height: 1.05;
    padding-bottom: 10px;

    background: linear-gradient(90deg, #ff7ab6, #ff4fa0, #ff9bd1, #ff4fa0);
    background-size: 300% auto;

    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;

    animation: welcomeGradient 4s linear infinite,
               welcomeGlow 2s ease-in-out infinite;
}

/* MOBILE HEADER PUTIH TENGAH */
@media (max-width: 800px) {
    html,
    body {
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
        justify-content: center;
        align-items: center;
        border-bottom: 1.5px solid #FFB3C6;
        overflow: visible !important;
    }

    .panel-left::before,
    .panel-left::after,
    .deco-circle,
    .brand-tagline,
    .steps {
        display: none !important;
    }

    .left-content {
        width: 100%;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-logo {
        margin: 0;
    }

    .login-logo-img {
        width: clamp(34px, 9vw, 42px);
        height: clamp(34px, 9vw, 42px);
    }

    .login-logo-text {
        font-size: clamp(20px, 6vw, 24px);
        white-space: nowrap;
    }

    .panel-right {
        min-height: calc(100vh - 72px);
        padding: 34px 28px 100px;
        align-items: flex-start;
    }

    .form-box {
        width: 100%;
        max-width: 430px;
        margin: 0 auto;
    }

    .field-row {
        grid-template-columns: 1fr;
    }

    .auth-title .halo {
        font-size: 48px;
    }

    .auth-title .welcome {
        font-size: 54px;
        line-height: 1.08;
        padding-bottom: 14px;
    }
}

@keyframes logoShimmer {
    0% { background-position: 200% center; }
    100% { background-position: -200% center; }
}

@keyframes haloFloat {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}

@keyframes welcomeGradient {
    0% { background-position: 0% center; }
    100% { background-position: 300% center; }
}

@keyframes welcomeGlow {
    0%, 100% {
        filter: drop-shadow(0 0 0px rgba(255,79,160,.3));
    }
    50% {
        filter: drop-shadow(0 0 12px rgba(255,79,160,.55));
    }
}
</style>
</head>
<body>

<!-- LEFT PANEL -->
<div class="panel-left">
    <div class="deco-circle deco-1"></div>
    <div class="deco-circle deco-2"></div>
    <div class="deco-circle deco-3"></div>

    <div class="left-content">
        <a href="../index.php" class="login-logo">
    <img src="../uploads/toko/logo.png" class="login-logo-img" alt="Cloudy Girls">
    <span class="login-logo-text">Cloudy <span>Girls</span></span>
</a>

        <p class="brand-tagline">Bergabunglah dan temukan ribuan pilihan fashion terbaik untukmu.</p>

        <div class="steps">
            <div class="step">
                <div class="step-line">
                    <div class="step-num">1</div>
                    <div class="step-connector"></div>
                </div>
                <div class="step-body">
                    <div class="step-title">Buat akun gratis</div>
                    <div class="step-desc">Daftar hanya butuh kurang dari satu menit</div>
                </div>
            </div>
            <div class="step">
                <div class="step-line">
                    <div class="step-num">2</div>
                    <div class="step-connector"></div>
                </div>
                <div class="step-body">
                    <div class="step-title">Jelajahi koleksi</div>
                    <div class="step-desc">Ribuan outfit terkini menanti pilihanmu</div>
                </div>
            </div>
            <div class="step">
                <div class="step-line">
                    <div class="step-num">3</div>
                    <div class="step-connector"></div>
                </div>
                <div class="step-body">
                    <div class="step-title">Belanja & nikmati</div>
                    <div class="step-desc">Pengiriman cepat langsung ke pintumu</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- RIGHT PANEL -->
<div class="panel-right">
    <div class="float-shape float-1"></div>
    <div class="float-shape float-2"></div>

    <div class="form-box">
        <a href="../index.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Kembali ke beranda
        </a>

        <div class="form-heading">
           <h1 class="auth-title">
    <span class="halo">Buat Akun</span>
    <span class="welcome">Barumu</span>
</h1>
        </div>

        <div class="tabs">
            <a href="login.php"    class="tab">Masuk</a>
            <a href="register.php" class="tab active">Daftar</a>
        </div>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert-err">
            <i class="bi bi-exclamation-circle-fill"></i>
            <?php
            $err = $_GET['error'];
            if ($err === 'email_exists')        echo 'Email sudah terdaftar. Silakan masuk.';
            elseif ($err === 'username_exists') echo 'Username sudah dipakai. Coba yang lain.';
            else echo 'Terjadi kesalahan. Silakan coba lagi.';
            ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="proses_register.php">
            <div class="field-row">
                <div class="field">
                    <label>Nama Lengkap</label>
                    <div class="field-wrap">
                        <input type="text" name="nama" placeholder="Nama kamu" required autocomplete="name">
                        <i class="bi bi-person field-icon"></i>
                        <i class="bi bi-check-lg field-check"></i>
                    </div>
                </div>
                <div class="field">
                    <label>Username</label>
                    <div class="field-wrap">
                        <input type="text" name="username" placeholder="username_mu" required
                               pattern="[a-zA-Z0-9_]+" title="Huruf, angka, dan underscore saja">
                        <i class="bi bi-at field-icon"></i>
                        <i class="bi bi-check-lg field-check"></i>
                    </div>
                </div>
            </div>

            <div class="field">
                <label>Email</label>
                <div class="field-wrap">
                    <input type="email" name="email" placeholder="contoh@email.com" required autocomplete="email">
                    <i class="bi bi-envelope field-icon"></i>
                    <i class="bi bi-check-lg field-check"></i>
                </div>
            </div>

            <div class="field">
                <label>Password</label>
                <div class="field-wrap">
                    <input type="password" name="password" id="pw" placeholder="Min. 6 karakter" required minlength="6"
                           oninput="checkStrength(this.value)">
                    <i class="bi bi-lock field-icon"></i>
                    <button type="button" class="toggle-pw" onclick="togglePw()" aria-label="Lihat password">
                        <i class="bi bi-eye-slash" id="pw-icon"></i>
                    </button>
                </div>
                <div class="pw-strength" id="pw-bars">
                    <div class="pw-bar" id="bar1"></div>
                    <div class="pw-bar" id="bar2"></div>
                    <div class="pw-bar" id="bar3"></div>
                    <div class="pw-bar" id="bar4"></div>
                </div>
                <div class="pw-hint" id="pw-hint">Minimal 6 karakter</div>
            </div>

            <button type="submit" class="btn-submit">Buat Akun Sekarang</button>
        </form>

        <p class="terms">
            Dengan mendaftar, kamu menyetujui <a href="#">Syarat & Ketentuan</a> serta <a href="#">Kebijakan Privasi</a> kami.
        </p>

        <div class="card-bottom">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
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

function checkStrength(val) {
    const bars = [document.getElementById('bar1'), document.getElementById('bar2'),
                  document.getElementById('bar3'), document.getElementById('bar4')];
    const hint = document.getElementById('pw-hint');

    bars.forEach(b => b.className = 'pw-bar');

    if (!val) { hint.textContent = 'Minimal 6 karakter'; hint.style.color = ''; return; }

    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;

    const level = score <= 1 ? 'weak' : score <= 2 ? 'medium' : 'strong';
    const labels = { weak: 'Lemah', medium: 'Cukup', strong: 'Kuat' };
    const colors = { weak: '#E8547A', medium: '#F5A623', strong: 'var(--mint)' };

    for (let i = 0; i < score; i++) bars[i].classList.add(level);
    hint.textContent = 'Kekuatan: ' + labels[level];
    hint.style.color = colors[level];
}

// Icon color sync on focus
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