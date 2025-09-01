<?php

// Simple test untuk verifikasi POST route penjualan

require 'vendor/autoload.php';

use Illuminate\Http\Request;
use App\Http\Controllers\LaporanController;

// Simulate Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Create test request
$request = new Request();
$request->merge([
    'periode' => 'custom',
    'tanggal_mulai' => '2024-01-01',
    'tanggal_selesai' => '2024-12-31'
]);

echo "=== Testing PenjualanReportApi Method ===\n";
echo "Request data: " . json_encode($request->all()) . "\n\n";

try {
    // Create controller instance
    $controller = new LaporanController();
    
    // Mock authenticated user
    $user = new \App\Models\User();
    $user->id = 1;
    $user->role = 'admin';
    $user->name = 'Test Admin';
    
    // Set auth user 
    \Illuminate\Support\Facades\Auth::shouldReceive('user')
        ->andReturn($user);
    
    // Call the method
    $result = $controller->penjualanReportApi($request);
    
    echo "✅ Method executed successfully!\n";
    
    if ($result instanceof \Illuminate\Http\JsonResponse) {
        $data = $result->getData(true);
        echo "Response status: " . $result->getStatusCode() . "\n";
        echo "Response keys: " . implode(', ', array_keys($data)) . "\n";
        
        if (isset($data['data']['summary'])) {
            echo "Summary found with keys: " . implode(', ', array_keys($data['data']['summary'])) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    if ($e instanceof \Illuminate\Validation\ValidationException) {
        echo "Validation errors:\n";
        foreach ($e->errors() as $field => $errors) {
            echo "  $field: " . implode(', ', $errors) . "\n";
        }
    }
}

echo "\n=== Test Complete ===\n";

?>
