<?php

// Test script untuk Purchase Order PDF export

require_once 'vendor/autoload.php';

echo "=== Testing Purchase Order PDF Export ===\n";

// Test API endpoint
$testUrl = "http://127.0.0.1:8000/api/laporan/purchase-order/pdf?periode=harian&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31";

echo "Test URL: $testUrl\n";

// Check if we can access the URL
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json',
            'Content-Type: application/json'
        ],
        'timeout' => 30
    ]
]);

echo "Calling URL...\n";
$result = file_get_contents($testUrl, false, $context);

if ($result === false) {
    echo "ERROR: Failed to call URL\n";
    $error = error_get_last();
    if ($error) {
        echo "Error details: " . $error['message'] . "\n";
    }
} else {
    echo "SUCCESS: URL accessible\n";
    echo "Response length: " . strlen($result) . " bytes\n";
    
    // Check if it's PDF
    if (strpos($result, '%PDF') === 0) {
        echo "✓ Response is PDF format\n";
        
        // Save to test file
        $filename = "test_po_report_" . date('Y-m-d_H-i-s') . ".pdf";
        file_put_contents($filename, $result);
        echo "✓ PDF saved as: $filename\n";
    } else {
        echo "✗ Response is not PDF format\n";
        echo "First 200 chars: " . substr($result, 0, 200) . "\n";
    }
}

echo "=== Test completed ===\n";
