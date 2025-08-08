<?php

// Test script to check available stok data
echo "Testing available stok data...\n";
echo "Current date: " . date('Y-m-d') . "\n\n";

// Test dengan curl
$url = 'http://127.0.0.1:8000/api/admin/getavailablestok';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n\n";

if ($response) {
    $data = json_decode($response, true);
    if (isset($data['data']) && is_array($data['data'])) {
        echo "Available stok count: " . count($data['data']) . "\n";
        foreach ($data['data'] as $index => $stok) {
            echo "Stok " . ($index + 1) . ":\n";
            echo "  - ID: " . ($stok['id'] ?? 'N/A') . "\n";
            echo "  - Tampil: " . ($stok['tampil'] ?? 'N/A') . "\n";
            echo "  - Stok: " . ($stok['stok'] ?? 'N/A') . "\n";
            echo "  - Tanggal: " . ($stok['tanggal'] ?? 'N/A') . "\n\n";
        }
    } else {
        echo "No data found or invalid response format\n";
    }
} else {
    echo "Failed to get response\n";
}

echo "Test completed.\n";
