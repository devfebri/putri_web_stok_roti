<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING FILTER TANGGAL ===" . PHP_EOL;

$today = date('Y-m-d');
echo "Today: {$today}" . PHP_EOL;

// Test dengan whereDate (yang benar untuk harian)
echo PHP_EOL . "Test 1: whereDate (should work)" . PHP_EOL;
$count1 = DB::table('transaksi')->whereDate('tanggal_transaksi', $today)->count();
echo "Result: {$count1}" . PHP_EOL;

// Test dengan whereBetween tanggal saja (yang salah)
echo PHP_EOL . "Test 2: whereBetween date only (will fail)" . PHP_EOL;
$count2 = DB::table('transaksi')->whereBetween('tanggal_transaksi', [$today, $today])->count();
echo "Result: {$count2}" . PHP_EOL;

// Test dengan whereBetween datetime (yang benar untuk mingguan/bulanan/tahunan)
echo PHP_EOL . "Test 3: whereBetween datetime (should work)" . PHP_EOL;
$count3 = DB::table('transaksi')
    ->where('tanggal_transaksi', '>=', $today . ' 00:00:00')
    ->where('tanggal_transaksi', '<=', $today . ' 23:59:59')
    ->count();
echo "Result: {$count3}" . PHP_EOL;

echo PHP_EOL . "=== CONCLUSION ===" . PHP_EOL;
echo "Test 1 (whereDate): {$count1} - Use for harian" . PHP_EOL;
echo "Test 2 (whereBetween date): {$count2} - Don't use!" . PHP_EOL;
echo "Test 3 (where datetime range): {$count3} - Use for mingguan/bulanan/tahunan" . PHP_EOL;
