<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Instalacion extends Model
{
    use HasFactory;

    protected $table = 'instalaciones';

    protected $fillable = [
        'usuario_id',
        'fecha_contratacion',
        'descripcion',
        'coordenadas',
        'foto',
    ];
}

