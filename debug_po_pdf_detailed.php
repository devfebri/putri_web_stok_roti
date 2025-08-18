<?php

// Debug Purchase Order PDF dengan error handling yang lebih detail

echo "=== DEBUGGING PO PDF ERROR ===\n";

// Get token first
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

$loginResult = file_get_contents($loginUrl, false, $context);
$loginResponse = json_decode($loginResult, true);
$token = $loginResponse['data']['token'];

echo "Token: " . substr($token, 0, 30) . "...\n\n";

// Test PDF endpoint dengan error handling
$pdfUrl = "http://127.0.0.1:8000/api/laporan/purchase-order/pdf?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31&token=" . urlencode($token);

echo "Testing URL: $pdfUrl\n\n";

$pdfContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Accept: application/json'
        ],
        'ignore_errors' => true
    ]
]);

$pdfResult = file_get_contents($pdfUrl, false, $pdfContext);

if ($pdfResult) {
    echo "Response Headers:\n";
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            echo "  $header\n";
        }
    }
    
    echo "\nResponse Length: " . strlen($pdfResult) . " bytes\n\n";
    
    // Check if it's JSON error
    $jsonResponse = json_decode($pdfResult, true);
    if ($jsonResponse) {
        echo "JSON Response:\n";
        print_r($jsonResponse);
    } else {
        echo "Raw Response (first 1000 chars):\n";
        echo substr($pdfResult, 0, 1000) . "\n";
        
        if (strpos($pdfResult, '%PDF') === 0) {
            echo "\n✓ PDF detected!\n";
            $filename = "test_debug_pdf_" . date('H-i-s') . ".pdf";
            file_put_contents($filename, $pdfResult);
            echo "Saved as: $filename\n";
        }
    }
} else {
    echo "ERROR: No response from server\n";
}

echo "\n=== DEBUG COMPLETED ===\n";
