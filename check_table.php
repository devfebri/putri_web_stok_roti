<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=web_putri', 'root', '');
    
    echo "Users table structure:\n";
    $stmt = $pdo->query('DESCRIBE users');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    echo "\nAll users data:\n";
    $stmt = $pdo->query('SELECT * FROM users');
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- ID: " . $row['id'] . ", Name: " . $row['name'] . ", Email: " . $row['email'];
        if (isset($row['username'])) {
            echo ", Username: " . $row['username'];
        }
        echo "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
