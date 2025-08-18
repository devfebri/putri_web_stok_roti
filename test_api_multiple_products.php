<?php

$url = 'http://localhost:8000/api/transaksi';

// Sample test data for multiple products
$data = [
    'nama_customer' => 'Test Customer Multiple Products',
    'metode_pembayaran' => 'Cash',
    'products' => [
        [
            'roti_id' => 1,
            'jumlah' => 2,
            'harga_satuan' => 15000
        ],
        [
            'roti_id' => 2,
            'jumlah' => 1,
            'harga_satuan' => 12000
        ]
    ]
];

// Initialize curl
$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

echo "=== Testing Multiple Products Transaksi API ===\n";
echo "URL: $url\n";
echo "Data: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";

// Execute request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_error($ch)) {
    echo "Curl error: " . curl_error($ch) . "\n";
} else {
    echo "HTTP Code: $httpCode\n";
    echo "Response: " . $response . "\n";
    
    // Try to decode JSON response
    $responseData = json_decode($response, true);
    if ($responseData) {
        echo "\nParsed Response:\n";
        print_r($responseData);
    }
}

curl_close($ch);
