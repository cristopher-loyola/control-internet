<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GastoOperativo extends Model
{
    use HasFactory;

    protected $table = 'gastos_operativos';

    protected $fillable = [
        'fecha',
        'monto',
        'categoria_id',
        'tipo_pago_id',
        'descripcion',
    ];
}

