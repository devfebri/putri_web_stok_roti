<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'],
    'database' => $_ENV['DB_DATABASE'],
    'username' => $_ENV['DB_USERNAME'],
    'password' => $_ENV['DB_PASSWORD'],
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

// Test API endpoint for new POS structure
function testPosApi($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = "http://localhost:8000/api" . $endpoint;
    
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

// Login to get token
function getAuthToken($email, $password) {
    return testPosApi('/login', 'POST', [
        'email' => $email,
        'password' => $password
    ]);
}

try {
    echo "=== TEST NEW POS SYSTEM WITH AUTH ===\n\n";
    
    // 1. Login as admin
    echo "1. Getting admin auth token\n";
    $loginResult = getAuthToken('admin@putri.com', 'admin123');
    echo "Login Status: {$loginResult['status']}\n";
    
    $token = null;
    if ($loginResult['status'] == 200 && isset($loginResult['data']['access_token'])) {
        $token = $loginResult['data']['access_token'];
        echo "✅ Admin token obtained\n";
    } else {
        echo "❌ Failed to get admin token\n";
        echo "Response: " . json_encode($loginResult['data'], JSON_PRETTY_PRINT) . "\n";
    }
    echo "\n";
    
    if ($token) {
        // 2. Test get all POs as admin
        echo "2. Testing GET /api/admin/pos (with admin token)\n";
        $result = testPosApi('/admin/pos', 'GET', null, $token);
        echo "Status: {$result['status']}\n";
        if ($result['data']) {
            echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        }
        echo "\n";
        
        // 3. Test create new PO as admin
        echo "3. Testing POST /api/admin/pos (Create new PO as admin)\n";
        $newPoData = [
            'deskripsi' => 'Test PO from new relational system',
            'tanggal_order' => date('Y-m-d'),
            'roti_items' => [
                ['roti_id' => 1, 'jumlah_po' => 20],
                ['roti_id' => 2, 'jumlah_po' => 25]
            ]
        ];
        
        $result = testPosApi('/admin/pos', 'POST', $newPoData, $token);
        echo "Status: {$result['status']}\n";
        if ($result['data']) {
            echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
            $newPosId = $result['data']['data']['id'] ?? null;
        }
        echo "\n";
        
        // 4. Test get specific PO as admin
        if (isset($newPosId)) {
            echo "4. Testing GET /api/admin/pos/{$newPosId} (Get specific PO)\n";
            $result = testPosApi("/admin/pos/{$newPosId}", 'GET', null, $token);
            echo "Status: {$result['status']}\n";
            if ($result['data']) {
                echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
            }
            echo "\n";
        }
    }
    
    // 5. Test frontliner access
    echo "5. Testing frontliner access\n";
    $frontlinerLogin = getAuthToken('frontliner@putri.com', 'frontliner123');
    echo "Frontliner Login Status: {$frontlinerLogin['status']}\n";
    
    if ($frontlinerLogin['status'] == 200 && isset($frontlinerLogin['data']['access_token'])) {
        $frontlinerToken = $frontlinerLogin['data']['access_token'];
        echo "✅ Frontliner token obtained\n";
        
        // Test frontliner access to PO
        $result = testPosApi('/frontliner/pos', 'GET', null, $frontlinerToken);
        echo "Frontliner PO access status: {$result['status']}\n";
        if ($result['data']) {
            echo "Frontliner PO count: " . count($result['data']['data'] ?? []) . "\n";
        }
    } else {
        echo "❌ Failed to get frontliner token\n";
    }
    echo "\n";
    
    // 6. Database verification
    echo "6. Database verification after tests\n";
    $posCount = Capsule::table('pos')->count();
    $rotiPosCount = Capsule::table('roti_pos')->count();
    $latestPos = Capsule::table('pos')->orderBy('id', 'desc')->first();
    
    echo "Total POs in database: {$posCount}\n";
    echo "Total Roti PO items: {$rotiPosCount}\n";
    if ($latestPos) {
        echo "Latest PO: {$latestPos->kode_po} - {$latestPos->deskripsi}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
