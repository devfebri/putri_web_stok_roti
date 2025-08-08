#!/bin/bash

# Test curl request to simulate Flutter app request
curl -X POST http://127.0.0.1:8000/api/frontliner/transaksi \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer test-token" \
  -d '{
    "stok_history_id": 1,
    "user_id": "2",
    "nama_customer": "Test Customer via CURL",
    "jumlah": 3,
    "harga_satuan": 5000,
    "total_harga": 15000,
    "metode_pembayaran": "CASH",
    "tanggal_transaksi": "2025-08-08"
  }'
