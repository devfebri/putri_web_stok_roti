<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiRoti extends Model
{
    protected $table = 'transaksi_roti';
    
    protected $fillable = [
        'transaksi_id',
        'user_id',
        'roti_id',
        'stok_history_id',
        'jumlah',
        'harga_satuan'
    ];

    /**
     * Relationship: TransaksiRoti belongs to Transaksi
     */
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksi_id');
    }

    /**
     * Relationship: TransaksiRoti belongs to User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship: TransaksiRoti belongs to Roti
     */
    public function roti()
    {
        return $this->belongsTo(Roti::class, 'roti_id');
    }

    /**
     * Relationship: TransaksiRoti belongs to StokHistory
     */
    public function stokHistory()
    {
        return $this->belongsTo(StokHistory::class, 'stok_history_id');
    }
}
