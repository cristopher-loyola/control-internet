<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistorialUsuario extends Model
{
    use HasFactory;

    protected $table = 'usuarios_historial';

    protected $fillable = [
        'usuario_original_id',
        'accion',
        'captured_at',
        'numero_servicio',
        'nombre_cliente',
        'domicilio',
        'telefono',
        'comunidad',
        'uso',
        'tecnologia',
        'dispositivo',
        'megas',
        'tarifa',
        'paquete',
        'estado_id',
        'estatus_servicio_id',
        'servicio_id',
        'fecha_contratacion',
    ];
}

