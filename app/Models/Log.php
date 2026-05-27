<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'accion',
        'modelo',
        'modelo_id',
        'valores_antiguos',
        'valores_nuevos',
        'ip_address',
        'user_agent'
    ];

    protected $casts = [
        'valores_antiguos' => 'array',
        'valores_nuevos' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
