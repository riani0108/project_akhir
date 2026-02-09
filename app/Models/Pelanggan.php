<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\PelangganResetPasswordNotification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Auth\Passwords\CanResetPassword as CanResetPasswordTrait;
use Illuminate\Notifications\Notifiable;

class Pelanggan extends Authenticatable implements CanResetPassword
{
    use Notifiable, CanResetPasswordTrait;

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

    /**
     * 🔥 WAJIB: mapping kolom password custom
     */
    public function getAuthPassword()
    {
        return $this->kata_kunci;
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new PelangganResetPasswordNotification($token));
    }

    // public function dataTowers()
    // {
    //     return $this->hasMany(DataTower::class, 'pelanggan_id');
    // }

    // public function dataAntennas()
    // {
    //     return $this->hasMany(DataAntenna::class);
    // }
}
