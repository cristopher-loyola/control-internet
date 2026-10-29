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

    public function recargoParaCorte(): float
    {
        $payload = $this->payload ?? [];
        if (($payload['recargo'] ?? 'no') !== 'si') {
            return 0.0;
        }

        // Algunos adelantos quedaron marcados con recargo al reintentar imprimir,
        // aunque el total guardado solo incluía los meses futuros. No descontar
        // una comisión por un recargo que ese recibo no llegó a cobrar.
        $esAdelanto = in_array($payload['prepay'] ?? null, ['si', true], true);
        $adelanto = (float) ($payload['prepay_total'] ?? 0);
        if ($esAdelanto && $adelanto > 0 && (float) ($payload['adeudo_pendiente'] ?? 0) <= 0
            && (float) $this->total + (float) ($payload['descuento'] ?? 0) <= $adelanto + 0.01) {
            return 0.0;
        }

        return 50.0;
    }
}
