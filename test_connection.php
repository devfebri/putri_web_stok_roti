<?php
$baseUrl = 'http://localhost:8000/api';

echo "Testing server connection...\n";

// Test basic connection
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/rotis');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Error: $error\n";
echo "Response: $response\n";
