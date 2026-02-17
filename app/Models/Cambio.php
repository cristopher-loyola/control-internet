<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cambio extends Model
{
    use HasFactory;

    protected $table = 'cambios';

    protected $fillable = [
        'usuario_id',
        'fecha',
        'descripcion',
    ];
}

