<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingrediente extends Model
{
    protected $table = 'ingredientes';

    protected $fillable = [
        'nombre',
        'procedencia',
        'clasificacion',
        'dato_nutricional_id',
        'precio_kg',
        'activo',
    ];

    protected $casts = [
        'precio_kg' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public function datoNutricional(): BelongsTo
    {
        return $this->belongsTo(IngredienteDatoNutricional::class, 'dato_nutricional_id');
    }

    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }
}
