<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrestamoDetalle extends Model
{
    protected $table = 'prestamo_detalles';

    public $timestamps = false;

    protected $fillable = [
        'prestamo_id',
        'producto_id',
        'producto_nombre',
        'unidad_codigo',
        'unidad_nombre',
        'producto_empaque',
        'cantidad',
        'cantidad_kgm',
        'valor_unitario',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'cantidad_kgm' => 'decimal:2',
        'valor_unitario' => 'decimal:4',
        'total' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function prestamo()
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
