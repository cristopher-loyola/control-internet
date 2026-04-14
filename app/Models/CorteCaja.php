<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorteCaja extends Model
{
    use HasFactory;

    protected $table = 'corte_cajas';

    protected $fillable = [
        'user_id',
        'zona',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'total_recaudado',
        'total_pagos',
        'observaciones',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'total_recaudado' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'corte_caja_id');
    }

    /**
     * Obtener el corte activo para una zona específica y usuario
     */
    public static function obtenerActivo(string $zona, int $userId): ?self
    {
        return self::where('zona', $zona)
            ->where('user_id', $userId)
            ->where('estado', 'activo')
            ->first();
    }

    /**
     * Verificar si hay un corte activo
     */
    public static function tieneActivo(string $zona, int $userId): bool
    {
        return self::where('zona', $zona)
            ->where('user_id', $userId)
            ->where('estado', 'activo')
            ->exists();
    }
}
