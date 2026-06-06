<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GastoCategoria extends Model
{
    protected $table = 'categoria_gastos';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function gastoTipos(): HasMany
    {
        return $this->hasMany(GastoTipo::class, 'categoria_gasto_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}