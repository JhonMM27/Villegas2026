<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoRacion extends Model
{
    protected $table = 'tipos_racion';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'orden',
        'precio_venta_saco',
        'activo',
    ];

    protected $casts = [
        'precio_venta_saco' => 'decimal:2',
        'orden' => 'integer',
        'activo' => 'boolean',
    ];

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }
}
