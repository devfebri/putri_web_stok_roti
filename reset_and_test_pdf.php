<?php

// Reset admin password dan test authentication

echo "=== Resetting Admin Password and Testing ===\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=web_putri', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check current admin password hash
    $stmt = $pdo->prepare("SELECT username, password FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "Admin user found: " . $admin['username'] . "\n";
        echo "Current password hash: " . $admin['password'] . "\n";
        
        // Update password to admin123
        $newPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin' AND role = 'admin'");
        $updateStmt->execute([$newPassword]);
        echo "✓ Password updated for admin\n";
        
        // Test login with new password
        echo "\n2. Testing login with updated password...\n";
        
        $loginUrl = "http://127.0.0.1:8000/api/proses_login_API";
        $loginData = json_encode([
            'username' => 'admin',
            'password' => 'admin123'
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'content' => $loginData,
                'ignore_errors' => true
            ]
        ]);

        $loginResult = file_get_contents($loginUrl, false, $context);
        
        if ($loginResult) {
            $loginResponse = json_decode($loginResult, true);
            echo "Login response: " . print_r($loginResponse, true) . "\n";
            
            if (isset($loginResponse['data']['token'])) {
                $token = $loginResponse['data']['token'];
                echo "✓ Token obtained: " . substr($token, 0, 30) . "...\n";
                
                // Test PDF endpoint
                echo "\n3. Testing PDF endpoint...\n";
                $pdfUrl = "http://127.0.0.1:8000/api/admin/laporan/purchase-order/pdf?periode=bulanan&tanggal_mulai=2024-01-01&tanggal_selesai=2024-12-31&token=" . urlencode($token);
                
                $pdfResult = @file_get_contents($pdfUrl);
                if ($pdfResult && strpos($pdfResult, '%PDF') === 0) {
                    $filename = "test_po_pdf_success_" . date('Y-m-d_H-i-s') . ".pdf";
                    file_put_contents($filename, $pdfResult);
                    echo "✓ PDF generated successfully: $filename\n";
                } else {
                    echo "✗ PDF generation failed\n";
                    if ($pdfResult) {
                        echo "Response: " . substr($pdfResult, 0, 300) . "\n";
                    }
                }
            } else {
                echo "✗ No token in login response\n";
            }
        } else {
            echo "✗ Login request failed\n";
        }
        
    } else {
        echo "ERROR: No admin user found\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";
