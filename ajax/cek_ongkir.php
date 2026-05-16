<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$api_key     = '490283e20418abefb57d61aac39b1d4f7753f97d3f7dd3c3aba9c9c98bdcd7a5';
$origin      = 'banyuwangi';
$destination = $_GET['destination'] ?? '';
$weight      = 1000;

function getOngkir($api_key, $courier, $origin, $destination, $weight) {
    $url = "https://api.binderbyte.com/v1/cost?" . http_build_query([
        'api_key'     => $api_key,
        'courier'     => $courier,
        'origin'      => $origin,
        'destination' => $destination,
        'weight'      => $weight,
    ]);
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}

$result = ['jnt' => null, 'jne' => null, 'jnt_etd' => null, 'jne_etd' => null];

// Cek JNT
$jnt = getOngkir($api_key, 'jnt', $origin, $destination, $weight);
// Coba berbagai path response
$jntCost = $jnt['data']['results'][0]['costs'][0]['cost'] 
        ?? $jnt['data']['costs'][0]['cost'] 
        ?? null;
$jntEtd  = $jnt['data']['results'][0]['costs'][0]['etd'] 
        ?? $jnt['data']['costs'][0]['etd'] 
        ?? null;
if ($jntCost !== null) {
    $result['jnt']     = $jntCost;
    $result['jnt_etd'] = $jntEtd ? $jntEtd . ' hari' : null;
}

// Cek JNE
$jne = getOngkir($api_key, 'jne', $origin, $destination, $weight);
$jneCost = $jne['data']['results'][0]['costs'][0]['cost'] 
        ?? $jne['data']['costs'][0]['cost'] 
        ?? null;
$jneEtd  = $jne['data']['results'][0]['costs'][0]['etd'] 
        ?? $jne['data']['costs'][0]['etd'] 
        ?? null;
if ($jneCost !== null) {
    $result['jne']     = $jneCost;
    $result['jne_etd'] = $jneEtd ? $jneEtd . ' hari' : null;
}

echo json_encode($result);