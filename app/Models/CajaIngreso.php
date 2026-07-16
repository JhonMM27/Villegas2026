<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaIngreso extends Model
{
    protected $table = 'caja_ingresos';

    protected $fillable = [
        'fecha',
        'monto',
        'caja_destino',
        'user_id',
        'user_nombre',
        'comentario',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function cajaDestinoOptions(): array
    {
        return [
            'P' => 'Principal',
            'D' => 'Depósito',
            'C' => 'Consorcio',
        ];
    }

    public function getCajaDestinoTextoAttribute(): string
    {
        return self::cajaDestinoOptions()[$this->caja_destino] ?? $this->caja_destino;
    }
}
