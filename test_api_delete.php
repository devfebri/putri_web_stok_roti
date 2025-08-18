<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing API Routes ===\n";

// Create admin user token for testing
$admin = \App\Models\User::where('role', 'admin')->first();
if (!$admin) {
    echo "No admin user found!\n";
    exit;
}

$token = $admin->createToken('test-delete')->plainTextToken;
echo "Generated test token for user: {$admin->name}\n";
echo "Token: " . substr($token, 0, 20) . "...\n\n";

// Get current PO records
$records = \App\Models\RotiPo::all();
echo "Current PO records in database:\n";
foreach($records as $record) {
    echo "ID: {$record->id}, Kode: {$record->kode_po}, Status: {$record->status}\n";
}

if($records->count() > 0) {
    $testId = $records->first()->id;
    echo "\n=== Testing DELETE API ===\n";
    echo "Testing delete on ID: $testId\n";
    
    // Test different role endpoints
    $roles = ['admin', 'kepalatokokios'];
    
    foreach($roles as $role) {
        $url = "http://127.0.0.1:8000/api/$role/rotipo/$testId";
        echo "\nTesting URL: $url\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $token",
            "Accept: application/json",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "Response Code: $httpCode\n";
        echo "Response: $response\n";
        
        if($httpCode == 200) {
            echo "✅ DELETE successful for role: $role\n";
            break; // Stop after successful delete
        } else {
            echo "❌ DELETE failed for role: $role\n";
        }
    }
    
    // Check if record was actually deleted
    $stillExists = \App\Models\RotiPo::find($testId);
    if($stillExists) {
        echo "\n❌ Record still exists after delete!\n";
    } else {
        echo "\n✅ Record successfully deleted from database!\n";
    }
    
    // Show remaining records
    $remainingRecords = \App\Models\RotiPo::all();
    echo "\nRemaining records: " . $remainingRecords->count() . "\n";
}

echo "\n=== Test Complete ===\n";
