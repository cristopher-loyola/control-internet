<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrepaySetting extends Model
{
    use HasFactory;

    protected $table = 'prepay_settings';

    protected $fillable = [
        'paquete',
        'enabled',
    ];
}

