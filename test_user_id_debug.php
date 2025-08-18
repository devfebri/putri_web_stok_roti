<?php

echo "DEBUG: Testing User ID Detection\n";
echo "================================\n\n";

// Base URL
$baseUrl = 'http://127.0.0.1:8000';

// Test dengan RSBAITURRAHIM
echo "1. Testing with RSBAITURRAHIM...\n";

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
$loginData = json_decode($loginResponse, true);
curl_close($curl);

if (isset($loginData['data']['token'])) {
    $token = $loginData['data']['token'];
    echo "Login successful for RSBAITURRAHIM\n";
    echo "Token: " . substr($token, 0, 20) . "...\n\n";
    
    // Test kepalatokokios route
    echo "Testing /api/kepalatokokios/rotipo with debug...\n";
    
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
    curl_close($curl);
    
    echo "API Response Code: $httpCode\n";
    
    if ($response) {
        $data = json_decode($response, true);
        
        if (isset($data['debug'])) {
            echo "DEBUG Information:\n";
            echo "- User ID: " . ($data['debug']['user_id'] ?? 'N/A') . "\n";
            echo "- User Role: " . ($data['debug']['user_role'] ?? 'N/A') . "\n";
            echo "- User Name: " . ($data['debug']['user_name'] ?? 'N/A') . "\n";
            echo "- Total Records: " . ($data['debug']['total_records'] ?? 'N/A') . "\n";
            echo "- Filter Applied: " . ($data['debug']['filter_applied'] ?? 'N/A') . "\n";
        }
        
        if (isset($data['data'])) {
            echo "Total POs returned: " . count($data['data']) . "\n";
            
            if (count($data['data']) > 0) {
                echo "Sample PO data:\n";
                $firstPo = $data['data'][0];
                echo "- Kode PO: " . ($firstPo['kode_po'] ?? 'N/A') . "\n";
                echo "- User ID in PO: " . ($firstPo['user_id'] ?? 'N/A') . "\n";
                echo "- User Name in PO: " . ($firstPo['name'] ?? 'N/A') . "\n";
            }
        }
        
        if (isset($data['message'])) {
            echo "Message: " . $data['message'] . "\n";
        }
    } else {
        echo "No response received\n";
    }
} else {
    echo "Login failed\n";
}

echo "\nDebug test completed!\n";

?>
