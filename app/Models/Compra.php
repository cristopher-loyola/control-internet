<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras';

    protected $fillable = [
        'fecha',
        'monto',
        'categoria_id',
        'tipo_pago_id',
        'proveedor',
        'descripcion',
    ];
}

