<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoFraccion extends Model
{
    protected $table = 'producto_fracciones';

    protected $fillable = [
        'producto_id',
        'producto_nombre',
        'unidad_codigo',
        'empaque',
        'codigo_detalle',
        'precio_lista',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'empaque' => 'integer',
        'codigo_detalle' => 'integer',
        'precio_lista' => 'decimal:2',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id', 'id');
    }

    public function unidad()
    {
        return $this->belongsTo(Unidad::class, 'unidad_codigo', 'codigo');
    }
}
