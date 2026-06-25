<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoStripe extends Model
{
    protected $table = 'pagos_stripe';

    protected $fillable = [
        'usuario_id',
        'payment_intent_id',
        'monto',
        'estado',
        'periodo',
        'pagado_at',
    ];

    protected $casts = [
        'pagado_at' => 'datetime',
        'monto'     => 'decimal:2',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }
}
