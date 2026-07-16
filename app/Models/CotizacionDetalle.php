<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CotizacionDetalle extends Model
{
    protected $table = 'cotizacion_detalles';

    public $timestamps = false;

    protected $fillable = [
        'cotizacion_id',
        'detalle',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'unidad_codigo',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'porcentaje_impuesto',
        'impuesto',
        'total',
    ];

    /**
     * Casts
     */
    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
        'porcentaje_impuesto' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function cotizacion()
    {
        return $this->belongsTo(Cotizacion::class, 'cotizacion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
