<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StokHistory extends Model
{
    protected $table = 'stok_history';
    
    protected $fillable = [
        'roti_id',
        'kepalatokokios_id',
        'stok',
        'stok_awal',
        'stok_masuk',
        'stok_keluar',
        'stok_akhir',
        'tanggal',
    ];

    /**
     * Relationship: StokHistory belongs to Kepala Toko Kios (User)
     */
    public function kepalaTokokios()
    {
        return $this->belongsTo(User::class, 'kepalatokokios_id');
    }

    /**
     * Relationship: StokHistory belongs to Roti
     */
    public function roti()
    {
        return $this->belongsTo(Roti::class, 'roti_id');
    }
}
