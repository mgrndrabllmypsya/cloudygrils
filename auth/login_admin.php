<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin — Cloudy Girls</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0;}
:root{--dark:#1C1917;--accent2:#7C3AED;--muted:#78716C;--border:#E7E5E4;--white:#FFFFFF;--pink2:#EC4899;}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:'DM Sans',sans-serif;background:linear-gradient(135deg,#1C1917,#2D1B69);padding:24px;}
.card{width:100%;max-width:340px;background:var(--white);border-radius:20px;padding:36px 32px;animation:fadeUp .45s ease both;}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.logo{display:block;font-family:'Playfair Display',serif;font-size:20px;font-weight:700;color:var(--dark);margin-bottom:4px;text-align:center;}
.logo span{color:var(--accent2);}
.badge-admin{display:inline-block;background:rgba(124,58,237,.1);color:var(--accent2);font-size:10px;font-weight:600;letter-spacing:1px;text-transform:uppercase;padding:4px 12px;border-radius:20px;margin-bottom:24px;}
.card-top{text-align:center;margin-bottom:24px;}
.alert-err{background:#FEF2F2;border:1px solid #FECACA;color:#991B1B;padding:10px 14px;border-radius:10px;font-size:12px;margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.field{margin-bottom:14px;}
.field label{display:block;font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--dark);margin-bottom:6px;}
.field-wrap{position:relative;}
.field-wrap .icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:13px;pointer-events:none;}
.field input{width:100%;padding:10px 14px 10px 38px;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;outline:none;transition:border-color .2s;}
.field input:focus{border-color:var(--accent2);box-shadow:0 0 0 3px rgba(124,58,237,.08);}
.field input::placeholder{color:#C4B9B0;}
.toggle-pw{position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;font-size:13px;}
.btn-submit{width:100%;padding:11px;background:linear-gradient(135deg,var(--accent2),var(--pink2));color:#fff;border:none;border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;font-weight:600;letter-spacing:1px;text-transform:uppercase;cursor:pointer;transition:opacity .2s;margin-top:8px;}
.btn-submit:hover{opacity:.88;}
.back-home{display:block;text-align:center;margin-top:16px;font-size:11px;color:var(--muted);}
.back-home:hover{color:var(--dark);}
</style>
</head>
<body>
<div class="card">
    <a href="../index.php" class="logo">Cloudy <span>Girls</span></a>
    <div class="card-top">
        <span class="badge-admin"><i class="bi bi-shield-lock"></i> Admin Panel</span>
    </div>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert-err"><i class="bi bi-exclamation-circle"></i> Username atau password salah.</div>
    <?php endif; ?>
    <form method="POST" action="proses_login_admin.php">
        <div class="field">
            <label>Username</label>
            <div class="field-wrap">
                <i class="bi bi-person icon"></i>
                <input type="text" name="username" placeholder="username admin" required>
            </div>
        </div>
        <div class="field">
            <label>Password</label>
            <div class="field-wrap">
                <i class="bi bi-lock icon"></i>
                <input type="password" name="password" id="pw" placeholder="••••••••" required>
                <button type="button" class="toggle-pw" onclick="togglePw()"><i class="bi bi-eye-slash" id="pw-icon"></i></button>
            </div>
        </div>
        <button type="submit" class="btn-submit"><i class="bi bi-shield-check"></i> Masuk sebagai Admin</button>
    </form>
    <a href="../index.php" class="back-home"><i class="bi bi-arrow-left"></i> Kembali ke toko</a>
</div>
<script>
function togglePw(){
    const pw=document.getElementById('pw'),ic=document.getElementById('pw-icon');
    pw.type=pw.type==='password'?'text':'password';
    ic.classList.toggle('bi-eye-slash');ic.classList.toggle('bi-eye');
}
</script>
</body>
</html>