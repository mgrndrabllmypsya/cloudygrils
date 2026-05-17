<?php
session_start();
include '../config/koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$pesanan_id = $_GET['id'] ?? null;
$pembeli_id = $_SESSION['user_id'];

if (!$pesanan_id) {
    header("Location: ../pages/home.php");
    exit;
}

// Ambil data pesanan
$stmt = $conn->prepare("
    SELECT p.*, pr.nama_barang, pr.foto 
    FROM pesanan p 
    JOIN produk pr ON p.produk_id = pr.id 
    WHERE p.id = ? AND p.pembeli_id = ?
");
$stmt->bind_param("ii", $pesanan_id, $pembeli_id);
$stmt->execute();
$pesanan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$pesanan) {
    echo "Pesanan tidak ditemukan.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Paket – CloudyGirls</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --purple: #7c3aed;
            --purple-light: #ede9fe;
            --pink: #db2777;
            --pink-light: #fce7f3;
            --bg: #f5f3ff;
            --card: #ffffff;
            --text: #1e1b2e;
            --muted: #6b7280;
            --border: #e5e7eb;
            --radius: 14px;
            --shadow: 0 2px 16px rgba(124,58,237,.08);
            --green: #059669;
            --green-light: #d1fae5;
        }
        body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }
        nav {
            background: #fff; border-bottom: 1px solid var(--border);
            padding: 0 2rem; height: 60px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 100;
        }
        .brand { font-family: 'Playfair Display', serif; font-size: 1.25rem; color: var(--purple); }
        .brand span { color: var(--pink); }
        .container { max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        .card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem; margin-bottom: 1.25rem; }
        .card-title { font-family: 'Playfair Display', serif; font-size: 1.1rem; margin-bottom: 1.25rem; color: var(--text); }

        /* Info Pesanan */
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .75rem; }
        .info-item label { font-size: .78rem; color: var(--muted); display: block; margin-bottom: .2rem; }
        .info-item span { font-weight: 600; font-size: .9rem; }

        /* Badge Status */
        .badge {
            display: inline-block; padding: .3rem .8rem;
            border-radius: 99px; font-size: .78rem; font-weight: 600;
        }
        .badge-menunggu { background: #fef3c7; color: #92400e; }
        .badge-dikonfirmasi { background: var(--purple-light); color: var(--purple); }
        .badge-diproses { background: #dbeafe; color: #1d4ed8; }
        .badge-dikirim { background: #fce7f3; color: var(--pink); }
        .badge-selesai { background: var(--green-light); color: var(--green); }
        .badge-dibatalkan { background: #fee2e2; color: #dc2626; }

        /* Resi Box */
        .resi-box {
            background: var(--purple-light);
            border-radius: 10px;
            padding: 1rem 1.25rem;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 1.25rem;
        }
        .resi-box .resi-info { }
        .resi-box .resi-label { font-size: .78rem; color: var(--muted); margin-bottom: .2rem; }
        .resi-box .resi-no { font-size: 1.1rem; font-weight: 700; color: var(--purple); letter-spacing: 1px; }
        .resi-box .resi-kurir { font-size: .82rem; color: var(--muted); margin-top: .15rem; text-transform: uppercase; font-weight: 600; }
        .btn-copy {
            background: var(--purple); color: #fff;
            border: none; border-radius: 8px;
            padding: .5rem 1rem; font-size: .82rem; font-weight: 600;
            cursor: pointer; transition: opacity .2s;
        }
        .btn-copy:hover { opacity: .85; }

        /* Timeline */
        .timeline { position: relative; padding-left: 2rem; }
        .timeline::before {
            content: '';
            position: absolute; left: 10px; top: 0; bottom: 0;
            width: 2px; background: var(--border);
        }
        .timeline-item { position: relative; padding-bottom: 1.25rem; }
        .timeline-item:last-child { padding-bottom: 0; }
        .timeline-dot {
            position: absolute; left: -2rem;
            width: 20px; height: 20px;
            border-radius: 50%;
            background: var(--border);
            border: 3px solid #fff;
            box-shadow: 0 0 0 2px var(--border);
            display: flex; align-items: center; justify-content: center;
            font-size: .6rem;
        }
        .timeline-item.latest .timeline-dot {
            background: var(--purple);
            box-shadow: 0 0 0 2px var(--purple);
        }
        .timeline-item.delivered .timeline-dot {
            background: var(--green);
            box-shadow: 0 0 0 2px var(--green);
        }
        .timeline-date { font-size: .75rem; color: var(--muted); margin-bottom: .2rem; }
        .timeline-desc { font-size: .88rem; font-weight: 500; }
        .timeline-loc { font-size: .78rem; color: var(--muted); margin-top: .1rem; }

        /* Loading */
        .loading-box {
            text-align: center; padding: 2rem;
            color: var(--muted); font-size: .9rem;
        }
        .spinner {
            width: 36px; height: 36px;
            border: 3px solid var(--border);
            border-top-color: var(--purple);
            border-radius: 50%;
            animation: spin .8s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* No resi yet */
        .no-resi-box {
            text-align: center; padding: 2rem;
            background: #fef3c7; border-radius: 10px;
            color: #92400e;
        }
        .no-resi-box .icon { font-size: 2rem; margin-bottom: .5rem; }
        .no-resi-box p { font-size: .88rem; }

        /* Produk mini */
        .produk-mini { display: flex; gap: 1rem; align-items: center; }
        .produk-mini img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; }
        .produk-mini .pname { font-weight: 600; font-size: .9rem; }
        .produk-mini .psub { font-size: .78rem; color: var(--muted); margin-top: .2rem; }

        .back-btn {
            display: inline-flex; align-items: center; gap: .4rem;
            color: var(--purple); font-size: .85rem; font-weight: 500;
            text-decoration: none; margin-bottom: 1rem;
        }
        .back-btn:hover { opacity: .75; }
    </style>
</head>
<body>

<nav>
    <div class="brand">Cloudy<span>Girls</span></div>
    <span style="font-size:.85rem;color:var(--muted);">Tracking Paket 📦</span>
</nav>

<div class="container">
    <a href="../pages/home.php" class="back-btn">← Kembali ke Beranda</a>

    <!-- Info Pesanan -->
    <div class="card">
        <div class="card-title">📋 Info Pesanan</div>
        <div class="produk-mini" style="margin-bottom:1rem;">
            <?php
                $foto = $pesanan['foto'] ?? '';
                $fotoSrc = $foto ? '../uploads/produk/' . $foto : 'https://via.placeholder.com/56x56?text=Foto';
            ?>
            <img src="<?= htmlspecialchars($fotoSrc) ?>" alt="foto">
            <div>
                <div class="pname"><?= htmlspecialchars($pesanan['nama_barang']) ?></div>
                <div class="psub">Kode: <?= htmlspecialchars($pesanan['kode_pesanan']) ?></div>
            </div>
        </div>
        <div class="info-grid">
            <div class="info-item">
                <label>Status Pesanan</label>
                <span>
                    <span class="badge badge-<?= $pesanan['status'] ?>">
                        <?= ucfirst($pesanan['status']) ?>
                    </span>
                </span>
            </div>
            <div class="info-item">
                <label>Metode</label>
                <span><?= strtoupper($pesanan['metode']) ?></span>
            </div>
            <div class="info-item">
                <label>Ekspedisi</label>
                <span><?= strtoupper($pesanan['ekspedisi'] ?? '-') ?></span>
            </div>
            <div class="info-item">
                <label>Total Bayar</label>
                <span>Rp <?= number_format($pesanan['total_bayar'], 0, ',', '.') ?></span>
            </div>
            <div class="info-item">
                <label>Alamat Tujuan</label>
                <span style="font-size:.82rem;"><?= htmlspecialchars($pesanan['kecamatan'] . ', ' . $pesanan['kota_tujuan'] . ', ' . $pesanan['provinsi']) ?></span>
            </div>
            <div class="info-item">
                <label>Tanggal Pesan</label>
                <span><?= date('d M Y', strtotime($pesanan['created_at'])) ?></span>
            </div>
        </div>
    </div>

    <!-- Tracking -->
    <div class="card">
        <div class="card-title">🚚 Tracking Pengiriman</div>

        <?php if (!$pesanan['no_resi']): ?>
        <div class="no-resi-box">
            <div class="icon">⏳</div>
            <p><strong>Nomor resi belum tersedia</strong></p>
            <p style="margin-top:.4rem;">Penjual sedang memproses pesananmu. Nomor resi akan muncul setelah paket dikirim.</p>
        </div>
        <?php else: ?>

        <!-- Resi Box -->
        <div class="resi-box">
            <div class="resi-info">
                <div class="resi-label">Nomor Resi</div>
                <div class="resi-no" id="noResi"><?= htmlspecialchars($pesanan['no_resi']) ?></div>
                <div class="resi-kurir"><?= htmlspecialchars($pesanan['kurir']) ?></div>
            </div>
            <button class="btn-copy" onclick="copyResi()">Salin</button>
        </div>

        <!-- Timeline -->
        <div id="trackingArea">
            <div class="loading-box">
                <div class="spinner"></div>
                Memuat data tracking...
            </div>
        </div>

        <?php endif; ?>
    </div>
</div>

<?php if ($pesanan['no_resi']): ?>
<script>
const NO_RESI = '<?= addslashes($pesanan['no_resi']) ?>';
const KURIR   = '<?= addslashes($pesanan['kurir']) ?>';

function copyResi() {
    navigator.clipboard.writeText(NO_RESI).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.textContent = 'Tersalin!';
        setTimeout(() => btn.textContent = 'Salin', 2000);
    });
}

async function loadTracking() {
    const area = document.getElementById('trackingArea');

    try {
        // Kirim POST ke cek_resi.php
        const formData = new FormData();
        formData.append('resi', NO_RESI);
        formData.append('kurir', KURIR);

        const res  = await fetch('../ajax/cek_resi.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();

        if (!data.success) {
            area.innerHTML = `
                <div class="no-resi-box">
                    <div class="icon">❌</div>
                    <p>${data.message || 'Data tracking tidak ditemukan'}</p>
                </div>`;
            return;
        }

        const d         = data.data;
        const history   = d.history || [];
        const isDelivered = d.status === 'DELIVERED';

        let html = '';

        // Status summary
        html += `
        <div style="margin-bottom:1.25rem;padding:.85rem 1rem;
            background:${isDelivered ? 'var(--green-light)' : 'var(--purple-light)'};
            border-radius:10px;">
            <div style="font-size:.78rem;color:var(--muted);margin-bottom:.2rem;">Status Terkini</div>
            <div style="font-weight:700;font-size:1rem;
                color:${isDelivered ? 'var(--green)' : 'var(--purple)'};">
                ${isDelivered ? '✅ TERKIRIM' : '🚚 ' + (d.status || '-')}
            </div>
            <div style="font-size:.78rem;color:var(--muted);margin-top:.2rem;">
                ${d.kurir} · ${d.service || '-'}
            </div>
            <div style="font-size:.78rem;color:var(--muted);margin-top:.1rem;">
                ${d.origin || '-'} → ${d.destination || '-'}
            </div>
        </div>`;

        // Timeline riwayat
        if (history.length > 0) {
            html += '<div class="timeline">';
            history.forEach((h, i) => {
                const isLatest       = i === 0;
                const isDeliveredItem = h.desc && h.desc.toLowerCase().includes('delivered');
                html += `
                <div class="timeline-item ${isLatest ? 'latest' : ''} ${isDeliveredItem ? 'delivered' : ''}">
                    <div class="timeline-dot">${isDeliveredItem ? '✓' : ''}</div>
                    <div class="timeline-date">${h.date || ''} ${h.time || ''}</div>
                    <div class="timeline-desc">${h.desc || '-'}</div>
                    ${h.location ? `<div class="timeline-loc">📍 ${h.location}</div>` : ''}
                </div>`;
            });
            html += '</div>';
        } else {
            html += `<p style="color:var(--muted);font-size:.88rem;">
                Belum ada data perjalanan paket.
            </p>`;
        }

        area.innerHTML = html;

    } catch(e) {
        area.innerHTML = `
            <div class="no-resi-box">
                <div class="icon">⚠️</div>
                <p>Gagal memuat tracking. Coba refresh halaman.</p>
            </div>`;
    }
}

// Otomatis load saat halaman dibuka
loadTracking();
</script>
<?php endif; ?>

</body>
</html>