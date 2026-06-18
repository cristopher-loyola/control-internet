<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cobrador extends Model
{
    protected $table = 'cobradores';

    protected $fillable = ['nombre', 'orden', 'activo'];

    protected $casts = ['activo' => 'boolean'];

    public function scopeActivos($query)
    {
        return $query->where('activo', true)->orderBy('orden')->orderBy('nombre');
    }
}
