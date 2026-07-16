<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormulaAlimentoDetalle extends Model
{
    protected $table = 'formula_alimento_detalles';

    protected $fillable = [
        'formula_alimento_id',
        'ingrediente_id',
        'cantidad_kg',
        'precio_kg',
    ];

    protected $casts = [
        'cantidad_kg' => 'decimal:4',
        'precio_kg' => 'decimal:4',
    ];

    public function formula(): BelongsTo
    {
        return $this->belongsTo(FormulaAlimento::class, 'formula_alimento_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(IngredienteDatoNutricional::class, 'ingrediente_id');
    }
}
