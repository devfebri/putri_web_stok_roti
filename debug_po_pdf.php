<?php

// Debug Purchase Order PDF functionality

echo "=== DEBUGGING PURCHASE ORDER PDF ===\n\n";

// Check if required database tables exist
try {
    $pdo = new PDO('mysql:host=localhost;dbname=pos_system', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "1. Database connection: OK\n";
    
    // Check pos table
    $posCount = $pdo->query("SELECT COUNT(*) FROM pos")->fetchColumn();
    echo "2. POS table records: $posCount\n";
    
    // Check roti_pos table
    $rotiPosCount = $pdo->query("SELECT COUNT(*) FROM roti_pos")->fetchColumn();
    echo "3. ROTI_POS table records: $rotiPosCount\n";
    
    // Check users table
    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "4. USERS table records: $usersCount\n";
    
    // Check rotis table
    $rotisCount = $pdo->query("SELECT COUNT(*) FROM rotis")->fetchColumn();
    echo "5. ROTIS table records: $rotisCount\n";
    
    // Check sample PO data
    $samplePo = $pdo->query("
        SELECT pos.kode_po, pos.tanggal_order, roti_pos.jumlah_po, rotis.nama_roti 
        FROM pos 
        JOIN roti_pos ON roti_pos.pos_id = pos.id 
        JOIN rotis ON rotis.id = roti_pos.roti_id 
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "6. Sample PO data:\n";
    foreach ($samplePo as $po) {
        echo "   - {$po['kode_po']} | {$po['tanggal_order']} | {$po['jumlah_po']}x {$po['nama_roti']}\n";
    }
    
} catch (Exception $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}

echo "\n7. Testing Laravel routes...\n";

// Check if Laravel can handle the request
$urls = [
    'http://127.0.0.1:8000/api/login',
    'http://127.0.0.1:8000/api/admin/laporan/purchase-order?periode=harian',
    'http://127.0.0.1:8000/api/admin/laporan/purchase-order/pdf?periode=harian'
];

foreach ($urls as $url) {
    echo "Testing: $url\n";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 5,
            'ignore_errors' => true
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    if ($result !== false) {
        $httpCode = 200;
        if (isset($http_response_header[0])) {
            preg_match('/\d{3}/', $http_response_header[0], $matches);
            $httpCode = $matches[0] ?? 200;
        }
        echo "  Status: $httpCode | Length: " . strlen($result) . " bytes\n";
    } else {
        echo "  Status: ERROR | Failed to connect\n";
    }
}

echo "\n=== DEBUG COMPLETED ===\n";
