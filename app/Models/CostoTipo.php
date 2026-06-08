<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostoTipo extends Model
{
    protected $table = 'costo_tipos';

    protected $primaryKey = 'id';

    protected $fillable = [
        'nombre',
        'activo',
        'categoria_costo_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function categoriaCosto(): BelongsTo
    {
        return $this->belongsTo(CostoCategoria::class, 'categoria_costo_id');
    }

    public function costos(): HasMany
    {
        return $this->hasMany(Costo::class, 'costo_tipo_id');
    }
}
