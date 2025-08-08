<?php

// Test what happens when we explicitly save empty string vs null
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Http\Kernel')->bootstrap();

use App\Models\Transaksi;
use Illuminate\Http\Request;

echo "=== Testing empty string behavior ===\n";

// Test with explicitly empty string
$transaction1 = Transaksi::create([
    'roti_id' => 1,
    'stok_history_id' => 1,
    'user_id' => 2,
    'nama_customer' => '',  // Explicitly empty string
    'jumlah' => 1,
    'harga_satuan' => 1000,
    'total_harga' => 1000,
    'metode_pembayaran' => 'TEST',
    'tanggal_transaksi' => '2025-08-08',
]);

echo "Created transaction with empty string:\n";
echo "ID: " . $transaction1->id . "\n";
echo "nama_customer value: '" . $transaction1->nama_customer . "'\n";
echo "nama_customer is null: " . ($transaction1->nama_customer === null ? 'YES' : 'NO') . "\n";
echo "nama_customer is empty string: " . ($transaction1->nama_customer === '' ? 'YES' : 'NO') . "\n";
echo "nama_customer length: " . strlen($transaction1->nama_customer ?? '') . "\n";

// Check from database directly
$fromDb = Transaksi::find($transaction1->id);
echo "\nFrom database:\n";
echo "nama_customer value: '" . $fromDb->nama_customer . "'\n";
echo "nama_customer is null: " . ($fromDb->nama_customer === null ? 'YES' : 'NO') . "\n";
echo "nama_customer is empty string: " . ($fromDb->nama_customer === '' ? 'YES' : 'NO') . "\n";
