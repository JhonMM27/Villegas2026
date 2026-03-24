<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraDetalle extends Model
{
    protected $table = 'compra_detalles';
    public $timestamps = false;

    protected $fillable = [
        'compra_id',
        'producto_id',
        'producto_nombre',
        'unidad_codigo',
        'producto_empaque',
        'cantidad',
        'cantidad_kgm',
        'costo_unitario',
        'costo_unitario_servicio',
        'subtotal',
        'porcentaje_impuesto',
        'impuesto',
        'total'
    ];
    
    // Relaciones
    public function compra()
    {
        return $this->belongsTo(Compra::class, 'compra_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
