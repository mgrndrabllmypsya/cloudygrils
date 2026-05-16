<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
 
$api_key = '490283e20418abefb57d61aac39b1d4f7753f97d3f7dd3c3aba9c9c98bdcd7a5'; // Ganti dengan API key BinderByte kamu
 
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.binderbyte.com/wilayah/provinsi?api_key=$api_key",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
]);
$response = curl_exec($curl);
$error = curl_error($curl);
curl_close($curl);
 
if ($error) {
    echo json_encode(['error' => $error]);
    exit;
}
 
$data = json_decode($response, true);
echo json_encode($data['value'] ?? []);
 