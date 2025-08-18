<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TESTING KODE TRANSAKSI GENERATION ===" . PHP_EOL;

// Test generate kode transaksi
$controller = new \App\Http\Controllers\TransaksiController();

// Use reflection to access private method
$reflection = new ReflectionClass($controller);
$method = $reflection->getMethod('generateKodeTransaksi');
$method->setAccessible(true);

// Generate 3 kode transaksi untuk testing
for ($i = 1; $i <= 3; $i++) {
    $kode = $method->invoke($controller);
    echo "Kode Transaksi #{$i}: {$kode}" . PHP_EOL;
}

echo PHP_EOL . "=== TESTING API ENDPOINT ===" . PHP_EOL;

try {
    // Test via API endpoint
    $response = $controller->getNextKodeTransaksiApi();
    $responseData = json_decode($response->getContent(), true);
    echo "API Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . PHP_EOL;
} catch (Exception $e) {
    echo "Error testing API: " . $e->getMessage() . PHP_EOL;
}
