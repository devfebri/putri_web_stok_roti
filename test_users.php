<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing User Table ===\n";

// Check user table structure
$users = \App\Models\User::all();
echo "Total users: " . $users->count() . "\n";

if($users->count() > 0) {
    $firstUser = $users->first();
    echo "First user data:\n";
    print_r($firstUser->toArray());
    
    echo "\nAvailable roles:\n";
    foreach($users as $user) {
        echo "ID: {$user->id}, Name: {$user->name}, Role: {$user->role}\n";
    }
}

echo "\n=== Test Complete ===\n";
