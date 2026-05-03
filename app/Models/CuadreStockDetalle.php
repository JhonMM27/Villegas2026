<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuadreStockDetalle extends Model
{
    protected $table = 'cuadre_stock_detalles';

    protected $fillable = [
        'cuadre_stock_id',
        'producto_id',
        'stock_sistema',
        'stock_fisico',
        'diferencia',
        'tipo',
    ];

    protected $casts = [
        'stock_sistema' => 'decimal:4',
        'stock_fisico' => 'decimal:4',
        'diferencia' => 'decimal:4',
    ];

    public function cuadreStock(): BelongsTo
    {
        return $this->belongsTo(CuadreStock::class, 'cuadre_stock_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }
}
