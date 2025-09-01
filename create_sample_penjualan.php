<?php
// Create sample data for testing
$host = 'localhost';
$dbname = 'web_putri';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== CHECKING EXISTING DATA ===\n\n";
    
    // Check users
    $users = $pdo->query("SELECT id, name, role FROM users LIMIT 3")->fetchAll(PDO::FETCH_OBJ);
    echo "Users: " . count($users) . "\n";
    foreach ($users as $user) {
        echo "  - ID: {$user->id}, Name: {$user->name}, Role: {$user->role}\n";
    }
    
    // Check rotis
    $rotis = $pdo->query("SELECT id, nama_roti, rasa_roti, harga_roti FROM rotis LIMIT 5")->fetchAll(PDO::FETCH_OBJ);
    echo "\nRotis: " . count($rotis) . "\n";
    foreach ($rotis as $roti) {
        echo "  - ID: {$roti->id}, Name: {$roti->nama_roti}, Rasa: {$roti->rasa_roti}, Harga: {$roti->harga_roti}\n";
    }
    
    // Check transaksi
    $transaksi = $pdo->query("SELECT id, kode_transaksi, nama_customer, total_harga FROM transaksi LIMIT 5")->fetchAll(PDO::FETCH_OBJ);
    echo "\nTransaksi: " . count($transaksi) . "\n";
    foreach ($transaksi as $t) {
        echo "  - ID: {$t->id}, Code: {$t->kode_transaksi}, Customer: {$t->nama_customer}, Total: {$t->total_harga}\n";
    }
    
    // Check transaksi_roti
    $transaksiRoti = $pdo->query("SELECT id, transaksi_id, roti_id, jumlah, harga_satuan FROM transaksi_roti LIMIT 5")->fetchAll(PDO::FETCH_OBJ);
    echo "\nTransaksi Roti: " . count($transaksiRoti) . "\n";
    foreach ($transaksiRoti as $tr) {
        echo "  - ID: {$tr->id}, Transaksi: {$tr->transaksi_id}, Roti: {$tr->roti_id}, Jumlah: {$tr->jumlah}, Harga: {$tr->harga_satuan}\n";
    }
    
    // If no data, create sample
    if (count($transaksi) === 0 && count($users) > 0 && count($rotis) > 0) {
        echo "\n=== CREATING SAMPLE DATA ===\n";
        
        $userId = $users[0]->id;
        $today = date('Y-m-d H:i:s');
        
        // Create transaksi
        $sql = "INSERT INTO transaksi (user_id, kode_transaksi, nama_customer, total_harga, metode_pembayaran, tanggal_transaksi, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        // Transaksi 1
        $stmt->execute([$userId, 'TRX00000001', 'Customer A', 45000, 'Cash', $today, $today, $today]);
        $transaksiId1 = $pdo->lastInsertId();
        
        // Transaksi 2
        $stmt->execute([$userId, 'TRX00000002', 'Customer B', 30000, 'Transfer', $today, $today, $today]);
        $transaksiId2 = $pdo->lastInsertId();
        
        // Create transaksi_roti items
        $sql = "INSERT INTO transaksi_roti (transaksi_id, user_id, roti_id, jumlah, harga_satuan, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        // Items for transaksi 1
        if (count($rotis) >= 2) {
            $stmt->execute([$transaksiId1, $userId, $rotis[0]->id, 2, 15000, $today, $today]);
            $stmt->execute([$transaksiId1, $userId, $rotis[1]->id, 1, 15000, $today, $today]);
        }
        
        // Items for transaksi 2
        if (count($rotis) >= 1) {
            $stmt->execute([$transaksiId2, $userId, $rotis[0]->id, 2, 15000, $today, $today]);
        }
        
        echo "Sample data created!\n";
        echo "Transaksi 1 ID: $transaksiId1\n";
        echo "Transaksi 2 ID: $transaksiId2\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
