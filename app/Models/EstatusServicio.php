<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstatusServicio extends Model
{
    use HasFactory;

    protected $table = 'estatus_servicios';

    protected $fillable = [
        'nombre',
    ];
}

