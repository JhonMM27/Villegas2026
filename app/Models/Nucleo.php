<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nucleo extends Model
{
    protected $table = 'nucleos';

    // id NO es autoincremental
    public $incrementing = false;

    // protected $keyType = 'int';

    protected $fillable = [
        'id',
        'nombre',
        'unidad_codigo',
        'unidad_nombre',
        'empaque',
        'cantidad_porcentaje',
        'items',
        'activo',
    ];

    /**
     * Producto asociado (1 a 1)
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'id');
    }

    /**
     * Detalle del núcleo (componentes)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(NucleoDetalle::class, 'nucleo_id');
    }
}
