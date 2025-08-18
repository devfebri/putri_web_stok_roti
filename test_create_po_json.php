<?php

// Test pembuatan PO dengan format JSON dari Flutter

$baseUrl = 'http://127.0.0.1:8000';

echo "=== TEST CREATE PO WITH JSON FORMAT ===\n\n";

// Login first
$loginUrl = "$baseUrl/api/proses_login_API";

$loginData = [
    'username' => 'admin',
    'password' => 'password'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$data = json_decode($response, true);

if (!$data || !isset($data['data']['token'])) {
    echo "❌ Login failed\n";
    exit;
}

$token = $data['data']['token'];
echo "✅ Login Success! Token: " . substr($token, 0, 30) . "...\n\n";

// Create PO with JSON format (like Flutter sends)
$createPoUrl = "$baseUrl/api/admin/pos";

$poData = [
    'deskripsi' => 'Test PO dari Flutter format',
    'tanggal_order' => '2025-08-13',
    'frontliner_id' => 6, // frontliner ID
    'roti_items' => [
        [
            'roti_id' => 1,
            'jumlah_po' => 10
        ],
        [
            'roti_id' => 2,
            'jumlah_po' => 5
        ]
    ]
];

echo "📋 PO Data: " . json_encode($poData, JSON_PRETTY_PRINT) . "\n\n";

$headers = [
    'Authorization: Bearer ' . $token,
    'Content-Type: application/json',
    'Accept: application/json'
];

curl_setopt($ch, CURLOPT_URL, $createPoUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($poData));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "🔗 URL: $createPoUrl\n";
echo "📡 HTTP Code: $httpCode\n";
echo "📡 Response: $response\n\n";

if ($httpCode == 200 || $httpCode == 201) {
    $responseData = json_decode($response, true);
    if ($responseData && isset($responseData['kode_po'])) {
        echo "✅ Success! PO created with kode: " . $responseData['kode_po'] . "\n";
    } else {
        echo "⚠️ Response tidak mengandung kode_po\n";
    }
} else {
    echo "❌ Error creating PO: HTTP $httpCode\n";
}

curl_close($ch);

echo "\n=== END TEST ===\n";
