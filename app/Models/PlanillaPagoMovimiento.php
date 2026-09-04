<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanillaPagoMovimiento extends Model
{
    protected $table = 'planilla_pago_movimientos';

    protected $fillable = [
        'planilla_pago_id',
        'accion',
        'estado_anterior',
        'estado_nuevo',
        'fecha_pago_anterior',
        'fecha_pago_nueva',
        'motivo',
        'user_id',
        'user_nombre',
    ];

    protected $casts = [
        'fecha_pago_anterior' => 'date',
        'fecha_pago_nueva' => 'date',
        'user_id' => 'integer',
    ];

    public function pago(): BelongsTo
    {
        return $this->belongsTo(PlanillaPago::class, 'planilla_pago_id');
    }
}
