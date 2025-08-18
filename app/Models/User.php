<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'role',
        'kepalatokokios_id',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Relationship: User belongs to Kepala Toko Kios
     */
    public function kepalaTokokios()
    {
        return $this->belongsTo(User::class, 'kepalatokokios_id');
    }

    /**
     * Relationship: Kepala Toko Kios has many frontliners
     */
    public function frontliners()
    {
        return $this->hasMany(User::class, 'kepalatokokios_id')->where('role', 'frontliner');
    }

    /**
     * Relationship: Kepala Toko Kios has many stok history
     */
    public function stokHistories()
    {
        return $this->hasMany(StokHistory::class, 'kepalatokokios_id');
    }
}
