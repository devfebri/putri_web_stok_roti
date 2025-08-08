<?php
echo "Testing New Waste System\n";
echo "========================\n\n";

// Test 1: Check available stok for waste
echo "1. Testing getAvailableStok API:\n";
$url = "http://127.0.0.1:8000/api/admin/getavailablestok";
$headers = [
    "Authorization: Bearer 1|EPH47V3RY7N4TJzWULO6UpP9lVxOwFh9KRNQ5uJF9c7d7ff9",
    "Accept: application/json"
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Check existing waste data
echo "2. Testing getWasteList API:\n";
$url = "http://127.0.0.1:8000/api/admin/waste";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: " . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "\n\n";

echo "Test completed!\n";
?>
