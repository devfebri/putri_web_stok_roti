# Test PowerShell request to simulate Flutter app request
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json" 
    "Authorization" = "Bearer test-token"
}

$body = @{
    stok_history_id = 1
    user_id = "2"
    nama_customer = "Test Customer via PowerShell"
    jumlah = 3
    harga_satuan = 5000
    total_harga = 15000
    metode_pembayaran = "CASH"
    tanggal_transaksi = "2025-08-08"
} | ConvertTo-Json

Write-Host "Sending request with body:"
Write-Host $body

try {
    $response = Invoke-RestMethod -Uri "http://127.0.0.1:8000/api/frontliner/transaksi" -Method POST -Headers $headers -Body $body
    Write-Host "Response:"
    $response | ConvertTo-Json -Depth 3
} catch {
    Write-Host "Error: $($_.Exception.Message)"
    Write-Host "Response Body: $($_.Exception.Response)"
}
