<?php

// Test script untuk memverifikasi frontend Flutter bisa akses endpoint POS baru

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

function testEndpoint($url, $method = 'GET', $data = null, $token = null) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => array_filter([
            'Content-Type: application/json',
            'Accept: application/json',
            $token ? "Authorization: Bearer $token" : null
        ]),
        CURLOPT_POSTFIELDS => $data ? json_encode($data) : null,
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    return [
        'status' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

function login($username, $password) {
    return testEndpoint('http://localhost:8000/api/proses_login_API', 'POST', [
        'username' => $username,
        'password' => $password
    ]);
}

try {
    echo "=== TESTING FLUTTER PO ENDPOINTS ===\n\n";
    
    // Test role yang berbeda
    $testUsers = [
        ['username' => 'rsarafah@gmail.com', 'password' => 'kepalatoko123', 'role' => 'kepalatokokios'],
        ['username' => 'farafah@gmail.com', 'password' => 'frontliner123', 'role' => 'frontliner'],
    ];
    
    foreach ($testUsers as $user) {
        echo "Testing user: {$user['username']} (role: {$user['role']})\n";
        
        // Login
        $loginResult = login($user['username'], $user['password']);
        if ($loginResult['status'] != 200) {
            echo "❌ Login failed for {$user['username']}\n";
            continue;
        }
        
        $token = $loginResult['data']['access_token'] ?? null;
        if (!$token) {
            echo "❌ No token received for {$user['username']}\n";
            continue;
        }
        
        echo "✅ Login success for {$user['username']}\n";
        
        // Test GET POS endpoint
        $role = $user['role'];
        $posUrl = "http://localhost:8000/api/{$role}/pos";
        echo "Testing: GET $posUrl\n";
        
        $posResult = testEndpoint($posUrl, 'GET', null, $token);
        echo "Status: {$posResult['status']}\n";
        
        if ($posResult['status'] == 200 && isset($posResult['data']['data'])) {
            echo "✅ Found " . count($posResult['data']['data']) . " POs\n";
            
            // Show first PO structure
            if (!empty($posResult['data']['data'])) {
                $firstPo = $posResult['data']['data'][0];
                echo "Sample PO structure:\n";
                echo "- ID: {$firstPo['id']}\n";
                echo "- Kode PO: {$firstPo['kode_po']}\n";
                echo "- Description: {$firstPo['deskripsi']}\n";
                echo "- Status: {$firstPo['status']}\n";
                echo "- Date: {$firstPo['tanggal_order']}\n";
                echo "- User: {$firstPo['user']['name']}\n";
                echo "- Frontliner: " . ($firstPo['frontliner']['name'] ?? 'None') . "\n";
                echo "- Roti Items: " . count($firstPo['roti_pos'] ?? []) . "\n";
                
                if (!empty($firstPo['roti_pos'])) {
                    echo "  Sample roti item:\n";
                    $rotiItem = $firstPo['roti_pos'][0];
                    echo "    - Roti: {$rotiItem['roti']['nama_roti']}\n";
                    echo "    - Quantity: {$rotiItem['jumlah_po']}\n";
                }
            }
        } else {
            echo "❌ Failed to get POS data\n";
            if (isset($posResult['data']['message'])) {
                echo "   Error: {$posResult['data']['message']}\n";
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
    }
    
    echo "=== ENDPOINT MAPPING FOR FLUTTER ===\n";
    echo "Flutter should use these endpoints:\n";
    echo "- kepalatokokios: /api/kepalatokokios/pos\n";
    echo "- frontliner: /api/frontliner/pos\n";
    echo "- admin: /api/admin/pos\n";
    echo "\nData structure returned:\n";
    echo "{\n";
    echo "  \"data\": [\n";
    echo "    {\n";
    echo "      \"id\": 1,\n";
    echo "      \"kode_po\": \"PO0001\",\n";
    echo "      \"deskripsi\": \"Description\",\n";
    echo "      \"tanggal_order\": \"2025-08-13\",\n";
    echo "      \"status\": 0,\n";
    echo "      \"user\": { \"name\": \"User Name\" },\n";
    echo "      \"frontliner\": { \"name\": \"Frontliner Name\" },\n";
    echo "      \"roti_pos\": [\n";
    echo "        {\n";
    echo "          \"id\": 1,\n";
    echo "          \"jumlah_po\": 10,\n";
    echo "          \"roti\": {\n";
    echo "            \"nama_roti\": \"Roti Name\",\n";
    echo "            \"rasa_roti\": \"Flavor\",\n";
    echo "            \"gambar_roti\": \"image.jpg\"\n";
    echo "          }\n";
    echo "        }\n";
    echo "      ]\n";
    echo "    }\n";
    echo "  ]\n";
    echo "}\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
