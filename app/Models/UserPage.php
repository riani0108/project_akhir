<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPage extends Model
{
    use HasFactory;

    protected $table = 'user_pages';

    protected $fillable = [
        'pelanggan_id',
        'page_name',
        'page_url',
        'is_default',
        'order',
        'page_settings'
    ];

    protected $casts = [
        'page_settings' => 'array',
        'is_default' => 'boolean'
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'pelanggan_id');
    }
}
