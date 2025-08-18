<?php

// Test penjualan endpoint secara langsung dengan token valid

$token = "9|SC4MDD0n3fkFZu0Lc4pKjQW6G5hffoavD6FdwO0L6406c506";

echo "=== Testing Penjualan with Token ===\n";

// Test API penjualan
echo "1. Testing Penjualan API...\n";
$apiUrl = "http://127.0.0.1:8000/api/admin/laporan/penjualan?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        'ignore_errors' => true
    ]
]);

$result = file_get_contents($apiUrl, false, $context);

if ($result) {
    $response = json_decode($result, true);
    if ($response && isset($response['data'])) {
        echo "✓ API working\n";
        print_r($response['data']['summary'] ?? []);
    } else {
        echo "✗ API error: " . substr($result, 0, 300) . "\n";
    }
} else {
    echo "✗ Failed to call API\n";
}

// Test PDF
echo "\n2. Testing Penjualan PDF...\n";
$pdfUrl = "http://127.0.0.1:8000/api/laporan/penjualan/pdf?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31&token=" . urlencode($token);

$pdfResult = file_get_contents($pdfUrl, false, $context);

if ($pdfResult && strpos($pdfResult, '%PDF') === 0) {
    $filename = "penjualan_test_" . date('H-i-s') . ".pdf";
    file_put_contents($filename, $pdfResult);
    echo "✓ PDF generated: $filename\n";
} else {
    echo "✗ PDF failed\n";
    if ($pdfResult) {
        $jsonResp = json_decode($pdfResult, true);
        if ($jsonResp) {
            echo "Error: " . ($jsonResp['message'] ?? 'Unknown') . "\n";
        } else {
            echo "Response: " . substr($pdfResult, 0, 300) . "\n";
        }
    }
}

echo "=== Done ===\n";
