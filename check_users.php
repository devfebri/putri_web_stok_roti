<?php

// Check Laravel users for testing
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

echo "Users in database:\n";
echo "==================\n";

try {
    $users = Capsule::table('users')->select('id', 'name', 'email', 'role')->get();
    
    foreach ($users as $user) {
        echo "ID: {$user->id} - Name: {$user->name} - Email: {$user->email} - Role: {$user->role}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nChecking some PO data:\n";
echo "======================\n";

try {
    $pos = Capsule::table('roti_pos')
        ->select('id', 'kode_po', 'user_id', 'status')
        ->where('status', '!=', '9')
        ->limit(5)
        ->get();
    
    foreach ($pos as $po) {
        echo "PO ID: {$po->id} - Kode: {$po->kode_po} - User ID: {$po->user_id} - Status: {$po->status}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

?>
