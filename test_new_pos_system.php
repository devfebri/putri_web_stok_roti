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

try {
    echo "=== TEST NEW POS SYSTEM ===\n\n";
    
    // 1. Test get all POs
    echo "1. Testing GET /api/pos\n";
    $result = testPosApi('/pos');
    echo "Status: {$result['status']}\n";
    if ($result['data']) {
        echo "Found " . count($result['data']['data'] ?? []) . " POs\n";
        if (isset($result['data']['data'][0])) {
            $firstPo = $result['data']['data'][0];
            echo "First PO: {$firstPo['kode_po']} - {$firstPo['deskripsi']}\n";
            echo "Roti items: " . count($firstPo['roti_pos'] ?? []) . "\n";
        }
    }
    echo "\n";
    
    // 2. Test create new PO
    echo "2. Testing POST /api/pos (Create new PO)\n";
    $newPoData = [
        'deskripsi' => 'Test PO from new system',
        'tanggal_order' => date('Y-m-d'),
        'user_id' => 1,
        'frontliner_id' => 2,
        'roti_items' => [
            ['roti_id' => 1, 'jumlah_po' => 10],
            ['roti_id' => 2, 'jumlah_po' => 15]
        ]
    ];
    
    $result = testPosApi('/pos', 'POST', $newPoData);
    echo "Status: {$result['status']}\n";
    if ($result['data']) {
        echo "Response: " . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
        $newPosId = $result['data']['data']['id'] ?? null;
    }
    echo "\n";
    
    // 3. Test get specific PO
    if (isset($newPosId)) {
        echo "3. Testing GET /api/pos/{$newPosId} (Get specific PO)\n";
        $result = testPosApi("/pos/{$newPosId}");
        echo "Status: {$result['status']}\n";
        if ($result['data']) {
            $po = $result['data']['data'];
            echo "PO: {$po['kode_po']} - {$po['deskripsi']}\n";
            echo "Items: " . count($po['roti_pos'] ?? []) . "\n";
        }
        echo "\n";
    }
    
    // 4. Test database consistency
    echo "4. Testing database consistency\n";
    $posCount = Capsule::table('pos')->count();
    $rotiPosCount = Capsule::table('roti_pos')->count();
    $orphanRotiPos = Capsule::table('roti_pos')
        ->leftJoin('pos', 'roti_pos.pos_id', '=', 'pos.id')
        ->whereNull('pos.id')
        ->count();
    
    echo "Total POs: {$posCount}\n";
    echo "Total Roti PO items: {$rotiPosCount}\n";
    echo "Orphan roti_pos (without valid pos_id): {$orphanRotiPos}\n";
    
    if ($orphanRotiPos == 0) {
        echo "✅ Database consistency: GOOD\n";
    } else {
        echo "❌ Database consistency: BAD - Found orphan records\n";
    }
    echo "\n";
    
    // 5. Test PO with relationships
    echo "5. Testing PO relationships\n";
    $posWithRelations = Capsule::table('pos')
        ->join('users as user', 'pos.user_id', '=', 'user.id')
        ->leftJoin('users as frontliner', 'pos.frontliner_id', '=', 'frontliner.id')
        ->leftJoin('roti_pos', 'pos.id', '=', 'roti_pos.pos_id')
        ->leftJoin('rotis', 'roti_pos.roti_id', '=', 'rotis.id')
        ->select(
            'pos.*',
            'user.name as user_name',
            'frontliner.name as frontliner_name',
            Capsule::raw('COUNT(roti_pos.id) as total_items'),
            Capsule::raw('SUM(roti_pos.jumlah_po) as total_quantity')
        )
        ->groupBy('pos.id')
        ->get();
    
    foreach ($posWithRelations as $po) {
        echo "PO {$po->kode_po}: User={$po->user_name}, Frontliner={$po->frontliner_name}, Items={$po->total_items}, Qty={$po->total_quantity}\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
