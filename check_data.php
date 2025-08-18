<?php
require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

echo "Database Check\n";
echo "==============\n\n";

try {
    // Check users table
    $users = DB::table('users')->get();
    echo "Total users: " . count($users) . "\n\n";
    
    if (count($users) > 0) {
        echo "Users list:\n";
        foreach ($users as $user) {
            echo "- ID: {$user->id}, Name: {$user->name}, Username: {$user->username}, Role: {$user->role_id}\n";
        }
        echo "\n";
    }
    
    // Check admin user specifically
    $admin = DB::table('users')->where('role_id', 0)->first();
    if ($admin) {
        echo "Admin found: {$admin->name} ({$admin->username})\n";
        echo "Password hash: " . substr($admin->password, 0, 20) . "...\n\n";
        
        // Test password verification
        if (password_verify('admin123', $admin->password)) {
            echo "✅ Admin password verification: SUCCESS\n";
        } else {
            echo "❌ Admin password verification: FAILED\n";
        }
    } else {
        echo "❌ No admin user found\n";
    }
    
    // Check rotis
    $rotis = DB::table('rotis')->get();
    echo "\nRotis count: " . count($rotis) . "\n";
    if (count($rotis) > 0) {
        foreach ($rotis as $roti) {
            echo "- {$roti->nama_roti} (Rp " . number_format($roti->harga_roti) . ")\n";
        }
    }
    
    // Check stok_history 
    $stoks = DB::table('stok_history')->get();
    echo "\nStock history count: " . count($stoks) . "\n";
    if (count($stoks) > 0) {
        foreach ($stoks as $stok) {
            echo "- Roti ID {$stok->roti_id}: {$stok->stok} pcs\n";
        }
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
