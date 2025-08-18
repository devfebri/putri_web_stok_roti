<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== DEBUGGING AUTH & ROLE ===" . PHP_EOL;

// Check users and their roles
echo "Users and their roles:" . PHP_EOL;
$users = \App\Models\User::all(['id', 'name', 'role', 'kepalatokokios_id']);
foreach($users as $user) {
    echo "ID: {$user->id}, Name: {$user->name}, Role: {$user->role}, Kepalatokokios: {$user->kepalatokokios_id}" . PHP_EOL;
}

// Check if there's any token for frontliner
echo PHP_EOL . "Tokens:" . PHP_EOL;
$tokens = \Illuminate\Support\Facades\DB::table('personal_access_tokens')->get(['id', 'tokenable_id', 'name', 'created_at']);
foreach($tokens as $token) {
    echo "Token ID: {$token->id}, User ID: {$token->tokenable_id}, Name: {$token->name}, Created: {$token->created_at}" . PHP_EOL;
}

// Check transaksi with all necessary relationships
echo PHP_EOL . "TransaksiRoti data:" . PHP_EOL;
$transaksiRoti = \App\Models\TransaksiRoti::all();
echo "Total TransaksiRoti records: " . count($transaksiRoti) . PHP_EOL;

if(count($transaksiRoti) > 0) {
    foreach($transaksiRoti as $tr) {
        echo "TransaksiRoti ID: {$tr->id}, Transaksi ID: {$tr->transaksi_id}, Roti ID: {$tr->roti_id}, Jumlah: {$tr->jumlah}" . PHP_EOL;
    }
}

// Check if transaksi has relationship with stok_history
echo PHP_EOL . "Checking relationship between transaksi and stok_history:" . PHP_EOL;
$transaksiWithStok = \App\Models\Transaksi::with(['transaksiRoti'])
    ->whereHas('transaksiRoti', function($query) {
        $query->whereExists(function($subQuery) {
            $subQuery->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('stok_history')
                ->whereColumn('stok_history.roti_id', 'transaksi_roti.roti_id');
        });
    })
    ->get();

echo "Transaksi with stok_history relationship: " . count($transaksiWithStok) . PHP_EOL;
?>
