<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Preparada extends Model
{
    //use HasFactory;

    protected $table = 'preparadas';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'fecha',
        'numero_interno',
        'formulacion_id',
        'producto_id',
        'producto_nombre',
        'producto_empaque',
        'costo_unitario',
        'cliente_id',
        'cliente_nombre',
        'ingreso_saco',
        'ingreso_kg',
        'ingreso_soles',
        'items',
        'estado',
    ];

    protected $casts = [
        //'fecha' => 'datetime',
        'costo_unitario' => 'decimal:4',
        'ingreso_saco' => 'decimal:4',
        'ingreso_kg' => 'decimal:4',
        'ingreso_soles' => 'decimal:4',
    ];

    // Relaciones

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(PreparadaDetalle::class, 'preparada_id');
    }
}
