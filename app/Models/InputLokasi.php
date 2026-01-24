<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InputLokasi extends Model
{
    use HasFactory;
    protected $table = 'input_lokasi';
    protected $fillable = [
        'nama',
        'tinggi_tower',
        'alamat',
        'koordinat',
    ];
}
