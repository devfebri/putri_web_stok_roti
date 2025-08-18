<?php
// Quick test script for waste code generation

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a request instance
$request = Illuminate\Http\Request::create('/');

// Boot the application
$kernel->bootstrap();

try {
    echo "Testing Waste Code Auto-Generation\n";
    echo "==================================\n\n";
    
    // Test the WasteController method directly
    $controller = new \App\Http\Controllers\WasteController();
    $response = $controller->getNextKodeWasteApi();
    
    // Get the response content
    $content = $response->getContent();
    $data = json_decode($content, true);
    
    echo "Response Status: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $content . "\n\n";
    
    if ($data && isset($data['kode_waste'])) {
        echo "SUCCESS! ✅\n";
        echo "Generated Waste Code: " . $data['kode_waste'] . "\n";
        echo "Message: " . $data['message'] . "\n";
    } else {
        echo "FAILED! ❌\n";
        echo "No kode_waste found in response\n";
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
