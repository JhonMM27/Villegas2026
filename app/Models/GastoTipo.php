<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GastoTipo extends Model
{
    protected $table = 'gasto_tipos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'activo',
        'categoria_gasto_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function categoriaGasto(): BelongsTo
    {
        return $this->belongsTo(GastoCategoria::class, 'categoria_gasto_id');
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'gasto_tipo_id');
    }
}
