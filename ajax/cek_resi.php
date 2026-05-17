<?php
// =============================================
// cek_resi.php — Tracking paket via BinderByte
// =============================================

header('Content-Type: application/json');

$api_key = '490283e20418abefb57d61aac39b1d4f7753f97d3f7dd3c3aba9c9c98bdcd7a5'; // Ganti dengan API key dari binderbyte.com

$resi   = isset($_POST['resi'])  ? trim($_POST['resi'])  : '';
$kurir  = isset($_POST['kurir']) ? strtolower(trim($_POST['kurir'])) : 'jnt';

if (empty($resi)) {
    echo json_encode([
        'success' => false,
        'message' => 'Nomor resi tidak boleh kosong.'
    ]);
    exit;
}

// Mapping nama kurir ke kode BinderByte
$kurir_map = [
    'jnt'     => 'jnt',
    'j&t'     => 'jnt',
    'jne'     => 'jne',
    'sicepat' => 'sicepat',
    'anteraja'=> 'anteraja',
    'pos'     => 'pos',
    'tiki'    => 'tiki',
    'wahana'  => 'wahana',
    'ninja'   => 'ninja',
    'idexpress' => 'idexpress',
];

$kurir_code = isset($kurir_map[$kurir]) ? $kurir_map[$kurir] : $kurir;

// Hit BinderByte API
$url = "https://api.binderbyte.com/v1/track"
     . "?api_key=" . urlencode($api_key)
     . "&courier=" . urlencode($kurir_code)
     . "&awb="     . urlencode($resi);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$err      = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghubungi server tracking: ' . $err
    ]);
    exit;
}

$data = json_decode($response, true);

// BinderByte mengembalikan status 200 jika sukses
if (!$data || $data['status'] != 200) {
    $msg = isset($data['message']) ? $data['message'] : 'Resi tidak ditemukan.';
    echo json_encode([
        'success' => false,
        'message' => $msg
    ]);
    exit;
}

// Format ulang response agar rapi untuk frontend
$summary  = $data['data']['summary']  ?? [];
$detail   = $data['data']['detail']   ?? [];
$history  = $data['data']['history']  ?? [];

echo json_encode([
    'success' => true,
    'data'    => [
        'resi'        => $resi,
        'kurir'       => strtoupper($kurir_code),
        'pengirim'    => $detail['shipper']    ?? '-',
        'penerima'    => $detail['receiver']   ?? '-',
        'origin'      => $detail['origin']     ?? '-',
        'destination' => $detail['destination'] ?? '-',
        'service'     => $detail['service']    ?? '-',
        'status'      => $summary['status']    ?? '-',
        'desc'        => $summary['desc']      ?? '-',
        'date'        => $summary['date']      ?? '-',
        'history'     => $history,
    ]
]);