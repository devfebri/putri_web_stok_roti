<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Frontliner Filter ===\n";

// Get kepalatokokios users
$kepalatokokios = \App\Models\User::where('role', 'kepalatokokios')->get();
echo "Kepala Toko Kios users:\n";
foreach($kepalatokokios as $kepala) {
    echo "ID: {$kepala->id}, Name: {$kepala->name}\n";
}

echo "\nAll frontliners with their kepalatokokios_id:\n";
$frontliners = \App\Models\User::where('role', 'frontliner')->get();
foreach($frontliners as $frontliner) {
    echo "ID: {$frontliner->id}, Name: {$frontliner->name}, Kepala Toko ID: {$frontliner->kepalatokokios_id}\n";
}

echo "\n=== Testing Filter Logic ===\n";

// Test for each kepalatokokios
foreach($kepalatokokios as $kepala) {
    echo "\nFor Kepala Toko: {$kepala->name} (ID: {$kepala->id})\n";
    
    $filteredFrontliners = \App\Models\User::select('id', 'name')
        ->where('role', 'frontliner')
        ->where('status', '!=', 9)
        ->where('kepalatokokios_id', $kepala->id)
        ->get();
    
    echo "Available frontliners: " . $filteredFrontliners->count() . "\n";
    foreach($filteredFrontliners as $frontliner) {
        echo "  - {$frontliner->name}\n";
    }
}

echo "\n=== Test Complete ===\n";
