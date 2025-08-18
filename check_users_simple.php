<?php
require_once 'vendor/autoload.php';

// Database connection configuration
$config = [
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'web_putri',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
];

// Create a new Capsule manager instance
$capsule = new Illuminate\Database\Capsule\Manager;
$capsule->addConnection($config);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Use Capsule
use Illuminate\Database\Capsule\Manager as DB;

echo "=== CHECKING USERS ===\n";

try {
    $users = DB::table('users')->select('username', 'role')->get();
    foreach ($users as $user) {
        echo $user->username . ' (' . $user->role . ')' . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
