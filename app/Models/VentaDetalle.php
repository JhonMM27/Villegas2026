<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';
    public $timestamps = false;

    protected $fillable = [
        'venta_id',
        'detalle',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'unidad_codigo',
        'salida_saco',
        'salida_kg',
        'cantidad',
        'entregado',
        'saldo',
        'precio_unitario',
        'subtotal',
        'porcentaje_impuesto',
        'impuesto',
        'total',
        'costo_unitario',
        'costo_total',
        'rentabilidad'
    ];

    /**
     * Casts
     */
    protected $casts = [
        'salida_saco'        => 'decimal:4',
        'salida_kg'          => 'decimal:2',
        'cantidad'           => 'decimal:2',
        'entregado'          => 'decimal:2',
        'saldo'              => 'decimal:2',
        'precio_unitario'    => 'decimal:4',
        'subtotal'           => 'decimal:2',
        'porcentaje_impuesto'=> 'decimal:2',
        'impuesto'           => 'decimal:2',
        'total'              => 'decimal:2',
        'costo_unitario'     => 'decimal:4',
        'costo_total'        => 'decimal:2',
        'rentabilidad'       => 'decimal:4',
    ];

    
    public function venta()
    {
        return $this->belongsTo(Venta::class, 'venta_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
