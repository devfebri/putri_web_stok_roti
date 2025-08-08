<?php

// Simple test to check if the available stok API is working with today's filter
$url = 'http://127.0.0.1:8000/api/getavailablestok';

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Accept: application/json'
        ]
    ]
]);

echo "Testing getAvailableStokApi endpoint...\n";
echo "URL: $url\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "Error: Could not fetch data from API\n";
} else {
    $data = json_decode($response, true);
    echo "Response received:\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "\n\n";
    
    if (isset($data['data']) && is_array($data['data'])) {
        echo "Available stok count: " . count($data['data']) . "\n";
        foreach ($data['data'] as $index => $stok) {
            echo "Stok " . ($index + 1) . ": {$stok['tampil']}\n";
            echo "  - ID: {$stok['id']}\n";
            echo "  - Tanggal: {$stok['tanggal']}\n";
            echo "  - Sisa Stok: {$stok['stok']}\n\n";
        }
    }
}

echo "Test completed.\n";
