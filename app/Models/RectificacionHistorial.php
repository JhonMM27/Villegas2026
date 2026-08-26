<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RectificacionHistorial extends Model
{
    protected $table = 'rectificacion_historiales';

    protected $fillable = [
        'modulo',
        'registro_id',
        'numero_rectificacion',
        'registro_referencia',
        'user_id',
        'user_nombre',
        'motivo',
        'datos_anteriores',
        'datos_nuevos',
        'cambios',
    ];

    protected $casts = [
        'registro_id' => 'integer',
        'numero_rectificacion' => 'integer',
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'cambios' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
