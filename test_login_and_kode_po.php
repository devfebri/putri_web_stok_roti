<?php

// Test login untuk mendapatkan token baru

$baseUrl = 'http://127.0.0.1:8000';

echo "=== TEST LOGIN ===\n\n";

// Login
$loginUrl = "$baseUrl/api/proses_login_API";

$loginData = [
    'username' => 'admin',
    'password' => 'password'
];

echo "🔗 Login URL: $loginUrl\n";
echo "📋 Login Data: " . json_encode($loginData) . "\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
    exit;
}

echo "📡 HTTP Code: $httpCode\n";
echo "📡 Response: $response\n\n";

if ($httpCode == 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['data']['token'])) {
        $token = $data['data']['token'];
        echo "✅ Login Success!\n";
        echo "🔑 Token: " . substr($token, 0, 50) . "...\n\n";
        
        // Test get next kode PO
        echo "=== TEST GET NEXT KODE PO ===\n\n";
        
        $nextKodeUrl = "$baseUrl/api/admin/getnextkodepo";
        
        $headers = [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ];
        
        echo "🔗 URL: $nextKodeUrl\n";
        
        curl_setopt($ch, CURLOPT_URL, $nextKodeUrl);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response2 = curl_exec($ch);
        $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        echo "📡 HTTP Code: $httpCode2\n";
        echo "📡 Response: $response2\n\n";
        
        if ($httpCode2 == 200) {
            $data2 = json_decode($response2, true);
            if ($data2 && isset($data2['kode_po'])) {
                echo "✅ Success! Next Kode PO: " . $data2['kode_po'] . "\n";
            } else {
                echo "⚠️ Response tidak mengandung kode_po\n";
            }
        } else {
            echo "❌ Error getting next kode PO: HTTP $httpCode2\n";
        }
        
    } else {
        echo "❌ Login failed: No access token in response\n";
    }
} else {
    echo "❌ Login failed: HTTP $httpCode\n";
}

curl_close($ch);

echo "\n=== END TEST ===\n";
