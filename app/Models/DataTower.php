<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataTower extends Model
{
    use HasFactory;
    protected $table = 'data_tower';
    protected $fillable = [
        'pelanggan_id',
        'nama_tower',
        'alamat_tower',
        'tinggi_tower',
        'latitude',
        'longitude',
        'keterangan',
    ];

    public function DataAntenna()
    {
        return $this->hasMany(DataAntenna::class, 'id_nama_tower', 'link_tower');
    }

    // public function pelanggan()
    // {
    //     return $this->belongsTo(Pelanggan::class);
    // }
}
