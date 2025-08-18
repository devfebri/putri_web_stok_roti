<?php
$host = 'localhost';
$dbname = 'web_putri';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== Showing all tables ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "Table: $table\n";
    }
    
    echo "\n=== Looking for PO-related tables ===\n";
    $poTables = array_filter($tables, function($table) {
        return strpos(strtolower($table), 'po') !== false;
    });
    
    foreach ($poTables as $table) {
        echo "\n=== Structure of $table ===\n";
        $stmt = $pdo->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            echo "$table.{$col['Field']} - {$col['Type']}\n";
        }
        
        echo "\n=== Sample data from $table ===\n";
        $stmt = $pdo->query("SELECT * FROM $table LIMIT 3");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($data as $row) {
            echo json_encode($row) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
