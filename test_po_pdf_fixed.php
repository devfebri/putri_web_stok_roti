<?php

// Test Purchase Order PDF dengan token authentication

echo "=== Testing PO PDF with Fixed Authentication ===\n";

// Login untuk mendapatkan token
$loginUrl = "http://127.0.0.1:8000/api/proses_login_API";
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
        'content' => $loginData,
        'ignore_errors' => true
    ]
]);

echo "1. Getting auth token...\n";
$loginResult = file_get_contents($loginUrl, false, $context);

if ($loginResult === false) {
    echo "ERROR: Failed to login\n";
    exit(1);
}

$loginResponse = json_decode($loginResult, true);

if (!$loginResponse || !isset($loginResponse['data']['access_token'])) {
    echo "ERROR: Invalid login response\n";
    echo "Response: " . print_r($loginResponse, true) . "\n";
    exit(1);
}

$token = $loginResponse['data']['access_token'];
echo "✓ Token obtained successfully\n";

// Test PDF endpoint dengan token di URL parameter
$pdfUrl = "http://127.0.0.1:8000/api/admin/laporan/purchase-order/pdf?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31&token=" . urlencode($token);

echo "2. Testing PDF endpoint with token in URL...\n";

$pdfContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/pdf'
        ],
        'ignore_errors' => true
    ]
]);

$pdfResult = file_get_contents($pdfUrl, false, $pdfContext);

if ($pdfResult === false) {
    echo "ERROR: Failed to get PDF\n";
    $error = error_get_last();
    if ($error) {
        echo "Error details: " . $error['message'] . "\n";
    }
} else {
    echo "✓ PDF endpoint accessible\n";
    echo "Response length: " . strlen($pdfResult) . " bytes\n";
    
    // Check content type from headers
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            echo "Header: $header\n";
        }
    }
    
    // Check if it's PDF
    if (strpos($pdfResult, '%PDF') === 0) {
        echo "✓ Response is PDF format\n";
        
        // Save to test file
        $filename = "test_po_pdf_fixed_" . date('Y-m-d_H-i-s') . ".pdf";
        file_put_contents($filename, $pdfResult);
        echo "✓ PDF saved as: $filename\n";
    } else {
        echo "✗ Response is not PDF format\n";
        echo "Content preview: " . substr($pdfResult, 0, 500) . "\n";
    }
}

echo "\n=== Test completed ===\n";
