<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataAntenna extends Model
{
    use HasFactory;

    protected $table = 'data_antenna';
    protected $fillable = [
        'pelanggan_id',
        'jenis_antenna',
        'id_nama_tower',
        'link_tower',

    ];

    // App/Models/DataAntenna.php

    // public function pelanggan()
    // {
    //     return $this->belongsTo(Pelanggan::class);
    // }

    public function data_tower_from()
    {
        return $this->belongsTo(\App\Models\DataTower::class, 'id_nama_tower');
    }

    public function data_tower_to()
    {
        return $this->belongsTo(\App\Models\DataTower::class, 'link_tower');
    }
}
