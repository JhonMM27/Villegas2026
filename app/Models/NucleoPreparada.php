<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NucleoPreparada extends Model
{
    protected $table = 'nucleo_preparadas';

    protected $fillable = [
        'user_id',
        'user_nombre',
        'nucleo_id',
        'nucleo_nombre',
        'unidad_nombre',
        'producto_empaque',
        'numero_interno',
        'fecha',
        'cantidad_porcentaje',
        'costo_unitario',
        'ingreso_kg',
        'ingreso_saco',
        'ingreso_soles',
        'items',
        'estado',
        'nota',
    ];

    protected $casts = [
        //'fecha' => 'datetime',
        'cantidad_porcentaje' => 'decimal:2',
        'costo_unitario' => 'decimal:4',
        'ingreso_kg' => 'decimal:4',
        'ingreso_saco' => 'decimal:4',
        'ingreso_soles' => 'decimal:4',
        'items' => 'integer',
        'user_id' => 'integer',
        'nucleo_id' => 'integer',
        'producto_empaque' => 'integer',
    ];

    // Relaciones
    public function detalles()
    {
        return $this->hasMany(NucleoPreparadaDetalle::class, 'nucleo_preparada_id');
    }

    public function nucleo()
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
