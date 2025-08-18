<?php

echo "Testing User-Specific PO Filtering\n";
echo "==================================\n\n";

// Base URL
$baseUrl = 'http://127.0.0.1:8000';

// Test login first for RSBAITURRAHIM
echo "Testing login for RSBAITURRAHIM...\n";

$loginUrl = $baseUrl . '/api/proses_login_API';
$loginData = [
    'username' => 'rsbaiturrahim',
    'password' => 'password'
];

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => $loginUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($loginData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);

$loginResponse = curl_exec($curl);
$loginHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

if (curl_error($curl)) {
    echo "CURL Error: " . curl_error($curl) . "\n";
}

curl_close($curl);

echo "Login HTTP Code: $loginHttpCode\n";

if ($loginHttpCode === 200 && $loginResponse) {
    $loginData = json_decode($loginResponse, true);
    
    if (isset($loginData['data']['token'])) {
        $token = $loginData['data']['token'];
        echo "Login successful!\n";
        echo "Token: " . substr($token, 0, 30) . "...\n\n";
        
        // Test PO filtering
        echo "Testing PO filtering for RSBAITURRAHIM...\n";
        
        $rotipoUrl = $baseUrl . '/api/kepalatokokios/rotipo';
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ];
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $rotipoUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_error($curl)) {
            echo "CURL Error: " . curl_error($curl) . "\n";
        }
        
        curl_close($curl);
        
        echo "PO API HTTP Code: $httpCode\n";
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['data']) && is_array($data['data'])) {
                echo "Total POs returned: " . count($data['data']) . "\n";
                
                if (count($data['data']) > 0) {
                    echo "PO Details:\n";
                    foreach ($data['data'] as $po) {
                        echo "  - Kode PO: " . ($po['kode_po'] ?? 'N/A');
                        echo " | User: " . ($po['name'] ?? 'N/A');
                        echo " | Roti: " . ($po['nama_roti'] ?? 'N/A');
                        echo " | Status: " . ($po['status'] ?? 'N/A') . "\n";
                    }
                }
            } else {
                echo "No PO data found\n";
                echo "Raw response: " . substr($response, 0, 200) . "\n";
            }
        } else {
            echo "No response received\n";
        }
    } else {
        echo "Login failed - No token in response\n";
        echo "Response: " . substr($loginResponse, 0, 200) . "\n";
    }
} else {
    echo "Login failed\n";
    if ($loginResponse) {
        echo "Response: " . substr($loginResponse, 0, 200) . "\n";
    }
}

echo "\nTest completed!\n";

?>
