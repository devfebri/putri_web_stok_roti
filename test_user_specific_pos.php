<?php

echo "Testing User-Specific PO Filtering\n";
echo "==================================\n\n";

// Function to test API
function testUserSpecificPOs($baseUrl, $authToken, $userName) {
    echo "Testing PO filtering for user: $userName\n";
    
    $url = $baseUrl . '/api/rotipo';
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $authToken
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "HTTP Status: $httpCode\n";
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['data']) && is_array($data['data'])) {
            echo "PO Count: " . count($data['data']) . "\n";
            
            // Group by user to show filtering
            $posByUser = [];
            foreach ($data['data'] as $po) {
                $user = $po['name'] ?? 'Unknown';
                if (!isset($posByUser[$user])) {
                    $posByUser[$user] = [];
                }
                $posByUser[$user][] = $po['kode_po'] ?? 'No Code';
            }
            
            echo "POs by User:\n";
            foreach ($posByUser as $user => $pos) {
                echo "  $user: " . implode(', ', $pos) . "\n";
            }
        } else {
            echo "No PO data found or invalid response format\n";
        }
    } else {
        echo "No response received\n";
    }
    
    echo "\n" . str_repeat("-", 50) . "\n\n";
}

// Base URL
$baseUrl = 'http://localhost:8000';

// Test different users
$testUsers = [
    [
        'name' => 'RSBAITURRAHIM',
        'email' => 'baithurrahimhit@gmail.com',
        'password' => '123456789'
    ],
    [
        'name' => 'RSARAFAH', 
        'email' => 'arafah@gmail.com',
        'password' => '123456789'
    ]
];

foreach ($testUsers as $user) {
    // Login to get token
    echo "Logging in as: " . $user['name'] . "\n";
    
    $loginUrl = $baseUrl . '/api/login';
    $loginData = [
        'email' => $user['email'],
        'password' => $user['password']
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
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $loginResponse = curl_exec($curl);
    $loginHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($loginHttpCode === 200 && $loginResponse) {
        $loginData = json_decode($loginResponse, true);
        if (isset($loginData['data']['access_token'])) {
            $token = $loginData['data']['access_token'];
            echo "Login successful - Token: " . substr($token, 0, 20) . "...\n\n";
            
            // Test PO filtering
            testUserSpecificPOs($baseUrl, $token, $user['name']);
        } else {
            echo "Login failed - No token in response\n\n";
        }
    } else {
        echo "Login failed - HTTP Code: $loginHttpCode\n\n";
    }
}

echo "Testing completed!\n";
?>
