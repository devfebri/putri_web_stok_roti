<?php

// Test untuk mendapatkan next kode PO

$baseUrl = 'http://127.0.0.1:8000';
$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vMTI3LjAuMC4xOjgwMDAvYXBpL2xvZ2luIiwiaWF0IjoxNzM0MDY3NDIzLCJleHAiOjE3MzQwNzEwMjMsIm5iZiI6MTczNDA2NzQyMywianRpIjoiUnpjSDJUVEJEcmI0MHk5ZSIsInN1YiI6IjIxIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.xw6BIe3b1nSMQn3fQT-SQ2-zlN4TgfM_CsP3zJHkf9o';

echo "=== TEST GET NEXT KODE PO ===\n\n";

// Test endpoint
$url = "$baseUrl/api/kepalatokokios/getnextkodepo";

$headers = [
    'Authorization: Bearer ' . $token,
    'Accept: application/json',
    'Content-Type: application/json'
];

echo "🔗 URL: $url\n";
echo "🔑 Token: " . substr($token, 0, 20) . "...\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    echo "❌ CURL Error: $error\n";
} else {
    echo "📡 HTTP Code: $httpCode\n";
    echo "📡 Response: $response\n\n";
    
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['kode_po'])) {
            echo "✅ Success! Next Kode PO: " . $data['kode_po'] . "\n";
        } else {
            echo "⚠️ Response tidak mengandung kode_po\n";
        }
    } else {
        echo "❌ Error: HTTP $httpCode\n";
    }
}

curl_close($ch);

echo "\n=== END TEST ===\n";
