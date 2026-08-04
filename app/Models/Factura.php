<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Factura extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference_number',
        'usuario_id',
        'numero_servicio',
        'periodo',
        'total',
        'payload',
        'created_by',
        'fingerprint',
        'fecha_contable',
    ];

    protected $casts = [
        'payload' => 'array',
        'fecha_contable' => 'datetime',
    ];

    /**
     * Fecha que deben usar los reportes: la contable si se fijó a mano
     * (ej. transferencias capturadas el mes siguiente al que corresponden),
     * si no la de captura.
     */
    public const FECHA_REPORTE_SQL = 'COALESCE(facturas.fecha_contable, facturas.created_at)';

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function cajero()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
