<?php
require_once 'vendor/autoload.php';

echo "=== TEST TOKEN & PDF DOWNLOAD ===\n";

// Test login dulu untuk dapatkan token
$loginData = [
    'username' => 'admin',
    'password' => 'password'
];

$loginUrl = 'http://127.0.0.1:8000/api/proses_login_API';
$loginCurl = curl_init();
curl_setopt($loginCurl, CURLOPT_URL, $loginUrl);
curl_setopt($loginCurl, CURLOPT_POST, true);
curl_setopt($loginCurl, CURLOPT_POSTFIELDS, json_encode($loginData));
curl_setopt($loginCurl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($loginCurl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($loginCurl, CURLOPT_TIMEOUT, 30);

$loginResponse = curl_exec($loginCurl);
$loginHttpCode = curl_getinfo($loginCurl, CURLINFO_HTTP_CODE);
curl_close($loginCurl);

if ($loginHttpCode === 200) {
    $loginData = json_decode($loginResponse, true);
    $token = $loginData['token'] ?? $loginData['data']['token'] ?? null;
    $user = $loginData['user'] ?? $loginData['data']['user'] ?? null;
    
    echo "✅ Login successful\n";
    echo "Full login response: " . json_encode($loginData, JSON_PRETTY_PRINT) . "\n";
    echo "User: " . ($user['name'] ?? 'Unknown') . " (" . ($user['role'] ?? 'Unknown') . ")\n";
    echo "Token: " . ($token ? substr($token, 0, 50) . "..." : "EMPTY") . "\n";
    
    // Test PDF download
    $pdfUrl = "http://127.0.0.1:8000/api/laporan/waste/pdf?periode=tahunan&token=" . urlencode($token);
    echo "\nTesting PDF URL: $pdfUrl\n";
    
    $pdfCurl = curl_init();
    curl_setopt($pdfCurl, CURLOPT_URL, $pdfUrl);
    curl_setopt($pdfCurl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($pdfCurl, CURLOPT_TIMEOUT, 30);
    curl_setopt($pdfCurl, CURLOPT_FOLLOWLOCATION, true);
    
    $pdfResponse = curl_exec($pdfCurl);
    $pdfHttpCode = curl_getinfo($pdfCurl, CURLINFO_HTTP_CODE);
    $pdfContentType = curl_getinfo($pdfCurl, CURLINFO_CONTENT_TYPE);
    curl_close($pdfCurl);
    
    echo "PDF Response Code: $pdfHttpCode\n";
    echo "PDF Content Type: $pdfContentType\n";
    
    if ($pdfHttpCode === 200) {
        if (strpos($pdfContentType, 'application/pdf') !== false) {
            echo "✅ PDF berhasil di-generate!\n";
            echo "✅ Content-Type: application/pdf\n";
            echo "✅ Response length: " . strlen($pdfResponse) . " bytes\n";
        } else {
            echo "❌ Response bukan PDF. Content-Type: $pdfContentType\n";
            echo "Response (first 500 chars): " . substr($pdfResponse, 0, 500) . "\n";
        }
    } else {
        echo "❌ PDF Error: $pdfHttpCode\n";
        echo "Response: " . substr($pdfResponse, 0, 500) . "\n";
    }
    
} else {
    echo "❌ Login failed: $loginHttpCode\n";
    echo "Response: $loginResponse\n";
}
?>
