<?php
header('Content-Type: application/json');

$api_key     = '490283e20418abefb57d61aac39b1d4f7753f97d3f7dd3c3aba9c9c98bdcd7a5';
$origin      = 'dist_35.10.16'; // Kecamatan Banyuwangi
$destination = $_GET['destination'] ?? '';
$weight      = 1; // dalam KG

if (!$destination) {
    echo json_encode(['jnt' => null, 'jne' => null]);
    exit;
}

// Tamb~ah prefix dist_ jika belum ada
if (!str_starts_with($destination, 'dist_') && !str_starts_with($destination, 'village_')) {
    $destination = 'dist_' . $destination;
}

function getOngkir($api_key, $courier, $origin, $destination, $weight) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://api.binderbyte.com/v1/cost",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'api_key'     => $api_key,
            'courier'     => $courier,
            'origin'      => $origin,
            'destination' => $destination,
            'weight'      => $weight,
        ]),
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

$result = ['jnt' => null, 'jne' => null, 'jnt_etd' => null, 'jne_etd' => null];

// JNT
$jnt = getOngkir($api_key, 'jnt', $origin, $destination, $weight);
if (!empty($jnt['data']['results'][0]['costs'])) {
    $result['jnt']     = $jnt['data']['results'][0]['costs'][0]['price'];
    $result['jnt_etd'] = $jnt['data']['results'][0]['costs'][0]['estimated'] ?? '';
}

// JNE
$jne = getOngkir($api_key, 'jne', $origin, $destination, $weight);
if (!empty($jne['data']['results'][0]['costs'])) {
    foreach ($jne['data']['results'][0]['costs'] as $cost) {
        if (strtoupper($cost['service']) === 'REG') {
            $result['jne']     = $cost['price'];
            $result['jne_etd'] = $cost['estimated'] ?? '';
            break;
        }
    }
    if (!$result['jne']) {
        $result['jne']     = $jne['data']['results'][0]['costs'][0]['price'];
        $result['jne_etd'] = $jne['data']['results'][0]['costs'][0]['estimated'] ?? '';
    }
}

echo json_encode($result);