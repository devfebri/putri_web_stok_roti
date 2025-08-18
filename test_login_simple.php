<?php
require_once 'vendor/autoload.php';

echo "Testing Login API\n";
echo "================\n\n";

$baseUrl = 'http://localhost:8000/api';

// Login dengan endpoint yang benar: /proses_login_API
echo "1. Testing login with correct endpoint...\n";
$loginData = [
    'username' => 'admin',
    'password' => 'admin123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/proses_login_API');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

if ($httpCode === 200) {
    $loginResult = json_decode($response, true);
    if (isset($loginResult['data']['token'])) {
        $token = $loginResult['data']['token'];
        echo "✅ Login successful! Token: " . substr($token, 0, 20) . "...\n\n";
        
        // Test protected endpoint
        echo "2. Testing protected endpoint with token...\n";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/frontliner/getproduk');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        echo "HTTP Code: $httpCode\n";
        echo "Response: $response\n\n";
        
        if ($httpCode === 200) {
            $products = json_decode($response, true);
            echo "✅ Products retrieved successfully!\n";
            echo "Product count: " . count($products['data']) . "\n";
            foreach ($products['data'] as $product) {
                echo "- {$product['nama']} (ID: {$product['id']}) - Stock: {$product['stok']}\n";
            }
        }
    } else {
        echo "❌ Login response doesn't contain token\n";
    }
} else {
    echo "❌ Login failed\n";
}
