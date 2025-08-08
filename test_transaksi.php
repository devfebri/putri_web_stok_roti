<?php

// Test file to simulate the transaction request
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

use App\Models\Transaksi;
use Illuminate\Http\Request;

// Create a test request with the data that should be sent from Flutter
$requestData = [
    'roti_id' => 1,  // This should be fetched from stok_history in the controller
    'stok_history_id' => 1,
    'user_id' => 2,
    'nama_customer' => 'Test Customer',
    'jumlah' => 2,
    'harga_satuan' => 5000,
    'total_harga' => 10000,
    'metode_pembayaran' => 'CASH',
    'tanggal_transaksi' => '2025-08-08',
];

echo "Testing with data:\n";
print_r($requestData);

// Test creating a transaction directly
try {
    $transaksi = Transaksi::create($requestData);
    echo "\nTransaction created successfully:\n";
    print_r($transaksi->toArray());
} catch (Exception $e) {
    echo "\nError creating transaction: " . $e->getMessage() . "\n";
}
