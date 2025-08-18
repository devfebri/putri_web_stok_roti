<?php

// Check Laravel users username field
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

echo "Users with username field:\n";
echo "==========================\n";

try {
    // Check table structure first
    $columns = Capsule::select("SHOW COLUMNS FROM users");
    echo "Table columns:\n";
    foreach ($columns as $column) {
        echo "- {$column->Field} ({$column->Type})\n";
    }
    
    echo "\nUser data:\n";
    $users = Capsule::table('users')->select('id', 'name', 'email', 'username', 'role')->get();
    
    foreach ($users as $user) {
        echo "ID: {$user->id} - Name: {$user->name} - Email: {$user->email} - Username: " . ($user->username ?? 'NULL') . " - Role: {$user->role}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
