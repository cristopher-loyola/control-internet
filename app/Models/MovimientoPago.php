<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MovimientoPago extends Model
{
    use HasFactory;

    protected $table = 'movimientos_pagos';

    protected $fillable = [
        'usuario_id',
        'fecha',
        'monto',
        'tipo_pago_id',
        'cajero_id',
    ];
}

