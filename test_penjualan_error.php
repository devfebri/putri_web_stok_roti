<?php
// Test penjualan API endpoint to debug 500 error
$baseUrl = 'http://localhost:8000/api';

echo "=== TESTING PENJUALAN API ENDPOINTS ===\n\n";

// Test different role-based endpoints
$roles = ['admin', 'pimpinan', 'kepalabakery', 'kepalatokokios', 'frontliner'];

foreach ($roles as $role) {
    echo "Testing role: $role\n";
    
    $data = [
        'periode' => 'harian',
        'tanggal_mulai' => '2025-09-01',
        'tanggal_selesai' => '2025-09-01'
    ];
    
    $url = "$baseUrl/$role/laporan/penjualan";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "  URL: $url\n";
    echo "  HTTP Code: $httpCode\n";
    if ($httpCode !== 200) {
        echo "  Error Response: " . substr($response, 0, 200) . "...\n";
    } else {
        echo "  Success!\n";
    }
    echo "\n";
}

// Test direct endpoint tanpa role
echo "Testing direct endpoint (no role prefix):\n";
$url = "$baseUrl/penjualan-report";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'periode' => 'harian',
    'tanggal_mulai' => '2025-09-01',
    'tanggal_selesai' => '2025-09-01'
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "  URL: $url\n";
echo "  HTTP Code: $httpCode\n";
echo "  Response: " . substr($response, 0, 500) . "\n";
