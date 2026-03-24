<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormulacionDetalle extends Model
{
    //use HasFactory;

    protected $table = 'formulacion_detalles';
    public $timestamps = false; 

    protected $fillable = [
        'formulacion_id',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'producto_linea',
        'salida_kg',
    ];

    protected $casts = [
        'salida_kg' => 'decimal:2',
    ];

    // Relaciones

    public function formulacion()
    {
        return $this->belongsTo(Formulacion::class, 'formulacion_id');
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
