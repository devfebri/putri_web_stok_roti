<?php
echo "Testing Fixed Stock Management\n";
echo "==============================\n\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=web_putri', 'root', '');
    
    // Login dan get token
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/proses_login_API');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => 'front1',
        'password' => 'password123'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "Login response: $response\n";
        throw new Exception("Login failed: HTTP $httpCode");
    }
    
    $loginData = json_decode($response, true);
    $token = $loginData['data']['token'];
    $userId = $loginData['data']['id'];
    
    echo "✓ Login successful (User ID: $userId)\n\n";
    
    // Check initial stock
    echo "Initial stock check:\n";
    $stmt = $pdo->prepare("
        SELECT r.nama_roti, sh.stok, sh.id as stock_id
        FROM rotis r
        JOIN stok_history sh ON r.id = sh.roti_id 
        WHERE sh.kepalatokokios_id = 4
        AND sh.id IN (
            SELECT MAX(id) 
            FROM stok_history 
            WHERE kepalatokokios_id = 4 
            GROUP BY roti_id
        )
        ORDER BY r.nama_roti
    ");
    $stmt->execute();
    $initialStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($initialStocks as $stock) {
        echo "- {$stock['nama_roti']}: {$stock['stok']} pcs\n";
    }
    echo "\n";
    
    // Create transaction
    echo "Creating transaction (Roti Tawar: 3 pcs):\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/frontliner/transaksi');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'user_id' => $userId,
        'nama_customer' => 'Test Customer',
        'metode_pembayaran' => 'Cash',
        'products' => [
            [
                'roti_id' => 1,
                'jumlah' => 3,
                'harga_satuan' => 5000
            ]
        ]
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 201) {
        echo "Transaction creation failed: HTTP $httpCode\n";
        echo "Response: $response\n";
        exit;
    }
    
    $transactionData = json_decode($response, true);
    $transactionId = $transactionData['data']['id'];
    echo "✓ Transaction created (ID: $transactionId)\n\n";
    
    // Check stock after transaction
    echo "Stock after transaction:\n";
    $stmt->execute();
    $afterStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($afterStocks as $stock) {
        echo "- {$stock['nama_roti']}: {$stock['stok']} pcs";
        
        // Find matching initial stock
        foreach ($initialStocks as $initial) {
            if ($initial['nama_roti'] === $stock['nama_roti']) {
                $diff = $stock['stok'] - $initial['stok'];
                if ($diff != 0) {
                    echo " (changed by $diff)";
                }
                break;
            }
        }
        echo "\n";
    }
    echo "\n";
    
    // Delete transaction
    echo "Deleting transaction to restore stock:\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/api/frontliner/transaksi/$transactionId");
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "Transaction deletion failed: HTTP $httpCode\n";
        echo "Response: $response\n";
        exit;
    }
    
    echo "✓ Transaction deleted\n\n";
    
    // Check final stock
    echo "Final stock (should be restored):\n";
    $stmt->execute();
    $finalStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($finalStocks as $stock) {
        echo "- {$stock['nama_roti']}: {$stock['stok']} pcs";
        
        // Find matching initial stock
        foreach ($initialStocks as $initial) {
            if ($initial['nama_roti'] === $stock['nama_roti']) {
                $diff = $stock['stok'] - $initial['stok'];
                if ($diff != 0) {
                    echo " (differs from initial by $diff)";
                } else {
                    echo " (✓ restored)";
                }
                break;
            }
        }
        echo "\n";
    }
    
    echo "\n✅ Stock management test completed!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
