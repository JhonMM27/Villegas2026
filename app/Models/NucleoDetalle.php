<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NucleoDetalle extends Model
{
    protected $table = 'nucleo_detalles';

    public $timestamps = false;

    protected $fillable = [
        'nucleo_id',
        'producto_id',
        'producto_nombre',
        'unidad_codigo',
        'cantidad',
    ];

    /**
     * Núcleo padre
     */
    public function nucleo(): BelongsTo
    {
        return $this->belongsTo(Nucleo::class, 'nucleo_id');
    }

    /**
     * Producto componente
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
