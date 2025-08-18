<?php

// Test script untuk Purchase Order PDF dengan token

echo "=== Testing Purchase Order PDF with Auth ===\n";

// First, get a token by logging in
$loginUrl = "http://127.0.0.1:8000/api/login";
$loginData = json_encode([
    'username' => 'admin',
    'password' => 'admin123'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        'content' => $loginData
    ]
]);

echo "Getting auth token...\n";
$loginResult = file_get_contents($loginUrl, false, $context);

if ($loginResult === false) {
    echo "ERROR: Failed to login\n";
    exit(1);
}

$loginResponse = json_decode($loginResult, true);
echo "Login response: " . print_r($loginResponse, true) . "\n";

if (!isset($loginResponse['data']['access_token'])) {
    echo "ERROR: No access token in response\n";
    exit(1);
}

$token = $loginResponse['data']['access_token'];
echo "Token obtained: " . substr($token, 0, 20) . "...\n";

// Now test the PDF endpoint
$pdfUrl = "http://127.0.0.1:8000/api/admin/laporan/purchase-order/pdf?periode=harian&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31";

$pdfContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ]
    ]
]);

echo "Calling PDF endpoint...\n";
$pdfResult = file_get_contents($pdfUrl, false, $pdfContext);

if ($pdfResult === false) {
    echo "ERROR: Failed to get PDF\n";
    $error = error_get_last();
    if ($error) {
        echo "Error details: " . $error['message'] . "\n";
    }
} else {
    echo "SUCCESS: PDF endpoint accessible\n";
    echo "Response length: " . strlen($pdfResult) . " bytes\n";
    
    // Check if it's PDF
    if (strpos($pdfResult, '%PDF') === 0) {
        echo "✓ Response is PDF format\n";
        
        // Save to test file
        $filename = "test_po_report_auth_" . date('Y-m-d_H-i-s') . ".pdf";
        file_put_contents($filename, $pdfResult);
        echo "✓ PDF saved as: $filename\n";
    } else {
        echo "✗ Response is not PDF format\n";
        echo "First 500 chars: " . substr($pdfResult, 0, 500) . "\n";
    }
}

echo "=== Test completed ===\n";
