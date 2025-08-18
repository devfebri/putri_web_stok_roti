<?php

// Test different passwords for user
require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// Setup database connection
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'web_putri',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

echo "Checking user password:\n";
echo "=======================\n";

try {
    $user = Capsule::table('users')->where('username', 'front1')->first();
    
    if ($user) {
        echo "User found:\n";
        echo "ID: {$user->id}\n";
        echo "Name: {$user->name}\n";
        echo "Username: {$user->username}\n";
        echo "Password hash: " . substr($user->password, 0, 50) . "...\n";
        
        // Test if password is hashed or plain text
        $testPasswords = ['123456789', 'password', '12345678', 'front1', 'password123', 'frontliner123'];
        
        foreach ($testPasswords as $testPass) {
            $isMatch = password_verify($testPass, $user->password);
            echo "Password '$testPass' matches: " . ($isMatch ? 'YES' : 'NO') . "\n";
        }
        
    } else {
        echo "User not found!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
