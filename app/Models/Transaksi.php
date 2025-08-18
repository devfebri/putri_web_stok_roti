<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    protected $table = 'transaksi';    protected $fillable = [
        'kode_transaksi',
        'user_id',
        'nama_customer',
        'total_harga',
        'metode_pembayaran',
        'tanggal_transaksi'
    ];

    protected $casts = [
        'tanggal_transaksi' => 'datetime'
    ];

    /**
     * Relationship with TransaksiRoti model (hasMany)
     */
    public function transaksiRoti()
    {
        return $this->hasMany(TransaksiRoti::class, 'transaksi_id');
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
}
