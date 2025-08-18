<?php
require_once __DIR__ . '/vendor/autoload.php';

// Load environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_DATABASE'] ?? 'putri_web_stok_roti';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== USER LOGIN TEST ===\n\n";
    
    // Get admin user
    $stmt = $pdo->prepare("SELECT id, name, username, email, password, role FROM users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Admin user found:\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Name: " . $user['name'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Role: " . $user['role'] . "\n";
        echo "Password hash: " . substr($user['password'], 0, 30) . "...\n\n";
        
        // Test different passwords
        $testPasswords = ['admin123', 'password', '123456', 'admin', 'putri123'];
        
        foreach ($testPasswords as $testPass) {
            if (password_verify($testPass, $user['password'])) {
                echo "✅ FOUND! Password is: $testPass\n";
                break;
            } else {
                echo "❌ Not: $testPass\n";
            }
        }
    } else {
        echo "❌ Admin user not found\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}

echo "\n=== END TEST ===\n";
