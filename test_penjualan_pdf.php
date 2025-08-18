<?php

// Test Penjualan PDF dengan struktur database yang benar

echo "=== Testing Penjualan PDF ===\n";

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
$loginResponse = json_decode($loginResult, true);

if (!$loginResponse || !isset($loginResponse['data']['token'])) {
    echo "ERROR: Login failed\n";
    echo "Response: " . print_r($loginResponse, true) . "\n";
    exit(1);
}

$token = $loginResponse['data']['token'];
echo "✓ Token obtained\n";

// Test API endpoint penjualan terlebih dahulu
echo "\n2. Testing Penjualan API endpoint...\n";
$apiUrl = "http://127.0.0.1:8000/api/admin/laporan/penjualan?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31";

$apiContext = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        'ignore_errors' => true
    ]
]);

$apiResult = file_get_contents($apiUrl, false, $apiContext);

if ($apiResult) {
    $apiResponse = json_decode($apiResult, true);
    if (isset($apiResponse['data'])) {
        echo "✓ API endpoint working\n";
        if (isset($apiResponse['data']['penjualan_list'])) {
            $count = count($apiResponse['data']['penjualan_list']);
            echo "  - Found $count penjualan records\n";
        }
        if (isset($apiResponse['data']['summary'])) {
            $summary = $apiResponse['data']['summary'];
            echo "  - Total penjualan: Rp " . number_format($summary['total_penjualan'] ?? 0) . "\n";
            echo "  - Total item: " . ($summary['total_item_terjual'] ?? 0) . "\n";
            echo "  - Jumlah transaksi: " . ($summary['jumlah_transaksi'] ?? 0) . "\n";
        }
    } else {
        echo "✗ API error: " . substr($apiResult, 0, 300) . "\n";
    }
} else {
    echo "✗ Failed to call API\n";
}

// Test PDF endpoint
echo "\n3. Testing PDF endpoint...\n";
$pdfUrl = "http://127.0.0.1:8000/api/laporan/penjualan/pdf?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31&token=" . urlencode($token);

$pdfResult = @file_get_contents($pdfUrl);

if ($pdfResult) {
    if (strpos($pdfResult, '%PDF') === 0) {
        $filename = "test_penjualan_pdf_" . date('H-i-s') . ".pdf";
        file_put_contents($filename, $pdfResult);
        echo "✓ PDF generated successfully: $filename\n";
        echo "✓ PDF size: " . strlen($pdfResult) . " bytes\n";
    } else {
        echo "✗ PDF generation failed\n";
        $jsonResponse = json_decode($pdfResult, true);
        if ($jsonResponse) {
            echo "Error: " . ($jsonResponse['message'] ?? 'Unknown error') . "\n";
        } else {
            echo "Response: " . substr($pdfResult, 0, 500) . "\n";
        }
    }
} else {
    echo "✗ Failed to call PDF endpoint\n";
}

echo "\n=== Test completed ===\n";
