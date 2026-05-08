<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NumeroApartado extends Model
{
    use HasFactory;

    protected $table = 'numeros_apartados';

    protected $fillable = [
        'numero_servicio',
        'user_id',
        'notas',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
