<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Notifiable;

class Pelanggan extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $table = 'pelanggan';

    protected $fillable = [
        'nama_pelanggan',
        'email',
        'kata_kunci',
    ];

    protected $hidden = [
        'kata_kunci',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];  

    public function getAuthPassword()
    {
        return $this->kata_kunci;
    }
}
