<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormulaAlimentoCerdo extends Model
{
    protected $table = 'formulas_alimento_cerdos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha',
        'activo',
        'kg_saco',
        'saco_vacio',
        'mano_obra',
        'energia',
        'merma',
        'precio_venta',
    ];

    protected $casts = [
        'fecha' => 'date',
        'activo' => 'boolean',
        'kg_saco' => 'decimal:2',
        'saco_vacio' => 'decimal:2',
        'mano_obra' => 'decimal:2',
        'energia' => 'decimal:2',
        'merma' => 'decimal:2',
        'precio_venta' => 'decimal:2',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(FormulaAlimentoCerdoDetalle::class, 'formula_alimento_cerdo_id');
    }
}
