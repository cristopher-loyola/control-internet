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
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
