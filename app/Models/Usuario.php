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
        'zona',
        'ip',
        'mac',
        'comunidad',
        'uso',
        'tecnologia',
        'dispositivo',
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

    public function estado()
    {
        return $this->belongsTo(Estado::class);
    }

    public function estatusServicio()
    {
        return $this->belongsTo(EstatusServicio::class, 'estatus_servicio_id');
    }
}
