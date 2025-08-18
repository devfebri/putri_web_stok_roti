<?php
echo "Debugging Stock Issue\n";
echo "====================\n\n";

try {
    // Database connection
    $pdo = new PDO('mysql:host=localhost;dbname=web_putri', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['front1']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "User data:\n";
    echo "- ID: " . $user['id'] . "\n";
    echo "- Name: " . $user['name'] . "\n";
    echo "- kepalatokokios_id: " . ($user['kepalatokokios_id'] ?? 'null') . "\n";
    echo "- Role: " . $user['role'] . "\n\n";
    
    $kepalatokokios_id = $user['kepalatokokios_id'];
    
    // Check all stok_history data for this kepala toko kios
    echo "All Stok History for kepalatokokios_id = {$kepalatokokios_id}:\n";
    $stmt = $pdo->prepare("
        SELECT sh.*, r.nama_roti 
        FROM stok_history sh
        JOIN rotis r ON r.id = sh.roti_id
        WHERE sh.kepalatokokios_id = ?
        ORDER BY sh.roti_id, sh.id
    ");
    $stmt->execute([$kepalatokokios_id]);
    $stokHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($stokHistory as $stok) {
        echo "- {$stok['nama_roti']}: {$stok['stok']} pcs (ID: {$stok['id']}, Roti ID: {$stok['roti_id']}, Date: {$stok['created_at']})\n";
    }
    echo "\n";
    
    // Get latest stock for each product
    echo "Latest Stock for each product:\n";
    $stmt = $pdo->prepare("
        SELECT sh.*, r.nama_roti
        FROM stok_history sh
        JOIN rotis r ON r.id = sh.roti_id
        WHERE sh.kepalatokokios_id = ? 
        AND sh.id IN (
            SELECT MAX(id) 
            FROM stok_history 
            WHERE kepalatokokios_id = ? 
            GROUP BY roti_id
        )
        ORDER BY r.nama_roti
    ");
    $stmt->execute([$kepalatokokios_id, $kepalatokokios_id]);
    $latestStocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($latestStocks as $stock) {
        echo "- {$stock['nama_roti']}: {$stock['stok']} pcs (Latest ID: {$stock['id']})\n";
    }
    echo "\n";
    
    // Test the exact query from API
    echo "Products from API query (same as TransaksiController):\n";
    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.nama_roti as nama,
            r.rasa_roti, 
            r.harga_roti as harga,
            r.gambar_roti,
            COALESCE(sh.stok, 0) as stok
        FROM rotis r
        LEFT JOIN stok_history sh ON r.id = sh.roti_id 
            AND sh.kepalatokokios_id = ?
            AND sh.id IN (
                SELECT MAX(id) 
                FROM stok_history 
                WHERE kepalatokokios_id = ? 
                GROUP BY roti_id
            )
        WHERE COALESCE(sh.stok, 0) > 0
        ORDER BY r.nama_roti
    ");
    $stmt->execute([$kepalatokokios_id, $kepalatokokios_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "- {$product['nama']}: {$product['stok']} pcs (ID: {$product['id']})\n";
    }
    echo "\n";
    
    // Simulate stock reduction for roti ID 1, quantity 2
    echo "Testing stock reduction simulation:\n";
    
    // Get current stock for roti ID 1
    $stmt = $pdo->prepare("
        SELECT * FROM stok_history 
        WHERE roti_id = 1 AND kepalatokokios_id = ?
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$kepalatokokios_id]);
    $currentStock = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($currentStock) {
        echo "Current stock for Roti ID 1: {$currentStock['stok']}\n";
        $newStock = $currentStock['stok'] - 2;
        echo "New stock after selling 2 pcs: {$newStock}\n";
        
        // Insert new stock record
        $stmt = $pdo->prepare("
            INSERT INTO stok_history (roti_id, stok, kepalatokokios_id, stok_awal, tanggal, created_at, updated_at)
            VALUES (?, ?, ?, ?, CURDATE(), NOW(), NOW())
        ");
        $stmt->execute([1, $newStock, $kepalatokokios_id, $currentStock['stok']]);
        
        echo "New stock record created with ID: " . $pdo->lastInsertId() . "\n";
        
        // Verify new stock
        $stmt = $pdo->prepare("
            SELECT * FROM stok_history 
            WHERE roti_id = 1 AND kepalatokokios_id = ?
            ORDER BY id DESC 
            LIMIT 1
        ");
        $stmt->execute([$kepalatokokios_id]);
        $verifyStock = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Verified latest stock: {$verifyStock['stok']}\n";
        echo "\n";
        
        // Test API query again
        echo "Products after stock reduction:\n";
        $stmt = $pdo->prepare("
            SELECT 
                r.id,
                r.nama_roti as nama,
                COALESCE(sh.stok, 0) as stok
            FROM rotis r
            LEFT JOIN stok_history sh ON r.id = sh.roti_id 
                AND sh.kepalatokokios_id = ?
                AND sh.id IN (
                    SELECT MAX(id) 
                    FROM stok_history 
                    WHERE kepalatokokios_id = ? 
                    GROUP BY roti_id
                )
            WHERE COALESCE(sh.stok, 0) > 0
            ORDER BY r.nama_roti
        ");
        $stmt->execute([$kepalatokokios_id, $kepalatokokios_id]);
        $newProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($newProducts as $product) {
            echo "- {$product['nama']}: {$product['stok']} pcs (ID: {$product['id']})\n";
        }
    } else {
        echo "No stock found for roti ID 1\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
