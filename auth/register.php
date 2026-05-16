<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;1,700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
:root{--cream:#FAF7F2;--dark:#1C1917;--accent2:#7C3AED;--muted:#78716C;--border:#E7E5E4;--white:#FFFFFF;--pink2:#EC4899;}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#1C1917,#2D1B69,#831843);padding:24px;}
.card{width:100%;max-width:380px;background:var(--white);border-radius:20px;padding:36px 32px;animation:fadeUp .45s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.logo{display:block;font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--dark);margin-bottom:20px;text-align:center;}
.logo span{color:var(--accent2);}
.card-top{text-align:center;margin-bottom:24px;}
.card-top h1{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;margin-bottom:4px;}
.card-top p{font-size:12px;color:var(--muted);}
.tabs{display:flex;background:var(--cream);border-radius:10px;padding:3px;margin-bottom:24px;position:relative;}
.tab{flex:1;text-align:center;font-size:13px;font-weight:500;color:var(--muted);padding:7px 0;border-radius:8px;text-decoration:none;position:relative;z-index:1;transition:color .25s;}
.tab.active{color:var(--dark);}
.tab-slider{position:absolute;top:3px;left:3px;height:calc(100% - 6px);width:calc(50% - 3px);background:var(--white);border-radius:8px;box-shadow:0 1px 4px rgba(0,0,0,.08);transition:transform .28s cubic-bezier(.4,0,.2,1);transform:translateX(calc(100% + 3px));}
.alert-err{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:10px 14px;border-radius:10px;font-size:12px;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--dark);margin-bottom:6px;}
.field-wrap{position:relative;}
.field-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;}
.field input{width:100%;padding:10px 14px 10px 38px;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;color:var(--dark);outline:none;transition:border-color .2s,box-shadow .2s;}
.field input:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(124,58,237,.08);}
.field input::placeholder{color:#C4B9B0;}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;}
.btn-submit{width:100%;padding:11px;background:linear-gradient(135deg,var(--accent2),var(--pink2));color:#fff;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:opacity .2s,transform .15s;margin-top:4px;}
.btn-submit:hover{opacity:.88;}
.card-bottom{text-align:center;margin-top:16px;font-size:12px;color:var(--muted);}
.card-bottom a{color:var(--accent2);font-weight:500;}
.back-home{display:block;text-align:center;margin-top:14px;font-size:11px;color:var(--muted);}
.back-home:hover{color:var(--dark);}
</style>
</head>
<body>
<div class="card">
    <a href="../index.php" class="logo">Cloudy <span>Girls</span></a>
    <div class="card-top">
        <h1>Buat Akun</h1>
        <p>Daftar gratis dan mulai belanja sekarang</p>
    </div>
    <div class="tabs">
        <div class="tab-slider"></div>
        <a href="login.php" class="tab">Masuk</a>
        <a href="register.php" class="tab active">Daftar</a>
    </div>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert-err">
        <i class="bi bi-exclamation-circle"></i>
        <?php
        $err = $_GET['error'];
        if ($err === 'email_exists') echo 'Email sudah terdaftar. Silakan masuk.';
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
                <input type="text" name="username" placeholder="username_kamu" required pattern="[a-zA-Z0-9_]+" title="Huruf, angka, dan underscore saja">
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
            <label>No. HP</label>
            <div class="field-wrap">
                <i class="bi bi-phone icon"></i>
                <input type="tel" name="no_hp" placeholder="08xxxxxxxxxx" required>
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="field-wrap">
                <i class="bi bi-lock icon"></i>
                <input type="password" name="password" id="pw" placeholder="Min. 6 karakter" required minlength="6">
                <button type="button" class="toggle-pw" onclick="togglePw()"><i class="bi bi-eye" id="pw-icon"></i></button>
            </div>
        </div>
        <button type="submit" class="btn-submit">Buat Akun</button>
    </form>
    <div class="card-bottom">Sudah punya akun? <a href="login.php">Masuk di sini</a></div>
    <a href="../index.php" class="back-home"><i class="bi bi-arrow-left"></i> Kembali ke beranda</a>
</div>
<script>
function togglePw(){
    const pw=document.getElementById('pw'),ic=document.getElementById('pw-icon');
    pw.type=pw.type==='password'?'text':'password';
    ic.classList.toggle('bi-eye');ic.classList.toggle('bi-eye-slash');
}
</script>
</body>
</html>