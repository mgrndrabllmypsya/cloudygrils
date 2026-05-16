<?php

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

$harga_produk   = $produk['harga'];
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
            --purple:   #7c3aed;
            --purple-light: #ede9fe;
            --pink:     #db2777;
            --pink-light: #fce7f3;
            --bg:       #f5f3ff;
            --card:     #ffffff;
            --text:     #1e1b2e;
            --muted:    #6b7280;
            --border:   #e5e7eb;
            --radius:   14px;
            --shadow:   0 2px 16px rgba(124,58,237,.08);
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        /* ── NAVBAR ── */
        nav {
            background: #fff;
            border-bottom: 1px solid var(--border);
            padding: 0 2rem;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .brand { font-family: 'Playfair Display', serif; font-size: 1.25rem; color: var(--purple); }
        .brand span { color: var(--pink); }

        /* ── LAYOUT ── */
        .container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 720px) {
            .container { grid-template-columns: 1fr; }
        }

        /* ── CARD ── */
        .card {
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
        }
        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.1rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .card-title .num {
            width: 26px; height: 26px;
            background: var(--purple);
            color: #fff;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: .75rem; font-family: 'DM Sans', sans-serif; font-weight: 600;
        }

        /* ── STEP INDICATOR ── */
        .steps {
            display: flex;
            gap: 0;
            margin-bottom: 1.5rem;
            background: var(--card);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .step-item {
            flex: 1;
            padding: .75rem .5rem;
            text-align: center;
            font-size: .78rem;
            font-weight: 500;
            color: var(--muted);
            border-bottom: 3px solid transparent;
            transition: all .3s;
        }
        .step-item.active {
            color: var(--purple);
            border-bottom-color: var(--purple);
            background: var(--purple-light);
        }
        .step-item.done {
            color: var(--pink);
            border-bottom-color: var(--pink);
        }

        /* ── FORM ELEMENTS ── */
        .form-group { margin-bottom: 1rem; }
        label {
            display: block;
            font-size: .83rem;
            font-weight: 500;
            margin-bottom: .35rem;
            color: var(--text);
        }
        label .req { color: var(--pink); }
        input[type=text], input[type=tel], input[type=number],
        select, textarea {
            width: 100%;
            padding: .6rem .85rem;
            border: 1.5px solid var(--border);
            border-radius: 9px;
            font-family: inherit;
            font-size: .9rem;
            color: var(--text);
            background: #fff;
            transition: border-color .2s;
            outline: none;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--purple);
            box-shadow: 0 0 0 3px rgba(124,58,237,.1);
        }
        textarea { resize: vertical; min-height: 80px; }

        /* ── METODE TABS ── */
        .metode-tabs {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: 1.5rem;
        }
        .metode-tab {
            border: 2px solid var(--border);
            border-radius: var(--radius);
            padding: 1rem;
            cursor: pointer;
            text-align: center;
            transition: all .2s;
            background: #fff;
        }
        .metode-tab:hover { border-color: var(--purple); }
        .metode-tab.selected {
            border-color: var(--purple);
            background: var(--purple-light);
        }
        .metode-tab .icon { font-size: 1.6rem; display: block; margin-bottom: .35rem; }
        .metode-tab .label { font-weight: 600; font-size: .9rem; color: var(--text); }
        .metode-tab .desc { font-size: .76rem; color: var(--muted); margin-top: .15rem; }

        /* ── SECTION TOGGLE ── */
        .section-body { display: none; }
        .section-body.visible { display: block; }

        /* ── REKENING OPTION ── */
        .rekening-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: 1rem;
        }
        .rek-card {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: .85rem 1rem;
            cursor: pointer;
            transition: all .2s;
        }
        .rek-card:hover { border-color: var(--purple); }
        .rek-card.selected { border-color: var(--purple); background: var(--purple-light); }
        .rek-card .rek-name { font-weight: 600; font-size: .9rem; }
        .rek-card .rek-no { font-size: .8rem; color: var(--muted); margin-top: .2rem; }

        /* ── UPLOAD AREA ── */
        .upload-area {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all .2s;
            position: relative;
        }
        .upload-area:hover, .upload-area.dragover { border-color: var(--purple); background: var(--purple-light); }
        .upload-area input[type=file] {
            position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%;
        }
        .upload-area .up-icon { font-size: 2rem; }
        .upload-area p { font-size: .83rem; color: var(--muted); margin-top: .35rem; }
        #preview-img {
            display: none;
            max-width: 100%;
            border-radius: 8px;
            margin-top: .75rem;
        }

        /* ── RINGKASAN SIDEBAR ── */
        .produk-mini {
            display: flex;
            gap: 1rem;
            align-items: center;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            margin-bottom: 1rem;
        }
        .produk-mini img {
            width: 64px; height: 64px;
            border-radius: 8px;
            object-fit: cover;
        }
        .produk-mini .pname { font-weight: 600; font-size: .9rem; }
        .produk-mini .pbadge {
            display: inline-block;
            background: var(--purple-light);
            color: var(--purple);
            font-size: .72rem;
            border-radius: 99px;
            padding: .15rem .6rem;
            margin-top: .2rem;
        }

        .row-price {
            display: flex;
            justify-content: space-between;
            font-size: .87rem;
            margin-bottom: .5rem;
        }
        .row-price.diskon { color: var(--pink); }
        .row-price.total {
            font-weight: 700;
            font-size: 1rem;
            border-top: 1px solid var(--border);
            padding-top: .75rem;
            margin-top: .5rem;
        }

        .nego-badge {
            background: var(--pink-light);
            color: var(--pink);
            font-size: .78rem;
            border-radius: 8px;
            padding: .5rem .75rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: .4rem;
        }

        /* ── EKSPEDISI ── */
        .ekspedisi-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .6rem;
            margin-bottom: 1rem;
        }
        .eks-card {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: .7rem .9rem;
            cursor: pointer;
            transition: all .2s;
        }
        .eks-card:hover { border-color: var(--purple); }
        .eks-card.selected { border-color: var(--purple); background: var(--purple-light); }
        .eks-card .eks-name { font-weight: 600; font-size: .88rem; }
        .eks-card .eks-ongkir { font-size: .78rem; color: var(--muted); margin-top: .15rem; }
        .eks-card.loading { opacity: .5; pointer-events: none; }

        /* ── ALERT ── */
        .alert {
            padding: .7rem 1rem;
            border-radius: 9px;
            font-size: .83rem;
            margin-bottom: 1rem;
            display: flex;
            gap: .5rem;
            align-items: flex-start;
        }
        .alert-info { background: var(--purple-light); color: var(--purple); }
        .alert-warn { background: #fef3c7; color: #92400e; }

        /* ── BTN ── */
        .btn {
            display: block;
            width: 100%;
            padding: .9rem;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: .95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--purple), var(--pink));
            color: #fff;
        }
        .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }

        /* ── HIDDEN INPUT ── */
        input[type=hidden] {}

        /* ── COD JENIS ── */
        .cod-jenis-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .75rem;
            margin-bottom: 1.25rem;
        }
        .cod-jenis-card {
            border: 2px solid var(--border);
            border-radius: 10px;
            padding: .85rem;
            cursor: pointer;
            text-align: center;
            transition: all .2s;
        }
        .cod-jenis-card:hover { border-color: var(--purple); }
        .cod-jenis-card.selected { border-color: var(--purple); background: var(--purple-light); }
        .cod-jenis-card .cj-icon { font-size: 1.4rem; }
        .cod-jenis-card .cj-label { font-weight: 600; font-size: .85rem; margin-top: .3rem; }
        .cod-jenis-card .cj-desc { font-size: .75rem; color: var(--muted); margin-top: .2rem; }
    </style>
</head>
<body>

<nav>
    <div class="brand">Cloudy<span>Girls</span></div>
    <span style="font-size:.85rem;color:var(--muted);">Checkout Aman 🔒</span>
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
          <span class="icon">💳</span>
          <div class="label">Transfer</div>
          <div class="desc">BCA atau DANA<br>Kirim via JNT/JNE</div>
        </div>
      </div>

      <!-- COD section -->
      <div class="section-body" id="secCOD">
        <div class="card-title" style="font-size:.95rem; margin-bottom:.75rem;"><span class="num" style="background:var(--pink)">▸</span> Jenis COD</div>
        <div class="cod-jenis-grid">
          <div class="cod-jenis-card" onclick="pilihCODJenis('temu')">
            <div class="cj-icon">🤝</div>
            <div class="cj-label">Temu di Titik</div>
            <div class="cj-desc">Sepakati lokasi temu</div>
          </div>
          <div class="cod-jenis-card" onclick="pilihCODJenis('antar')">
            <div class="cj-icon">🏠</div>
            <div class="cj-label">Antar ke Rumah</div>
            <div class="cj-desc">Diantar ke alamatmu</div>
          </div>
        </div>

        <div class="form-group">
          <label>Lokasi / Titik Temu <span class="req">*</span></label>
          <input type="text" name="lokasi_cod" id="lokasi_cod" placeholder="Contoh: Alfamart Jl. Gajah Mada / Alamat lengkap rumah">
        </div>
        <div class="form-group">
          <label>Catatan untuk Penjual</label>
          <textarea name="catatan" placeholder="Waktu yang diinginkan, patokan, dll."></textarea>
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
      <div class="rekening-grid">
        <div class="rek-card" onclick="pilihRek('bca')">
          <div class="rek-name">🏦 BCA</div>
          <div class="rek-no">1234567890<br>a/n Cloudy Girls</div>
        </div>
        <div class="rek-card" onclick="pilihRek('dana')">
          <div class="rek-name">💜 DANA</div>
          <div class="rek-no">08123456789<br>a/n Cloudy Girls</div>
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
          $foto = $produk['foto'] ?? '';
          $fotoSrc = $foto ? '../uploads/produk/' . $foto : 'https://via.placeholder.com/64x64?text=Foto';
        ?>
        <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="foto_produk">
        <div>
          <div class="pnama"><?= htmlspecialchars($produk['nama_barang']) ?></div>
          <span class="pbadge"><?= htmlspecialchars($produk['kategori'] ?? '') ?></span>
          <?php if ($produk['ukuran'] ?? ''): ?>
          <span class="pbadge" style="background:var(--pink-light);color:var(--pink);">Ukuran <?= htmlspecialchars($produk['ukuran']) ?></span>
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
const HARGA    = <?= $harga_produk ?>;
const ADA_DISKON = <?= $ada_diskon ? 'true' : 'false' ?>;
const DISKON   = <?= $diskon_nominal ?>;

let metode     = '';
let ongkir     = 0;
let rekeningSel = '';
let ekspedisi  = '';
let kecamatanSel = false;

// ── Pilih Metode ──
function pilihMetode(m) {
  metode = m;
  document.getElementById('inp_metode').value = m;
  document.getElementById('tabCOD').classList.toggle('selected', m === 'cod');
  document.getElementById('tabTransfer').classList.toggle('selected', m === 'transfer');
  document.getElementById('secCOD').classList.toggle('visible', m === 'cod');
  document.getElementById('secTransfer').classList.toggle('visible', m === 'transfer');
  document.getElementById('sectionTransferDetail').style.display = m === 'transfer' ? 'block' : 'none';

  // Step bar
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
  document.getElementById('lokasi_cod').placeholder = j === 'temu'
    ? 'Contoh: Alfamart Jl. Gajah Mada (dekat SPBU)'
    : 'Alamat lengkap rumah + patokan';
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

// ── RajaOngkir Wilayah ──
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
  const selKec  = document.getElementById('selKecamatan');
  const selKota = document.getElementById('selKota');
  const optKec  = selKec.options[selKec.selectedIndex];
  const optKota = selKota.options[selKota.selectedIndex];
  if (!optKec.value) return;

  document.getElementById('inp_kec_id').value = optKec.value;
  kecamatanSel = true;

  // Pakai nama kota saja (tanpa KAB./KOTA)
  const namaKota = (optKota.dataset.nama || '').toLowerCase()
                    .replace('kab. ', '').replace('kota ', '').trim();

  document.getElementById('areaEkspedisi').style.display = 'block';
  await hitungOngkir(namaKota); // ← kirim nama kota saja
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
      document.getElementById('ongkirJNT').textContent =
        'Rp ' + parseInt(data.jnt).toLocaleString('id-ID') +
        (data.jnt_etd ? ` · ${data.jnt_etd}` : '');
    } else {
      document.getElementById('ongkirJNT').textContent = 'Tidak tersedia';
    }
    if (data.jne !== null) {
      cardJNE.dataset.ongkir = data.jne;
      document.getElementById('ongkirJNE').textContent =
        'Rp ' + parseInt(data.jne).toLocaleString('id-ID') +
        (data.jne_etd ? ` · ${data.jne_etd}` : '');
    } else {
      document.getElementById('ongkirJNE').textContent = 'Tidak tersedia';
    }
  } catch(e) {
    document.getElementById('ongkirJNT').textContent = 'Gagal memuat';
    document.getElementById('ongkirJNE').textContent = 'Gagal memuat';
  } finally {
    ['eksJNT','eksJNE'].forEach(id => document.getElementById(id).classList.remove('loading'));
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