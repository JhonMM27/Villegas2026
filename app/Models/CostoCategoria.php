<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostoCategoria extends Model
{
    protected $table = 'categoria_costos';

    protected $fillable = [
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function costoTipos(): HasMany
    {
        return $this->hasMany(CostoTipo::class, 'categoria_costo_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
