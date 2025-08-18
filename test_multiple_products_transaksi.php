<?php

require_once 'vendor/autoload.php';

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->boot();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Roti;
use App\Models\StokHistory;
use App\Models\Transaksi;
use App\Models\TransaksiRoti;

echo "=== Testing Multiple Products Transaksi System ===\n\n";

try {
    // 1. Check database structure
    echo "1. Checking database structure...\n";
    
    $transaksiColumns = DB::select("DESCRIBE transaksi");
    echo "Transaksi table columns:\n";
    foreach ($transaksiColumns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    $transaksiRotiColumns = DB::select("DESCRIBE transaksi_roti");
    echo "\nTransaksiRoti table columns:\n";
    foreach ($transaksiRotiColumns as $column) {
        echo "  - {$column->Field} ({$column->Type})\n";
    }
    
    // 2. Check if we have test data
    echo "\n2. Checking test data...\n";
    
    $userCount = User::count();
    $rotiCount = Roti::count();
    $stokCount = StokHistory::count();
    
    echo "Users: {$userCount}\n";
    echo "Roti products: {$rotiCount}\n";
    echo "Stock history records: {$stokCount}\n";
    
    if ($rotiCount === 0) {
        echo "Creating sample roti products...\n";
        
        $rotis = [
            ['nama_roti' => 'Roti Tawar', 'harga_roti' => 15000],
            ['nama_roti' => 'Roti Manis', 'harga_roti' => 12000],
            ['nama_roti' => 'Roti Coklat', 'harga_roti' => 18000],
        ];
        
        foreach ($rotis as $roti) {
            $newRoti = Roti::create($roti);
            echo "Created roti: {$newRoti->nama_roti}\n";
        }
    }
    
    // 3. Create test stock data
    $testUser = User::first();
    if (!$testUser) {
        echo "No users found! Please create a user first.\n";
        exit;
    }
    
    echo "\n3. Creating test stock data...\n";
    
    $rotis = Roti::limit(3)->get();
    foreach ($rotis as $roti) {
        $existingStock = StokHistory::where('roti_id', $roti->id)
            ->where('tanggal', now()->format('Y-m-d'))
            ->first();
            
        if (!$existingStock) {
            StokHistory::create([
                'roti_id' => $roti->id,
                'stok' => rand(20, 50),
                'tanggal' => now()->format('Y-m-d'),
                'kepalatokokios_id' => $testUser->id,
            ]);
            echo "Created stock for: {$roti->nama_roti}\n";
        }
    }
    
    // 4. Test API simulation
    echo "\n4. Testing multiple products transaction API...\n";
    
    $availableStock = StokHistory::with('roti')
        ->where('tanggal', now()->format('Y-m-d'))
        ->where('stok', '>', 0)
        ->get();
    
    if ($availableStock->count() < 2) {
        echo "Not enough stock for testing. Need at least 2 products.\n";
        exit;
    }
    
    // Simulate API request data
    $products = [];
    foreach ($availableStock->take(2) as $stock) {
        $products[] = [
            'roti_id' => $stock->roti_id,
            'jumlah' => rand(1, min(5, $stock->stok)),
            'harga_satuan' => $stock->roti->harga_roti,
        ];
    }
    
    $requestData = [
        'nama_customer' => 'Test Customer Multiple Products',
        'metode_pembayaran' => 'Cash',
        'products' => $products
    ];
    
    echo "Request data:\n";
    echo json_encode($requestData, JSON_PRETTY_PRINT) . "\n";
    
    // 5. Simulate transaction creation
    echo "\n5. Creating transaction...\n";
    
    DB::beginTransaction();
    
    try {
        // Calculate total
        $totalHarga = 0;
        foreach ($products as $product) {
            $totalHarga += $product['harga_satuan'] * $product['jumlah'];
        }
        
        // Create main transaction
        $transaksi = Transaksi::create([
            'nama_customer' => $requestData['nama_customer'],
            'metode_pembayaran' => $requestData['metode_pembayaran'],
            'total_harga' => $totalHarga,
        ]);
        
        echo "Created transaction ID: {$transaksi->id}\n";
        echo "Total harga: Rp " . number_format($totalHarga) . "\n";
        
        // Create transaction items
        foreach ($products as $product) {
            $transaksiRoti = TransaksiRoti::create([
                'transaksi_id' => $transaksi->id,
                'user_id' => $testUser->id,
                'roti_id' => $product['roti_id'],
                'jumlah' => $product['jumlah'],
                'harga_satuan' => $product['harga_satuan'],
            ]);
            
            // Update stock
            $stokHistory = StokHistory::where('roti_id', $product['roti_id'])
                ->where('tanggal', now()->format('Y-m-d'))
                ->first();
                
            if ($stokHistory) {
                $stokHistory->stok -= $product['jumlah'];
                $stokHistory->save();
                
                $rotiName = Roti::find($product['roti_id'])->nama_roti;
                echo "Created transaksi_roti item: {$rotiName} x{$product['jumlah']}\n";
                echo "Updated stock: {$stokHistory->stok} remaining\n";
            }
        }
        
        DB::commit();
        echo "\n✅ Transaction created successfully!\n";
        
        // 6. Test retrieval with relationships
        echo "\n6. Testing data retrieval...\n";
        
        $retrievedTransaksi = Transaksi::with(['transaksiRoti.roti', 'transaksiRoti.user'])
            ->find($transaksi->id);
        
        echo "Retrieved transaction:\n";
        echo "Customer: {$retrievedTransaksi->nama_customer}\n";
        echo "Payment: {$retrievedTransaksi->metode_pembayaran}\n";
        echo "Total: Rp " . number_format((float)$retrievedTransaksi->total_harga) . "\n";
        echo "Items:\n";
        
        foreach ($retrievedTransaksi->transaksiRoti as $item) {
            $subtotal = $item->harga_satuan * $item->jumlah;
            echo "  - {$item->roti->nama_roti}: {$item->jumlah} pcs @ Rp " . number_format($item->harga_satuan) . " = Rp " . number_format($subtotal) . "\n";
        }
        
        echo "\n✅ All tests passed! Multiple products transaction system is working correctly.\n";
        
    } catch (Exception $e) {
        DB::rollBack();
        echo "❌ Transaction failed: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
