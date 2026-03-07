<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CargoMora extends Model
{
    use HasFactory;

    protected $table = 'cargos_mora';

    protected $fillable = [
        'usuario_id',
        'numero_servicio',
        'periodo',
        'monto',
        'applied_at',
    ];
}

