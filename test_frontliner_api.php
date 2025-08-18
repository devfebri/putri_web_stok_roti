<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing getFrontlinersApi Endpoint ===\n";

// Test for each kepalatokokios
$kepalatokokios = \App\Models\User::where('role', 'kepalatokokios')->get();

foreach($kepalatokokios as $kepala) {
    echo "\n=== Testing for {$kepala->name} (ID: {$kepala->id}) ===\n";
    
    // Create token for this user
    $token = $kepala->createToken('test-frontliner')->plainTextToken;
    echo "Generated token for: {$kepala->name}\n";
    
    // Test API call
    $url = "http://127.0.0.1:8000/api/kepalatokokios/getfrontliners";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer $token",
        "Accept: application/json",
        "Content-Type: application/json"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Response Code: $httpCode\n";
    echo "Response: $response\n";
    
    if($httpCode == 200) {
        $data = json_decode($response, true);
        if(isset($data['data'])) {
            echo "Available frontliners for {$kepala->name}:\n";
            foreach($data['data'] as $frontliner) {
                echo "  - ID: {$frontliner['id']}, Name: {$frontliner['name']}\n";
            }
        }
    }
    
    // Clean up token
    $kepala->tokens()->delete();
}

echo "\n=== Test Complete ===\n";
