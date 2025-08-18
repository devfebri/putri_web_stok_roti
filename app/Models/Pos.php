<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pos extends Model
{
    use HasFactory;
    
    protected $table = 'pos';
    
    protected $fillable = [
        'kode_po',
        'deskripsi',
        'tanggal_order',
        'status',
        'user_id'
    ];
    
    protected $casts = [
        'tanggal_order' => 'date',
        'status' => 'integer'
    ];
    
    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    
    public function rotiPos()
    {
        return $this->hasMany(RotiPo::class, 'pos_id');
    }
}
