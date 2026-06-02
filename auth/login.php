<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Masuk — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
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

/* LEFT PANEL — decorative */
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
    background: rgba(94, 196, 182, 0.2);
    bottom: -80px; right: -80px;
}

.deco-circle {
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
}
.deco-1 { width: 180px; height: 180px; background: rgba(232,84,122,0.12); top: 60px; right: 40px; }
.deco-2 { width: 80px; height: 80px; background: rgba(94,196,182,0.25); bottom: 160px; left: 55px; }
.deco-3 { width: 40px; height: 40px; background: rgba(232,84,122,0.2); bottom: 80px; right: 120px; }

.left-content { position: relative; z-index: 1; text-align: center; }

.brand-icon {
    width: 100px; height: 100px;
    border-radius: 28px;
    background: transparent;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 16px;
    transition: transform .4s ease;
}

.brand-icon:hover { transform: translateY(-4px) rotate(3deg); }

.brand-icon img {
    width: 68px; height: 68px;
    object-fit: contain;
}

.brand-name {
    font-family: 'Cormorant Garamond', serif;
    font-size: 44px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.1;
    letter-spacing: -0.5px;
    margin-bottom: 12px;
    white-space: nowrap;
}

.brand-name em {
    font-style: italic;
    color: var(--rose);
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

/* Feature pills */
.features {
    display: flex;
    flex-direction: column;
    gap: 10px;
    width: 100%;
    max-width: 280px;
    margin: 0 auto;
}

.feat-pill {
    background: rgba(255,255,255,0.65);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.8);
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

.feat-pill:hover { transform: translateX(4px); background: rgba(255,255,255,0.85); }

.feat-pill:nth-child(1) { animation-delay: .1s; }
.feat-pill:nth-child(2) { animation-delay: .2s; }
.feat-pill:nth-child(3) { animation-delay: .3s; }

@keyframes slideIn {
    from { opacity: 0; transform: translateX(-16px); }
    to   { opacity: 1; transform: translateX(0); }
}

.feat-pill .dot {
    width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
}
.dot-rose { background: var(--rose-light); color: var(--rose); }
.dot-mint { background: var(--mint-light); color: var(--mint); }
.dot-sand { background: var(--sand); color: var(--rose-dark); }

/* RIGHT PANEL — form */
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
    top: 0; left: 0; right: 0;
    height: 3px;
    background: linear-gradient(90deg, var(--rose-light), var(--rose), var(--mint));
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

/* BACK LINK */
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
.back-link:hover { color: var(--rose); gap: 10px; }
.back-link i { font-size: 13px; }

/* HEADING */
.form-heading {
    margin-bottom: 32px;
}
.form-heading h1 {
    font-family: 'Cormorant Garamond', serif;
    font-size: 36px;
    font-weight: 700;
    color: var(--text);
    line-height: 1.1;
    margin-bottom: 6px;
}
.form-heading h1 em { font-style: italic; color: var(--rose); }
.form-heading p { font-size: 13.5px; color: var(--muted); font-weight: 400; }

/* TABS */
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

/* ALERT */
.alert-err {
    background: #FFF0F3;
    border: 1px solid rgba(232,84,122,.3);
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
    0%,100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
}

/* FIELDS */
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
.field input:hover { border-color: rgba(232,84,122,.35); background: #fff; }
.field input:focus {
    border-color: var(--mint);
    background: #fff;
    box-shadow: 0 0 0 4px rgba(94,196,182,.15);
}
.field input::placeholder { color: var(--muted); font-size: 13.5px; }

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

.toggle-pw {
    position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
    background: none; border: none;
    color: var(--muted); cursor: pointer;
    font-size: 15px; padding: 4px;
    transition: color .2s;
}
.toggle-pw:hover { color: var(--rose); }

/* FORGOT */
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

/* SUBMIT */
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
    box-shadow: 0 6px 20px rgba(232,84,122,.3);
}
.btn-submit::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(255,255,255,.15), transparent);
    pointer-events: none;
}
.btn-submit:hover {
    background: var(--rose-dark);
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(192,48,96,.35);
}
.btn-submit:active { transform: translateY(0); }

/* DIVIDER */
.divider {
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 22px 0;
    font-size: 11px;
    color: var(--muted);
    letter-spacing: .5px;
    text-transform: uppercase;
}
.divider::before, .divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--line);
}

/* SOCIAL */
.social-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 26px;
}
.btn-social {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 11px;
    border: 1.5px solid var(--line);
    border-radius: 12px;
    background: #fff;
    font-family: 'Outfit', sans-serif;
    font-size: 13px;
    font-weight: 500;
    color: var(--text2);
    text-decoration: none;
    cursor: pointer;
    transition: border-color .2s, background .2s, transform .2s;
}
.btn-social:hover {
    border-color: rgba(232,84,122,.4);
    background: var(--sand);
    transform: translateY(-1px);
}
.btn-social i { font-size: 16px; }
.btn-social .ic-google { color: #EA4335; }
.btn-social .ic-facebook { color: #1877F2; }

/* BOTTOM TEXT */
.card-bottom {
    text-align: center;
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

/* FLOATING SHAPES on right panel */
.float-shape {
    position: absolute;
    pointer-events: none;
    opacity: .06;
}
.float-1 { width: 200px; height: 200px; border-radius: 50%; background: var(--rose); bottom: -60px; right: -60px; }
.float-2 { width: 100px; height: 100px; border-radius: 50%; background: var(--mint); top: 80px; right: 30px; }

/* RESPONSIVE */
@media (max-width: 800px) {
    body { grid-template-columns: 1fr; overflow: auto; }
    .panel-left { display: none; }
    .panel-right { min-height: 100vh; padding: 36px 28px; }
    .form-box { max-width: 100%; }
}
</style>
</head>
<body>

<!-- LEFT — decorative panel -->
<div class="panel-left">
    <div class="deco-circle deco-1"></div>
    <div class="deco-circle deco-2"></div>
    <div class="deco-circle deco-3"></div>

    <div class="left-content">
        <a href="../index.php" class="brand-icon">
            <img src="../uploads/toko/logo.png" alt="Cloudy Girls Logo">
        </a>

        <h2 class="brand-name">Cloudy <em>Girls</em></h2>
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

<!-- RIGHT — form panel -->
<div class="panel-right">
    <div class="float-shape float-1"></div>
    <div class="float-shape float-2"></div>

    <div class="form-box">
        <a href="../index.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Kembali ke beranda
        </a>

        <div class="form-heading">
            <h1>Halo, <em>Selamat<br>Datang!</em></h1>
            <p>Masuk ke akunmu untuk mulai belanja</p>
        </div>

        <div class="tabs">
            <a href="login.php"    class="tab active">Masuk</a>
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

// Focus-icon sync: highlight left icon on focus
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