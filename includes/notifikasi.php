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
?>
