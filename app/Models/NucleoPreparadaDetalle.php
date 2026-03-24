<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NucleoPreparadaDetalle extends Model
{
    protected $table = 'nucleo_preparada_detalles';
    public $timestamps = false; 

    protected $fillable = [
        'nucleo_preparada_id',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'unidad_codigo',
        'costo_unitario',
        'cantidad_porcentaje',
        'salida_kg',
        'salida_soles',
    ];

    protected $casts = [
        'costo_unitario' => 'decimal:4',
        'cantidad_porcentaje' => 'decimal:2',
        'salida_kg' => 'decimal:4',        
        'salida_soles' => 'decimal:4',
        'nucleo_preparada_id' => 'integer',
        'producto_id' => 'integer',
        'producto_empaque' => 'integer',
    ];

    // Relaciones
    public function nucleoPreparada()
    {
        return $this->belongsTo(NucleoPreparada::class, 'nucleo_preparada_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
