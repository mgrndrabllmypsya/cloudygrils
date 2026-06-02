<?php

function kirimNotifikasiPembeli($conn, $pembeli_id, $judul, $pesan, $tipe = 'pesanan', $referensi_id = null) {
    $judul       = mysqli_real_escape_string($conn, $judul);
    $pesan       = mysqli_real_escape_string($conn, $pesan);
    $tipe        = mysqli_real_escape_string($conn, $tipe);
    $ref         = $referensi_id ? (int)$referensi_id : 'NULL';
    $pembeli_id  = (int)$pembeli_id;

    mysqli_query($conn, "INSERT INTO notifikasi 
        (penerima, pembeli_id, judul, pesan, tipe, referensi_id, is_read, created_at)
        VALUES ('pembeli', $pembeli_id, '$judul', '$pesan', '$tipe', $ref, 0, NOW())");
}

function kirimNotifikasiAdmin($conn, $judul, $pesan, $tipe = 'pesanan', $referensi_id = null) {
    $judul = mysqli_real_escape_string($conn, $judul);
    $pesan = mysqli_real_escape_string($conn, $pesan);
    $tipe  = mysqli_real_escape_string($conn, $tipe);
    $ref   = $referensi_id ? (int)$referensi_id : 'NULL';

    mysqli_query($conn, "INSERT INTO notifikasi 
        (penerima, pembeli_id, judul, pesan, tipe, referensi_id, is_read, created_at)
        VALUES ('admin', NULL, '$judul', '$pesan', '$tipe', $ref, 0, NOW())");
}

function getNotifikasiPembeli($conn, $pembeli_id) {
    $pembeli_id = (int)$pembeli_id;
    $result = mysqli_query($conn, "SELECT * FROM notifikasi 
        WHERE penerima = 'pembeli' AND pembeli_id = $pembeli_id 
        ORDER BY created_at DESC");
    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}

function countUnreadPembeli($conn, $pembeli_id) {
    $pembeli_id = (int)$pembeli_id;
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifikasi 
        WHERE penerima = 'pembeli' AND pembeli_id = $pembeli_id AND is_read = 0");
    return mysqli_fetch_assoc($result)['total'];
}

function countUnreadAdmin($conn) {
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM notifikasi 
        WHERE penerima = 'admin' AND is_read = 0");
    return mysqli_fetch_assoc($result)['total'];
}

function tandaiDibacaPembeli($conn, $pembeli_id) {
    $pembeli_id = (int)$pembeli_id;
    mysqli_query($conn, "UPDATE notifikasi SET is_read = 1 
        WHERE penerima = 'pembeli' AND pembeli_id = $pembeli_id");
}

function tandaiDibacaAdmin($conn) {
    mysqli_query($conn, "UPDATE notifikasi SET is_read = 1 WHERE penerima = 'admin'");
}

// ── Helper: kirim notifikasi hasil nego dari penjual ke pembeli ──────────────

function kirimNotifikasiNegoDisetujui($conn, $pembeli_id, $nego_id, $nama_produk, $harga_asli, $harga_deal) {
    $harga_asli_fmt = 'Rp' . number_format($harga_asli, 0, ',', '.');
    $harga_deal_fmt = 'Rp' . number_format($harga_deal, 0, ',', '.');
    kirimNotifikasiPembeli(
        $conn,
        $pembeli_id,
        "Penawaran Kamu Disetujui!",
        "Selamat! Penjual menyetujui penawaran kamu untuk produk \"{$nama_produk}\". Harga deal: {$harga_deal_fmt} (dari {$harga_asli_fmt}). Segera lanjutkan ke pembayaran.",
        'nego',
        $nego_id
    );
}

function kirimNotifikasiNegoDitolak($conn, $pembeli_id, $nego_id, $nama_produk, $harga_tawar, $pesan_penjual = '') {
    $harga_fmt = 'Rp' . number_format($harga_tawar, 0, ',', '.');
    $info_pesan = $pesan_penjual ? " Pesan penjual: \"{$pesan_penjual}\"." : '';
    kirimNotifikasiPembeli(
        $conn,
        $pembeli_id,
        "Penawaran Kamu Ditolak",
        "Penawaran {$harga_fmt} untuk produk \"{$nama_produk}\" tidak disetujui penjual.{$info_pesan}",
        'nego',
        $nego_id
    );
}

function kirimNotifikasiNegoCounter($conn, $pembeli_id, $nego_id, $nama_produk, $harga_asli, $harga_tawar, $harga_counter, $pesan_penjual = '') {
    $harga_asli_fmt    = 'Rp' . number_format($harga_asli,    0, ',', '.');
    $harga_tawar_fmt   = 'Rp' . number_format($harga_tawar,   0, ',', '.');
    $harga_counter_fmt = 'Rp' . number_format($harga_counter, 0, ',', '.');
    $info_pesan = $pesan_penjual ? " Pesan penjual: \"{$pesan_penjual}\"." : '';
    kirimNotifikasiPembeli(
        $conn,
        $pembeli_id,
        "Penjual Memberi Penawaran Balik",
        "Untuk produk \"{$nama_produk}\", penawaranmu {$harga_tawar_fmt} dibalas dengan harga {$harga_counter_fmt} (harga awal {$harga_asli_fmt}).{$info_pesan} Setuju atau tolak penawaran ini.",
        'nego',
        $nego_id
    );
}

// ── Helper: kirim notifikasi update status pesanan ke pembeli ────────────────

function kirimNotifikasiStatusPesanan($conn, $pembeli_id, $pesanan_id, $kode_pesanan, $status_baru) {
    $pesan_map = [
        'diproses'        => ["Pesanan Sedang Diproses",      "Pesanan #{$kode_pesanan} sedang diproses oleh penjual."],
        'dikemas'         => ["Pesanan Sedang Dikemas",        "Pesanan #{$kode_pesanan} sedang dikemas dan siap dikirim."],
        'dikirim'         => ["Pesanan Dalam Pengiriman",      "Pesanan #{$kode_pesanan} sudah dikirim. Pantau resi pengirimanmu."],
        'tiba'            => ["Pesanan Telah Tiba!",           "Pesanan #{$kode_pesanan} sudah tiba di tujuan. Jangan lupa konfirmasi penerimaan."],
        'selesai'         => ["Pesanan Selesai",               "Pesanan #{$kode_pesanan} telah selesai. Terima kasih sudah berbelanja!"],
        'dibatalkan'      => ["Pesanan Dibatalkan",            "Pesanan #{$kode_pesanan} telah dibatalkan."],
    ];

    if (!isset($pesan_map[$status_baru])) return;

    [$judul, $pesan] = $pesan_map[$status_baru];
    kirimNotifikasiPembeli($conn, $pembeli_id, $judul, $pesan, 'pesanan', $pesanan_id);
}