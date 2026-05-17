<?php
header('Content-Type: application/json');

$api_key = '490283e20418abefb57d61aac39b1d4f7753f97d3f7dd3c3aba9c9c98bdcd7a5';

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => "https://api.binderbyte.com/wilayah/provinsi?api_key={$api_key}",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response, true);
echo json_encode($data['value'] ?? []);