<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios';

    protected $fillable = [
        'numero_servicio',
        'nombre_cliente',
        'domicilio',
        'telefono',
        'comunidad',
        'uso',
        'megas',
        'tarifa',
        'paquete',
        'ip_servicio',
        'olt_ubicado',
        'estado_id',
        'estatus_servicio_id',
        'servicio_id',
        'fecha_contratacion',
        'numero_servicio_anterior',
    ];
}
