<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Waste extends Model
{
    protected $fillable = [
        'kode_waste',
        'stok_history_id',
        'user_id',
        'jumlah_waste',
        'tanggal_expired',
        'keterangan',
        'status',
    ];

    protected $casts = [
        'tanggal_expired' => 'datetime:Y-m-d',
    ];

    public function stokHistory()
    {
        return $this->belongsTo(StokHistory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
