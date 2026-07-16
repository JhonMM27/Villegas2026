<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentaEntregaDetalle extends Model
{
    use HasFactory;

    protected $table = 'venta_entrega_detalles';

    public $timestamps = false;

    protected $fillable = [
        'venta_entrega_id',
        'venta_detalle_id',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'cantidad',
        'saco_entregado',
        'salida_kg',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'saco_entregado' => 'decimal:2',
        'salida_kg' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    // Pertenece a una entrega
    public function entrega()
    {
        return $this->belongsTo(VentaEntrega::class, 'venta_entrega_id');
    }

    // Pertenece a un detalle de venta
    public function ventaDetalle()
    {
        return $this->belongsTo(VentaDetalle::class, 'venta_detalle_id');
    }

    // Producto (opcional, solo para consultas rápidas)
    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}
