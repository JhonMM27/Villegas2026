<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoMedio extends Model
{
    protected $table = 'pago_medios';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
