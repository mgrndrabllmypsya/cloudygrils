<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
*,*::before,*::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg:      #FFF5F8;
    --surface: #FFFFFF;
    --surface2:#FFF0F5;
    --border:  #FFB6D0;
    --accent:  #FF4081;
    --accent2: #F50057;
    --pink:    #FF80AB;
    --muted:   #AAAAAA;
    --text:    #1A1A1A;
    --text2:   #444444;
    --red:     #FF1744;
}
body {
    min-height: 100vh;
    display: flex; align-items: center; justify-content: center;
    font-family: 'DM Sans', sans-serif;
    background: linear-gradient(135deg, #FF80AB 0%, #FF4081 45%, #F50057 100%);
    padding: 24px;
    position: relative;
}
body::before {
    content: '';
    position: fixed; inset: 0;
    background-image: radial-gradient(circle, rgba(255,255,255,.25) 1px, transparent 1px);
    background-size: 24px 24px;
    pointer-events: none;
}
.blob {
    position: fixed; border-radius: 50%; filter: blur(70px); pointer-events: none; z-index: 0;
}
.blob-1 { width: 350px; height: 350px; background: rgba(255,255,255,.2); top: -80px; right: -80px; }
.blob-2 { width: 280px; height: 280px; background: rgba(245,0,87,.35); bottom: -60px; left: -60px; }

.card {
    width: 100%; max-width: 400px;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 24px;
    padding: 40px 36px;
    animation: fadeUp .45s ease both;
    position: relative; z-index: 1;
    box-shadow: 0 24px 64px rgba(255,64,129,.2);
}
@keyframes fadeUp { from { opacity:0; transform:translateY(16px) } to { opacity:1; transform:translateY(0) } }

.logo {
    display: block; text-align: center;
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 700;
    color: var(--text); margin-bottom: 24px;
    text-decoration: none;
}
.logo span { color: var(--accent); }

.card-top { text-align: center; margin-bottom: 28px; }
.card-top h1 {
    font-family: 'Playfair Display', serif;
    font-size: 22px; font-weight: 700;
    color: var(--text); margin-bottom: 4px;
}
.card-top p { font-size: 12px; color: var(--muted); }

/* TABS */
.tabs {
    display: flex; background: var(--surface2);
    border: 1.5px solid var(--border);
    border-radius: 12px; padding: 3px;
    margin-bottom: 28px; position: relative;
}
.tab {
    flex: 1; text-align: center; font-size: 13px; font-weight: 500;
    color: var(--muted); padding: 8px 0; border-radius: 9px;
    text-decoration: none; position: relative; z-index: 1;
    transition: color .25s;
}
.tab.active { color: var(--accent); font-weight: 600; }
.tab-slider {
    position: absolute; top: 3px; left: 3px;
    height: calc(100% - 6px); width: calc(50% - 3px);
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: 9px;
    box-shadow: 0 2px 8px rgba(255,64,129,.15);
    transition: transform .28s cubic-bezier(.4,0,.2,1);
    transform: translateX(calc(100% + 3px));
}

/* ALERT */
.alert-err {
    background: rgba(255,23,68,.08);
    border: 1px solid rgba(255,23,68,.25);
    color: var(--red);
    padding: 10px 14px; border-radius: 10px;
    font-size: 12px; margin-bottom: 18px;
    display: flex; align-items: center; gap: 8px;
}

/* FIELDS */
.field { margin-bottom: 16px; }
.field label {
    display: block; font-size: 11px; font-weight: 600;
    letter-spacing: .8px; text-transform: uppercase;
    color: var(--text2); margin-bottom: 6px;
}
.field-wrap { position: relative; }
.field-wrap .icon {
    position: absolute; left: 13px; top: 50%;
    transform: translateY(-50%); color: var(--muted);
    font-size: 14px; pointer-events: none;
}
.field input {
    width: 100%; padding: 11px 14px 11px 40px;
    border: 1.5px solid var(--border); border-radius: 10px;
    font-family: 'DM Sans', sans-serif; font-size: 13px;
    color: var(--text); background: var(--surface2);
    outline: none; transition: border-color .2s, box-shadow .2s;
}
.field input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(255,64,129,.1);
    background: var(--surface);
}
.field input::placeholder { color: #CCAABC; }
.toggle-pw {
    position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
    background: none; border: none; color: var(--muted);
    cursor: pointer; font-size: 14px; padding: 2px;
    transition: color .2s;
}
.toggle-pw:hover { color: var(--accent); }

/* SUBMIT */
.btn-submit {
    width: 100%; padding: 12px; margin-top: 4px;
    background: linear-gradient(135deg, var(--pink), var(--accent2));
    color: #fff; border: none; border-radius: 12px;
    font-family: 'DM Sans', sans-serif;
    font-size: 13px; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase;
    cursor: pointer;
    box-shadow: 0 6px 20px rgba(255,64,129,.4);
    transition: opacity .2s, transform .15s;
}
.btn-submit:hover { opacity: .88; transform: translateY(-1px); }
.btn-submit:active { transform: scale(.985); }

.card-bottom { text-align: center; margin-top: 20px; font-size: 12px; color: var(--muted); }
.card-bottom a { color: var(--accent); font-weight: 600; text-decoration: none; }
.card-bottom a:hover { text-decoration: underline; }

.back-home {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    margin-top: 18px; font-size: 11px; color: var(--muted);
    text-decoration: none; transition: color .2s;
}
.back-home:hover { color: var(--accent); }
</style>
</head>
<body>
<div class="blob blob-1"></div>
<div class="blob blob-2"></div>

<div class="card">
    <a href="../index.php" class="logo">Cloudy <span>Girls</span></a>

    <div class="card-top">
        <h1>Buat Akun</h1>
        <p>Daftar gratis dan mulai belanja sekarang</p>
    </div>

    <div class="tabs">
        <div class="tab-slider"></div>
        <a href="login.php"    class="tab">Masuk</a>
        <a href="register.php" class="tab active">Daftar</a>
    </div>

    <?php if (isset($_GET['error'])): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-circle"></i>
        <?php
        $err = $_GET['error'];
        if ($err === 'email_exists')    echo 'Email sudah terdaftar. Silakan masuk.';
        elseif ($err === 'username_exists') echo 'Username sudah dipakai. Coba yang lain.';
        else echo 'Terjadi kesalahan. Silakan coba lagi.';
        ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="proses_register.php">
        <div class="field">
            <label>Nama Lengkap</label>
            <div class="field-wrap">
                <i class="bi bi-person icon"></i>
                <input type="text" name="nama" placeholder="Nama lengkap kamu" required>
            </div>
        </div>
        <div class="field">
            <label>Username</label>
            <div class="field-wrap">
                <i class="bi bi-at icon"></i>
                <input type="text" name="username" placeholder="username_kamu" required
                       pattern="[a-zA-Z0-9_]+" title="Huruf, angka, dan underscore saja">
            </div>
        </div>
        <div class="field">
            <label>Email</label>
            <div class="field-wrap">
                <i class="bi bi-envelope icon"></i>
                <input type="email" name="email" placeholder="contoh@email.com" required>
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="field-wrap">
                <i class="bi bi-lock icon"></i>
                <input type="password" name="password" id="pw" placeholder="Min. 6 karakter" required minlength="6">
                <button type="button" class="toggle-pw" onclick="togglePw()">
                    <i class="bi bi-eye" id="pw-icon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-submit">Buat Akun</button>
    </form>

    <div class="card-bottom">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
    <a href="../index.php" class="back-home"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a>
</div>

<script>
function togglePw() {
    const pw = document.getElementById('pw'), ic = document.getElementById('pw-icon');
    pw.type = pw.type === 'password' ? 'text' : 'password';
    ic.classList.toggle('bi-eye');
    ic.classList.toggle('bi-eye-slash');
}
</script>
</body>
</html>