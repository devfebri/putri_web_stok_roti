<?php

// Test file to simulate what happens when nama_customer is empty
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

use App\Models\Transaksi;
use Illuminate\Http\Request;

// Test scenarios
$scenarios = [
    'Empty string' => [
        'roti_id' => 1,
        'stok_history_id' => 1,
        'user_id' => 2,
        'nama_customer' => '',  // Empty string
        'jumlah' => 2,
        'harga_satuan' => 5000,
        'total_harga' => 10000,
        'metode_pembayaran' => 'CASH',
        'tanggal_transaksi' => '2025-08-08',
    ],
    'Null value' => [
        'roti_id' => 1,
        'stok_history_id' => 1,
        'user_id' => 2,
        'nama_customer' => null,  // Null value
        'jumlah' => 2,
        'harga_satuan' => 5000,
        'total_harga' => 10000,
        'metode_pembayaran' => 'CASH',
        'tanggal_transaksi' => '2025-08-08',
    ],
    'Missing field' => [
        'roti_id' => 1,
        'stok_history_id' => 1,
        'user_id' => 2,
        // 'nama_customer' => '',  // Missing field
        'jumlah' => 2,
        'harga_satuan' => 5000,
        'total_harga' => 10000,
        'metode_pembayaran' => 'CASH',
        'tanggal_transaksi' => '2025-08-08',
    ]
];

foreach ($scenarios as $scenarioName => $requestData) {
    echo "\n=== Testing scenario: $scenarioName ===\n";
    print_r($requestData);
    
    try {
        $transaksi = Transaksi::create($requestData);
        echo "Transaction created successfully:\n";
        echo "nama_customer: " . json_encode($transaksi->nama_customer) . "\n";
        echo "ID: " . $transaksi->id . "\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
