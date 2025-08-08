<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';    protected $fillable = [
        'roti_id',
        'stok_history_id',
        'user_id',
        'nama_customer',
        'jumlah',
        'harga_satuan',
        'total_harga',
        'metode_pembayaran',
        'tanggal_transaksi'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'datetime',
        'harga_satuan' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'jumlah' => 'integer'
    ];

    /**
     * Relationship with Roti model
     */
    public function roti()
    {
        return $this->belongsTo(Roti::class);
    }

    /**
     * Relationship with User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_transaksi', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by product
     */
    public function scopeByProduct($query, $rotiId)
    {
        return $query->where('roti_id', $rotiId);
    }
}
