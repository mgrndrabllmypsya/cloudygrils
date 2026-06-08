<?php
session_name('session_pembeli');
session_start();
include '../config/koneksi.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$produk_id  = $_GET['produk_id'] ?? null;
$pembeli_id = $_SESSION['user_id'];

if (!$produk_id) {
    header("Location: ../pages/home.php");
    exit;
}

// Ambil data produk
$stmt = $conn->prepare("SELECT * FROM produk WHERE id = ?");
$stmt->bind_param("i", $produk_id);
$stmt->execute();
$produk = $stmt->get_result()->fetch_assoc();
$stmt->close();
$toko = mysqli_fetch_assoc(mysqli_query($conn, "SELECT alamat, maps_url, no_rek_bca, nama_rek_bca, no_dana, nama_dana FROM pengaturan_toko WHERE id=1"));
if (!$produk) {
    echo "Produk tidak ditemukan.";
    exit;
}

// Ambil data pembeli
$stmt2 = $conn->prepare("SELECT * FROM pembeli WHERE id = ?");
$stmt2->bind_param("i", $pembeli_id);
$stmt2->execute();
$pembeli = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

// Nego belum tersedia
$nego = null;
$nego_id = (int)($_GET['nego_id'] ?? 0);

if ($nego_id) {
    $stmt_nego = $conn->prepare("
        SELECT * FROM nego_harga 
        WHERE id = ? AND pembeli_id = ? AND produk_id = ? AND status = 'disetujui'
        LIMIT 1
    ");
    $stmt_nego->bind_param("iii", $nego_id, $pembeli_id, $produk_id);
    $stmt_nego->execute();
    $nego = $stmt_nego->get_result()->fetch_assoc();
    $stmt_nego->close();
}

$harga_produk   = $nego ? (float)$nego['harga_deal'] : (float)$produk['harga'];
$ada_diskon     = $harga_produk > 50000;
$diskon_nominal = $ada_diskon ? 10000 : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – CloudyGirls</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
    --pink-1:   #FF8FAB;
    --pink-2:   #FFB3C6;
    --pink-3:   #FFD6E0;
    --pink-4:   #FFF0F5;
    --pink-deep:#FF6FA3;
    --pink-dark:#e05c8a;
    --bg:       #FFF5F8;
    --card:     #ffffff;
    --text:     #3a1a28;
    --muted:    #b07898;
    --border:   #f0c8d8;
    --radius:   14px;
    --shadow:   0 2px 16px rgba(255,111,163,.10);

    --font-heading: 'Poppins', sans-serif;
    --font-body:    'Lato', sans-serif;
    --font-ui:      'Poppins', sans-serif;
}

body {
    font-family: var(--font-body);
    background: #f9cfcf;
    background-attachment: fixed;
    color: var(--text);
    min-height: 100vh;
    font-size: 15px;
    line-height: 1.7;
}

/* ── NAV ── */
nav {
    background: rgba(255,255,255,.90);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--border);
    padding: 0 2rem;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
}
.brand {
    font-family: var(--font-heading);
    font-size: 22px; font-weight: 900;
    color: #1db899b1;
    display: flex; align-items: center; gap: 10px;
}
.brand span       { color: #1db899b1; }
.brand span.pink-text { color: #ff009db1; }
.logo-img {
    width: 40px; height: 40px; border-radius: 50%;
    object-fit: cover; border: 1px solid var(--border);
}
.checkout-text {
    font-family: var(--font-body);
    font-size: .85rem; color: var(--muted);
}

/* ── LAYOUT ── */
.container {
    max-width: 900px; margin: 2rem auto; padding: 0 1rem;
    display: grid; grid-template-columns: 1fr 340px;
    gap: 1.5rem; align-items: start;
}
@media (max-width: 720px) {
    .container { grid-template-columns: 1fr; }
}

/* ── CARD ── */
.card {
    background: var(--card);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    padding: 1.5rem;
}
.card-title {
    font-family: var(--font-heading);
    font-size: 1.1rem; font-weight: 700;
    margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: .5rem;
    color: var(--text);
}
.card-title .num {
    font-family: var(--font-ui);
    width: 26px; height: 26px;
    background: linear-gradient(135deg, var(--pink-1), var(--pink-deep));
    color: #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: .75rem; font-weight: 600;
}

/* ── STEP INDICATOR ── */
.steps {
    display: flex; gap: 0; margin-bottom: 1.5rem;
    background: var(--card); border-radius: var(--radius);
    box-shadow: var(--shadow); border: 1px solid var(--border);
    overflow: hidden;
}
.step-item {
    font-family: var(--font-ui);
    flex: 1; padding: .75rem .5rem;
    text-align: center; font-size: .78rem; font-weight: 500;
    color: var(--muted); border-bottom: 3px solid transparent;
    transition: all .3s;
}
.step-item.active { color: var(--pink-deep); border-bottom-color: var(--pink-deep); background: var(--pink-4); }
.step-item.done   { color: var(--pink-1);    border-bottom-color: var(--pink-1); }

/* ── FORM ELEMENTS ── */
.form-group { margin-bottom: 1rem; }
label {
    font-family: var(--font-ui);
    display: block; font-size: .83rem; font-weight: 500;
    margin-bottom: .35rem; color: var(--text);
}
label .req { color: var(--pink-deep); }
input[type=text], input[type=tel], input[type=number],
select, textarea {
    font-family: var(--font-body);
    width: 100%; padding: .6rem .85rem;
    border: 1.5px solid var(--border); border-radius: 9px;
    font-size: .9rem; color: var(--text); background: #fff;
    transition: border-color .2s, box-shadow .2s; outline: none;
}
input:focus, select:focus, textarea:focus {
    border-color: var(--pink-1);
    box-shadow: 0 0 0 3px rgba(255,143,171,.15);
}
textarea { resize: vertical; min-height: 80px; }

/* ── METODE TABS ── */
.metode-tabs {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: .75rem; margin-bottom: 1.5rem;
}
.metode-tab {
    font-family: var(--font-ui);
    border: 2px solid var(--border); border-radius: var(--radius);
    padding: 1rem; cursor: pointer; text-align: center;
    transition: all .2s; background: #fff;
}
.metode-tab:hover  { border-color: var(--pink-1); background: #1db899b1; }
.metode-tab.selected { border-color: var(--pink-deep); background: linear-gradient(135deg, var(--pink-4), #fff8fa); }
.metode-tab .icon  { font-size: 1.6rem; display: block; margin-bottom: .35rem; }
.metode-tab .label { font-family: var(--font-ui); font-weight: 600; font-size: .9rem; color: var(--text); }
.metode-tab .desc  { font-family: var(--font-body); font-size: .76rem; color: var(--muted); margin-top: .15rem; }

/* ── SECTION TOGGLE ── */
.section-body { display: none; }
.section-body.visible { display: block; }

/* ── REKENING OPTION ── */
.rekening-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: .75rem; margin-bottom: 1rem;
}
.rek-card {
    border: 2px solid var(--border); border-radius: 10px;
    padding: .85rem 1rem; cursor: pointer; transition: all .2s;
}
.rek-card:hover    { border-color: var(--pink-1); background: #1db899b1; }
.rek-card.selected { border-color: var(--pink-deep); background: #1db899b1; }
.rek-card .rek-name { font-family: var(--font-ui); font-weight: 600; font-size: .9rem; }
.rek-card .rek-no   { font-family: var(--font-body); font-size: .8rem; color: var(--muted); margin-top: .2rem; }

/* ── UPLOAD AREA ── */
.upload-area {
    border: 2px dashed var(--border); border-radius: var(--radius);
    padding: 1.5rem; text-align: center; cursor: pointer;
    transition: all .2s; position: relative;
}
.upload-area:hover, .upload-area.dragover { border-color: var(--pink-1); background: var(--pink-4); }
.upload-area input[type=file] {
    position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
}
.upload-area .up-icon { font-size: 2rem; }
.upload-area p { font-family: var(--font-body); font-size: .83rem; color: var(--muted); margin-top: .35rem; }
#preview-img { display: none; max-width: 100%; border-radius: 8px; margin-top: .75rem; }

/* ── RINGKASAN SIDEBAR ── */
.produk-mini {
    display: flex; gap: 1rem; align-items: center;
    padding-bottom: 1rem; border-bottom: 1px solid var(--border); margin-bottom: 1rem;
}
.produk-mini img {
    width: 64px; height: 64px; border-radius: 8px;
    object-fit: cover; border: 2px solid var(--pink-3);
}
.produk-mini .pname { font-family: var(--font-ui); font-weight: 600; font-size: .9rem; }
.produk-mini .pbadge {
    font-family: var(--font-ui);
    display: inline-block; background: var(--pink-3); color: var(--pink-deep);
    font-size: .72rem; border-radius: 99px; padding: .15rem .6rem; margin-top: .2rem;
}

.row-price {
    font-family: var(--font-body);
    display: flex; justify-content: space-between;
    font-size: .87rem; margin-bottom: .5rem;
}
.row-price.diskon { color: var(--pink-deep); }
.row-price.total {
    font-family: var(--font-heading);
    font-weight: 700; font-size: 1rem;
    border-top: 1px solid var(--border);
    padding-top: .75rem; margin-top: .5rem; color: var(--pink-deep);
}

.nego-badge {
    font-family: var(--font-ui);
    background: var(--pink-3); color: var(--pink-deep);
    font-size: .78rem; border-radius: 8px;
    padding: .5rem .75rem; margin-bottom: 1rem;
    display: flex; align-items: center; gap: .4rem;
}

/* ── EKSPEDISI ── */
.ekspedisi-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: .6rem; margin-bottom: 1rem;
}
.eks-card {
    border: 2px solid var(--border); border-radius: 10px;
    padding: .7rem .9rem; cursor: pointer; transition: all .2s;
}
.eks-card:not(.loading):not(.unavailable):not(.selected):hover { border-color: var(--pink-1); background: var(--pink-4); }
.eks-card.selected { border-color: var(--pink-deep); background: var(--pink-4); box-shadow: 0 0 0 1px var(--pink-deep); }
.eks-card .eks-name   { font-family: var(--font-ui); font-weight: 600; font-size: .88rem; }
.eks-card .eks-ongkir { font-family: var(--font-body); font-size: .78rem; color: var(--muted); margin-top: .15rem; }
.eks-card.loading     { opacity: .5; pointer-events: none; }
.eks-card.unavailable {
    opacity: .45;
    cursor: not-allowed;
    pointer-events: none;
    background: #f5f5f5;
    border-color: #ddd;
}
.eks-card.unavailable .eks-ongkir { color: #e05c5c; }

/* ── ALERT ── */
.alert {
    font-family: var(--font-body);
    padding: .7rem 1rem; border-radius: 9px;
    font-size: .83rem; margin-bottom: 1rem;
    display: flex; gap: .5rem; align-items: flex-start;
}
.alert-info { background: var(--pink-3); color: var(--pink-deep); }
.alert-warn { background: #fef3c7; color: #92400e; }

/* ── BTN ── */
.btn {
    font-family: var(--font-ui);
    display: block; width: 100%; padding: .9rem;
    border: none; border-radius: 10px;
    font-size: .95rem; font-weight: 600;
    cursor: pointer; transition: all .2s;
}
.btn-primary {
    background: linear-gradient(135deg, var(--pink-1), var(--pink-deep));
    color: #fff; box-shadow: 0 4px 14px rgba(255,111,163,.30);
}
.btn-primary:hover    { opacity: .9; transform: translateY(-1px); background: #1db899b1; }
.btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; box-shadow: none; }

/* ── COD JENIS ── */
.cod-jenis-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: .75rem; margin-bottom: 1.25rem;
}
.cod-jenis-card {
    font-family: var(--font-ui);
    border: 2px solid var(--border); border-radius: 10px;
    padding: .85rem; cursor: pointer; text-align: center; transition: all .2s;
}
.cod-jenis-card:hover    { border-color: var(--pink-1); background: #1db899b1; }
.cod-jenis-card.selected { border-color: var(--pink-deep); background: var(--pink-4); }
.cod-jenis-card .cj-icon  { font-size: 1.4rem; }
.cod-jenis-card .cj-label { font-family: var(--font-ui); font-weight: 600; font-size: .85rem; margin-top: .3rem; color: var(--text); }
.cod-jenis-card .cj-desc  { font-family: var(--font-body); font-size: .75rem; color: var(--muted); margin-top: .2rem; }
    </style>
</head>
<body>

<nav>
    <div class="brand">
        <img src="../uploads/toko/logo.png" class="logo-img" alt="Logo">
        <span>Cloudy</span> <span class="pink-text">Girls</span>
    </div>
    
    <span class="checkout-text">Checkout Aman 🔒</span>
</nav>

<form method="POST" action="proses_checkout.php" enctype="multipart/form-data" id="checkoutForm">
<input type="hidden" name="produk_id"    value="<?= $produk['id'] ?>">
<input type="hidden" name="pembeli_id"   value="<?= $pembeli_id ?>">
<input type="hidden" name="nego_id"      value="<?= $nego['id'] ?? '' ?>">
<input type="hidden" name="harga_produk" value="<?= $harga_produk ?>">
<input type="hidden" name="metode"       id="inp_metode"   value="">
<input type="hidden" name="ekspedisi"    id="inp_ekspedisi" value="">
<input type="hidden" name="ongkir"       id="inp_ongkir"   value="0">
<input type="hidden" name="metode_transfer" id="inp_rek"  value="">
<input type="hidden" name="kecamatan_id" id="inp_kec_id"  value="">
<input type="hidden" name="cod_jenis"    id="inp_cod_jenis" value="">

<div class="container">

  <!-- ══ KOLOM KIRI ══ -->
  <div>

    <!-- Step indicator -->
    <div class="steps" id="stepBar">
      <div class="step-item active" id="step1">1 · Metode</div>
      <div class="step-item" id="step2">2 · Alamat</div>
      <div class="step-item" id="step3">3 · Konfirmasi</div>
    </div>

    <!-- ── STEP 1: Pilih metode ── -->
    <div class="card" id="sectionMetode">
      <div class="card-title"><span class="num">1</span> Pilih Metode Transaksi</div>

      <div class="metode-tabs">
        <div class="metode-tab" id="tabCOD" onclick="pilihMetode('cod')">
          <span class="icon">🛵</span>
          <div class="label">COD</div>
          <div class="desc">Bayar saat terima<br>Khusus Banyuwangi Kota</div>
        </div>
        <div class="metode-tab" id="tabTransfer" onclick="pilihMetode('transfer')">
          <span class="icon">💸</span>
          <div class="label">Transfer</div>
          <div class="desc">BCA atau DANA<br>Kirim via JNT/JNE</div>
        </div>
      </div>

      <!-- COD section -->
      <div class="section-body" id="secCOD">
        <div class="card-title" style="font-size:.95rem; margin-bottom:.75rem;"><span class="num" style="background:linear-gradient(135deg,var(--pink-1),var(--pink-deep))">▸</span> Jenis COD</div>
        <div class="cod-jenis-grid">
          <div class="cod-jenis-card" onclick="pilihCODJenis('antar')">
            <div class="cj-icon">🛵</div>
            <div class="cj-label">Antar ke Rumah</div>
            <div class="cj-desc">Penjual antar ke alamatmu<br>(area Banyuwangi Kota)</div>
          </div>
          <div class="cod-jenis-card" onclick="pilihCODJenis('ambil')">
            <div class="cj-icon">🏠</div>
            <div class="cj-label">Beli ke Rumah Penjual</div>
            <div class="cj-desc">Datang langsung ke tempat kami</div>
          </div>
        </div>

        <div class="form-group">
          <label>Alamat Lengkap <span class="req">*</span></label>
          <input type="text" name="lokasi_cod" id="lokasi_cod" placeholder="Contoh: Jl. Ahmad Yani No. 10, RT 02/03, Kel. Sobo">
        </div>

        <div id="maps-btn-wrap" style="display:none; margin-top:8px; margin-bottom:12px;">
          <a id="maps-link" href="" target="_blank"
             style="display:flex;align-items:center;justify-content:center;gap:8px;
                    width:100%;padding:11px;border-radius:10px;
                    background:linear-gradient(135deg,#1A73E8,#1557B0);
                    color:#fff;font-size:13px;font-weight:600;text-decoration:none;
                    box-shadow:0 3px 10px rgba(26,115,232,.25);transition:opacity .2s;"
             onmouseover="this.style.opacity='.88'" onmouseout="this.style.opacity='1'">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="white">
                  <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
              </svg>
              Buka Lokasi di Google Maps
          </a>
          <div style="font-size:11px;color:var(--muted);text-align:center;margin-top:5px;">
              <i>Area Banyuwangi Kota</i>
          </div>
        </div>

        <div class="form-group">
          <label>Catatan untuk Penjual</label>
          <textarea name="catatan_cod" id="catatan_cod" placeholder="Contoh: jam berapa kamu akan datang, patokan, dll."></textarea>
        </div>
      </div>

      <!-- Transfer section -->
      <div class="section-body" id="secTransfer">
        <div class="alert alert-info">📦 Pengiriman via JNT atau JNE ke seluruh Indonesia</div>

        <div class="form-group">
          <label>Nama Penerima <span class="req">*</span></label>
          <input type="text" name="nama_penerima" value="<?= htmlspecialchars($pembeli['nama'] ?? '') ?>" placeholder="Nama lengkap penerima">
        </div>
        <div class="form-group">
          <label>No. HP Penerima <span class="req">*</span></label>
          <input type="tel" name="no_hp_penerima" value="<?= htmlspecialchars($pembeli['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxxx">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem;">
          <div class="form-group">
            <label>Provinsi <span class="req">*</span></label>
            <select name="provinsi" id="selProvinsi" onchange="loadKota()">
              <option value="">— Pilih Provinsi —</option>
            </select>
          </div>
          <div class="form-group">
            <label>Kota/Kabupaten <span class="req">*</span></label>
            <select name="kota_tujuan" id="selKota" onchange="loadKecamatan()" disabled>
              <option value="">— Pilih Kota —</option>
            </select>
          </div>
        </div>

        <div class="form-group">
          <label>Kecamatan <span class="req">*</span></label>
          <select name="kecamatan" id="selKecamatan" onchange="onKecamatanChange()" disabled>
            <option value="">— Pilih Kecamatan —</option>
          </select>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:.75rem;">
          <div class="form-group">
            <label>Detail Alamat <span class="req">*</span></label>
            <input type="text" name="detail_alamat" placeholder="Nama jalan, nomor rumah, RT/RW">
          </div>
          <div class="form-group">
            <label>Kode Pos</label>
            <input type="text" name="kode_pos" placeholder="12345" maxlength="10">
          </div>
        </div>

        <!-- Pilih Ekspedisi -->
        <div id="areaEkspedisi" style="display:none; margin-top:.5rem;">
          <label style="margin-bottom:.6rem;">Pilih Ekspedisi <span class="req">*</span></label>
          <div class="ekspedisi-grid" id="ekspedisiGrid">
            <div class="eks-card" id="eksJNT" onclick="pilihEks('jnt')">
              <div class="eks-name">JNT Express</div>
              <div class="eks-ongkir" id="ongkirJNT">Pilih kecamatan dulu</div>
            </div>
            <div class="eks-card" id="eksJNE" onclick="pilihEks('jne')">
              <div class="eks-name">JNE Regular</div>
              <div class="eks-ongkir" id="ongkirJNE">Pilih kecamatan dulu</div>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label>Catatan</label>
          <textarea name="catatan" placeholder="Pesan tambahan untuk penjual (opsional)"></textarea>
        </div>
      </div>
    </div><!-- /card metode -->

    <!-- ── STEP 3: Transfer & Bukti ── -->
    <div class="card" id="sectionTransferDetail" style="display:none; margin-top:1rem;">
      <div class="card-title"><span class="num">3</span> Pilih Rekening & Upload Bukti</div>

      <?php if ($ada_diskon): ?>
      <div class="alert alert-info">🎉 Kamu dapat diskon ongkir <strong>Rp10.000</strong> karena harga produk &gt; Rp50.000!</div>
      <?php endif; ?>

      <label style="margin-bottom:.6rem;">Rekening Tujuan Transfer <span class="req">*</span></label>
      <?php
        $no_bca  = !empty($toko['no_rek_bca'])   ? htmlspecialchars($toko['no_rek_bca'])   : '-';
        $nm_bca  = !empty($toko['nama_rek_bca']) ? htmlspecialchars($toko['nama_rek_bca']) : 'Cloudy Girls';
        $no_dana = !empty($toko['no_dana'])       ? htmlspecialchars($toko['no_dana'])      : '-';
        $nm_dana = !empty($toko['nama_dana'])     ? htmlspecialchars($toko['nama_dana'])    : 'Cloudy Girls';
      ?>
      <div class="rekening-grid">
        <div class="rek-card" onclick="pilihRek('bca')">
          <div class="rek-name">
            <svg width="20" height="20" viewBox="0 0 40 40" style="vertical-align:middle;margin-right:5px;" xmlns="http://www.w3.org/2000/svg">
              <rect width="40" height="40" rx="6" fill="#005BAA"/>
              <text x="50%" y="55%" dominant-baseline="middle" text-anchor="middle" fill="white" font-size="11" font-weight="bold" font-family="Arial">BCA</text>
            </svg> BCA
          </div>
          <div class="rek-no"><?= $no_bca ?><br>a/n <?= $nm_bca ?></div>
        </div>
        <div class="rek-card" onclick="pilihRek('dana')">
          <div class="rek-name">
            <img src="../uploads/icons/dana.png" style="width:20px;height:20px;object-fit:contain;vertical-align:middle;margin-right:5px;"> DANA
          </div>
          <div class="rek-no"><?= $no_dana ?><br>a/n <?= $nm_dana ?></div>
        </div>
      </div>

      <div class="form-group">
        <label>Jumlah yang Ditransfer <span class="req">*</span></label>
        <input type="number" name="jumlah_transfer" id="inpJumlah" placeholder="Masukkan nominal sesuai total bayar" readonly>
      </div>

      <div class="form-group">
        <label>Upload Bukti Transfer <span class="req">*</span></label>
        <div class="upload-area" id="uploadArea">
          <input type="file" name="bukti_transfer" id="fileBukti" accept="image/*" onchange="previewFile(this)">
          <div class="up-icon">📷</div>
          <p>Klik atau seret foto struk transfer ke sini<br><small>JPG, PNG, maks 2MB</small></p>
          <img id="preview-img" src="" alt="preview">
        </div>
      </div>
    </div>

  </div><!-- /kolom kiri -->

  <!-- ══ SIDEBAR KANAN ══ -->
  <div>
    <div class="card">
      <div class="card-title" style="font-size:1rem;">🛍 Ringkasan Pesanan</div>

      <div class="produk-mini">
        <?php
        $foto = $produk['foto_utama'] ?? '';
        $fotoSrc = $foto ? '../uploads/produk/' . $foto : 'https://via.placeholder.com/64x64?text=Foto';
        ?>
        <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="foto_produk">
        <div>
          <div class="pnama"><?= htmlspecialchars($produk['nama_barang']) ?></div>
          <span class="pbadge"><?= htmlspecialchars($produk['kategori'] ?? '') ?></span>
          <?php if ($produk['ukuran'] ?? ''): ?>
          <span class="pbadge" style="background:var(--pink-2);color:#fff;"><?= htmlspecialchars($produk['ukuran']) ?></span>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($nego): ?>
      <div class="nego-badge">🤝 Harga Nego Disetujui</div>
      <?php endif; ?>

      <div class="row-price">
        <span>Harga produk</span>
        <span>Rp <?= number_format($harga_produk, 0, ',', '.') ?></span>
      </div>
      <div class="row-price diskon" id="rowDiskon" style="display:none">
        <span>Diskon ongkir</span>
        <span>– Rp 10.000</span>
      </div>
      <div class="row-price" id="rowOngkir" style="display:none">
        <span>Ongkos kirim</span>
        <span id="txtOngkir">Rp 0</span>
      </div>
      <div class="row-price total">
        <span>Total Bayar</span>
        <span id="txtTotal">Rp <?= number_format($harga_produk, 0, ',', '.') ?></span>
      </div>

      <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
        Buat Pesanan 
      </button>
     
    </div>
  </div>

</div><!-- /container -->
</form>

<script>
const HARGA      = <?= $harga_produk ?>;
const ADA_DISKON = <?= $ada_diskon ? 'true' : 'false' ?>;
const DISKON     = <?= $diskon_nominal ?>;

let metode       = '';
let ongkir       = 0;
let rekeningSel  = '';
let ekspedisi    = '';
let kecamatanSel = false;

// ── Link Google Maps langsung ke lokasi toko ──
const MAPS_TOKO = 'https://maps.app.goo.gl/zo5cvjjenoCqa7mk6';

// ── Pilih Metode ──
function pilihMetode(m) {
    metode = m;
    document.getElementById('inp_metode').value = m;
    document.getElementById('tabCOD').classList.toggle('selected', m === 'cod');
    document.getElementById('tabTransfer').classList.toggle('selected', m === 'transfer');
    document.getElementById('secCOD').classList.toggle('visible', m === 'cod');
    document.getElementById('secTransfer').classList.toggle('visible', m === 'transfer');
    document.getElementById('sectionTransferDetail').style.display = m === 'transfer' ? 'block' : 'none';

    document.getElementById('step2').classList.add('active');
    if (m === 'cod') {
        ongkir = 0;
        document.getElementById('rowDiskon').style.display = 'none';
        document.getElementById('rowOngkir').style.display = 'none';
    }
    hitungTotal();
    cekSubmit();
}

// ── COD Jenis ──
function pilihCODJenis(j) {
    document.getElementById('inp_cod_jenis').value = j;
    document.querySelectorAll('.cod-jenis-card').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');

    const alamat   = <?= json_encode($toko['alamat'] ?? '') ?>;
    const mapsUrl  = <?= json_encode($toko['maps_url'] ?? '') ?>;
    const mapsWrap = document.getElementById('maps-btn-wrap');

    if (j === 'antar') {
        // Antar ke rumah pembeli — input alamat bebas diisi
        document.getElementById('lokasi_cod').placeholder = 'Contoh: Jl. Ahmad Yani No. 10, RT 02/03, Kel. Sobo';
        document.getElementById('lokasi_cod').readOnly    = false;
        document.getElementById('lokasi_cod').value       = '';
        mapsWrap.style.display = 'none';
    } else {
        // Ambil ke rumah penjual — tampilkan alamat toko & tombol maps
        document.getElementById('lokasi_cod').value    = alamat || 'Rumah Penjual';
        document.getElementById('lokasi_cod').readOnly = true;

        // ✅ Pakai link Google Maps langsung, sama seperti di index.php
        document.getElementById('maps-link').href = MAPS_TOKO;
        mapsWrap.style.display = 'block';
    }
    cekSubmit();
}

// ── Rekening ──
function pilihRek(r) {
    rekeningSel = r;
    document.getElementById('inp_rek').value = r;
    document.querySelectorAll('.rek-card').forEach(el => el.classList.remove('selected'));
    event.currentTarget.classList.add('selected');
    cekSubmit();
}

// ── Ekspedisi ──
function pilihEks(e) {
    ekspedisi = e;
    document.getElementById('inp_ekspedisi').value = e;
    document.getElementById('eksJNT').classList.toggle('selected', e === 'jnt');
    document.getElementById('eksJNE').classList.toggle('selected', e === 'jne');

    const ongkirVal = e === 'jnt'
        ? parseInt(document.getElementById('eksJNT').dataset.ongkir || 0)
        : parseInt(document.getElementById('eksJNE').dataset.ongkir || 0);

    ongkir = ongkirVal;
    document.getElementById('inp_ongkir').value = ongkir;

    if (ADA_DISKON) {
        document.getElementById('rowDiskon').style.display = 'flex';
    }
    document.getElementById('rowOngkir').style.display = 'flex';
    document.getElementById('txtOngkir').textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
    hitungTotal();
    cekSubmit();
}

function hitungTotal() {
    const diskonVal = (metode === 'transfer' && ADA_DISKON) ? DISKON : 0;
    const total = HARGA - diskonVal + ongkir;
    document.getElementById('txtTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('inpJumlah').value = total;
}

function cekSubmit() {
    let ok = false;
    if (metode === 'cod') {
        const jenis  = document.getElementById('inp_cod_jenis').value;
        const lokasi = document.getElementById('lokasi_cod').value.trim();
        ok = jenis !== '' && lokasi !== '';
    } else if (metode === 'transfer') {
        ok = kecamatanSel && ekspedisi !== '' && rekeningSel !== ''
            && document.getElementById('fileBukti').files.length > 0;
    }
    document.getElementById('btnSubmit').disabled = !ok;
}

// ── Preview bukti ──
function previewFile(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        const img = document.getElementById('preview-img');
        img.src = e.target.result;
        img.style.display = 'block';
    };
    reader.readAsDataURL(file);
    cekSubmit();
}

// ── Drag & drop ──
const ua = document.getElementById('uploadArea');
ua.addEventListener('dragover', e => { e.preventDefault(); ua.classList.add('dragover'); });
ua.addEventListener('dragleave', () => ua.classList.remove('dragover'));
ua.addEventListener('drop', e => {
    e.preventDefault(); ua.classList.remove('dragover');
    const dt = e.dataTransfer;
    if (dt.files.length) {
        document.getElementById('fileBukti').files = dt.files;
        previewFile(document.getElementById('fileBukti'));
    }
});

// ── BinderByte Wilayah & Ongkir ──
async function loadProvinsi() {
    try {
        const res  = await fetch('../ajax/get_provinsi.php');
        const data = await res.json();
        const sel  = document.getElementById('selProvinsi');
        data.forEach(p => {
            const opt = document.createElement('option');
            opt.value        = p.id;
            opt.dataset.nama = p.name;
            opt.textContent  = p.name;
            sel.appendChild(opt);
        });
    } catch(e) { console.warn('loadProvinsi:', e); }
}

async function loadKota() {
    const provId = document.getElementById('selProvinsi').value;
    const sel    = document.getElementById('selKota');
    sel.innerHTML = '<option value="">Memuat...</option>';
    sel.disabled  = true;
    document.getElementById('selKecamatan').innerHTML = '<option value="">— Pilih Kecamatan —</option>';
    document.getElementById('selKecamatan').disabled  = true;
    document.getElementById('areaEkspedisi').style.display = 'none';
    kecamatanSel = false;
    try {
        const res  = await fetch(`../ajax/get_kota.php?id_provinsi=${provId}`);
        const data = await res.json();
        sel.innerHTML = '<option value="">— Pilih Kota —</option>';
        data.forEach(k => {
            const opt = document.createElement('option');
            opt.value        = k.id;
            opt.dataset.nama = k.name;
            opt.textContent  = k.name;
            sel.appendChild(opt);
        });
        sel.disabled = false;
    } catch(e) { console.warn('loadKota:', e); }
}

async function loadKecamatan() {
    const kotaId = document.getElementById('selKota').value;
    const sel    = document.getElementById('selKecamatan');
    sel.innerHTML = '<option value="">Memuat...</option>';
    sel.disabled  = true;
    document.getElementById('areaEkspedisi').style.display = 'none';
    kecamatanSel = false;
    try {
        const res  = await fetch(`../ajax/get_kecamatan.php?id_kabupaten=${kotaId}`);
        const data = await res.json();
        sel.innerHTML = '<option value="">— Pilih Kecamatan —</option>';
        data.forEach(k => {
            const opt = document.createElement('option');
            opt.value        = k.id;
            opt.dataset.nama = k.name;
            opt.textContent  = k.name;
            sel.appendChild(opt);
        });
        sel.disabled = false;
    } catch(e) { console.warn('loadKecamatan:', e); }
}

async function onKecamatanChange() {
    const selKec = document.getElementById('selKecamatan');
    const optKec = selKec.options[selKec.selectedIndex];
    if (!optKec.value) return;

    document.getElementById('inp_kec_id').value = optKec.value;
    kecamatanSel = true;

    document.getElementById('areaEkspedisi').style.display = 'block';
    await hitungOngkir(optKec.value);
    cekSubmit();
}

async function hitungOngkir(destination) {
    ['eksJNT','eksJNE'].forEach(id => document.getElementById(id).classList.add('loading'));
    document.getElementById('ongkirJNT').textContent = 'Menghitung...';
    document.getElementById('ongkirJNE').textContent = 'Menghitung...';
    try {
        const res  = await fetch(`../ajax/cek_ongkir.php?destination=${encodeURIComponent(destination)}`);
        const data = await res.json();
        const cardJNT = document.getElementById('eksJNT');
        const cardJNE = document.getElementById('eksJNE');
        if (data.jnt !== null) {
            cardJNT.dataset.ongkir = data.jnt;
            cardJNT.classList.remove('unavailable');
            document.getElementById('ongkirJNT').textContent =
                'Rp ' + parseInt(data.jnt).toLocaleString('id-ID') +
                (data.jnt_etd ? ` · ${data.jnt_etd}` : '');
        } else {
            delete cardJNT.dataset.ongkir;
            cardJNT.classList.add('unavailable');
            cardJNT.classList.remove('selected');
            document.getElementById('ongkirJNT').textContent = 'Tidak tersedia';
            if (ekspedisi === 'jnt') {
                ekspedisi = '';
                document.getElementById('inp_ekspedisi').value = '';
            }
        }
        if (data.jne !== null) {
            cardJNE.dataset.ongkir = data.jne;
            cardJNE.classList.remove('unavailable');
            document.getElementById('ongkirJNE').textContent =
                'Rp ' + parseInt(data.jne).toLocaleString('id-ID') +
                (data.jne_etd ? ` · ${data.jne_etd}` : '');
        } else {
            delete cardJNE.dataset.ongkir;
            cardJNE.classList.add('unavailable');
            cardJNE.classList.remove('selected');
            document.getElementById('ongkirJNE').textContent = 'Tidak tersedia';
            if (ekspedisi === 'jne') {
                ekspedisi = '';
                document.getElementById('inp_ekspedisi').value = '';
            }
        }
    } catch(e) {
        document.getElementById('ongkirJNT').textContent = 'Gagal memuat';
        document.getElementById('ongkirJNE').textContent = 'Gagal memuat';
    } finally {
        ['eksJNT','eksJNE'].forEach(id => document.getElementById(id).classList.remove('loading'));
        hitungTotal();
        cekSubmit();
    }
}

// Live validasi lokasi COD
document.addEventListener('DOMContentLoaded', () => {
    loadProvinsi();
    const loc = document.getElementById('lokasi_cod');
    if (loc) loc.addEventListener('input', cekSubmit);
});
</script>

</body>
</html>