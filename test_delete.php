<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Delete Fix ===\n";

// Check total records
$total = \App\Models\RotiPo::count();
echo "Total RotiPo records: $total\n";

// Show all records with status
$records = \App\Models\RotiPo::select('id', 'kode_po', 'status')->get();
echo "\nAll records:\n";
foreach($records as $record) {
    echo "ID: {$record->id}, Kode: {$record->kode_po}, Status: {$record->status}\n";
}

// Test delete function if we have records
if($total > 0) {
    echo "\n=== Testing delete function ===\n";
    
    // Find first record
    $firstRecord = \App\Models\RotiPo::first();
    $firstId = $firstRecord->id;
    echo "Testing delete on ID: $firstId\n";
    
    // Test the controller delete method
    $controller = new \App\Http\Controllers\RotiPoController();
    
    // Call destroyApi method
    $response = $controller->destroyApi($firstId);
    $responseData = $response->getData();
    
    echo "Delete response: " . json_encode($responseData) . "\n";
    
    // Check if record still exists
    $stillExists = \App\Models\RotiPo::find($firstId);
    if($stillExists) {
        echo "ERROR: Record still exists after delete!\n";
        echo "Record status: {$stillExists->status}\n";
    } else {
        echo "SUCCESS: Record deleted successfully!\n";
    }
    
    $newTotal = \App\Models\RotiPo::count();
    echo "Total records after delete: $newTotal\n";
}

echo "\n=== Test Complete ===\n";
