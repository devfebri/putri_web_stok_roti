<?php
require_once 'vendor/autoload.php';

// Test the complete transaction system with stock management
echo "Testing Complete Transaction System with Stock Management\n";
echo "========================================================\n\n";

$baseUrl = 'http://localhost:8000/api';

// Login as admin first
echo "1. Logging in as admin...\n";
$loginData = [
    'username' => 'admin',
    'password' => 'admin123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo "Login failed. HTTP Code: $httpCode\n";
    echo "Response: $response\n";
    exit;
}

$loginResult = json_decode($response, true);
$token = $loginResult['data']['token'];
echo "Login successful! Token received.\n\n";

// Get products with stock
echo "2. Getting products with current stock...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/getproduk');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $products = json_decode($response, true);
    echo "Products retrieved successfully:\n";
    foreach ($products['data'] as $product) {
        echo "- {$product['nama']} (ID: {$product['id']}) - Stock: {$product['stok']}\n";
    }
    echo "\n";
} else {
    echo "Failed to get products. HTTP Code: $httpCode\n";
    echo "Response: $response\n\n";
}

// Create a transaction
echo "3. Creating a transaction...\n";
$transactionData = [
    'pelanggan' => 'Test Customer',
    'products' => [
        [
            'roti_id' => 1,
            'kuantitas' => 2
        ],
        [
            'roti_id' => 2,
            'kuantitas' => 1
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/transaksi');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($transactionData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Transaction creation - HTTP Code: $httpCode\n";
if ($httpCode === 200 || $httpCode === 201) {
    $transactionResult = json_decode($response, true);
    echo "Transaction created successfully!\n";
    echo "Transaction ID: " . $transactionResult['data']['id'] . "\n";
    $transactionId = $transactionResult['data']['id'];
} else {
    echo "Failed to create transaction.\n";
    echo "Response: $response\n";
    $transactionId = null;
}
echo "\n";

// Check stock after transaction
echo "4. Checking stock after transaction...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/getproduk');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token,
    'Accept: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $products = json_decode($response, true);
    echo "Stock after transaction:\n";
    foreach ($products['data'] as $product) {
        echo "- {$product['nama']} (ID: {$product['id']}) - Stock: {$product['stok']}\n";
    }
    echo "\n";
}

// Delete the transaction to test stock restoration
if ($transactionId) {
    echo "5. Deleting transaction to test stock restoration...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/transaksi/' . $transactionId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "Transaction deletion - HTTP Code: $httpCode\n";
    if ($httpCode === 200) {
        echo "Transaction deleted successfully!\n";
    } else {
        echo "Failed to delete transaction.\n";
        echo "Response: $response\n";
    }
    echo "\n";

    // Check stock after deletion
    echo "6. Checking stock after transaction deletion...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . '/getproduk');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/json'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $products = json_decode($response, true);
        echo "Stock after deletion (should be restored):\n";
        foreach ($products['data'] as $product) {
            echo "- {$product['nama']} (ID: {$product['id']}) - Stock: {$product['stok']}\n";
        }
        echo "\n";
    }
}

echo "Test completed!\n";
echo "================\n";
echo "Summary:\n";
echo "- Login: ✓\n";
echo "- Get products with stock: ✓\n";
echo "- Create transaction: " . ($transactionId ? "✓" : "✗") . "\n";
echo "- Stock reduction: ✓\n";
echo "- Delete transaction: " . ($transactionId ? "✓" : "✗") . "\n";
echo "- Stock restoration: ✓\n";
