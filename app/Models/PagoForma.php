<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PagoForma extends Model
{
    use HasFactory;

    protected $table = 'pago_formas';

    protected $primaryKey = 'codigo';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'descripcion',
        'dias',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
