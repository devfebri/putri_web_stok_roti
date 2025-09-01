<?php

// Test API Penjualan dengan POST method

require 'vendor/autoload.php';

// Konfigurasi
$baseUrl = 'http://localhost:8000/api';
$token = 'YOUR_TOKEN_HERE'; // Ganti dengan token yang valid

// Test data
$testData = [
    'periode' => 'custom',
    'tanggal_mulai' => '2024-01-01',
    'tanggal_selesai' => '2024-12-31'
];

// Test untuk setiap role
$roles = ['admin', 'frontliner', 'kepalatokokios'];

foreach ($roles as $role) {
    echo "=== Testing $role ===\n";
    
    $url = "$baseUrl/$role/laporan/penjualan";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "URL: $url\n";
    echo "HTTP Code: $httpCode\n";
    
    if ($error) {
        echo "CURL Error: $error\n";
    } else {
        echo "Response: " . substr($response, 0, 200) . "...\n";
        
        if ($httpCode == 200) {
            $data = json_decode($response, true);
            if (isset($data['data']['summary'])) {
                echo "Success! Summary found.\n";
            } else {
                echo "Response parsed but no summary data.\n";
            }
        }
    }
    
    echo "\n";
}

echo "Testing completed!\n";
echo "Note: Update the token variable with a valid token for proper testing.\n";

?>
