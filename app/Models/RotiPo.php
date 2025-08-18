<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RotiPo extends Model
{
    protected $fillable = [
        'roti_id',
        'user_id',
        'frontliner_id',
        'pos_id',
        'jumlah_po'
    ];

    // Define relationships
    public function roti()
    {
        return $this->belongsTo(Roti::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function frontliner()
    {
        return $this->belongsTo(User::class, 'frontliner_id');
    }
    
    public function pos()
    {
        return $this->belongsTo(Pos::class);
    }
}
