<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreparadaDetalle extends Model
{
    // use HasFactory;

    protected $table = 'preparada_detalles';

    public $timestamps = false;

    protected $fillable = [
        'preparada_id',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'salida_saco',
        'salida_kg',
        'salida_soles',
        'precio_unitario',
    ];

    protected $casts = [
        'salida_saco' => 'decimal:4',
        'salida_kg' => 'decimal:4',
        'salida_soles' => 'decimal:4',
        'precio_unitario' => 'decimal:4',
    ];

    // Relaciones

    public function preparada()
    {
        return $this->belongsTo(Preparada::class, 'preparada_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
