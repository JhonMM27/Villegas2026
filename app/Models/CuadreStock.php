<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuadreStock extends Model
{
    protected $table = 'cuadre_stocks';

    protected $fillable = [
        'fecha',
        'estado',
        'rectificacion_count',
        'notas',
        'user_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'rectificacion_count' => 'integer',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(CuadreStockDetalle::class, 'cuadre_stock_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeCompletados($query)
    {
        return $query->where('estado', 'completado');
    }

    public function scopeAnulados($query)
    {
        return $query->where('estado', 'anulado');
    }
}
