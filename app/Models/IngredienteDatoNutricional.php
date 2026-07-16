<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IngredienteDatoNutricional extends Model
{
    protected $table = 'ingrediente_datos_nutricionales';

    protected $fillable = [
        'ingrediente',
        'procedencia',
        'clasificacion',
        'nutriente',
        'aporte',
        'materia_seca',
        'proteina_cruda',
        'enl',
        'em',
        'fdn',
        'fibra',
        'fda',
        'grasa',
        'calcio',
        'fosforo',
        'magnesio',
        'almidon',
        'azucar',
        'ceniza',
        'lactosa',
        'lisina',
        'metionina',
        'treonina',
        'activo',
    ];

    protected $casts = [
        'aporte' => 'decimal:4',
        'materia_seca' => 'decimal:2',
        'proteina_cruda' => 'decimal:2',
        'enl' => 'decimal:2',
        'em' => 'decimal:3',
        'fdn' => 'decimal:2',
        'fibra' => 'decimal:2',
        'fda' => 'decimal:2',
        'grasa' => 'decimal:2',
        'calcio' => 'decimal:2',
        'fosforo' => 'decimal:2',
        'magnesio' => 'decimal:2',
        'almidon' => 'decimal:2',
        'azucar' => 'decimal:2',
        'ceniza' => 'decimal:2',
        'lactosa' => 'decimal:2',
        'lisina' => 'decimal:3',
        'metionina' => 'decimal:3',
        'treonina' => 'decimal:3',
        'activo' => 'boolean',
    ];

    public function detallesFormula(): HasMany
    {
        return $this->hasMany(FormulaAlimentoDetalle::class, 'ingrediente_id');
    }

    public function detallesFormulaCerdo(): HasMany
    {
        return $this->hasMany(FormulaAlimentoCerdoDetalle::class, 'ingrediente_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
