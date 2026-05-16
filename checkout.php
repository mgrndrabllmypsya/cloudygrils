<?php
session_start();
require_once '../config/koneksi.php';
require_once '../includes/helpers.php';
requireLogin('../auth/login.php');

$produk_id = isset($_GET['produk_id']) ? (int)$_GET['produk_id'] : 0;
if (!$produk_id) { header("Location: ../pages/home.php"); exit; }

$user_id = $_SESSION['user_id'];
$q_user  = mysqli_query($conn, "SELECT * FROM pembeli WHERE id=$user_id LIMIT 1");
$user    = mysqli_fetch_assoc($q_user);

$q_produk = mysqli_query($conn, "SELECT * FROM produk WHERE id=$produk_id AND status='aktif' LIMIT 1");
if (!$q_produk || mysqli_num_rows($q_produk) === 0) {
    header("Location: ../pages/home.php?err=produk_habis"); exit;
}
$produk = mysqli_fetch_assoc($q_produk);

$q_toko = mysqli_query($conn, "SELECT * FROM pengaturan_toko LIMIT 1");
$toko   = $q_toko ? mysqli_fetch_assoc($q_toko) : [];

$diskon   = $produk['harga'] > 50000 ? 10000 : 0;
$base_url = '../';
$page_title = 'Checkout';
include '../includes/header.php';
?>
<style>
.checkout-wrap{max-width:900px;margin:40px auto;padding:0 40px;display:grid;grid-template-columns:1fr 380px;gap:32px;}
.checkout-form h2{font-family:'Playfair Display',serif;font-size:22px;font-weight:700;margin-bottom:24px !important;}
.step-title{font-size:12px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--accent2);margin-bottom:16px;}
.field{margin-bottom:16px;}
.field label{display:block;font-size:11px;font-weight:600;letter-spacing:.8px;text-transform:uppercase;color:var(--dark);margin-bottom:6px;}
.field input,.field select,.field textarea{width:100%;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-family:'DM Sans',sans-serif;font-size:13px;color:var(--dark);outline:none;transition:border-color .2s;background:var(--white);}
.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--accent2);}
.field textarea{resize:vertical;min-height:80px;}
.metode-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px;}
.metode-card{border:2px solid var(--border);border-radius:12px;padding:14px;cursor:pointer;transition:all .2s;text-align:center;}
.metode-card:hover{border-color:var(--accent2);}
.metode-card.selected{border-color:var(--accent2);background:rgba(124,58,237,.06);}
.metode-card input{display:none;}
.metode-icon{font-size:24px;margin-bottom:4px;}
.metode-label{font-size:13px;font-weight:600;}
.metode-sub{font-size:11px;color:var(--muted);margin-top:2px;}
.ekspedisi-row{display:flex;gap:10px;margin-bottom:8px;}
.ekspedisi-card{flex:1;border:2px solid var(--border);border-radius:12px;padding:12px;cursor:pointer;transition:all .2s;position:relative;}
.ekspedisi-card:hover{border-color:var(--accent2);}
.ekspedisi-card.selected{border-color:var(--accent2);background:rgba(124,58,237,.06);}
.ekspedisi-card input{display:none;}
.badge-label{position:absolute;top:8px;right:8px;font-size:9px;font-weight:700;padding:2px 8px;border-radius:20px;letter-spacing:.5px;}
.badge-termurah{background:rgba(16,185,129,.1);color:#059669;}
.badge-tercepat{background:rgba(245,158,11,.1);color:#d97706;}
.rekening-card{border:1.5px solid var(--border);border-radius:12px;padding:14px;margin-bottom:10px;cursor:pointer;transition:border-color .2s;}
.rekening-card:hover,.rekening-card.selected{border-color:var(--accent2);}
.rekening-card input{display:none;}
.summary-card{background:var(--white);border:1px solid var(--border);border-radius:16px;padding:24px;position:sticky;top:100px;}
.summary-card h3{font-family:'Playfair Display',serif;font-size:18px;font-weight:700;margin-bottom:16px !important;}
.summary-img{aspect-ratio:3/4;border-radius:10px;overflow:hidden;background:var(--cream);margin-bottom:14px;}
.summary-img img{width:100%;height:100%;object-fit:cover;}
.summary-row{display:flex;justify-content:space-between;align-items:center;font-size:13px;margin-bottom:8px;}
.summary-row.total{font-size:16px;font-weight:700;color:var(--accent2);border-top:1px solid var(--border);padding-top:12px;margin-top:4px;}
.diskon-row{color:#059669;}
.btn-submit{width:100%;padding:14px;background:linear-gradient(135deg,var(--accent2),#EC4899);color:#fff;border:none;border-radius:12px;font-size:14px;font-weight:600;cursor:pointer;margin-top:16px;transition:opacity .2s;}
.btn-submit:hover{opacity:.88;}
.section-divider{height:1px;background:var(--border);margin:24px 0;}
.info-box{background:rgba(124,58,237,.06);border:1px solid rgba(124,58,237,.2);border-radius:10px;padding:12px 14px;font-size:12px;color:var(--accent2);margin-bottom:16px;}
.loading-ongkir{font-size:13px;color:var(--muted);padding:12px;text-align:center;display:none;}
@media(max-width:768px){.checkout-wrap{grid-template-columns:1fr;padding:0 16px;}.metode-grid{grid-template-columns:1fr 1fr;}.ekspedisi-row{flex-direction:column;}}
</style>

<div class="checkout-wrap">
    <div class="checkout-form">
        <h2>Checkout</h2>
        <form method="POST" action="../transaksi/proses_checkout.php" id="checkoutForm">
            <input type="hidden" name="produk_id" value="<?= $produk_id ?>">
            <input type="hidden" name="total_bayar" id="inputTotal" value="0">
            <input type="hidden" name="ongkir_final" id="inputOngkir" value="0">
            <input type="hidden" name="ekspedisi_nama" id="inputEkspedisi" value="">
            <input type="hidden" name="ekspedisi_layanan" id="inputLayanan" value="">
            <input type="hidden" name="estimasi_hari" id="inputEstimasi" value="">
            <input type="hidden" name="kecamatan_id" id="inputKecamatanId" value="">

            <!-- METODE -->
            <div class="step-title">1. Pilih Metode Transaksi</div>
            <div class="metode-grid">
                <label class="metode-card" id="cardCod" onclick="pilihMetode('cod')">
                    <input type="radio" name="metode" value="cod" id="metodeCod">
                    <div class="metode-icon">🤝</div>
                    <div class="metode-label">COD</div>
                    <div class="metode-sub">Bayar saat ketemu / antar</div>
                    <?php if (!isBanyuwangiKota($conn, $user['kota'] ?? '')): ?>
                    <div style="font-size:10px;color:#ef4444;margin-top:4px;">*Khusus Banyuwangi Kota</div>
                    <?php endif; ?>
                </label>
                <label class="metode-card selected" id="cardTransfer" onclick="pilihMetode('transfer')">
                    <input type="radio" name="metode" value="transfer" id="metodeTransfer" checked>
                    <div class="metode-icon">🏦</div>
                    <div class="metode-label">Transfer</div>
                    <div class="metode-sub">BCA / DANA + pengiriman</div>
                </label>
            </div>

            <!-- SECTION COD -->
            <div id="sectionCod" style="display:none;">
                <div class="section-divider"></div>
                <div class="step-title">2. Detail COD</div>
                <div class="field">
                    <label>Jenis COD</label>
                    <select name="jenis_cod" id="jenisCod">
                        <option value="temu">Ketemu di titik temu</option>
                        <option value="antar">Antar ke rumah saya</option>
                    </select>
                </div>
                <div class="field">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal_cod" min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                </div>
                <div class="field">
                    <label>Jam</label>
                    <input type="time" name="jam_cod">
                </div>
                <div class="field">
                    <label>Lokasi / Alamat</label>
                    <textarea name="lokasi_cod" placeholder="Titik temu atau alamat rumahmu..."></textarea>
                </div>
                <div class="field">
                    <label>Catatan (opsional)</label>
                    <textarea name="catatan_cod" placeholder="Pesan tambahan untuk penjual..."></textarea>
                </div>
            </div>

            <!-- SECTION TRANSFER -->
            <div id="sectionTransfer">
                <div class="section-divider"></div>
                <div class="step-title">2. Alamat Pengiriman</div>
                <div class="field">
                    <label>Nama Penerima</label>
                    <input type="text" name="nama_penerima" value="<?= escape($user['nama']) ?>" required>
                </div>
                <div class="field">
                    <label>No. HP Penerima</label>
                    <input type="tel" name="hp_penerima" value="<?= escape($user['no_hp'] ?? '') ?>" required>
                </div>
                <div class="field">
                    <label>Provinsi</label>
                    <input type="text" name="provinsi" placeholder="Jawa Timur" required>
                </div>
                <div class="field">
                    <label>Kota / Kabupaten</label>
                    <input type="text" name="kota" id="inputKota" placeholder="Banyuwangi" required>
                </div>
                <div class="field">
                    <label>Kecamatan</label>
                    <input type="text" name="kecamatan" id="inputKecamatan" placeholder="Banyuwangi" required>
                </div>
                <div class="field">
                    <label>Detail Alamat</label>
                    <textarea name="detail_alamat" placeholder="Nama jalan, nomor rumah, RT/RW..." required></textarea>
                </div>

                <div class="section-divider"></div>
                <div class="step-title">3. Pilih Ekspedisi</div>
                <div class="info-box"><i class="bi bi-info-circle"></i> Isi kecamatan terlebih dahulu untuk melihat estimasi ongkir.</div>
                <div id="ekspedisiWrap" style="display:none;">
                    <div class="ekspedisi-row" id="ekspedisiRow">
                        <!-- Diisi via JS setelah hit API -->
                    </div>
                </div>
                <div class="loading-ongkir" id="loadingOngkir"><i class="bi bi-arrow-clockwise"></i> Menghitung ongkir...</div>

                <div class="section-divider"></div>
                <div class="step-title">4. Pilih Rekening Tujuan</div>
                <?php if (!empty($toko['rekening_bca'])): ?>
                <label class="rekening-card" id="cardBca" onclick="pilihRekening('bca')">
                    <input type="radio" name="metode_transfer" value="bca" checked>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;background:#003087;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;">BCA</div>
                        <div>
                            <div style="font-size:14px;font-weight:600;"><?= escape($toko['rekening_bca']) ?></div>
                            <div style="font-size:12px;color:var(--muted);">a.n. <?= escape($toko['nama_rekening_bca'] ?? 'Cloudy Girls') ?></div>
                        </div>
                    </div>
                </label>
                <?php endif; ?>
                <?php if (!empty($toko['rekening_dana'])): ?>
                <label class="rekening-card" id="cardDana" onclick="pilihRekening('dana')">
                    <input type="radio" name="metode_transfer" value="dana">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:40px;height:40px;background:#118EEA;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;">DANA</div>
                        <div>
                            <div style="font-size:14px;font-weight:600;"><?= escape($toko['rekening_dana']) ?></div>
                            <div style="font-size:12px;color:var(--muted);">a.n. <?= escape($toko['nama_rekening_dana'] ?? 'Cloudy Girls') ?></div>
                        </div>
                    </div>
                </label>
                <?php endif; ?>

                <div class="field" style="margin-top:16px;">
                    <label>Catatan (opsional)</label>
                    <textarea name="catatan_transfer" placeholder="Pesan tambahan..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit">
                <i class="bi bi-bag-check"></i> Lanjut Pesan
            </button>
        </form>
    </div>

    <!-- SUMMARY -->
    <div>
        <div class="summary-card">
            <h3>Ringkasan</h3>
            <div class="summary-img">
                <img src="../uploads/produk/<?= escape($produk['foto_utama']) ?>"
                     alt="<?= escape($produk['nama_barang']) ?>"
                     onerror="this.src='https://placehold.co/300x400/FAF7F2/A78BFA?text=CG'">
            </div>
            <div style="font-size:14px;font-weight:600;margin-bottom:6px;"><?= escape($produk['nama_barang']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-bottom:16px;"><?= escape($produk['kondisi']) ?> <?= $produk['ukuran'] ? '· ' . escape($produk['ukuran']) : '' ?></div>

            <div class="summary-row"><span>Harga produk</span><span><?= formatRupiah($produk['harga']) ?></span></div>
            <?php if ($diskon > 0): ?>
            <div class="summary-row diskon-row"><span>Diskon pengiriman</span><span>-<?= formatRupiah($diskon) ?></span></div>
            <?php endif; ?>
            <div class="summary-row" id="rowOngkir" style="display:none;"><span>Ongkos kirim</span><span id="nilaiOngkir">-</span></div>
            <div class="summary-row total"><span>Total Bayar</span><span id="nilaiTotal"><?= formatRupiah($produk['harga'] - $diskon) ?></span></div>
        </div>
    </div>
</div>

<script>
const hargaProduk = <?= $produk['harga'] ?>;
const diskon      = <?= $diskon ?>;
const apiKey      = 'RAJAONGKIR_API_KEY'; // Ganti dengan API key RajaOngkir
const originId    = '3855'; // ID kecamatan Banyuwangi (dari RajaOngkir)
let selectedEkspedisi = null;

function pilihMetode(metode) {
    document.getElementById('cardCod').classList.remove('selected');
    document.getElementById('cardTransfer').classList.remove('selected');
    document.getElementById('sectionCod').style.display = 'none';
    document.getElementById('sectionTransfer').style.display = 'none';

    if (metode === 'cod') {
        document.getElementById('cardCod').classList.add('selected');
        document.getElementById('metodeCod').checked = true;
        document.getElementById('sectionCod').style.display = 'block';
        updateTotal(0);
        document.getElementById('rowOngkir').style.display = 'none';
    } else {
        document.getElementById('cardTransfer').classList.add('selected');
        document.getElementById('metodeTransfer').checked = true;
        document.getElementById('sectionTransfer').style.display = 'block';
    }
}

function pilihRekening(val) {
    document.querySelectorAll('.rekening-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('card' + val.charAt(0).toUpperCase() + val.slice(1)).classList.add('selected');
}
// Default selected rekening
document.getElementById('cardBca')?.classList.add('selected');

// Hitung ongkir saat kecamatan di-blur
document.getElementById('inputKecamatan')?.addEventListener('blur', function() {
    const kecamatan = this.value.trim();
    const kota      = document.getElementById('inputKota').value.trim();
    if (!kecamatan || !kota) return;
    hitungOngkir(kecamatan);
});

async function hitungOngkir(kecamatan) {
    document.getElementById('loadingOngkir').style.display = 'block';
    document.getElementById('ekspedisiWrap').style.display = 'none';

    try {
        // Cari ID kecamatan dari RajaOngkir
        const resCity = await fetch(`https://rajaongkir.komerce.id/api/v1/destination/domestic-destination?search=${encodeURIComponent(kecamatan)}&limit=5`, {
            headers: { 'key': apiKey }
        });
        const dataCity = await resCity.json();
        const dest = dataCity?.data?.[0];
        if (!dest) throw new Error('Kecamatan tidak ditemukan');

        const destId = dest.id;
        document.getElementById('inputKecamatanId').value = destId;

        // Hitung ongkir JNE + JNT
        const formData = new FormData();
        formData.append('origin', originId);
        formData.append('destination', destId);
        formData.append('weight', <?= $produk['berat_gram'] ?>);
        formData.append('courier', 'jne:jnt');

        const resOngkir = await fetch('https://rajaongkir.komerce.id/api/v1/calculate/domestic-cost', {
            method: 'POST',
            headers: { 'key': apiKey },
            body: formData
        });
        const dataOngkir = await resOngkir.json();
        tampilkanEkspedisi(dataOngkir?.data ?? []);
    } catch(e) {
        console.error(e);
        document.getElementById('loadingOngkir').style.display = 'none';
        alert('Gagal mengambil data ongkir. Pastikan kecamatan sesuai.');
    }
}

function tampilkanEkspedisi(data) {
    document.getElementById('loadingOngkir').style.display = 'none';

    // Kumpulkan semua layanan dari JNE dan JNT
    let layanan = [];
    data.forEach(kurir => {
        (kurir.costs ?? []).forEach(c => {
            layanan.push({
                kurir: kurir.code.toUpperCase(),
                nama: c.service,
                ongkir: c.cost[0]?.value ?? 0,
                hari: c.cost[0]?.etd ?? '?'
            });
        });
    });

    if (layanan.length === 0) {
        document.getElementById('ekspedisiWrap').innerHTML = '<p style="color:var(--muted);font-size:13px;">Layanan tidak tersedia untuk area ini.</p>';
        document.getElementById('ekspedisiWrap').style.display = 'block';
        return;
    }

    // Label termurah & tercepat
    const ongkirMin = Math.min(...layanan.map(l => l.ongkir));
    const hariMin   = Math.min(...layanan.map(l => parseInt(l.hari) || 999));

    const html = layanan.slice(0,4).map((l, i) => {
        const isTermurah = l.ongkir === ongkirMin;
        const isTercepat = (parseInt(l.hari) || 999) === hariMin;
        return `
        <div class="ekspedisi-card ${i===0?'selected':''}" id="eksp${i}" onclick="pilihEkspedisi(${i}, '${l.kurir}', '${l.nama}', ${l.ongkir}, '${l.hari}')">
            ${isTermurah ? '<span class="badge-label badge-termurah">Termurah</span>' : ''}
            ${isTercepat && !isTermurah ? '<span class="badge-label badge-tercepat">Tercepat</span>' : ''}
            <div style="font-size:13px;font-weight:700;">${l.kurir} ${l.nama}</div>
            <div style="font-size:14px;font-weight:700;color:var(--accent2);margin-top:4px;">Rp ${l.ongkir.toLocaleString('id-ID')}</div>
            <div style="font-size:11px;color:var(--muted);">Estimasi ${l.hari} hari</div>
        </div>`;
    }).join('');

    document.getElementById('ekspedisiRow').innerHTML = html;
    document.getElementById('ekspedisiWrap').style.display = 'block';

    // Pilih default pertama
    if (layanan.length > 0) {
        const l = layanan[0];
        pilihEkspedisi(0, l.kurir, l.nama, l.ongkir, l.hari);
    }
}

function pilihEkspedisi(idx, kurir, layanan, ongkir, hari) {
    document.querySelectorAll('.ekspedisi-card').forEach((c,i) => {
        c.classList.toggle('selected', i === idx);
    });
    selectedEkspedisi = {kurir, layanan, ongkir, hari};
    document.getElementById('inputEkspedisi').value = kurir;
    document.getElementById('inputLayanan').value   = layanan;
    document.getElementById('inputEstimasi').value  = hari;
    document.getElementById('inputOngkir').value    = ongkir;
    document.getElementById('nilaiOngkir').textContent = 'Rp ' + ongkir.toLocaleString('id-ID');
    document.getElementById('rowOngkir').style.display = 'flex';
    updateTotal(ongkir);
}

function updateTotal(ongkir) {
    const total = hargaProduk - diskon + ongkir;
    document.getElementById('nilaiTotal').textContent = 'Rp ' + total.toLocaleString('id-ID');
    document.getElementById('inputTotal').value = total;
}
</script>
<?php include '../includes/footer.php'; ?>