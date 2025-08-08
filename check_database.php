<?php

// Simple database check without Laravel bootstrap
$host = '127.0.0.1';
$dbname = 'putri_db'; // Adjust database name as needed
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Database connection successful!\n\n";
    
    // Check stok_history records
    echo "Checking stok_history table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM stok_history");
    $result = $stmt->fetch();
    echo "Total stok_history records: " . $result['total'] . "\n\n";
    
    // Check today's records
    $today = date('Y-m-d');
    echo "Today's date: $today\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM stok_history WHERE tanggal = ?");
    $stmt->execute([$today]);
    $result = $stmt->fetch();
    echo "Today's stok_history records: " . $result['total'] . "\n\n";
    
    // Show all stok_history data
    echo "All stok_history records:\n";
    $stmt = $pdo->query("
        SELECT sh.id, sh.roti_id, sh.stok, sh.tanggal, r.nama_roti, r.rasa_roti 
        FROM stok_history sh 
        LEFT JOIN rotis r ON r.id = sh.roti_id 
        ORDER BY sh.tanggal DESC 
        LIMIT 10
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']}, Roti: {$row['nama_roti']} - {$row['rasa_roti']}, Stok: {$row['stok']}, Tanggal: {$row['tanggal']}\n";
    }
    
    // Check roti table
    echo "\nChecking rotis table:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM rotis");
    $result = $stmt->fetch();
    echo "Total roti records: " . $result['total'] . "\n";
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
    echo "Default database name: putri_db\n";
    echo "Default username: root\n";
    echo "Default password: (empty)\n";
}

echo "\nDatabase check completed.\n";
